<?php

namespace addons\cus\job;

use think\queue\Job;

class Test extends BaseJob
{
    /**
     * 普通优先级队列测试
     */
    public function cus(Job $job, $data)
    {
        // 创建目录
        $this->mkdir();
        
        // 写入日志文件
        $filename = RUNTIME_PATH . 'storage/queue/cus.log';
        file_put_contents($filename, date('Y-m-d H:i:s'));

        $job->delete();
    }


    /**
     * 高优先级队列测试
     */
    public function cusHigh(Job $job, $data)
    {
        // 创建目录
        $this->mkdir();

        // 写入日志文件
        $filename = RUNTIME_PATH . 'storage/queue/cus-high.log';
        file_put_contents($filename, date('Y-m-d H:i:s'));

        $job->delete();
    }


    /**
     * 创建目录
     */
    private function mkdir()
    {
        $dir = RUNTIME_PATH . 'storage/queue/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
