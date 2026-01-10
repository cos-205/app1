<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use app\common\model\CusUserWalletLog;
use app\common\model\fuka\WealthCard;
use app\common\model\fuka\CardFlowConfig;
use app\common\model\fuka\CardFlowLog;
use app\common\model\fuka\CardOrder;
use app\common\model\fuka\CardAgreementFlow;
use app\common\model\fuka\UserAgreementFlow;
use app\common\model\fuka\MemberLevel;
use app\common\validate\fuka\WealthCard as WealthCardValidate;
use app\common\service\PaymentService;
use think\Db;

/**
 * 财富金卡接口
 * 
 * @ApiTitle    (财富金卡系统)
 * @ApiSummary  (财富金卡相关接口)
 * @ApiSector   (财富金卡)
 */
class Card extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';


    /**
     * 申请财富金卡
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/wealthCard/apply)
     * @ApiReturn ({'code':'1','msg':'申请成功','data':{'card':{}}})
     */
    public function apply()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 检查会员等级
        if ($user->member_level < 1) {
            $this->error('需要成为铂金会员才能领取财富金卡');
        }

        // 检查是否已实名
        if (!$user->is_realname) {
            $this->error('请先完成实名认证');
        }

        // 检查是否已有金卡
        $card = WealthCard::where('user_id', $user->id)->find();
        if ($card && $card->apply_status > 0) {
            $this->error('您已申请过财富金卡');
        }

        // 检查邀请人数（需要2位有效邀请）- 暂时不需要此条件
        // $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
        // if (!$userStats || $userStats->valid_invite_count < 2) {
        //     $this->error('需要邀请2位好友完成实名认证才能申请财富金卡');
        // }

        Db::startTrans();
        try {
            if (!$card) {
                $card = new WealthCard();
                $card->user_id = $user->id;
                $card->holder_name = $user->realname;
                $card->holder_idcard = $user->idcard;
                $card->card_no = 'CARD' . date('Ymd') . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            }

            $card->apply_status = 2; // 审核中
            $card->apply_time = time();
            $card->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('申请失败，请稍后重试');
        }
        
        $this->success('申请成功，等待审核', ['card' => $card]);
    }

    /**
     * 获取流程配置（新版）
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/flowConfig)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'steps':[],'apply_conditions':{}}})
     */
    public function flowConfig()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            // 自动创建金卡记录
            $card = new WealthCard();
            $card->user_id = $user->id;
            $card->holder_name = $user->realname ?: '';
            $card->holder_idcard = $user->idcard ?: '';
            $card->card_no = 'CARD' . date('Ymd') . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            $card->flow_status = 0;
            $card->apply_status = 0;
            $card->save();
        }

        // 实时检查申领条件
        // 实时查询已实名认证的好友数量（确保准确性）
        $validInviteCount = User::where('parent_user_id', $user->id)
            ->where('is_realname', 1)
            ->where('status', 'normal')
            ->count();
        
        // 根据实时统计的邀请人数自动升级会员等级
        $this->updateMemberLevelByInviteCount($user, $validInviteCount);
        
        // 重新获取用户信息（会员等级可能已更新）
        $user = User::get($user->id);
        
        // 条件1：铂金会员（会员等级 >= 1 且 邀请2位好友）
        $hasInvite = $validInviteCount >= 2;
        $isMemberLevel = intval($user->member_level) >= 1;
        $isMember = $isMemberLevel && $hasInvite; // 需要同时满足会员等级和邀请条件
        
        // 条件2：实名认证
        $isRealname = intval($user->is_realname) === 1;
        
        // 条件3：邀请2位好友完成实名认证（已合并到铂金会员条件中）
        // $hasInvite = $validInviteCount >= 2;
        
        // 条件4：收货地址（从 fa_cus_user_address 表查询）
        $userAddress = Db::name('cus_user_address')
            ->where('user_id', $user->id)
            ->find();
        $hasAddress = !empty($userAddress);
        
        // 申领条件汇总
        $applyConditions = [
            [
                'name' => '铂金会员',
                'desc' => '需要成为铂金会员',
                'completed' => $isMember,
                'required' => true,
                'value' => $user->member_level,
                'member_level_ok' => $isMemberLevel,
                'invite_ok' => $hasInvite
            ],
            [
                'name' => '实名认证',
                'desc' => '需要完成实名认证',
                'completed' => $isRealname,
                'required' => true,
                'value' => $user->is_realname,
                'realname' => $user->realname ?: ''
            ],
            // [
            //     'name' => '邀请好友',
            //     'desc' => sprintf('需要邀请2位好友完成实名认证（当前：%d/2）', $validInviteCount),
            //     'completed' => $hasInvite,
            //     'required' => true,
            //     'current' => $validInviteCount,
            //     'target' => 2
            // ],
            [
                'name' => '收货地址',
                'desc' => '需要填写收货地址',
                'completed' => $hasAddress,
                'required' => true,
                'address_count' => $userAddress ? 1 : 0,
                'has_consignee' => $userAddress ? !empty($userAddress['consignee']) : false,
                'has_mobile' => $userAddress ? !empty($userAddress['mobile']) : false,
                'has_address' => $userAddress ? !empty($userAddress['address']) : false
            ]
        ];
        
        // 是否可以申领（所有条件都满足）
        $canApply = $isMember && $isRealname && $hasAddress;

        // 获取流程配置
        $flowConfigs = CardFlowConfig::where('status', 'normal')
            ->order('step asc')
            ->select();

        // 如果已领取金卡（apply_status >= 2），自动启用步骤1
        if ($card->apply_status >= 2) {
            // 如果 flow_status 还是0，自动设置为1以启用步骤1
            if ($card->flow_status == 0) {
                $card->flow_status = 1;
                $card->save();
            }
            
            // 检查步骤1的流程记录是否存在
            $step1Log = CardFlowLog::where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->where('flow_step', 1)
                ->find();
            
            // 如果步骤1的流程记录不存在，自动创建
            if (!$step1Log) {
                $step1Config = CardFlowConfig::where('step', 1)
                    ->where('status', 'normal')
                    ->find();
                
                if ($step1Config) {
                    $step1Log = new CardFlowLog();
                    $step1Log->user_id = $user->id;
                    $step1Log->card_id = $card->id;
                    $step1Log->flow_step = 1;
                    $step1Log->step_name = $step1Config->step_name;
                    $step1Log->need_fee = $step1Config->need_fee ? 1 : 0;
                    $step1Log->fee_amount = $step1Config->fee_amount;
                    $step1Log->fee_name = $step1Config->step_name . '费用';
                    $step1Log->flow_status = 1; // 未支付
                    $step1Log->save();
                    
                    // 创建协议流程记录
                    $this->createAgreementFlowRecords($user->id, 1);
                    
                    output_log('info', [
                        'title' => '[金卡系统] 自动创建协议签署流程记录',
                        'user_id' => $user->id,
                        'card_id' => $card->id
                    ]);
                }
            }
        }

        // 获取用户流程记录
        $flowLogs = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->select();
        
        $flowLogMap = [];
        foreach ($flowLogs as $log) {
            $flowLogMap[$log->flow_step] = $log;
        }

        // 获取用户协议流程记录（步骤1）
        $agreementFlows = UserAgreementFlow::where('user_id', $user->id)
            ->where('step_id', 1)
            ->select();
        $agreementSigned = false;
        foreach ($agreementFlows as $flow) {
            if ($flow->status >= 1) { // 已签署（进行中或已完成）
                $agreementSigned = true;
                break;
            }
        }

        // 组装流程信息
        $steps = [];
        foreach ($flowConfigs as $config) {
            $log = isset($flowLogMap[$config->step]) ? $flowLogMap[$config->step] : null;
            
            // 判断是否已签署/已提交数据
            $dataSubmitted = false;
            if ($config->step == 1) {
                // 步骤1：检查是否已签署协议
                $dataSubmitted = $agreementSigned;
            } elseif (in_array($config->step, [2, 3])) {
                // 步骤3、4：检查是否已提交数据
                $dataSubmitted = $log && !empty($log->extra_data);
            }
            
            // 步骤启用逻辑
            // 步骤1：需要按流程状态启用（金卡申请流程的一部分）
            // 步骤2-9：独立功能，只要申请了金卡就可以开通（不依赖前置步骤）
            $isEnabled = false;
            if ($config->step == 1) {
                // 步骤1：需要 flow_status >= 1（已申请金卡）
                $isEnabled = $card->flow_status >= $config->step;
            } else {
                // 步骤2-9：只要申请了金卡（apply_status >= 2）就可以开通
                $isEnabled = $card->apply_status >= 2;
            }
            
            $steps[] = [
                'step' => $config->step,
                'step_type' => $config->step_type,
                'step_name' => $config->step_name,
                'step_desc' => $config->step_desc,
                'need_fee' => $config->need_fee,
                'fee_amount' => $config->fee_amount,
                'fee_receiver' => $config->fee_receiver,
                'fee_purpose' => $config->fee_purpose,
                'button_text' => $config->button_text,
                'completed_text' => $config->completed_text,
                'completed_title' => $config->completed_title,
                'scene_desc' => $config->scene_desc,
                'refund_rule' => $config->refund_rule,
                // 用户流程状态
                'flow_status' => $log ? $log->flow_status : 1,  // 1=未支付, 3=已完成
                'order_id' => $log ? $log->order_id : null,
                // 是否启用
                'enabled' => $isEnabled,
                // 是否为独立功能（步骤2-9）
                'is_standalone' => $config->step > 1,
                // 前置动作状态
                'agreement_signed' => $config->step == 1 ? $agreementSigned : null, // 步骤1：是否已签署协议
                'data_submitted' => in_array($config->step, [2, 3]) ? $dataSubmitted : null, // 步骤2、3：是否已提交数据
            ];
        }

        // 获取基础配置（从 shop.basic 读取，不使用缓存确保获取最新配置）
        $basicConfig = \app\admin\model\cus\Config::getConfigs('shop.basic', false);
        if (!$basicConfig) {
            $basicConfig = [];
        }
        
        // 根据配置过滤步骤（在返回给前端之前就过滤，确保数据准确性）
        // 判断逻辑：同时判断步骤ID和步骤名称，确保准确对应
        $hideWithdraw = intval($basicConfig['hide_withdraw'] ?? 0);
        $hideEntryTicket = intval($basicConfig['hide_entry_ticket'] ?? 0);
        
        if ($hideWithdraw === 1) {
            // 隐藏提现功能（步骤5：财富金卡APP提现至卡片）
            $steps = array_filter($steps, function($step) {
                // 判断步骤ID是否为5，且步骤名称包含"提现"
                if ($step['step'] == 5) {
                    $stepName = $step['step_name'] ?? '';
                    // 如果步骤名称包含"提现"，则隐藏（返回false表示过滤掉）
                    return mb_strpos($stepName, '提现', 0, 'UTF-8') === false;
                }
                return true;
            });
            // 重新索引数组
            $steps = array_values($steps);
        }
        
        if ($hideEntryTicket === 1) {
            // 隐藏入场券功能（步骤6：邮寄支付宝会员入场证）
            $steps = array_filter($steps, function($step) {
                // 判断步骤ID是否为6，且步骤名称包含"入场券"或"入场证"
                if ($step['step'] == 6) {
                    $stepName = $step['step_name'] ?? '';
                    // 如果步骤名称包含"入场券"或"入场证"，则隐藏（返回false表示过滤掉）
                    return mb_strpos($stepName, '入场券', 0, 'UTF-8') === false 
                        && mb_strpos($stepName, '入场证', 0, 'UTF-8') === false;
                }
                return true;
            });
            // 重新索引数组
            $steps = array_values($steps);
        }

        $this->success('获取成功', [
            'card_id' => $card->id,
            'card_status' => [
                'flow_status' => $card->flow_status,
                'apply_status' => $card->apply_status,
                'holder_name' => $card->holder_name ?: '',
                'holder_idcard' => $card->holder_idcard ?: '',
                'balance' => $card->card_balance ? number_format($card->card_balance, 2, '.', '') : '0.00',
                'card_no' => $card->card_no ?: '',
                'agreement_signed' => $agreementSigned, // 是否已签署协议
            ],
            'steps' => $steps,
            'total_amount' => array_sum(array_column($steps, 'fee_amount')),
            'apply_conditions' => $applyConditions,
            'can_apply' => $canApply,
            'invite_progress' => [
                'current' => $validInviteCount,
                'target' => 2,
                'completed' => $hasInvite
            ]
        ]);
    }

    /**
     * 创建支付订单
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/createOrder)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤")
     * @ApiReturn ({'code':'1','msg':'创建成功','data':{'order':{}}})
     */
    public function createOrder()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 0);
        if ($step < 1 || $step > 9) {
            $this->error('无效的参数');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        if (!$flowConfig->need_fee) {
            $this->error('该步骤无需支付');
        }

        // 检查金卡申请状态（所有步骤都需要先申请金卡）
        if ($card->apply_status < 2) {
            $this->error('请先申请财富金卡');
        }

        // 步骤1：需要检查是否已签署协议
        // 步骤2-9：独立功能，不检查前置步骤，只需要申请了金卡即可
        if ($step == 1) {
            // 步骤1：检查是否已签署协议
            $agreementFlow = UserAgreementFlow::where('user_id', $user->id)
                ->where('step_id', 1)
                ->where('status', '>=', 1) // 已签署（进行中或已完成）
                ->find();
            
            if (!$agreementFlow) {
                $this->error('请先签署协议');
            }
        } elseif (in_array($step, [2, 3])) {
            // 步骤3、4：检查是否已提交数据
            $currentLog = CardFlowLog::where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->where('flow_step', $step)
                ->find();
            
            if (!$currentLog || empty($currentLog->extra_data)) {
                $this->error('请先提交必要的数据');
            }
        }

        // 检查当前步骤是否已完成
        $currentLog = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('flow_step', $step)
            ->find();
        
        if ($currentLog && $currentLog->flow_status == 3) {
            $this->error('该步骤已完成，无需重复支付');
        }

        // 检查是否已有未支付的订单
        $existOrder = CardOrder::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('step_id', $step)
            ->where('pay_status', 0)
            ->order('id desc')
            ->find();
        
        if ($existOrder) {
            // 检查订单是否过期（30分钟）
            if (time() - $existOrder->createtime < 1800) {
                $this->success('订单已存在', ['order' => $existOrder]);
            } else {
                // 订单已过期，标记为超时（可选）
                output_log('info', [
                    'title' => '订单已超时',
                    'order_id' => $existOrder->id,
                    'order_no' => $existOrder->order_no
                ]);
            }
        }

        // 创建新订单
        Db::startTrans();
        try {
            // 生成唯一订单号（增加用户ID避免冲突）
            $orderNo = 'CO' . date('YmdHis') . sprintf('%06d', $user->id % 1000000) . rand(100, 999);
            
            // 再次检查订单号是否重复（极端情况）
            $duplicate = CardOrder::where('order_no', $orderNo)->find();
            if ($duplicate) {
                $orderNo = 'CO' . date('YmdHis') . sprintf('%06d', $user->id % 1000000) . rand(1000, 9999);
            }
            
            $order = new CardOrder();
            $order->user_id = $user->id;
            $order->card_id = $card->id;
            $order->step_id = $step;
            $order->step_name = $flowConfig->step_name;
            $order->order_no = $orderNo;
            $order->amount = $flowConfig->fee_amount;
            $order->pay_status = 0;
            $order->refund_status = 0;
            $order->save();

            Db::commit();
            
            output_log('info', [
                'title' => '创建金卡支付订单',
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_no' => $orderNo,
                'step' => $step,
                'amount' => $flowConfig->fee_amount
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            output_log('error', [
                'title' => '创建订单失败',
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('创建订单失败，请稍后重试');
        }
        
        // success 必须在 try-catch 外面，否则抛出的异常会被catch捕获
        $this->success('创建成功', ['order' => $order]);
    }

    /**
     * 获取订单信息
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/getOrderInfo)
     * @ApiParams (name="order_id", type="integer", required=true, description="订单ID")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'order':{}}})
     */
    public function getOrderInfo()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderId = $this->request->param('order_id/d', 0);
        if (!$orderId) {
            $this->error('订单ID不能为空');
        }

        $order = CardOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$order) {
            $this->error('订单不存在');
        }

        $this->success('获取成功', ['order' => $order]);
    }

    /**
     * 获取支付参数
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/getPaymentParams)
     * @ApiParams (name="order_id", type="integer", required=true, description="订单ID")
     * @ApiParams (name="pay_type", type="string", required=true, description="支付类型:wechat/alipay/unionpay")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'payment_url':'','order_no':''}})
     */
    public function getPaymentParams()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderId = $this->request->param('order_id/d', 0);
        $payType = $this->request->param('pay_type', '');

        if (!$orderId) {
            $this->error('订单ID不能为空');
        }

        if (!in_array($payType, ['wechat', 'alipay', 'unionpay'])) {
            $this->error('不支持的支付方式');
        }

        $order = CardOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$order) {
            $this->error('订单不存在');
        }

        if ($order->pay_status == 1) {
            $this->error('订单已支付');
        }

        // 检查订单是否超时
        if (time() - $order->createtime > 1800) {
            $this->error('订单已超时，请重新创建');
        }

        try {
            // 使用统一支付服务生成支付参数
            $paymentParams = PaymentService::generatePaymentParams($order, $payType);

            output_log('info', [
                'title' => '获取支付参数',
                'user_id' => $user->id,
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'pay_type' => $payType
            ]);


        } catch (\Exception $e) {
            output_log('error', [
                'title' => '获取支付参数失败',
                'user_id' => $user->id,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
        $this->success('获取成功', $paymentParams);
            
    }

    /**
     * 查询支付结果
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/paymentResult)
     * @ApiParams (name="order_id", type="integer", required=false, description="订单ID")
     * @ApiParams (name="order_no", type="string", required=false, description="订单号")
     * @ApiReturn ({'code':'1','msg':'查询成功','data':{'pay_status':0,'order':{}}})
     */
    public function paymentResult()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderId = $this->request->param('order_id/d', 0);
        $orderNo = $this->request->param('order_no', '');
        
        if (!$orderId && !$orderNo) {
            $this->error('订单ID或订单号不能为空');
        }

        $orderQuery = CardOrder::where('user_id', $user->id);
        
        if ($orderId) {
            $orderQuery->where('id', $orderId);
        } elseif ($orderNo) {
            $orderQuery->where('order_no', $orderNo);
        }
        
        $order = $orderQuery->find();
        
        if (!$order) {
            $this->error('订单不存在');
        }

        $this->success('查询成功', [
            'pay_status' => $order->pay_status,
            'order' => $order
        ]);
    }

    /**
     * 签署协议（步骤1专用）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/signAgreement)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤（固定为1）")
     * @ApiReturn ({'code':'1','msg':'签署成功','data':{}})
     */
    public function signAgreement()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 0);
        // 支持步骤1和步骤4（协议签署类步骤）
        if (!in_array($step, [1, 4])) {
            $this->error('此接口仅用于协议签署步骤（步骤1或步骤4）');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }


        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        Db::startTrans();
        try {
            // 创建或更新协议流程记录
            $this->createAgreementFlowRecords($user->id, $step);
            
            // 更新所有协议流程记录状态为"进行中"（已签署，等待支付）
            UserAgreementFlow::where('user_id', $user->id)
                ->where('step_id', $step)
                ->where('status', '<', 2)
                ->update([
                    'status' => 1, // 进行中（已签署，等待支付）
                    'start_time' => time(),
                    'updatetime' => time()
                ]);

            // 创建或更新流程记录
            $flowLog = CardFlowLog::where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->where('flow_step', $step)
                ->find();

            if (!$flowLog) {
                $flowLog = new CardFlowLog();
                $flowLog->user_id = $user->id;
                $flowLog->card_id = $card->id;
                $flowLog->flow_step = $step;
                $flowLog->step_name = $flowConfig->step_name;
                $flowLog->need_fee = $flowConfig->need_fee ? 1 : 0;
                $flowLog->fee_amount = $flowConfig->fee_amount;
                $flowLog->fee_name = $flowConfig->step_name . '费用';
            }

            // 更新流程状态为"未支付"（已签署，等待支付）
            $flowLog->flow_status = 1; // 未支付（已签署，等待支付）
            $flowLog->save();

            Db::commit();
            
            output_log('info', [
                'title' => '协议签署成功',
                'user_id' => $user->id,
                'step' => $step,
                'flow_log_id' => $flowLog->id
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            output_log('error', [
                'title' => '协议签署失败',
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('签署失败，请稍后重试');
        }
        
        $this->success('协议签署成功，请继续支付');
    }

    /**
     * 提交步骤数据（用于步骤3、4等需要提交数据的步骤）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/submitStepData)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤（3、4等）")
     * @ApiParams (name="data", type="object", required=true, description="步骤数据（如密码、限额等）")
     * @ApiReturn ({'code':'1','msg':'提交成功','data':{}})
     */
    public function submitStepData()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 0);
        if ($step < 1 || $step > 9) {
            $this->error('无效的步骤编号');
        }

        // 处理步骤数据 - 尝试多种方式获取 POST JSON 数据
        $stepData = null;
        
        // 方式1: 直接获取 data 参数
        $params = $this->request->param();
        $stepData = $params["data"];
        // 验证数据是否为有效的数组
        if ( empty($stepData)) {
            output_log('submitStepData_param_error', [
                'user_id' => $user->id,
                'step' => $step,
                'all_params' => $this->request->param(),
                'post_params' => $this->request->post(),
                'raw_content' => $this->request->getContent(),
                'error' => '数据格式错误或为空'
            ]);
            $this->error('请提交必要的数据');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        // 验证步骤数据
        try {
            $this->validateStepData($step, $stepData);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        Db::startTrans();
        try {
            // 获取或创建流程记录
            $flowLog = CardFlowLog::where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->where('flow_step', $step)
                ->find();

            if (!$flowLog) {
                $flowLog = new CardFlowLog();
                $flowLog->user_id = $user->id;
                $flowLog->card_id = $card->id;
                $flowLog->flow_step = $step;
                $flowLog->step_name = $flowConfig->step_name;
                $flowLog->need_fee = $flowConfig->need_fee ? 1 : 0;
                $flowLog->fee_amount = $flowConfig->fee_amount;
                $flowLog->fee_name = $flowConfig->step_name . '费用';
            }

            // 保存步骤数据（JSON格式）
            $flowLog->extra_data = json_encode($stepData, JSON_UNESCAPED_UNICODE);
            // 更新流程状态为"未支付"（已提交数据，等待支付）
            $flowLog->flow_status = 1; // 未支付（已提交数据，等待支付）
            $flowLog->save();

            Db::commit();
            
            output_log('info', [
                'title' => '步骤数据提交成功',
                'user_id' => $user->id,
                'step' => $step,
                'flow_log_id' => $flowLog->id
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            output_log('error', [
                'title' => '步骤数据提交失败',
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('提交失败，请稍后重试');
        }
        
        $this->success('数据提交成功，请继续支付');
    }

    /**
     * 验证步骤数据
     * @throws \Exception
     */
    private function validateStepData($step, $data)
    {
        switch ($step) {
            case 2: // 设置卡片密码
                if (!isset($data['card_password'])) {
                    throw new \Exception('请设置卡片密码');
                }
                $password = trim($data['card_password']);
                if (strlen($password) != 6 || !ctype_digit($password)) {
                    throw new \Exception('密码必须为6位数字');
                }
                break;
            
            case 3: // 大额收付款功能
                if (!isset($data['payment_limit'])) {
                    throw new \Exception('请设置支付限额');
                }
                $limit = floatval($data['payment_limit']);
                if ($limit <= 0 || $limit > 1000000) {
                    throw new \Exception('支付限额必须在0-100万之间');
                }
                break;
            
            case 5: // 绑定金卡
                if (!isset($data['card_no']) || empty($data['card_no'])) {
                    throw new \Exception('请提供金卡卡号');
                }
                if (!isset($data['holder_name']) || empty($data['holder_name'])) {
                    throw new \Exception('请提供持卡人姓名');
                }
                break;
            
            case 6: // 邮寄确认
                if (!isset($data['address_id']) || empty($data['address_id'])) {
                    throw new \Exception('请选择收货地址');
                }
                if (!isset($data['consignee']) || empty($data['consignee'])) {
                    throw new \Exception('请提供收货人姓名');
                }
                if (!isset($data['mobile']) || empty($data['mobile'])) {
                    throw new \Exception('请提供联系电话');
                }
                if (!isset($data['address']) || empty($data['address'])) {
                    throw new \Exception('请提供详细地址');
                }
                break;
            
            default:
                // 其他步骤不需要提交数据，允许通过
                break;
        }
    }

    /**
     * 完成流程步骤（新版）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/completeStepV2)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤（1-9）")
     * @ApiReturn ({'code':'1','msg':'提交成功','data':{}})
     */
    public function completeStepV2()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 0);

        if ($step < 1 || $step > 9) {
            $this->error('无效的步骤编号');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        // 获取流程记录
        $flowLog = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('flow_step', $step)
            ->find();

        // 检查支付状态
        if ($flowConfig->need_fee) {
            if (!$flowLog || $flowLog->flow_status < 2) {
                $this->error('请先完成支付');
            }
            
            if ($flowLog->flow_status == 3) {
                $this->error('该步骤已完成');
            }
        }

        Db::startTrans();
        try {
            if (!$flowLog) {
                $flowLog = new CardFlowLog();
                $flowLog->user_id = $user->id;
                $flowLog->card_id = $card->id;
                $flowLog->flow_step = $step;
                $flowLog->step_name = $flowConfig->step_name;
                $flowLog->need_fee = $flowConfig->need_fee ? 1 : 0;
                $flowLog->fee_amount = $flowConfig->fee_amount;
                $flowLog->fee_name = $flowConfig->step_name . '费用';
            }

            // 如果已支付，直接更新状态为已完成（支付成功后自动审核通过）
            if ($flowLog->flow_status >= 2) {
                $flowLog->flow_status = 3; // 已完成
                $flowLog->complete_time = time();
            } else {
                // 未支付的情况（理论上不应该到这里，因为上面已经检查了）
                $flowLog->flow_status = 1; // 未支付
            }
            $flowLog->save();

            // 如果是步骤1（协议签署），创建协议流程记录
            if ($step == 1 && $flowLog->flow_status == 3) {
                $this->createAgreementFlowRecords($user->id, $step);
            }

            Db::commit();
            
            output_log('info', [
                'title' => '提交流程步骤',
                'user_id' => $user->id,
                'step' => $step,
                'flow_log_id' => $flowLog->id
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            output_log('error', [
                'title' => '提交步骤失败',
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('提交失败，请稍后重试');
        }
        
        // success 必须在 try-catch 外面，否则抛出的异常会被catch捕获
        $this->success('提交成功');
    }

    /**
     * 创建协议流程记录
     */
    private function createAgreementFlowRecords($userId, $stepId)
    {
        // 获取协议流程配置
        $agreementFlows = CardAgreementFlow::where('step_id', $stepId)
            ->order('flow_step asc')
            ->select();

        foreach ($agreementFlows as $flow) {
            $userFlow = UserAgreementFlow::where('user_id', $userId)
                ->where('step_id', $stepId)
                ->where('flow_step', $flow->flow_step)
                ->find();
            
            if (!$userFlow) {
                $userFlow = new UserAgreementFlow();
                $userFlow->user_id = $userId;
                $userFlow->step_id = $stepId;
                $userFlow->flow_step = $flow->flow_step;
                // flow_name 不需要存储，从 CardAgreementFlow 表关联获取即可
                $userFlow->status = 0; // 未开始
                $userFlow->save();
            }
        }
    }

    /**
     * 获取协议列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/agreementList)
     * @ApiParams (name="step_id", type="integer", required=true, description="步骤ID（默认为1）")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[]}})
     */
    public function agreementList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $stepId = $this->request->param('step_id/d', 1);

        // 获取协议流程配置
        $agreementFlows = CardAgreementFlow::where('step_id', $stepId)
            ->order('flow_step asc')
            ->select();

        // 获取用户协议流程记录
        $userFlows = UserAgreementFlow::where('user_id', $user->id)
            ->where('step_id', $stepId)
            ->select();
        
        $userFlowMap = [];
        foreach ($userFlows as $flow) {
            $userFlowMap[$flow->flow_step] = $flow;
        }

        // 组装数据
        $list = [];
        foreach ($agreementFlows as $flow) {
            $userFlow = isset($userFlowMap[$flow->flow_step]) ? $userFlowMap[$flow->flow_step] : null;
            
            $list[] = [
                'flow_step' => $flow->flow_step,
                'flow_name' => $flow->flow_name,
                'flow_desc' => $flow->flow_desc,
                'estimated_days' => $flow->estimated_days,
                'sort' => $flow->sort,
                'status' => $userFlow ? $userFlow->status : 0,
                'start_time' => $userFlow ? $userFlow->start_time : null,
                'completed_time' => $userFlow ? $userFlow->completed_time : null,
            ];
        }

        $this->success('获取成功', ['list' => $list]);
    }

    /**
     * 获取协议详情
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/agreementDetail)
     * @ApiParams (name="step_id", type="integer", required=true, description="步骤ID")
     * @ApiParams (name="flow_step", type="integer", required=true, description="流程步骤")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'detail':{}}})
     */
    public function agreementDetail()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $stepId = $this->request->param('step_id/d', 1);
        $flowStep = $this->request->param('flow_step/d', 0);

        // 获取协议流程配置
        $agreementFlow = CardAgreementFlow::where('step_id', $stepId)
            ->where('flow_step', $flowStep)
            ->find();
        
        if (!$agreementFlow) {
            $this->error('协议流程不存在');
        }

        // 获取用户协议流程记录
        $userFlow = UserAgreementFlow::where('user_id', $user->id)
            ->where('step_id', $stepId)
            ->where('flow_step', $flowStep)
            ->find();

        $detail = [
            'flow_step' => $agreementFlow->flow_step,
            'flow_name' => $agreementFlow->flow_name,
            'flow_desc' => $agreementFlow->flow_desc,
            'estimated_days' => $agreementFlow->estimated_days,
            'status' => $userFlow ? $userFlow->status : 0,
            'start_time' => $userFlow ? $userFlow->start_time : null,
            'completed_time' => $userFlow ? $userFlow->completed_time : null,
        ];

        $this->success('获取成功', ['detail' => $detail]);
    }

    /**
     * 获取协议内容（用于协议签署页面）
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/agreementContent)
     * @ApiParams (name="step", type="integer", required=true, description="步骤ID（1或4）")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'step_name':'','content':''}})
     */
    public function agreementContent()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 1);
        
        // 只支持协议签署步骤
        if (!in_array($step, [1, 4])) {
            $this->error('此接口仅用于协议签署步骤');
        }

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        // 获取协议流程列表（用于组装协议内容）
        $agreementFlows = CardAgreementFlow::where('step_id', $step)
            ->order('flow_step asc')
            ->select();

        // 组装协议内容
        $content = '';
        foreach ($agreementFlows as $flow) {
            if (!empty($flow->flow_desc)) {
                $content .= $flow->flow_desc . "\n\n";
            }
        }

        // 如果没有协议流程配置，使用步骤描述作为默认内容
        if (empty($content) && !empty($flowConfig->step_desc)) {
            $content = $flowConfig->step_desc;
        }

        // 如果还是没有内容，使用默认协议模板
        if (empty($content)) {
            $content = "请在此处配置协议内容。\n\n协议内容可以从后台管理系统配置。";
        }

        $this->success('获取成功', [
            'step' => $step,
            'step_name' => $flowConfig->step_name,
            'content' => $content,
        ]);
    }

    /**
     * 根据邀请人数自动升级会员等级
     * @param User $user 用户对象
     * @param int $validInviteCount 有效邀请人数（已实名认证）
     */
    private function updateMemberLevelByInviteCount($user, $validInviteCount)
    {
        // 从会员等级表中读取升级条件（不写死）
        $memberLevels = MemberLevel::where('status', 'normal')
            ->order('invite_count', 'desc')  // 按邀请人数降序排序（邀请人数多的在前）
            ->order('level', 'desc')         // 同邀请人数时，按等级降序
            ->select();

        // select() 返回数组，使用 empty() 检查
        if (empty($memberLevels)) {
            output_log('warning', [
                'title' => '[金卡系统] 会员等级配置表为空，无法升级',
                'user_id' => $user->id,
                'valid_invite_count' => $validInviteCount
            ]);
            return;
        }

        // 找到满足邀请人数条件的最高等级
        // 逻辑：邀请人数 >= 该等级要求的邀请人数，且等级最高
        $newLevel = 0; // 默认为普通会员
        $newLevelName = '普通会员';
        
        foreach ($memberLevels as $levelConfig) {
            $requiredInviteCount = intval($levelConfig->invite_count);
            $levelValue = intval($levelConfig->level);
            
            // 如果邀请人数达到该等级的要求，且该等级比当前找到的等级更高
            if ($validInviteCount >= $requiredInviteCount && $levelValue > $newLevel) {
                $newLevel = $levelValue;
                $newLevelName = $levelConfig->name;
            }
        }

        // 只在等级提升时更新
        $currentLevel = intval($user->member_level);
        if ($newLevel > $currentLevel) {
            $oldLevel = $currentLevel;
            $user->member_level = $newLevel;
            $user->save();

            // 同时更新统计表中的有效邀请人数
            $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
            if ($userStats) {
                $userStats->valid_invite_count = $validInviteCount;
                $userStats->last_update_time = time();
                $userStats->updatetime = time();
                $userStats->save();
            }

            // 记录等级提升日志
            output_log('info', [
                'title' => '[金卡系统] 会员等级自动提升',
                'user_id' => $user->id,
                'mobile' => $user->mobile,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'new_level_name' => $newLevelName,
                'valid_invite_count' => $validInviteCount
            ]);
        } else {
            // 即使等级不变，也更新统计表中的有效邀请人数（保持数据同步）
            $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
            if ($userStats && $userStats->valid_invite_count != $validInviteCount) {
                $userStats->valid_invite_count = $validInviteCount;
                $userStats->last_update_time = time();
                $userStats->updatetime = time();
                $userStats->save();
            }
        }
    }
}

