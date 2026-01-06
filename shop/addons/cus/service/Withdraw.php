<?php

namespace addons\cus\service;

use addons\cus\exception\CusException;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;
use app\admin\model\cus\Withdraw as WithdrawModel;
use app\admin\model\cus\WithdrawLog as WithdrawLogModel;
use addons\cus\library\Operator;
use app\admin\model\cus\ThirdOauth;
use addons\cus\service\Wallet as WalletService;
use addons\cus\library\pay\PayService;
use app\admin\model\cus\user\User;

class Withdraw
{

    protected $user = null;

    /**
     * @var array
     */
    public $config = [];

    public function __construct($user = null)
    {
        if ($user) {
            $this->user = is_numeric($user) ? User::get($user) : $user;
        }

        // 提现规则
        $config = sheep_config('shop.recharge_withdraw.withdraw');

        $config['min_amount'] = $config['min_amount'] == 0 ? $config['min_amount'] : number_format(floatval($config['min_amount']), 2, '.', '');
        $config['max_amount'] = $config['max_amount'] == 0 ? $config['max_amount'] : number_format(floatval($config['max_amount']), 2, '.', '');
        $config['charge_rate_format'] = round(floatval($config['charge_rate']), 1);      // 1 位小数
        $config['charge_rate'] = round((floatval($config['charge_rate']) * 0.01), 3);

        $this->config = $config;
    }


    // 发起提现申请
    public function apply($params)
    {
        $type = $params['type'];
        $money = $params['money'] ?? 0;
        $money = (string)$money;

        // 手续费
        $charge = bcmul($money, (string)$this->config['charge_rate'], 2);

        // 检查提现规则
        $this->checkApply($type, $money, $charge);

        // 获取账号信息
        $withdrawInfo = $this->getAccountInfo($type, $params);
        // 添加提现记录
        $withdraw = new WithdrawModel();
        $withdraw->user_id = $this->user->id;
        $withdraw->amount = $money;
        $withdraw->charge_fee = $charge;
        $withdraw->charge_rate = $this->config['charge_rate'];
        $withdraw->withdraw_sn = get_sn($this->user->id, 'W');
        $withdraw->withdraw_type = $type;
        $withdraw->withdraw_info = $withdrawInfo;
        $withdraw->status = 0;
        $withdraw->platform = request()->header('platform');
        $withdraw->save();

        // 佣金钱包变动
        WalletService::change($this->user, 'commission', -bcadd($charge, $money, 2), 'withdraw', [
            'withdraw_id' => $withdraw->id,
            'amount' => $withdraw->amount,
            'charge_fee' => $withdraw->charge_fee,
            'charge_rate' => $withdraw->charge_rate,
        ]);

        $this->handleLog($withdraw, '用户发起提现申请', $this->user);

        return $withdraw;
    }


    // 继续提现
    public function retry($params)
    {
        $withdraw = WithdrawModel::where('user_id', $this->user->id)
            ->where('withdraw_sn', $params['withdraw_sn'])
            ->where('status', 1)
            ->where('withdraw_type', 'wechat')
            ->find();

        if (!$withdraw) {
            throw new CusException('提现记录不存在');
        }
        if (request()->header('platform') !== $withdraw->platform) {
            throw new CusException('请在发起该次提现的平台操作');
        }

        $this->handleLog($withdraw, '用户重新发起提现申请');
        // 实时检查提现单状态
        return $this->checkWechatTransferResult($withdraw);
    }

    // 取消提现(适用于微信商家转账)
    public function cancel($params)
    {
        $withdraw = WithdrawModel::where('user_id', $this->user->id)
            ->where('withdraw_sn', $params['withdraw_sn'])
            ->where('status', 1)
            ->where('withdraw_type', 'wechat')
            ->find();

        if (!$withdraw) {
            throw new CusException('提现记录不存在');
        }

        if (request()->header('platform') !== $withdraw->platform) {
            throw new CusException('请在发起该次提现的平台操作');
        }

        // 实时检查提现单状态
        $withdraw = $this->checkWechatTransferResult($withdraw);

        if ($withdraw->wechat_transfer_state !== 'WAIT_USER_CONFIRM') {
            throw new CusException('该提现单暂无法发起取消操作,请联系客服');
        }
        // 提交微信撤销单
        $payService = new PayService($withdraw->withdraw_type, $withdraw->platform);

        try {
            $result = $payService->cancelTransferOrder([
                'withdraw_sn' => $withdraw->withdraw_sn,
            ]);
        } catch (\Exception $e) {
            \think\Log::error('撤销提现失败: ' . ': 行号: ' . $e->getLine() . ': 文件: ' . $e->getFile() . ': 错误信息: ' . $e->getMessage());
            $this->handleLog($withdraw, '撤销微信商家转账失败: ' . $e->getMessage());
            throw new CusException($e->getMessage());
        }

        $withdraw->wechat_transfer_state = $result['state'];
        $withdraw->save();
        $this->handleLog($withdraw, '申请撤销微信商家转账成功，报文信息：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        return $withdraw;
    }


    // 新版本微信商家转账 
    // https://pay.weixin.qq.com/doc/v3/merchant/4012711988
    public function handleWechatTransfer($withdraw)
    {
        operate_disabled();

        $payService = new PayService($withdraw->withdraw_type, $withdraw->platform);
        $payload = [
            '_action' => 'mch_transfer', // 新版转账
            'out_bill_no' => $withdraw->withdraw_sn,
            'transfer_scene_id' => '1005',
            'openid' => $withdraw->withdraw_info['微信ID'] ?? '',
            'user_name' => $withdraw->withdraw_info['真实姓名'] ?? '',  // 明文传参即可，sdk 会自动加密
            'transfer_amount' => $withdraw->amount,
            'transfer_remark' => "用户[" . ($withdraw->withdraw_info['微信用户'] ?? '') . "]提现",
            'transfer_scene_report_infos' => [
                ['info_type' => '岗位类型', 'info_content' => '推广专员'],
                ['info_type' => '报酬说明', 'info_content' => '推广佣金报酬'],
            ],
        ];
        try {
            list($response, $transferData) = $payService->transfer($payload);
            $withdraw->status = 1; // 提现处理中
            $withdraw->wechat_transfer_state = $response['state'];
            $withdraw->payment_json = json_encode($response, JSON_UNESCAPED_UNICODE);
            $withdraw->save();
            $this->handleLog($withdraw, '用户发起微信商家转账收款，报文信息：' . json_encode($response, JSON_UNESCAPED_UNICODE), $this->user);

            return $transferData;
        } catch (\Exception $e) {
            \think\Log::error('提现失败: ' . ': 行号: ' . $e->getLine() . ': 文件: ' . $e->getFile() . ': 错误信息: ' . $e->getMessage());
            // $withdraw->wechat_transfer_state = 'NOT_FOUND';
            $withdraw->save();
            $this->handleFail($withdraw, $e->getMessage());
            throw new CusException($e->getMessage());
        }

        // 发起提现失败，自动处理退还佣金

    }

    // 支付宝提现
    public function handleAlipayWithdraw($withdraw)
    {
        operate_disabled();

        Db::startTrans();
        try {
            $withdraw = $this->handleAgree($withdraw, $this->user);
            $withdraw = $this->handleWithdraw($withdraw, $this->user);

            Db::commit();
        } catch (CusException $e) {
            Db::commit();       // 不回滚，记录错误日志
            throw new CusException($e->getMessage());
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            throw new CusException($message);
        } catch (\Exception $e) {
            Db::rollback();
            throw new CusException($e->getMessage());
        }
    }



    // 同意操作
    public function handleAgree($withdraw, $oper = null)
    {
        if ($withdraw->status != 0) {
            throw new CusException('请勿重复操作');
        }
        $withdraw->status = 1;
        $withdraw->save();
        return $this->handleLog($withdraw, '同意提现申请', $oper);
    }

    // 处理打款
    public function handleWithdraw($withdraw, $oper = null)
    {
        $withDrawStatus = false;
        if ($withdraw->status != 1) {
            throw new CusException('请勿重复操作');
        }
        if ($withdraw->withdraw_type !== 'bank') {
            $withDrawStatus = $this->handleTransfer($withdraw);
        } else {
            $withDrawStatus = true;
        }
        if ($withDrawStatus) {
            $withdraw->status = 2;
            $withdraw->paid_fee = $withdraw->amount;
            $withdraw->save();
            return $this->handleLog($withdraw, '已打款', $oper);
        }
        return $withdraw;
    }

    // 拒绝操作
    public function handleRefuse($withdraw, $refuse_msg)
    {
        if ($withdraw->status != 0 && $withdraw->status != 1) {
            throw new CusException('请勿重复操作');
        }
        $withdraw->status = -1;
        $withdraw->save();

        // 退回用户佣金
        WalletService::change($this->user, 'commission', bcadd($withdraw->charge_fee, $withdraw->amount, 2), 'withdraw_error', [
            'withdraw_id' => $withdraw->id,
            'amount' => $withdraw->amount,
            'charge_fee' => $withdraw->charge_fee,
            'charge_rate' => $withdraw->charge_rate,
        ]);

        return $this->handleLog($withdraw, '拒绝:' . $refuse_msg);
    }


    // 失败
    public function handleFail($withdraw, $refuse_msg, $status = -2)
    {
        if ($withdraw->status != 0 && $withdraw->status != 1) {
            throw new CusException('请勿重复操作');
        }
        $withdraw->status = $status;
        $withdraw->save();

        // 退回用户佣金
        WalletService::change($this->user, 'commission', bcadd($withdraw->charge_fee, $withdraw->amount, 2), 'withdraw_error', [
            'withdraw_id' => $withdraw->id,
            'amount' => $withdraw->amount,
            'charge_fee' => $withdraw->charge_fee,
            'charge_rate' => $withdraw->charge_rate,
        ]);

        return $this->handleLog($withdraw, '提现失败，已退还提现佣金:' . $refuse_msg);
    }


    // 商家转账回调
    public function handleWechatTransferNotify($action, $withdrawSn, $originData)
    {
        $withdraw = WithdrawModel::where('withdraw_sn', $withdrawSn)->find();
        if (!$withdraw) {
            throw new CusException('提现记录不存在');
        }

        if ($withdraw->status !== 1) {
            throw new CusException('提现记录状态不正确');
        }

        $this->user = User::get($withdraw->user_id);

        if (!$this->user) {
            throw new CusException('用户不存在');
        }

        $withdraw->wechat_transfer_state = $action;

        switch ($action) {
            case 'SUCCESS':
                $withdraw->status = 2;
                $this->handleLog($withdraw, '微信商家转账成功，报文信息: ' . json_encode($originData, JSON_UNESCAPED_UNICODE), $this->user);
                $withdraw->save();
                break;
            case 'FAILED':
                $this->handleFail($withdraw, '微信商家转账失败，报文信息: ' . json_encode($originData, JSON_UNESCAPED_UNICODE), -2);
                break;
            case 'CANCELLED':   // 撤销完成
                $this->handleFail($withdraw, '微信商家转账撤销成功，报文信息: ' . json_encode($originData, JSON_UNESCAPED_UNICODE), -3);
                break;
            default:
                $this->handleLog($withdraw, '回调结果不是终态，报文信息: ' . json_encode($originData, JSON_UNESCAPED_UNICODE), $this->user);
        }

        return true;
    }

    // 检查微信商家转账单结果
    public function checkWechatTransferResult($withdraw)
    {
        if ($withdraw->createtime < strtotime('-30 day')) {
            throw new CusException('提现单据已过期，请联系客服解决');
        }
        $payService = new PayService($withdraw->withdraw_type, $withdraw->platform);

        $result = $payService->queryTransferOrder([
            'withdraw_sn' => $withdraw->withdraw_sn,
        ]);
        $withdraw->wechat_transfer_state = $result['state'];
        $withdraw->save();
        $this->handleLog($withdraw, '查询微信商家转账单，报文信息：' . json_encode($result, JSON_UNESCAPED_UNICODE));

        return $withdraw;
    }


    // 企业付款提现
    private function handleTransfer($withdraw)
    {
        operate_disabled();
        $type = $withdraw->withdraw_type;
        $platform = $withdraw->platform;

        $payService = new PayService($type, $platform);

        if ($type == 'alipay') {
            $payload = [
                'out_biz_no' => $withdraw->withdraw_sn,
                'trans_amount' => $withdraw->amount,
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene' => 'DIRECT_TRANSFER',
                // 'order_title' => '余额提现到',
                'remark' => '用户提现',
                'payee_info' => [
                    'identity' => $withdraw->withdraw_info['支付宝账户'] ?? '',
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name' => $withdraw->withdraw_info['真实姓名'] ?? '',
                ]
            ];
        }

        try {
            list($code, $response) = $payService->transfer($payload);
            if ($code === 1) {
                $withdraw->payment_json = json_encode($response, JSON_UNESCAPED_UNICODE);
                $withdraw->save();
                return true;
            }
            throw new CusException(json_encode($response, JSON_UNESCAPED_UNICODE));
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            throw new CusException($message);
        } catch (\Exception $e) {
            \think\Log::error('提现失败：' . ' 行号：' . $e->getLine() . '文件：' . $e->getFile() . '错误信息：' . $e->getMessage());
            $this->handleLog($withdraw, '提现失败：' . $e->getMessage());
            throw new CusException($e->getMessage());     // 弹出错误信息
        }
        return false;
    }


    // 添加日志
    private function handleLog($withdraw, $oper_info, $oper = null)
    {
        $oper = Operator::get($oper);

        WithdrawLogModel::insert([
            'withdraw_id' => $withdraw->id,
            'content' => $oper_info,
            'oper_type' => $oper['type'],
            'oper_id' => $oper['id'],
            'createtime' => time()
        ]);
        return $withdraw;
    }


    // 提现条件检查
    private function checkApply($type, $money, $charge)
    {
        if (!in_array($type, $this->config['methods'])) {
            throw new CusException('暂不支持该提现方式');
        }

        if ($money <= 0) {
            throw new CusException('请输入正确的提现金额');
        }

        // 检查最小提现金额
        if ($this->config['min_amount'] > 0 && $money < $this->config['min_amount']) {
            throw new CusException('提现金额不能少于 ' . $this->config['min_amount'] . '元');
        }
        // 检查最大提现金额
        if ($this->config['max_amount'] > 0 && $money > $this->config['max_amount']) {
            throw new CusException('提现金额不能大于 ' . $this->config['max_amount'] . '元');
        }


        if ($this->user->commission < bcadd($charge, $money, 2)) {
            throw new CusException('可提现佣金不足');
        }

        // 检查最大提现次数
        if (isset($this->config['max_num']) && $this->config['max_num'] > 0) {
            $start_time = $this->config['num_unit'] == 'day' ? strtotime(date('Y-m-d', time())) : strtotime(date('Y-m', time()));

            $num = WithdrawModel::where('user_id', $this->user->id)->where('createtime', '>=', $start_time)->count();

            if ($num >= $this->config['max_num']) {
                throw new CusException('每' . ($this->config['num_unit'] == 'day' ? '日' : '月') . '提现次数不能大于 ' . $this->config['max_num'] . '次');
            }
        }
    }


    // 获取提现账户信息
    private function getAccountInfo($type, $params)
    {
        $platform = request()->header('platform');

        switch ($type) {
            case 'wechat':
                if ($platform == 'App') {
                    $platform = 'openPlatform';
                } elseif (in_array($platform, ['WechatOfficialAccount', 'WechatMiniProgram'])) {
                    $platform = lcfirst(str_replace('Wechat', '', $platform));
                }
                $thirdOauth = ThirdOauth::where('provider', 'wechat')->where('platform', $platform)->where('user_id', $this->user->id)->find();
                if (!$thirdOauth) {
                    throw new CusException('请先绑定微信账号', -1);
                }
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '微信用户' => $thirdOauth['nickname'] ?: $this->user['nickname'],
                    '微信ID'  => $thirdOauth['openid'],
                ];
                break;
            case 'alipay':
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '支付宝账户' => $params['account_no']
                ];
                break;
            case 'bank':
                $withdrawInfo = [
                    '真实姓名' => $params['account_name'],
                    '开户行' => $params['account_header'] ?? '',
                    '银行卡号' => $params['account_no']
                ];
                break;
        }
        if (!isset($withdrawInfo)) {
            throw new CusException('您的提现信息有误');
        }

        return $withdrawInfo;
    }
}
