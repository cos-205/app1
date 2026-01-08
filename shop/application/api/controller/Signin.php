<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use app\common\model\CusActivitySignin;
use app\common\model\fuka\SigninRewardRule;
use app\common\model\fuka\SigninRewardLog;
use app\common\model\fuka\UserStatistics;
use app\common\model\fuka\ChanceLog;
use app\common\model\fuka\WealthCard;
use app\common\model\fuka\CardBalanceLog;
use think\Db;

/**
 * 签到接口
 * 
 * @ApiTitle    (签到系统)
 * @ApiSummary  (签到相关接口)
 * @ApiSector   (签到)
 */
class Signin extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    /**
     * 获取签到信息
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/signin/getInfo)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{}})
     */
    public function getInfo()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取集福机会
        $userStats = UserStatistics::where('user_id', $user->id)->find();
        $fukaChance = $userStats ? $userStats->fuka_chance : 0;

        // 获取金卡余额
        $wealthCard = WealthCard::where('user_id', $user->id)
            ->where('status', 'normal')
            ->find();
        $cardBalance = $wealthCard ? $wealthCard->card_balance : 0;

        // 获取最近7天签到记录
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $signinDates = CusActivitySignin::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->order('date asc')
            ->column('date');

        $this->success('获取成功', [
            'signin_days' => $user->signin_days ?: 0,
            'fuka_chance' => $fukaChance,
            'card_balance' => $cardBalance,
            'last_signin_date' => $user->last_signin_date ?: '',
            'signin_dates' => $signinDates
        ]);
    }

    /**
     * 每日签到
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/signin/doSignin)
     * @ApiReturn ({'code':'1','msg':'签到成功','data':{'signin_days':0,'reward':{}}})
     */
    public function doSignin()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 检查今日是否已签到
        $today = date('Y-m-d');
        if ($user->last_signin_date == $today) {
            $this->error('今日已签到');
        }

        $signinDays = 0;
        $fukaChance = 0;
        $reward = null;
        
        Db::startTrans();
        try {
            // 计算连续签到天数
            $signinDays = $this->calculateSigninDays($user, $today);
            
            // 更新用户签到信息
            $user->signin_days = $signinDays;
            $user->last_signin_date = $today;
            $user->save();

            // 增加集福机会
            $userStats = UserStatistics::where('user_id', $user->id)->find();
            if (!$userStats) {
                $userStats = new UserStatistics();
                $userStats->user_id = $user->id;
                $userStats->fuka_chance = 0;
            }
            $userStats->fuka_chance += 1;
            $userStats->save();
            $fukaChance = $userStats->fuka_chance;

            // 记录集福机会日志
            $chanceLog = new ChanceLog();
            $chanceLog->user_id = $user->id;
            $chanceLog->change_type = 1; // 获得
            $chanceLog->change_count = 1;
            $chanceLog->before_count = $userStats->fuka_chance - 1;
            $chanceLog->after_count = $userStats->fuka_chance;
            $chanceLog->source_type = 1; // 签到
            $chanceLog->remark = '每日签到获得';
            $chanceLog->save();

            // 检查是否有签到奖励
            $rewardRule = SigninRewardRule::where('days', $signinDays)
                ->where('status', 'normal')
                ->find();
            
            if ($rewardRule) {
                // 检查是否已领取
                $rewardLog = SigninRewardLog::where('user_id', $user->id)
                    ->where('rule_id', $rewardRule->id)
                    ->where('days', $signinDays)
                    ->find();
                
                if (!$rewardLog) {
                    // 创建奖励记录（待领取）
                    $rewardLog = new SigninRewardLog();
                    $rewardLog->user_id = $user->id;
                    $rewardLog->rule_id = $rewardRule->id;
                    $rewardLog->days = $signinDays;
                    $rewardLog->reward_type = $rewardRule->reward_type;
                    $rewardLog->reward_money = $rewardRule->reward_money;
                    $rewardLog->reward_chance = $rewardRule->reward_chance;
                    $rewardLog->is_received = 0;
                    $rewardLog->save();
                    
                    $reward = [
                        'rule_id' => $rewardRule->id,
                        'days' => $signinDays,
                        'reward_type' => $rewardRule->reward_type,
                        'reward_money' => $rewardRule->reward_money,
                        'reward_chance' => $rewardRule->reward_chance,
                        'description' => $rewardRule->description,
                        'is_received' => 0
                    ];
                }
            }

            // 记录签到（使用现有表）
            $signin = new CusActivitySignin();
            $signin->user_id = $user->id;
            $signin->date = $today;
            $signin->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('签到失败：' . $e->getMessage());
        }
        
        $this->success('签到成功', [
            'signin_days' => $signinDays,
            'fuka_chance' => $fukaChance,
            'reward' => $reward
        ]);
    }

    /**
     * 计算连续签到天数
     * 
     * @param User $user
     * @param string $today
     * @return int
     */
    private function calculateSigninDays($user, $today)
    {
        $lastSigninDate = $user->last_signin_date;
        
        if (!$lastSigninDate) {
            return 1; // 首次签到
        }

        $lastDate = strtotime($lastSigninDate);
        $todayDate = strtotime($today);
        $diffDays = ($todayDate - $lastDate) / 86400;

        if ($diffDays == 1) {
            // 连续签到
            return $user->signin_days + 1;
        } else {
            // 中断，重新开始
            return 1;
        }
    }

    /**
     * 领取签到奖励
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/signin/receiveReward)
     * @ApiParams (name="rule_id", type="integer", required=true, description="奖励规则ID")
     * @ApiReturn ({'code':'1','msg':'领取成功','data':{}})
     */
    public function receiveReward()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }
        
        $ruleId = $this->request->param('rule_id/d', 0);
        if (!$ruleId) {
            $this->error('请提供奖励规则ID');
        }

        // 获取奖励记录
        $rewardLog = SigninRewardLog::where('user_id', $user->id)
            ->where('rule_id', $ruleId)
            ->where('is_received', 0)
            ->find();
        
        if (!$rewardLog) {
            $this->error('奖励不存在或已领取');
        }

        // 获取奖励规则
        $rewardRule = SigninRewardRule::get($ruleId);
        if (!$rewardRule) {
            $this->error('奖励规则不存在');
        }

        Db::startTrans();
        try {
            // 发放奖励
            if ($rewardRule->reward_type == 1) {
                // 现金奖励 - 发放到金卡账户余额
                $money = $rewardRule->reward_money * 10000; // 转换为元
                
                // 获取用户的财富金卡
                $wealthCard = WealthCard::where('user_id', $user->id)
                    ->where('status', 'normal')
                    ->find();
                
                if (!$wealthCard) {
                    Db::rollback();
                    $this->error('请先申领财富金卡');
                }
                
                // 更新金卡余额
                $beforeBalance = $wealthCard->card_balance;
                $wealthCard->card_balance = bcadd($wealthCard->card_balance, $money, 2);
                $wealthCard->save();
                
                // 记录金卡余额变动日志
                $balanceLog = new CardBalanceLog();
                $balanceLog->user_id = $user->id;
                $balanceLog->card_id = $wealthCard->id;
                $balanceLog->change_type = 1; // 增加
                $balanceLog->change_money = $money;
                $balanceLog->before_balance = $beforeBalance;
                $balanceLog->after_balance = $wealthCard->card_balance;
                $balanceLog->source_type = 'signin_reward';
                $balanceLog->source_id = $rewardLog->id;
                $balanceLog->remark = '签到奖励：' . $rewardRule->description;
                $balanceLog->save();
            } elseif ($rewardRule->reward_type == 2) {
                // 集福机会奖励
                $userStats = UserStatistics::where('user_id', $user->id)->find();
                if (!$userStats) {
                    $userStats = new UserStatistics();
                    $userStats->user_id = $user->id;
                    $userStats->fuka_chance = 0;
                }
                $userStats->fuka_chance += $rewardRule->reward_chance;
                $userStats->save();

                // 记录日志
                $chanceLog = new ChanceLog();
                $chanceLog->user_id = $user->id;
                $chanceLog->change_type = 1;
                $chanceLog->change_count = $rewardRule->reward_chance;
                $chanceLog->before_count = $userStats->fuka_chance - $rewardRule->reward_chance;
                $chanceLog->after_count = $userStats->fuka_chance;
                $chanceLog->source_type = 1; // 签到
                $chanceLog->remark = $rewardRule->description;
                $chanceLog->save();
            }

            // 更新奖励记录
            $rewardLog->is_received = 1;
            $rewardLog->receive_time = time();
            $rewardLog->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('领取失败：' . $e->getMessage());
        }
        
        $this->success('领取成功');
    }

    /**
     * 获取签到奖励列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/signin/rewardList)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'signin_days':0}})
     */
    public function rewardList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取所有奖励规则
        $rules = SigninRewardRule::where('status', 'normal')
            ->order('days asc')
            ->select();

        // 获取用户已领取的奖励
        $receivedRewards = SigninRewardLog::where('user_id', $user->id)
            ->where('is_received', 1)
            ->column('rule_id');

        $list = [];
        foreach ($rules as $rule) {
            $item = [
                'id' => $rule->id,
                'days' => $rule->days,
                'reward_type' => $rule->reward_type,
                'reward_money' => $rule->reward_money,
                'reward_chance' => $rule->reward_chance,
                'description' => $rule->description,
                'is_received' => in_array($rule->id, $receivedRewards),
                'can_receive' => $user->signin_days >= $rule->days && !in_array($rule->id, $receivedRewards)
            ];
            $list[] = $item;
        }

        $this->success('获取成功', [
            'list' => $list,
            'signin_days' => $user->signin_days
        ]);
    }

    /**
     * 获取签到记录
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/signin/records)
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function records()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);

        // 获取签到记录
        $list = CusActivitySignin::where('user_id', $user->id)
            ->order('date desc')
            ->paginate($limit, false, ['page' => $page]);

        $records = [];
        foreach ($list as $item) {
            $records[] = [
                'id' => $item->id,
                'date' => $item->date,
                'reward' => '+1 集福机会'
            ];
        }

        $this->success('获取成功', [
            'list' => $records,
            'total' => $list->total(),
            'per_page' => $list->listRows(),
            'current_page' => $list->currentPage()
        ]);
    }
}

