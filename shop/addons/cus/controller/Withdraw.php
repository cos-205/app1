<?php

namespace addons\cus\controller;

use think\Db;
use think\exception\HttpResponseException;
use app\admin\model\cus\Withdraw as WithdrawModel;
use addons\cus\service\Withdraw as WithdrawService;

class Withdraw extends Common
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = auth_user();
        $withdraws = WithdrawModel::where(['user_id' => $user->id])->order('id desc')->paginate($this->request->param('list_rows', 10))->each(function ($withdraw) {
            $withdraw->hidden(['withdraw_info']);
        });

        $this->success('获取成功', $withdraws);
    }


    // 提现规则
    public function rules()
    {
        $user = auth_user();
        $config = (new WithdrawService($user))->config;

        $this->success('提现规则', $config);
    }


    // 发起提现请求
    public function apply()
    {
        $this->repeatFilter('addons/cus/withdraw');

        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".apply");

        $withdrawService = new WithdrawService($user);
        // 申请提现
        Db::startTrans();
        try {
            $withdraw = $withdrawService->apply($params);
            Db::commit();
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            $this->error($message);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        // 微信新版本商家转账
        if ($withdraw->withdraw_type === 'wechat') {
            try {
                $transferData = $withdrawService->handleWechatTransfer($withdraw);
            } catch (HttpResponseException $e) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                $this->error($message);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }



        // 支付宝提现+自动打款
        if ($withdraw->withdraw_type === 'alipay' && $withdrawService->config['auto_arrival']) {
            try {
                // 记录提现日志
                $withdrawService->handleAlipayWithdraw($withdraw);
            } catch (HttpResponseException $e) {
                $data = $e->getResponse()->getData();
                $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
                $this->error($message);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $this->success('申请成功', [
            'id' => $withdraw->id,
            'type' => $withdraw->withdraw_type,
            'withdraw_sn' => $withdraw->withdraw_sn,
            'transfer_data' => $transferData ?? null,
        ]);
    }

    // 继续提现（仅支持微信商家转账）
    public function transfer()
    {
        $this->repeatFilter('addons/cus/withdraw');

        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".transfer");

        $withdrawService = new WithdrawService($user);
        // 如果 微信提现， result 为 package_info，其他的为 withdrawModel 对象
        $result = $withdrawService->retry($params);

        $this->success('操作成功', $result);
    }

    // 取消提现（仅支持微信商家转账）
    public function cancel()
    {
        $this->repeatFilter('addons/cus/withdraw');

        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".cancel");

        $withdrawService = new WithdrawService($user);

        try {
            $result = $withdrawService->cancel($params);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('操作成功', $result);
    }

    // 取消提现（仅支持微信商家转账）
    public function retry()
    {
        $this->repeatFilter('addons/cus/withdraw');

        $user = auth_user();
        $params = $this->request->param();

        $this->svalidate($params, ".retry");

        $withdrawService = new WithdrawService($user);

        try {
            $withdraw = $withdrawService->retry($params);
            $transferData = $withdrawService->handleWechatTransfer($withdraw);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('申请成功', [
            'id' => $withdraw->id,
            'type' => $withdraw->withdraw_type,
            'withdraw_sn' => $withdraw->withdraw_sn,
            'transfer_data' => $transferData ?? null,
        ]);
    }
}
