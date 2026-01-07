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
use app\common\validate\fuka\WealthCard as WealthCardValidate;
use app\common\service\PaymentService;
use think\Db;
use think\Log;

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
     * 获取金卡信息
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/wealthCard/info)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'card':{},'flow_config':[]}})
     */
    public function info()
    {
        //字符查找
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 检查会员等级
        if ($user->member_level < 1) {
            $this->success('需要成为铂金会员才能领取财富金卡',[]);
        }

        // 获取或创建金卡记录
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            // 创建金卡记录
            $card = new WealthCard();
            $card->user_id = $user->id;
            $card->holder_name = $user->realname;
            $card->holder_idcard = $user->idcard;
            $card->flow_status = 0;
            $card->apply_status = 0;
            $card->save();
        }

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('status', 'normal')
            ->order('sort_order asc')
            ->select();

        // 获取流程完成记录
        $flowLogs = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->select();
        
        $flowLogMap = [];
        foreach ($flowLogs as $log) {
            $flowLogMap[$log->flow_step] = $log;
        }

        // 组装流程信息
        $flowList = [];
        foreach ($flowConfig as $config) {
            $log = isset($flowLogMap[$config->step]) ? $flowLogMap[$config->step] : null;
            $flowList[] = [
                'step' => $config->step,
                'step_name' => $config->step_name,
                'step_desc' => $config->step_desc,
                'need_fee' => $config->need_fee,
                'fee_amount' => $config->fee_amount,
                'fee_name' => $config->fee_name,
                'need_refund' => $config->need_refund,
                'refund_days' => $config->refund_days,
                'is_completed' => $log ? $log->is_completed : 0,
                'is_pay_fee' => $log ? $log->is_pay_fee : 0,
                'is_refund_fee' => $log ? $log->is_refund_fee : 0
            ];
        }

        $this->success('获取成功', [
            'card' => $card,
            'flow_config' => $flowList
        ]);
    }

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

        // 检查邀请人数（需要2位有效邀请）
        $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
        if (!$userStats || $userStats->valid_invite_count < 2) {
            $this->error('需要邀请2位好友完成实名认证才能申请财富金卡');
        }

        Db::startTrans();
        try {
            if (!$card) {
                $card = new WealthCard();
                $card->user_id = $user->id;
                $card->holder_name = $user->realname;
                $card->holder_idcard = $user->idcard;
            }

            $card->apply_status = 1; // 审核中
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
     * 完成流程步骤
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/wealthCard/completeStep)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤（1-8）")
     * @ApiReturn ({'code':'1','msg':'完成成功','data':{}})
     */
    public function completeStep()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 参数验证
        $params = $this->request->only(['step']);
        $this->validate($params, WealthCardValidate::class . '.completeStep');
        
        $step = $this->request->param('step/d', 0);

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

        // 检查是否已完成
        $flowLog = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('flow_step', $step)
            ->find();
        
        if ($flowLog && $flowLog->is_completed) {
            $this->error('该步骤已完成');
        }

        // 如果需要收费，检查是否已支付
        if ($flowConfig->need_fee && (!$flowLog || !$flowLog->is_pay_fee)) {
            $this->error('请先支付费用');
        }
        
        Db::startTrans();
        try {
            // 创建或更新流程记录
            if (!$flowLog) {
                $flowLog = new CardFlowLog();
                $flowLog->user_id = $user->id;
                $flowLog->card_id = $card->id;
                $flowLog->flow_step = $step;
                $flowLog->step_name = $flowConfig->step_name;
            }
            
            $flowLog->is_completed = 1;
            $flowLog->complete_time = time();
            $flowLog->save();

            // 更新金卡流程状态
            if ($card->flow_status < $step) {
                $card->flow_status = $step;
                $card->save();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('完成失败，请稍后重试');
        }
        
        $this->success('完成成功');
    }

    /**
     * 支付流程费用
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/wealthCard/payFee)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤")
     * @ApiReturn ({'code':'1','msg':'支付成功','data':{}})
     */
    public function payFee()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 参数验证
        $params = $this->request->only(['step']);
        $this->validate($params, WealthCardValidate::class . '.payFee');
        
        $step = $this->request->param('step/d', 0);

        // 获取流程配置
        $flowConfig = CardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig || !$flowConfig->need_fee) {
            $this->error('该步骤无需支付费用');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 检查是否已支付
        $flowLog = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('flow_step', $step)
            ->find();
        
        if ($flowLog && $flowLog->is_pay_fee) {
            $this->error('费用已支付');
        }

        Db::startTrans();
        try {

            // 创建或更新流程记录
            if (!$flowLog) {
                $flowLog = new CardFlowLog();
                $flowLog->user_id = $user->id;
                $flowLog->card_id = $card->id;
                $flowLog->flow_step = $step;
                $flowLog->step_name = $flowConfig->step_name;
            }
            
            $flowLog->need_fee = 1;
            $flowLog->fee_amount = $flowConfig->fee_amount;
            $flowLog->fee_name = $flowConfig->fee_name;
            $flowLog->is_pay_fee = 1;
            $flowLog->pay_fee_time = time();
            $flowLog->pay_trade_no = 'WC' . time() . rand(1000, 9999);
            $flowLog->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('支付失败，请稍后重试');
        }
        
        $this->success('支付成功');
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
            $card->flow_status = 0;
            $card->apply_status = 0;
            $card->save();
        }

        // 实时检查申领条件
        $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
        $validInviteCount = $userStats ? intval($userStats->valid_invite_count) : 0;
        
        // 条件1：铂金会员（会员等级 >= 1）
        $isMember = intval($user->member_level) >= 1;
        
        // 条件2：实名认证
        $isRealname = intval($user->is_realname) === 1;
        
        // 条件3：邀请2位好友完成实名认证
        $hasInvite = $validInviteCount >= 2;
        
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
                'value' => $user->member_level
            ],
            [
                'name' => '实名认证',
                'desc' => '需要完成实名认证',
                'completed' => $isRealname,
                'required' => true,
                'value' => $user->is_realname,
                'realname' => $user->realname ?: ''
            ],
            [
                'name' => '邀请好友',
                'desc' => sprintf('需要邀请2位好友完成实名认证（当前：%d/2）', $validInviteCount),
                'completed' => $hasInvite,
                'required' => true,
                'current' => $validInviteCount,
                'target' => 2
            ],
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
        $canApply = $isMember && $isRealname && $hasInvite && $hasAddress;

        // 获取流程配置
        $flowConfigs = CardFlowConfig::where('status', 'normal')
            ->order('step asc')
            ->select();

        // 获取用户流程记录
        $flowLogs = CardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->select();
        
        $flowLogMap = [];
        foreach ($flowLogs as $log) {
            $flowLogMap[$log->flow_step] = $log;
        }

        // 组装流程信息
        $steps = [];
        foreach ($flowConfigs as $config) {
            $log = isset($flowLogMap[$config->step]) ? $flowLogMap[$config->step] : null;
            
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
                'flow_status' => $log ? $log->flow_status : 1,  // 1=未支付
                'order_id' => $log ? $log->order_id : null,
                'extra_data' => $log && $log->extra_data ? json_decode($log->extra_data, true) : null,
            ];
        }

        $this->success('获取成功', [
            'card_id' => $card->id,
            'card_status' => [
                'flow_status' => $card->flow_status,
                'apply_status' => $card->apply_status,
            ],
            'steps' => $steps,
            'total_amount' => array_sum(array_column($steps, 'fee_amount')),
            'apply_conditions' => $applyConditions,
            'can_apply' => $canApply,
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

        // 检查金卡审核状态
        if ($card->apply_status != 2) {
            $this->error('金卡尚未审核通过，无法支付');
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

        // 检查前置步骤是否已完成（步骤必须按顺序完成）
        if ($step > 1) {
            $prevStep = $step - 1;
            $prevLog = CardFlowLog::where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->where('flow_step', $prevStep)
                ->find();
            
            if (!$prevLog || $prevLog->flow_status != 3) {
                $this->error('请先完成上一步骤');
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
                Log::info('订单已超时', [
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
            
            Log::info('创建金卡支付订单', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_no' => $orderNo,
                'step' => $step,
                'amount' => $flowConfig->fee_amount
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建订单失败', [
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

            Log::info('获取支付参数', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'pay_type' => $payType
            ]);

            $this->success('获取成功', $paymentParams);
            
        } catch (\Exception $e) {
            Log::error('获取支付参数失败', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 查询支付结果
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/card/paymentResult)
     * @ApiParams (name="order_id", type="integer", required=true, description="订单ID")
     * @ApiReturn ({'code':'1','msg':'查询成功','data':{'pay_status':0,'order':{}}})
     */
    public function paymentResult()
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

        $this->success('查询成功', [
            'pay_status' => $order->pay_status,
            'order' => $order
        ]);
    }

    /**
     * 完成流程步骤（新版，支持A类步骤数据提交）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/card/completeStepV2)
     * @ApiParams (name="step", type="integer", required=true, description="流程步骤（1-9）")
     * @ApiParams (name="extra_data", type="object", required=false, description="额外数据（A类步骤需要）")
     * @ApiReturn ({'code':'1','msg':'提交成功','data':{}})
     */
    public function completeStepV2()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $step = $this->request->param('step/d', 0);
        $extraData = $this->request->param('extra_data', null);

        if ($step < 1 || $step > 9) {
            $this->error('无效的步骤编号');
        }

        // 获取金卡信息
        $card = WealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 检查金卡审核状态
        if ($card->apply_status != 2) {
            $this->error('金卡尚未审核通过');
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

        // A类步骤需要提交额外数据
        if ($flowConfig->step_type == 'A') {
            if (!$extraData || !is_array($extraData)) {
                $this->error('请提交必要的数据');
            }

            // 验证额外数据
            try {
                $this->validateExtraData($step, $extraData);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
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

            // 保存额外数据（JSON格式）
            if ($extraData) {
                $flowLog->extra_data = json_encode($extraData, JSON_UNESCAPED_UNICODE);
            }

            // 更新状态为待审核
            $flowLog->flow_status = 2; // 已支付待审核
            $flowLog->save();

            // 如果是步骤1（协议签署），创建协议流程记录
            if ($step == 1 && $flowLog->flow_status == 2) {
                $this->createAgreementFlowRecords($user->id, $step);
            }

            Db::commit();
            
            Log::info('提交流程步骤', [
                'user_id' => $user->id,
                'step' => $step,
                'flow_log_id' => $flowLog->id,
                'extra_data' => $extraData
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('提交步骤失败', [
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('提交失败，请稍后重试');
        }
        
        // success 必须在 try-catch 外面，否则抛出的异常会被catch捕获
        $this->success('提交成功，等待审核');
    }

    /**
     * 验证额外数据
     * @throws \Exception
     */
    private function validateExtraData($step, $extraData)
    {
        switch ($step) {
            case 1: // 协议签署
                if (!isset($extraData['agreement_signed']) || !$extraData['agreement_signed']) {
                    throw new \Exception('请确认已签署协议');
                }
                break;
            
            case 3: // 设置卡片密码
                if (!isset($extraData['card_password'])) {
                    throw new \Exception('请设置卡片密码');
                }
                $password = trim($extraData['card_password']);
                if (strlen($password) != 6 || !ctype_digit($password)) {
                    throw new \Exception('密码必须为6位数字');
                }
                break;
            
            case 4: // 大额收付款功能
                if (!isset($extraData['payment_limit'])) {
                    throw new \Exception('请设置支付限额');
                }
                $limit = floatval($extraData['payment_limit']);
                if ($limit <= 0 || $limit > 1000000) {
                    throw new \Exception('支付限额必须在0-100万之间');
                }
                break;
        }
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
}

