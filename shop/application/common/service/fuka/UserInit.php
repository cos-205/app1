<?php

namespace app\common\service\fuka;

use app\admin\model\cus\user\User as UserModel;
use app\admin\model\cus\Share as ShareModel;
use app\common\model\fuka\UserStatistics as UserStatisticsModel;
use app\common\model\fuka\ChanceLog as ChanceLogModel;
use think\Db;

/**
 * 福卡系统 - 用户初始化服务
 */
class UserInit
{
    /**
     * @var UserModel 用户模型
     */
    protected $user;

    /**
     * 构造函数
     * @param UserModel $user 用户对象
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * 生成邀请码
     * @return string 邀请码
     */
    public function generateInviteCode()
    {
        // 如果已有邀请码，则不重新生成
        if (!empty($this->user->invite_code)) {
            return $this->user->invite_code;
        }

        // 生成格式：INV + 用户ID（补零到6位）
        $inviteCode = 1100000 + $this->user->id;

        // 检查是否重复（理论上不会重复，但做个保险）
        $exists = UserModel::where('invite_code', $inviteCode)
            ->where('id', '<>', $this->user->id)
            ->find();

        if ($exists) {
            // 如果重复，添加随机数
            $inviteCode .= mt_rand(100, 999);
        }

        // 更新用户表
        $this->user->invite_code = $inviteCode;
        $this->user->save();

        return $inviteCode;
    }

    /**
     * 创建用户统计记录
     * @return UserStatisticsModel
     */
    public function createUserStatistics()
    {
        // 检查是否已存在
        $exists = UserStatisticsModel::where('user_id', $this->user->id)->find();
        if ($exists) {
            return $exists;
        }

        // 确定团队ID和队长ID
        $teamId = 0;
        $isTeamLeader = 0;

        if ($this->user->parent_user_id) {
            // 有推荐人，找到推荐人的团队
            $parentUser = UserModel::get($this->user->parent_user_id);
            if ($parentUser) {
                $parentStats = UserStatisticsModel::where('user_id', $parentUser->id)->find();
                if ($parentStats) {
                    // 继承推荐人的团队
                    $teamId = $parentStats->team_id ?: $parentUser->id;
                }
            }
        } else {
            // 没有推荐人，自己作为队长
            $teamId = $this->user->id;
            $isTeamLeader = 1;
        }

        // 创建统计记录
        $statistics = new UserStatisticsModel();
        $statistics->user_id = $this->user->id;
        $statistics->team_id = $teamId;
        $statistics->is_team_leader = $isTeamLeader;
        $statistics->total_invite_count = 0;
        $statistics->valid_invite_count = 0;
        $statistics->total_fuka_count = 0;
        $statistics->current_fuka_count = 0;
        $statistics->fuka_chance = 0; // 初始集福机会为0，签到后获得
        $statistics->dividend_money = 0;
        $statistics->total_dividend = 0;
        $statistics->last_update_time = time();
        $statistics->createtime = time();
        $statistics->updatetime = time();
        $statistics->status = 'normal';
        $statistics->save();

        return $statistics;
    }

    /**
     * 更新推荐人的邀请统计（用户注册时调用）
     */
    public function updateInviterInviteCount()
    {
        if (!$this->user->parent_user_id) {
            return;
        }

        // 获取推荐人
        $inviter = UserModel::get($this->user->parent_user_id);
        if (!$inviter) {
            return;
        }

        // 更新推荐人的总邀请人数
        $inviterStats = UserStatisticsModel::where('user_id', $inviter->id)->find();
        if ($inviterStats) {
            // 统计推荐人的直接下级数量（通过parent_user_id）
            $totalInviteCount = UserModel::where('parent_user_id', $inviter->id)
                ->where('status', 'normal')
                ->count();
            
            $inviterStats->total_invite_count = $totalInviteCount;
            $inviterStats->last_update_time = time();
            $inviterStats->updatetime = time();
            $inviterStats->save();
        }
    }

    /**
     * 更新推荐人的有效邀请人数和会员等级（用户完成实名认证后调用）
     */
    public function updateInviterStatisticsAfterRealname()
    {
        if (!$this->user->is_realname || !$this->user->parent_user_id) {
            return;
        }

        // 获取推荐人
        $inviter = UserModel::get($this->user->parent_user_id);
        if (!$inviter) {
            return;
        }

        // 更新推荐人的有效邀请人数（团队内所有已实名认证的用户，包括多级）
        $inviterStats = UserStatisticsModel::where('user_id', $inviter->id)->find();
        if ($inviterStats) {
            // 统计团队内所有已实名认证的用户数量（包括多级）
            $validInviteCount = $this->getTeamRealnameCount($inviter->id);

            $oldValidInviteCount = $inviterStats->valid_invite_count;
            $inviterStats->valid_invite_count = $validInviteCount;
            $inviterStats->last_update_time = time();
            $inviterStats->updatetime = time();
            $inviterStats->save();

            // 根据有效邀请人数更新会员等级
            $this->updateMemberLevel($inviter, $validInviteCount);

            // 发放集福机会奖励
            $this->grantInviteReward($inviter, $inviterStats, $oldValidInviteCount, $validInviteCount);
        }
    }

    /**
     * 发放邀请奖励（集福机会）
     * 
     * @param UserModel $inviter 邀请人
     * @param UserStatisticsModel $inviterStats 邀请人统计
     * @param int $oldValidInviteCount 旧的有效邀请人数
     * @param int $newValidInviteCount 新的有效邀请人数
     */
    protected function grantInviteReward($inviter, $inviterStats, $oldValidInviteCount, $newValidInviteCount)
    {
        Db::startTrans();
        try {
            // 1. 每邀请1位好友注册并实名认证，获得1次集福机会
            $chanceToAdd = 0;
            $remark = '';

            // 检查是否是新增加的实名认证用户
            if ($newValidInviteCount > $oldValidInviteCount) {
                $chanceToAdd = 1; // 每邀请1位好友实名认证，获得1次集福机会
                $remark = '邀请好友完成实名认证获得';
            }

            // 2. 每邀请3位好友，额外获得1次集福机会（团队推广）
            // 计算新增的3的倍数
            $oldGroups = intval($oldValidInviteCount / 3); // 旧的3人组数
            $newGroups = intval($newValidInviteCount / 3); // 新的3人组数
            
            if ($newGroups > $oldGroups) {
                $teamRewardCount = $newGroups - $oldGroups; // 新增的3人组数
                $chanceToAdd += $teamRewardCount; // 每3人额外获得1次集福机会
                if ($teamRewardCount > 0) {
                    $remark .= ($remark ? '，' : '') . "团队推广奖励（每邀请3位好友额外获得1次）";
                }
            }

            // 发放集福机会
            if ($chanceToAdd > 0) {
                $beforeCount = $inviterStats->fuka_chance;
                $inviterStats->fuka_chance += $chanceToAdd;
                $inviterStats->save();

                // 记录集福机会日志
                $chanceLog = new ChanceLogModel();
                $chanceLog->user_id = $inviter->id;
                $chanceLog->change_type = 1; // 获得
                $chanceLog->change_count = $chanceToAdd;
                $chanceLog->before_count = $beforeCount;
                $chanceLog->after_count = $inviterStats->fuka_chance;
                $chanceLog->source_type = 2; // 邀请
                $chanceLog->source_id = $this->user->id; // 被邀请人ID
                $chanceLog->remark = $remark;
                $chanceLog->createtime = time();
                $chanceLog->updatetime = time();
                $chanceLog->status = 'normal';
                $chanceLog->save();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            // 记录错误信息（不使用\think\Log）
        }
    }

    /**
     * 更新会员等级
     * @param UserModel $user 用户
     * @param int $validInviteCount 有效邀请人数
     */
    protected function updateMemberLevel($user, $validInviteCount)
    {
        // 从会员等级表中读取升级条件（不写死）
        $memberLevels = \app\common\model\fuka\MemberLevel::where('status', 'normal')
            ->order('invite_count', 'desc')  // 按邀请人数降序排序（邀请人数多的在前）
            ->order('level', 'desc')         // 同邀请人数时，按等级降序
            ->select();

        // select() 返回数组，使用 empty() 检查
        if (empty($memberLevels)) {
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

            // 记录等级提升日志
            $this->logMemberLevelUpgrade($user, $oldLevel, $newLevel, $newLevelName, $validInviteCount);
            
            // 升级后创建分红记录（如果有分红权益）
            $this->createDividendRecordAfterUpgrade($user, $newLevel);
        }
    }

    /**
     * 记录会员等级提升日志
     * @param UserModel $user 用户对象
     * @param int $oldLevel 旧等级
     * @param int $newLevel 新等级
     * @param string $newLevelName 新等级名称
     * @param int $validInviteCount 有效邀请人数
     */
    protected function logMemberLevelUpgrade($user, $oldLevel, $newLevel, $newLevelName, $validInviteCount)
    {
        try {
            // 写入会员等级日志表
            $log = new \app\admin\model\fuka\MemberLevelLog();
            $log->user_id = $user->id;
            $log->old_level = $oldLevel;
            $log->new_level = $newLevel;
            $log->invite_count = $validInviteCount;
            $log->createtime = time();
            $log->updatetime = time();
            $log->status = 'normal';
            $log->save();
            
            // 记录系统日志
            if (function_exists('output_log')) {
                output_log('info', [
                    'title' => '[会员系统] 会员等级自动提升',
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                    'old_level' => $oldLevel,
                    'new_level' => $newLevel,
                    'new_level_name' => $newLevelName,
                    'valid_invite_count' => $validInviteCount
                ]);
            }
        } catch (\Exception $e) {
            // 记录错误但不影响主流程
            if (function_exists('output_log')) {
                output_log('error', [
                    'title' => '[会员系统] 记录等级提升日志失败',
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 升级后创建分红记录
     * @param UserModel $user 用户对象
     * @param int $newLevel 新等级
     */
    protected function createDividendRecordAfterUpgrade($user, $newLevel)
    {
        try {
            // 获取新等级的配置
            $levelConfig = \app\common\model\fuka\MemberLevel::where('level', $newLevel)
                ->where('status', 'normal')
                ->find();
            
            if (!$levelConfig) {
                return;
            }
            
            // 检查是否有分红权益
            $dividendMoney = floatval($levelConfig->dividend_money);
            if ($dividendMoney <= 0) {
                return; // 没有分红权益，不需要创建记录
            }
            
            // 获取当前月份（YYYY-MM格式）
            $currentMonth = date('Y-m');
            
            // 检查是否已存在该月份的分红记录
            $exists = \app\common\model\fuka\DividendRecord::where('user_id', $user->id)
                ->where('dividend_month', $currentMonth)
                ->where('status', 'normal')
                ->find();
            
            if ($exists) {
                // 如果已存在，更新金额和等级（可能用户升级了）
                $exists->member_level = $newLevel;
                $exists->dividend_money = $dividendMoney;
                $exists->updatetime = time();
                $exists->save();
                return;
            }
            
            // 创建新的分红记录
            $dividendRecord = new \app\common\model\fuka\DividendRecord();
            $dividendRecord->user_id = $user->id;
            $dividendRecord->member_level = $newLevel;
            $dividendRecord->dividend_month = $currentMonth;
            $dividendRecord->dividend_money = $dividendMoney;
            $dividendRecord->send_status = 0; // 待发放
            $dividendRecord->send_channel = 'alipay'; // 支付宝
            $dividendRecord->createtime = time();
            $dividendRecord->updatetime = time();
            $dividendRecord->status = 'normal';
            $dividendRecord->save();
            
            // 更新用户统计表中的分红余额
            $userStats = UserStatisticsModel::where('user_id', $user->id)->find();
            if ($userStats) {
                $userStats->dividend_money += $dividendMoney;
                $userStats->updatetime = time();
                $userStats->save();
            }
            
            // 记录日志
            if (function_exists('output_log')) {
                output_log('info', [
                    'title' => '[分红系统] 升级后创建分红记录',
                    'user_id' => $user->id,
                    'member_level' => $newLevel,
                    'dividend_money' => $dividendMoney,
                    'dividend_month' => $currentMonth
                ]);
            }
        } catch (\Exception $e) {
            // 记录错误但不影响主流程
            if (function_exists('output_log')) {
                output_log('error', [
                    'title' => '[分红系统] 创建分红记录失败',
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 生成推荐关系表
     */
    public function createShareRelation()
    {
        if (!$this->user->parent_user_id) {
            return;
        }
        
        // 检查是否已存在
        $exists = ShareModel::where('user_id', $this->user->id)
            ->where('share_id', $this->user->parent_user_id)
            ->find();
        
        if ($exists) {
            return;
        }
        
        $share = new ShareModel();
        $share->user_id = $this->user->id;
        $share->share_id = $this->user->parent_user_id;
        
        // 查询父级ID树和层级
        $parentShare = ShareModel::where('user_id', $this->user->parent_user_id)->find();
        
        if ($parentShare) {
            // 父级有推荐关系，继承其parent_ids并添加父级ID
            $share->parent_ids = $parentShare->parent_ids . ',' . $this->user->parent_user_id;
        } else {
            // 父级是根节点
            $share->parent_ids = $this->user->parent_user_id;
        }
        
        $share->createtime = time();
        $share->updatetime = time();
        $share->save();
    }

    /**
     * 获取团队内所有已实名认证的用户数量（包括多级）
     * @param int $userId 用户ID
     * @return int
     */
    protected function getTeamRealnameCount($userId)
    {
        // 获取1级成员（直接邀请）
        $level1Users = UserModel::where('parent_user_id', $userId)
            ->where('status', 'normal')
            ->column('id');
        
        // 获取2级成员（1级成员邀请的）
        $level2Users = [];
        if (!empty($level1Users)) {
            $level2Users = UserModel::where('parent_user_id', 'in', $level1Users)
                ->where('status', 'normal')
                ->column('id');
        }
        
        // 获取3级成员（2级成员邀请的）
        $level3Users = [];
        if (!empty($level2Users)) {
            $level3Users = UserModel::where('parent_user_id', 'in', $level2Users)
                ->where('status', 'normal')
                ->column('id');
        }
        
        // 合并所有层级的用户ID
        $allTeamUserIds = array_merge($level1Users, $level2Users, $level3Users);
        
        if (empty($allTeamUserIds)) {
            return 0;
        }
        
        // 统计所有团队内已实名认证的用户数量
        $count = UserModel::where('id', 'in', $allTeamUserIds)
            ->where('is_realname', 1)
            ->where('status', 'normal')
            ->count();
        
        return $count;
    }
}

