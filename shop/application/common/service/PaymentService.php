<?php

namespace app\common\service;

use app\common\model\fuka\CardOrder;
use app\common\model\fuka\CardFlowLog;
use app\common\model\fuka\WealthCard;
use think\Db;
use think\Log;

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

        // 获取回调地址
        $notifyUrl = self::getNotifyUrl($payType);

        // TODO: 对接真实支付SDK
        // 这里应该调用对应的支付SDK生成支付参数
        
        $result = [
            'pay_type' => $payType,
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'notify_url' => $notifyUrl,
            'payment_url' => self::getMockPaymentUrl($order, $payType),
        ];

        Log::info('生成支付参数', $result);

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
     * 提取订单号
     */
    private static function extractOrderNo($data, $payType)
    {
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
            // 1. 查询订单
            $order = CardOrder::where('order_no', $orderNo)->lock(true)->find();
            if (!$order) {
                throw new \Exception('订单不存在');
            }

            // 2. 幂等性检查
            if ($order->pay_status == 1) {
                Log::info('订单已支付，跳过处理', ['order_no' => $orderNo]);
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

            // 5. 获取或创建流程记录
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

            // 6. 更新流程记录状态为"已支付待审核"
            $flowLog->order_id = $order->id;
            $flowLog->flow_status = 2; // 已支付待审核
            $flowLog->is_pay_fee = 1;
            $flowLog->pay_fee_time = time();
            $flowLog->pay_trade_no = $transactionId;
            $flowLog->save();

            // 7. 更新金卡流程状态（安全检查）
            $card = WealthCard::where('id', $order->card_id)->find();
            if ($card) {
                if ($card->flow_status < $order->step_id) {
                    $card->flow_status = $order->step_id;
                    $card->save();
                }
            } else {
                Log::warning('金卡不存在', ['card_id' => $order->card_id]);
            }

            Db::commit();

            Log::info('支付回调处理成功', [
                'order_no' => $orderNo,
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'pay_type' => $payType,
                'user_id' => $order->user_id,
                'step_id' => $order->step_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('支付回调处理失败', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 验证签名
     * 
     * @param array $data 回调数据
     * @param string $payType 支付类型
     * @return bool
     */
    private static function verifySign($data, $payType)
    {
        switch ($payType) {
            case self::PAY_TYPE_WECHAT:
                return self::verifyWechatSign($data);
            case self::PAY_TYPE_ALIPAY:
                return self::verifyAlipaySign($data);
            case self::PAY_TYPE_UNIONPAY:
                return self::verifyUnionpaySign($data);
            default:
                return false;
        }
    }

    /**
     * 验证微信支付签名
     */
    private static function verifyWechatSign($data)
    {
        // TODO: 实现微信支付签名验证
        $apiKey = config('payment.wechat.api_key');
        if (empty($apiKey)) {
            Log::warning('微信支付API密钥未配置');
            return true; // 开发环境跳过验证
        }

        $sign = $data['sign'] ?? '';
        unset($data['sign'], $data['sign_type']);

        ksort($data);
        $string = '';
        foreach ($data as $k => $v) {
            if ($v !== '' && !is_array($v)) {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string .= 'key=' . $apiKey;

        $calcSign = strtoupper(md5($string));

        return $sign === $calcSign;
    }

    /**
     * 验证支付宝签名
     */
    private static function verifyAlipaySign($data)
    {
        // TODO: 实现支付宝签名验证
        $alipayPublicKey = config('payment.alipay.public_key');
        if (empty($alipayPublicKey)) {
            Log::warning('支付宝公钥未配置');
            return true; // 开发环境跳过验证
        }

        $sign = $data['sign'] ?? '';
        unset($data['sign'], $data['sign_type']);

        ksort($data);
        $string = '';
        foreach ($data as $k => $v) {
            if ($v !== '' && !is_array($v)) {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string = rtrim($string, '&');

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($alipayPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        return openssl_verify(
            $string,
            base64_decode($sign),
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
    }

    /**
     * 验证云闪付签名
     */
    private static function verifyUnionpaySign($data)
    {
        // TODO: 实现云闪付签名验证
        $unionpayPublicKey = config('payment.unionpay.public_key');
        if (empty($unionpayPublicKey)) {
            Log::warning('云闪付公钥未配置');
            return true; // 开发环境跳过验证
        }

        $sign = $data['signature'] ?? '';
        unset($data['signature']);

        ksort($data);
        $string = http_build_query($data);

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($unionpayPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        return openssl_verify(
            $string,
            base64_decode($sign),
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
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
     * 获取模拟支付链接（开发环境使用）
     */
    private static function getMockPaymentUrl($order, $payType)
    {
        $baseUrl = config('app.app_url') ?: 'http://your-domain.com';
        return $baseUrl . '/mock/payment?order_no=' . $order->order_no . '&pay_type=' . $payType;
    }
}

