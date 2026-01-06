<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use app\common\model\CusUserWalletLog;
use app\common\model\fuka\FukaWealthCard;
use app\common\model\fuka\FukaCardFlowConfig;
use app\common\model\fuka\FukaCardFlowLog;
use app\common\validate\fuka\WealthCard as WealthCardValidate;
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
            $this->error('需要成为铂金会员才能领取财富金卡');
        }

        // 获取或创建金卡记录
        $card = FukaWealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            // 创建金卡记录
            $card = new FukaWealthCard();
            $card->user_id = $user->id;
            $card->holder_name = $user->realname;
            $card->holder_idcard = $user->idcard;
            $card->flow_status = 0;
            $card->apply_status = 0;
            $card->save();
        }

        // 获取流程配置
        $flowConfig = FukaCardFlowConfig::where('status', 'normal')
            ->order('sort_order asc')
            ->select();

        // 获取流程完成记录
        $flowLogs = FukaCardFlowLog::where('user_id', $user->id)
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
        $card = FukaWealthCard::where('user_id', $user->id)->find();
        if ($card && $card->apply_status > 0) {
            $this->error('您已申请过财富金卡');
        }

        // 检查邀请人数（需要2位有效邀请）
        $userStats = \app\common\model\fuka\FukaUserStatistics::where('user_id', $user->id)->find();
        if (!$userStats || $userStats->valid_invite_count < 2) {
            $this->error('需要邀请2位好友完成实名认证才能申请财富金卡');
        }

        Db::startTrans();
        try {
            if (!$card) {
                $card = new FukaWealthCard();
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
        $card = FukaWealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 获取流程配置
        $flowConfig = FukaCardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig) {
            $this->error('流程配置不存在');
        }

        // 检查是否已完成
        $flowLog = FukaCardFlowLog::where('user_id', $user->id)
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
                $flowLog = new FukaCardFlowLog();
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
        $flowConfig = FukaCardFlowConfig::where('step', $step)
            ->where('status', 'normal')
            ->find();
        
        if (!$flowConfig || !$flowConfig->need_fee) {
            $this->error('该步骤无需支付费用');
        }

        // 获取金卡信息
        $card = FukaWealthCard::where('user_id', $user->id)->find();
        if (!$card) {
            $this->error('请先申请财富金卡');
        }

        // 检查是否已支付
        $flowLog = FukaCardFlowLog::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('flow_step', $step)
            ->find();
        
        if ($flowLog && $flowLog->is_pay_fee) {
            $this->error('费用已支付');
        }

        // 检查余额
        if ($user->money < $flowConfig->fee_amount) {
            $this->error('余额不足');
        }

        Db::startTrans();
        try {
            // 扣减余额
            $beforeMoney = $user->money;
            $afterMoney = $beforeMoney - $flowConfig->fee_amount;
            User::money(-$flowConfig->fee_amount, $user->id, '财富金卡流程费用：' . $flowConfig->fee_name);

            // 记录钱包日志
            $walletLog = new CusUserWalletLog();
            $walletLog->user_id = $user->id;
            $walletLog->type = 'money';
            $walletLog->event = 'wealth_card_fee';
            $walletLog->change_money = -$flowConfig->fee_amount;
            $walletLog->before_money = $beforeMoney;
            $walletLog->after_money = $afterMoney;
            $walletLog->oper_type = 'fuka_wealth_card_fee';
            $walletLog->oper_id = $flowLog->id;
            $walletLog->memo = '财富金卡流程费用：' . $flowConfig->fee_name;
            $walletLog->save();

            // 创建或更新流程记录
            if (!$flowLog) {
                $flowLog = new FukaCardFlowLog();
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
}

