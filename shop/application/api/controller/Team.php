<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use app\common\model\fuka\FukaUserStatistics;
use app\common\model\fuka\FukaUserInvite;
use app\common\model\fuka\FukaTeamRelation;
use app\common\model\fuka\FukaTeamReward;
use think\Db;

/**
 * 团队接口
 * 
 * @ApiTitle    (团队系统)
 * @ApiSummary  (团队相关接口)
 * @ApiSector   (团队)
 */
class Team extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    /**
     * 获取我的团队信息
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/team/myTeam)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'statistics':{},'members':[]}})
     */
    public function myTeam()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户统计信息
        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        
        // 动态计算层级统计
        $level1Count = $this->getLevelCount($user->id, 1);
        $level2Count = $this->getLevelCount($user->id, 2);
        $level3Count = $this->getLevelCount($user->id, 3);

        $statistics = [
            'total_invite_count' => $userStats ? $userStats->total_invite_count : 0,
            'valid_invite_count' => $userStats ? $userStats->valid_invite_count : 0,
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
            'level3_count' => $level3Count,
            'is_team_leader' => $userStats ? $userStats->is_team_leader : 0,
            'team_id' => $userStats ? $userStats->team_id : 0
        ];

        // 获取团队成员列表（1级）
        $members = User::where('parent_user_id', $user->id)
            ->where('status', 'normal')
            ->field('id,nickname,avatar,is_realname,createtime')
            ->order('createtime desc')
            ->limit(20)
            ->select();

        $this->success('获取成功', [
            'statistics' => $statistics,
            'members' => $members
        ]);
    }

    /**
     * 获取指定层级的会员数量
     * 
     * @param int $userId 用户ID
     * @param int $level 层级（1、2、3）
     * @return int
     */
    private function getLevelCount($userId, $level)
    {
        if ($level == 1) {
            // 1级会员（直接邀请）
            return User::where('parent_user_id', $userId)
                ->where('is_realname', 1)
                ->where('status', 'normal')
                ->count();
        } elseif ($level == 2) {
            // 2级会员（1级会员邀请的）
            return User::alias('u1')
                ->join('__USER__ u2', 'u1.parent_user_id = u2.id')
                ->where('u2.parent_user_id', $userId)
                ->where('u1.is_realname', 1)
                ->where('u1.status', 'normal')
                ->count();
        } elseif ($level == 3) {
            // 3级会员（2级会员邀请的）
            return User::alias('u1')
                ->join('__USER__ u2', 'u1.parent_user_id = u2.id')
                ->join('__USER__ u3', 'u2.parent_user_id = u3.id')
                ->where('u3.parent_user_id', $userId)
                ->where('u1.is_realname', 1)
                ->where('u1.status', 'normal')
                ->count();
        }
        
        return 0;
    }

    /**
     * 获取团队成员列表（分页）
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/team/memberList)
     * @ApiParams (name="level", type="integer", required=false, description="层级：1=1级,2=2级,3=3级，不传则返回所有")
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function memberList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $level = $this->request->param('level/d', 0);

        $query = null;
        
        if ($level == 1) {
            // 1级会员
            $query = User::where('parent_user_id', $user->id);
        } elseif ($level == 2) {
            // 2级会员
            $query = User::alias('u1')
                ->join('fa_user u2', 'u1.parent_user_id = u2.id')
                ->where('u2.parent_user_id', $user->id)
                ->field('u1.*');
        } elseif ($level == 3) {
            // 3级会员
            $query = User::alias('u1')
                ->join('fa_user u2', 'u1.parent_user_id = u2.id')
                ->join('fa_user u3', 'u2.parent_user_id = u3.id')
                ->where('u3.parent_user_id', $user->id)
                ->field('u1.*');
        } else {
            // 所有层级
            $this->error('请指定层级');
        }

        $list = $query->where('status', 'normal')
            ->field('id,nickname,avatar,is_realname,createtime')
            ->order('createtime desc')
            ->paginate();

        $this->success('获取成功', $list);
    }

    /**
     * 获取邀请记录列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/team/inviteList)
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function inviteList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $list = FukaUserInvite::where('inviter_id', $user->id)
            ->order('createtime desc')
            ->paginate();

        $this->success('获取成功', $list);
    }

    /**
     * 获取团队奖励记录
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/team/rewardList)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[]}})
     */
    public function rewardList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        if (!$userStats || !$userStats->is_team_leader) {
            $this->error('您不是队长');
        }

        $list = FukaTeamReward::where('team_id', $userStats->team_id)
            ->order('createtime desc')
            ->select();

        $this->success('获取成功', ['list' => $list]);
    }
}

