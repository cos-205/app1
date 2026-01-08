<?php

namespace addons\cus\listener;

use app\admin\model\cus\user\User as UserModel;
use app\admin\model\cus\Share as ShareModel;
use app\common\service\fuka\UserInit as UserInitService;

/**
 * 福卡系统监听器
 */
class Fuka
{
    /**
     * 用户注册成功后
     * 初始化福卡系统相关数据
     */
    public function userRegisterAfter($payload)
    {
        $user = $payload['user'];
        
        if (!$user || !$user->id) {
            return;
        }

        $userInitService = new UserInitService($user);
            
        // 1. 生成邀请码
        $userInitService->generateInviteCode();
        
        // 2. 创建用户统计记录
        $userInitService->createUserStatistics();
        
        // 3. 如果有推荐人，更新推荐人统计
        if ($user->parent_user_id) {
            $userInitService->updateInviterInviteCount();
        }
         //4.生成推荐关系表
         $userInitService->createShareRelation();
    }

    /**
     * 用户完成实名认证后
     * 更新推荐人统计和会员等级
     */
    public function userRealnameAfter($payload)
    {
        $user = $payload['user'];
        
        if (!$user || !$user->id) {
            return;
        }

        $userInitService = new UserInitService($user);
            
        // 更新推荐人的有效邀请人数和会员等级
        $userInitService->updateInviterStatisticsAfterRealname();
    }
}
