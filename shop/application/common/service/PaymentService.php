<?php

namespace app\common\service;

use app\common\model\fuka\CardOrder;
use app\common\model\fuka\CardFlowLog;
use app\common\model\fuka\WealthCard;
use app\common\model\fuka\UserAgreementFlow;
use app\common\model\fuka\CardAgreementFlow;
use app\common\model\fuka\ExchangeRecord;
use app\common\model\fuka\Prize;
use think\Db;

/**
 * 统一支付服务类
 * 处理支付参数生成和支付回调
 */
class PaymentService
{
    /**
     * 支付类型映射
     */
    const PAY_TYPE_WECHAT = 'wechat';
    const PAY_TYPE_ALIPAY = 'alipay';
    const PAY_TYPE_UNIONPAY = 'unionpay';

    /**
     * 订单号字段映射（不同支付方式的字段名）
     */
    private static $orderNoFields = [
        self::PAY_TYPE_WECHAT => 'out_trade_no',
        self::PAY_TYPE_ALIPAY => 'out_trade_no',
        self::PAY_TYPE_UNIONPAY => 'orderId',
    ];

    /**
     * 第三方交易号字段映射
     */
    private static $transactionIdFields = [
        self::PAY_TYPE_WECHAT => 'transaction_id',
        self::PAY_TYPE_ALIPAY => 'trade_no',
        self::PAY_TYPE_UNIONPAY => 'queryId',
    ];

    /**
     * 生成支付参数
     * 
     * @param CardOrder $order 订单对象
     * @param string $payType 支付类型
     * @return array
     */
    public static function generatePaymentParams($order, $payType)
    {
        if (!in_array($payType, [self::PAY_TYPE_WECHAT, self::PAY_TYPE_ALIPAY, self::PAY_TYPE_UNIONPAY])) {
            throw new \Exception('不支持的支付方式');
        }

        // 验证订单
        if (!$order || $order->pay_status == 1) {
            throw new \Exception('订单不存在或已支付');
        }

        // 生成商户支付单号（如果还没有）
        if (empty($order->merchant_trade_no)) {
            $merchantTradeNo = self::generateMerchantTradeNo($payType);
            $order->merchant_trade_no = $merchantTradeNo;
            $order->pay_type = $payType;
            $order->save();
        } else {
            $merchantTradeNo = $order->merchant_trade_no;
        }

        // 获取回调地址
        $notifyUrl = self::getNotifyUrl($payType);

        // TODO: 对接真实支付SDK
        // 这里应该调用对应的支付SDK生成支付参数
        // 接入第三方支付后，请移除 getMockPaymentUrl 调用，改为调用真实支付SDK
        
        // 开发环境：返回测试支付网址
        $mockUrl = self::getMockPaymentUrl($order, $payType, $merchantTradeNo);
        
        $result = [
            'pay_type' => $payType,
            'order_no' => $order->order_no,
            'merchant_trade_no' => $merchantTradeNo,
            'amount' => $order->amount,
            'notify_url' => $notifyUrl,
            'payment_url' => $mockUrl,  // H5环境使用
            'pay_url' => $mockUrl,      // 兼容字段
        ];

        output_log('info', [
            'title' => '生成支付参数',
            'order_no' => $order->order_no,
            'merchant_trade_no' => $merchantTradeNo,
            'pay_type' => $payType,
            'payment_url' => $mockUrl
        ]);

        return $result;
    }

    /**
     * 处理支付回调
     * 
     * @param array $data 回调数据
     * @param string $payType 支付类型
     * @return bool
     * @throws \Exception
     */
    public static function processNotify($data, $payType)
    {
        // 1. 验证支付类型
        if (!in_array($payType, [self::PAY_TYPE_WECHAT, self::PAY_TYPE_ALIPAY, self::PAY_TYPE_UNIONPAY])) {
            throw new \Exception('不支持的支付类型');
        }

        // 2. 提取订单号和交易号
        $orderNo = self::extractOrderNo($data, $payType);
        $transactionId = self::extractTransactionId($data, $payType);

        if (empty($orderNo)) {
            throw new \Exception('订单号为空');
        }

        // 3. 验证签名（TODO: 配置真实密钥后启用）
        // if (!self::verifySign($data, $payType)) {
        //     throw new \Exception('签名验证失败');
        // }

        // 4. 处理订单
        return self::processPaymentSuccess($orderNo, $transactionId, $payType);
    }

    /**
     * 提取订单号（优先使用merchant_trade_no，兼容out_trade_no）
     */
    private static function extractOrderNo($data, $payType)
    {
        // 优先使用 merchant_trade_no（商户支付单号）
        if (!empty($data['merchant_trade_no'])) {
            return $data['merchant_trade_no'];
        }
        
        // 兼容第三方支付回调的字段名
        $field = self::$orderNoFields[$payType] ?? 'out_trade_no';
        return $data[$field] ?? '';
    }

    /**
     * 提取第三方交易号
     */
    private static function extractTransactionId($data, $payType)
    {
        $field = self::$transactionIdFields[$payType] ?? 'transaction_id';
        return $data[$field] ?? '';
    }

    /**
     * 处理支付成功
     * 
     * @param string $orderNo 订单号
     * @param string $transactionId 第三方交易号
     * @param string $payType 支付类型
     * @return bool
     * @throws \Exception
     */
    private static function processPaymentSuccess($orderNo, $transactionId, $payType)
    {
        Db::startTrans();
        try {
            // 1. 查询订单（优先使用商户支付单号，兼容订单号）
            $order = CardOrder::where(function($query) use ($orderNo) {
                $query->where('merchant_trade_no', $orderNo)
                      ->whereOr('order_no', $orderNo);
            })->lock(true)->find();
            
            if (!$order) {
                throw new \Exception('订单不存在');
            }

            // 2. 幂等性检查
            if ($order->pay_status == 1) {
                output_log('info', [
                    'title' => '订单已支付，跳过处理',
                    'order_no' => $orderNo
                ]);
                Db::commit();
                return true;
            }

            // 3. 验证订单是否超时（30分钟）
            if (time() - $order->createtime > 1800) {
                throw new \Exception('订单已超时');
            }

            // 4. 更新订单状态
            $order->pay_status = 1;
            $order->pay_type = $payType;
            $order->pay_time = time();
            $order->transaction_id = $transactionId;
            $order->save();

            // 5. 根据订单类型执行不同的业务逻辑
            if ($order->order_type == 'pickup_code') {
                // 取件码订单：生成取件码并更新兑换记录
                self::handlePickupCodeOrder($order);
            } elseif ($order->order_type == 'vehicle_doc') {
                // 车辆证书订单：生成证书信息并更新兑换记录
                self::handleVehicleDocOrder($order);
            } else {
                // 默认：金卡流程订单，执行原有逻辑
                self::handleCardFlowOrder($order);
            }

            Db::commit();

            output_log('info', [
                'title' => '支付回调处理成功',
                'order_no' => $orderNo,
                'order_id' => $order->id,
                'order_type' => $order->order_type,
                'transaction_id' => $transactionId,
                'pay_type' => $payType,
                'user_id' => $order->user_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            output_log('error', [
                'title' => '支付回调处理失败',
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理取件码订单支付成功
     */
    private static function handlePickupCodeOrder($order)
    {
        // 获取兑换记录
        $exchange = ExchangeRecord::where('id', $order->related_id)
            ->where('user_id', $order->user_id)
            ->find();
        
        if (!$exchange) {
            throw new \Exception('兑换记录不存在');
        }

        // 检查是否已生成取件码
        if ($exchange->is_get_pickup_code == 1) {
            output_log('info', [
                'title' => '取件码已存在，跳过生成',
                'exchange_id' => $exchange->id,
                'pickup_code' => $exchange->pickup_code
            ]);
            return;
        }

        // 生成取件码
        $pickupCode = self::generatePickupCode();
        
        // 更新兑换记录
        $exchange->pickup_code = $pickupCode;
        $exchange->is_get_pickup_code = 1;
        $exchange->pickup_code_fee = $order->amount;
        $exchange->pay_pickup_time = time();
        $exchange->save();

        output_log('info', [
            'title' => '取件码生成成功',
            'exchange_id' => $exchange->id,
            'pickup_code' => $pickupCode,
            'user_id' => $order->user_id
        ]);
    }

    /**
     * 处理车辆证书订单支付成功
     * 汽车奖品：支付证书费用后进入托运流程
     * 
     * 流程说明：
     * - status = 2: 已备货
     * - status = 3: 托运中（付费后进入此阶段）← 这里
     * - status = 4: 已到达
     */
    private static function handleVehicleDocOrder($order)
    {
        // 获取兑换记录
        $exchange = ExchangeRecord::where('id', $order->related_id)
            ->where('user_id', $order->user_id)
            ->find();
        
        if (!$exchange) {
            throw new \Exception('兑换记录不存在');
        }

        // 检查是否已生成证书
        if ($exchange->is_get_certificate == 1) {
            output_log('info', [
                'title' => '车辆证书已存在，跳过生成',
                'exchange_id' => $exchange->id,
                'certificate_no' => $exchange->certificate_no,
                'exchange_status' => $exchange->exchange_status
            ]);
            return;
        }

        // 生成证书号
        $certificateNo = self::generateVehicleDocNo();
        
        // 更新兑换记录
        $exchange->certificate_no = $certificateNo;
        $exchange->is_get_certificate = 1;
        $exchange->certificate_fee = $order->amount;
        $exchange->pay_certificate_time = time();
        
        // 关键：支付成功后，更新物流状态到"托运中"（status = 3）
        // 汽车奖品：支付证书费 → 进入托运流程
        if ($exchange->exchange_status < 3) {
            $exchange->exchange_status = 3;
            $exchange->transport_time = time(); // 设置托运开始时间
        }
        
        $exchange->save();

        output_log('info', [
            'title' => '车辆证书支付成功，已进入托运流程',
            'exchange_id' => $exchange->id,
            'certificate_no' => $certificateNo,
            'exchange_status' => $exchange->exchange_status,
            'user_id' => $order->user_id
        ]);
    }

    /**
     * 处理金卡流程订单支付成功
     */
    private static function handleCardFlowOrder($order)
    {
        $flowLog = CardFlowLog::where('user_id', $order->user_id)
            ->where('card_id', $order->card_id)
            ->where('flow_step', $order->step_id)
            ->lock(true)
            ->find();

        if (!$flowLog) {
            // 创建流程记录
            $flowLog = new CardFlowLog();
            $flowLog->user_id = $order->user_id;
            $flowLog->card_id = $order->card_id;
            $flowLog->flow_step = $order->step_id;
            $flowLog->step_name = $order->step_name;
            $flowLog->need_fee = 1;
            $flowLog->fee_amount = $order->amount;
            $flowLog->fee_name = $order->step_name . '费用';
        }

        // 6. 更新流程记录状态为"已完成"（自动审核通过）
        $flowLog->order_id = $order->id;
        $flowLog->flow_status = 3; // 已完成（支付成功后自动审核通过）
        $flowLog->is_pay_fee = 1;
        $flowLog->pay_fee_time = time();
        $flowLog->pay_trade_no = $order->transaction_id;
        $flowLog->complete_time = time(); // 设置完成时间
        $flowLog->save();
        
        // 7. 不自动更新金卡流程状态
        // 支付成功后，步骤标记为"已完成"，但金卡 flow_status 保持不变
        // 需要等待管理员手动激活下一步
        output_log('info', [
            'title' => '支付成功，步骤已完成，等待管理员激活下一步',
            'user_id' => $order->user_id,
            'step_id' => $order->step_id,
            'card_id' => $order->card_id
        ]);

        // 8. 确认前置动作已完成，并更新相关状态
        if ($order->step_id == 1) {
            // 步骤1：支付成功后，初始化协议流程状态
            // 1. 确保所有协议流程记录已创建
            $agreementFlows = CardAgreementFlow::where('step_id', 1)
                ->order('flow_step asc')
                ->select();
            
            foreach ($agreementFlows as $flow) {
                $userFlow = UserAgreementFlow::where('user_id', $order->user_id)
                    ->where('step_id', 1)
                    ->where('flow_step', $flow->flow_step)
                    ->find();
                
                if (!$userFlow) {
                    $userFlow = new UserAgreementFlow();
                    $userFlow->user_id = $order->user_id;
                    $userFlow->step_id = 1;
                    $userFlow->flow_step = $flow->flow_step;
                    $userFlow->status = 0; // 未开始
                    $userFlow->save();
                }
            }
            
            // 2. 只将 flow_step=1（协议签署）标记为已完成
            UserAgreementFlow::where('user_id', $order->user_id)
                ->where('step_id', 1)
                ->where('flow_step', 1)
                ->update([
                    'status' => 2, // 已完成
                    'completed_time' => time(),
                    'updatetime' => time()
                ]);
            
            // 3. 将 flow_step=2（协议审核）标记为进行中
            $flow2 = UserAgreementFlow::where('user_id', $order->user_id)
                ->where('step_id', 1)
                ->where('flow_step', 2)
                ->find();
            
            if ($flow2) {
                $flow2->status = 1; // 进行中
                $flow2->start_time = time();
                $flow2->updatetime = time();
                $flow2->save();
            } else {
                // 如果不存在，创建记录并设置为进行中
                $flow2 = new UserAgreementFlow();
                $flow2->user_id = $order->user_id;
                $flow2->step_id = 1;
                $flow2->flow_step = 2;
                $flow2->status = 1; // 进行中
                $flow2->start_time = time();
                $flow2->save();
            }
            
            // 4. 明确将其他步骤（flow_step 3-5）重置为未开始（status=0）
            // 因为签署协议时可能已经将它们设置为进行中，需要重置
            UserAgreementFlow::where('user_id', $order->user_id)
                ->where('step_id', 1)
                ->where('flow_step', '>', 2) // flow_step > 2，即 3, 4, 5
                ->update([
                    'status' => 0, // 未开始
                    'start_time' => null,
                    'completed_time' => null,
                    'updatetime' => time()
                ]);
            
            output_log('info', [
                'title' => '协议流程状态已初始化：步骤1已完成，步骤2进行中',
                'user_id' => $order->user_id,
                'step_id' => 1
            ]);
        } elseif (in_array($order->step_id, [2, 3])) {
            // 步骤3、4：检查是否已提交数据
            if (empty($flowLog->extra_data)) {
                output_log('warning', [
                    'title' => '支付成功但未找到步骤数据',
                    'user_id' => $order->user_id,
                    'step_id' => $order->step_id
                ]);
            }
        }
    }




    /**
     * 生成商户支付单号
     * 
     * @param string $payType 支付类型
     * @return string
     */
    private static function generateMerchantTradeNo($payType)
    {
        $prefix = '';
        switch ($payType) {
            case self::PAY_TYPE_WECHAT:
                $prefix = 'WX';
                break;
            case self::PAY_TYPE_ALIPAY:
                $prefix = 'AL';
                break;
            case self::PAY_TYPE_UNIONPAY:
                $prefix = 'UP';
                break;
            default:
                $prefix = 'MT';
        }
        
        return $prefix . date('YmdHis') . rand(100000, 999999);
    }

    /**
     * 获取回调地址
     */
    private static function getNotifyUrl($payType)
    {
        $baseUrl = config('app.app_url') ?: 'http://your-domain.com';
        return $baseUrl . '/api/payment/notify?pay_type=' . $payType;
    }

    /**
     * 生成测试交易号
     * 
     * @param string $payType 支付类型
     * @return string
     */
    private static function generateTestTransactionId($payType)
    {
        $prefix = '';
        switch ($payType) {
            case self::PAY_TYPE_WECHAT:
                $prefix = '420000';
                break;
            case self::PAY_TYPE_ALIPAY:
                $prefix = '2024';
                break;
            case self::PAY_TYPE_UNIONPAY:
                $prefix = '622848';
                break;
            default:
                $prefix = 'TEST';
        }
        
        return $prefix . date('YmdHis') . rand(100000, 999999);
    }

    /**
     * 获取模拟支付链接（开发环境使用）
     * TODO: 接入第三方支付后，请移除此方法，改为调用真实支付SDK
     */
    private static function getMockPaymentUrl($order, $payType, $merchantTradeNo)
    {
        // 生成测试交易号
        $testTransactionId = self::generateTestTransactionId($payType);
        
        // 根据支付类型生成对应的回调参数
        $callbackParams = [
            'pay_type' => $payType,
            'merchant_trade_no' => $merchantTradeNo,
            'order_no' => $order->order_no, // 兼容字段
            'amount' => $order->amount,
        ];
        
        // 添加支付类型对应的交易号字段
        $transactionField = self::$transactionIdFields[$payType] ?? 'transaction_id';
        $callbackParams[$transactionField] = $testTransactionId;
        
        // 添加支付类型对应的订单号字段（兼容第三方支付回调格式）
        $orderNoField = self::$orderNoFields[$payType] ?? 'out_trade_no';
        $callbackParams[$orderNoField] = $merchantTradeNo;
        
        // 构建测试回调URL
        $baseUrl = request()->domain();
        $testUrl = $baseUrl . '/api/payment/notify?' . http_build_query($callbackParams);
        
        // 返回测试网址，实际接入后应返回真实支付链接
        return $testUrl;
    }

    /**
     * 生成取件码
     * 
     * @return string
     */
    private static function generatePickupCode()
    {
        // 生成6位随机数字作为取件码
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 生成车辆证书号
     * 
     * @return string
     */
    private static function generateVehicleDocNo()
    {
        // 生成格式：DOC + 年月日 + 6位随机数
        return 'DOC' . date('Ymd') . rand(100000, 999999);
    }
}

