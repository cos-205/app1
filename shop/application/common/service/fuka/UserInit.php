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

        // 更新推荐人的有效邀请人数（已实名的直接下级）
        $inviterStats = UserStatisticsModel::where('user_id', $inviter->id)->find();
        if ($inviterStats) {
            $validInviteCount = UserModel::where('parent_user_id', $inviter->id)
                ->where('is_realname', 1)
                ->where('status', 'normal')
                ->count();

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
        // 会员等级规则：
        // 0=普通
        // 1=铂金会员（邀请2位实名认证用户）
        // 2=黄金会员（邀请5位实名认证用户）
        // 3=钻石会员（邀请10位实名认证用户）
        // 4=黑金会员（邀请20位实名认证用户）
        // 5=至尊会员（邀请50位实名认证用户）

        $newLevel = 0;
        if ($validInviteCount >= 50) {
            $newLevel = 5; // 至尊会员
        } elseif ($validInviteCount >= 20) {
            $newLevel = 4; // 黑金会员
        } elseif ($validInviteCount >= 10) {
            $newLevel = 3; // 钻石会员
        } elseif ($validInviteCount >= 5) {
            $newLevel = 2; // 黄金会员
        } elseif ($validInviteCount >= 2) {
            $newLevel = 1; // 铂金会员
        }

        // 只在等级提升时更新
        if ($newLevel > $user->member_level) {
            $oldLevel = $user->member_level;
            $user->member_level = $newLevel;
            $user->save();
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
}

