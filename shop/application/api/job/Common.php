<?php

namespace app\api\job;

use think\queue\Job;

/**
 * 通用队列任务
 */
class Common
{
    /**
     * 处理用户实名认证后的业务逻辑
     * 已移除福卡相关业务逻辑
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
            
            // 福卡监听器已移除
            // 如需添加其他实名认证后的业务逻辑，请在此处添加
            
            // 删除任务
            $job->delete();
            
        } catch (\Exception $e) {
            output_log('error', [
                'title' => '用户实名认证队列任务执行失败',
                'error' => $e->getMessage()
            ]);
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

