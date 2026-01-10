<?php

namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\fuka\DividendService;

/**
 * 分红发放定时任务
 * 每月1号自动执行，发放待发放的分红
 * 
 * 使用方法：
 * php think dividend:send
 * 
 * 配置crontab：
 * 0 1 1 * * cd /home/www/fund_shop && php think dividend:send >> /tmp/dividend_send.log 2>&1
 */
class DividendSend extends Command
{
    protected function configure()
    {
        $this->setName('dividend:send')
            ->setDescription('发放会员分红（每月1号执行）');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('==================== 开始执行分红发放任务 ====================');
        $output->writeln('执行时间：' . date('Y-m-d H:i:s'));
        
        try {
            $service = new DividendService();
            $result = $service->batchSendDividend();
            
            $output->writeln('==================== 分红发放完成 ====================');
            $output->writeln("总计：{$result['total']} 条");
            $output->writeln("成功：{$result['success']} 条");
            $output->writeln("失败：{$result['failed']} 条");
            $output->writeln('====================================================');
            
        } catch (\Exception $e) {
            $output->writeln('==================== 分红发放失败 ====================');
            $output->writeln("错误信息：" . $e->getMessage());
            $output->writeln('====================================================');
            
            if (function_exists('output_log')) {
                output_log('error', [
                    'title' => '[分红系统] 定时任务执行失败',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
