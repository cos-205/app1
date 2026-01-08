<?php

namespace app\common\service\fuka;

use app\common\model\User;
use app\common\model\fuka\UserStatistics;
use app\common\model\fuka\Rank;
use think\Db;

/**
 * 福卡排行榜更新服务
 */
class RankUpdate
{
    /**
     * 更新用户排行榜数据
     * 
     * @param int $userId 用户ID
     * @return bool
     */
    public static function updateUserRank($userId)
    {
        try {
            // 获取用户信息
            $user = User::get($userId);
            if (!$user) {
                return false;
            }

            // 获取用户统计信息
            $userStats = UserStatistics::where('user_id', $userId)->find();
            if (!$userStats) {
                return false;
            }

            // 获取用户当前福卡数量（未使用的）
            $fukaCount = \app\common\model\fuka\UserCard::where('user_id', $userId)
                ->where('is_used', 0)
                ->where('status', 'normal')
                ->count();

            // 获取或创建排行榜记录
            $rank = Rank::where('user_id', $userId)->find();
            if (!$rank) {
                $rank = new Rank();
                $rank->user_id = $userId;
            }

            // 更新排行榜数据
            $rank->nickname = $user->nickname ?: $user->mobile;
            $rank->avatar = $user->avatar ?: '';
            $rank->fuka_count = $fukaCount;
            $rank->update_time = time();
            $rank->status = 'normal';
            $rank->save();

            // 重新计算所有用户排名
            self::recalculateRanks();

            return true;
        } catch (\Exception $e) {
            // 记录错误信息（不使用\think\Log）
            // 错误信息已通过异常捕获，直接返回false
            return false;
        }
    }

    /**
     * 重新计算所有用户排名
     */
    public static function recalculateRanks()
    {
        try {
            // 获取所有排行榜记录，按福卡数量降序排序
            $ranks = Rank::where('status', 'normal')
                ->order('fuka_count desc, update_time asc')
                ->select();

            // 更新排名
            $rankNumber = 1;
            foreach ($ranks as $rank) {
                $rank->rank = $rankNumber;
                $rank->save();
                $rankNumber++;
            }

            // 只保留前100名（可选，避免排行榜过大）
            Rank::where('status', 'normal')
                ->where('rank', '>', 100)
                ->update(['status' => 'hidden']);

            return true;
        } catch (\Exception $e) {
            // 记录错误信息（不使用\think\Log）
            return false;
        }
    }

    /**
     * 批量更新排行榜（定时任务使用）
     */
    public static function batchUpdateRanks()
    {
        try {
            // 获取所有有福卡的用户统计记录
            $userStats = UserStatistics::where('current_fuka_count', '>', 0)
                ->select();

            foreach ($userStats as $stat) {
                self::updateUserRank($stat->user_id);
            }

            return true;
        } catch (\Exception $e) {
            // 记录错误信息（不使用\think\Log）
            return false;
        }
    }
}

