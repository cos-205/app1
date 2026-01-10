<?php

namespace app\common\service\fuka;

use app\common\model\fuka\DividendRecord;
use app\common\model\fuka\MemberLevel;
use app\common\model\User;
use think\Db;
use think\Exception;

/**
 * 分红发放服务
 */
class DividendService
{
    /**
     * 发放单条分红记录
     * @param DividendRecord $record 分红记录
     * @return array ['success' => bool, 'message' => string, 'trade_no' => string|null]
     */
    public function sendDividend($record)
    {
        Db::startTrans();
        try {
            // 检查记录状态
            if ($record->send_status != 0) {
                return [
                    'success' => false,
                    'message' => '分红记录状态不正确，无法发放',
                    'trade_no' => null
                ];
            }
            
            // 获取用户信息
            $user = User::get($record->user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户不存在',
                    'trade_no' => null
                ];
            }
            
            // 检查用户是否有支付宝账号
            if (empty($user->alipay_account)) {
                $record->send_status = 2; // 发放失败
                $record->fail_reason = '用户未绑定支付宝账号';
                $record->updatetime = time();
                $record->save();
                
                Db::commit();
                return [
                    'success' => false,
                    'message' => '用户未绑定支付宝账号',
                    'trade_no' => null
                ];
            }
            
            // 调用支付宝转账接口（需要根据实际支付宝接口实现）
            $result = $this->transferToAlipay($user->alipay_account, $record->dividend_money, $record);
            
            if ($result['success']) {
                // 发放成功
                $record->send_status = 1; // 已发放
                $record->send_time = time();
                $record->send_account = $user->alipay_account;
                $record->trade_no = $result['trade_no'];
                $record->updatetime = time();
                $record->save();
                
                // 更新用户余额（如果需要）
                // User::money($record->dividend_money * 10000, $user->id, '会员分红');
                
                // 更新用户统计表
                $userStats = \app\common\model\fuka\UserStatistics::where('user_id', $user->id)->find();
                if ($userStats) {
                    $userStats->dividend_money = max(0, $userStats->dividend_money - $record->dividend_money);
                    $userStats->total_dividend += $record->dividend_money;
                    $userStats->updatetime = time();
                    $userStats->save();
                }
                
                Db::commit();
                
                // 记录成功日志
                if (function_exists('output_log')) {
                    output_log('info', [
                        'title' => '[分红系统] 分红发放成功',
                        'user_id' => $user->id,
                        'dividend_record_id' => $record->id,
                        'amount' => $record->dividend_money,
                        'trade_no' => $result['trade_no']
                    ]);
                }
                
                return [
                    'success' => true,
                    'message' => '分红发放成功',
                    'trade_no' => $result['trade_no']
                ];
            } else {
                // 发放失败
                $record->send_status = 2; // 发放失败
                $record->fail_reason = $result['message'];
                $record->updatetime = time();
                $record->save();
                
                Db::commit();
                
                // 记录失败日志
                if (function_exists('output_log')) {
                    output_log('error', [
                        'title' => '[分红系统] 分红发放失败',
                        'user_id' => $user->id,
                        'dividend_record_id' => $record->id,
                        'error' => $result['message']
                    ]);
                }
                
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'trade_no' => null
                ];
            }
        } catch (Exception $e) {
            Db::rollback();
            
            // 更新记录状态
            $record->send_status = 2;
            $record->fail_reason = '系统错误：' . $e->getMessage();
            $record->updatetime = time();
            $record->save();
            
            // 记录错误日志
            if (function_exists('output_log')) {
                output_log('error', [
                    'title' => '[分红系统] 分红发放异常',
                    'user_id' => $record->user_id,
                    'dividend_record_id' => $record->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            return [
                'success' => false,
                'message' => '系统错误：' . $e->getMessage(),
                'trade_no' => null
            ];
        }
    }
    
    /**
     * 调用支付宝转账接口
     * @param string $alipayAccount 支付宝账号
     * @param float $amount 金额（万元）
     * @param DividendRecord $record 分红记录
     * @return array
     */
    protected function transferToAlipay($alipayAccount, $amount, $record)
    {
        // TODO: 根据实际支付宝接口实现
        // 这里需要集成支付宝转账接口
        // 示例代码（需要替换为实际接口调用）
        
        try {
            // 金额转换为元（数据库存储的是万元）
            $amountYuan = $amount * 10000;
            
            // 调用支付宝转账接口
            // $alipay = new \Alipay\Transfer();
            // $result = $alipay->transfer($alipayAccount, $amountYuan, '会员分红');
            
            // 模拟接口调用（实际需要替换）
            // 这里返回模拟结果，实际使用时需要调用真实接口
            $tradeNo = 'ALIPAY' . date('YmdHis') . rand(1000, 9999);
            
            // 记录日志
            if (function_exists('output_log')) {
                output_log('info', [
                    'title' => '[分红系统] 调用支付宝转账接口',
                    'user_id' => $record->user_id,
                    'alipay_account' => $alipayAccount,
                    'amount' => $amountYuan,
                    'trade_no' => $tradeNo
                ]);
            }
            
            return [
                'success' => true,
                'trade_no' => $tradeNo,
                'message' => '转账成功'
            ];
        } catch (Exception $e) {
            if (function_exists('output_log')) {
                output_log('error', [
                    'title' => '[分红系统] 支付宝转账失败',
                    'user_id' => $record->user_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return [
                'success' => false,
                'trade_no' => null,
                'message' => '支付宝转账失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量发放分红（每月1号执行）
     * @return array ['total' => int, 'success' => int, 'failed' => int]
     */
    public function batchSendDividend()
    {
        // 查询所有待发放的分红记录
        $records = DividendRecord::where('send_status', 0)
            ->where('status', 'normal')
            ->select();
        
        $total = count($records);
        $success = 0;
        $failed = 0;
        
        foreach ($records as $record) {
            $result = $this->sendDividend($record);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        // 记录批量发放日志
        if (function_exists('output_log')) {
            output_log('info', [
                'title' => '[分红系统] 批量发放分红完成',
                'total' => $total,
                'success' => $success,
                'failed' => $failed
            ]);
        }
        
        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed
        ];
    }
}
