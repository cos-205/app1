<?php

namespace app\api\job;

use think\queue\Job;
use addons\cus\listener\Fuka;

/**
 * 通用队列任务
 */
class Common
{
    /**
     * 处理用户实名认证后的业务逻辑
     * 
     * @param Job $job 当前任务对象
     * @param array $data 任务数据
     */
    public function userRealnameAfter(Job $job, $data)
    {
        try {
            if (!isset($data['user_id'])) {
                $job->delete();
                return;
            }
            
            $user = \app\common\model\User::get($data['user_id']);
            if (!$user) {
                $job->delete();
                return;
            }
            
            // 调用福卡监听器处理实名认证后的业务
            $fukaListener = new Fuka();
            $fukaListener->userRealnameAfter(['user' => $user]);
            
            // 删除任务
            $job->delete();
            
        } catch (\Exception $e) {
            
        }
    }
    
    /**
     * 队列任务失败回调
     * 
     * @param array $data 任务数据
     */
    public function failed($data)
    {
        output_log('error', [
            'title' => '用户实名认证队列任务最终失败',
            'data' => $data
        ]);
    }
}

