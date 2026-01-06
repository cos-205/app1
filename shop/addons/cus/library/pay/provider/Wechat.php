<?php

namespace addons\cus\library\pay\provider;

use think\Log;
use think\exception\HttpResponseException;
use addons\cus\exception\CusException;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection as YansongdaCollection;

class Wechat extends Base
{
    protected $payService = null;
    protected $platform = null;

    public function __construct($payService, $platform = null)
    {
        $this->payService = $payService;

        $this->platform = $platform;
    }



    public function pay($order, $config = [])
    {
        $this->init('wechat', $config);

        if (isset($this->config['wechat']['default']['mode']) && $this->config['wechat']['default']['mode'] === 2) {
            if (in_array($this->platform, ['WechatOfficialAccount', 'WechatMiniProgram'])) {
                $order['payer']['sub_openid'] = $order['payer']['openid'] ?? '';
                unset($order['payer']['openid']);
            }
        }

        $order['amount']['total'] = intval(bcmul((string)$order['total_amount'], '100'));        // 按分 为单位

        if ($this->platform == 'H5') {
            $order['_type'] = 'app';        // 使用 配置中的 app_id 字段
            $order['scene_info'] = [
                'payer_client_ip' => request()->ip(),
                'h5_info' => [
                    'type' => 'Wap',
                ]
            ];
        }

        unset($order['order_id'], $order['total_amount']);
        $method = $this->getMethod('wechat');

        $result = $this->tryPay($method, $order);

        return $result;
    }

    public function transfer($payload, $config = [])
    {
        $config['notify_url'] = $config['notify_url'] ?? request()->domain() . '/addons/cus/pay/notifyTransfer/payment/wechat/platform/' . $this->platform;
        $payload['notify_url'] = $config['notify_url'];

        // 新版商户转账，支持服务商模式，(但是当前版本yansongda 不支持，还是需要切换到子商户转账
        $this->init('wechat', $config, 'sub_mch');
        $code = 0;
        $payload['transfer_amount'] = intval(bcmul((string)$payload['transfer_amount'], '100'));

        if ($payload['transfer_amount'] < 30) {        // 0.3元以下不允许输入姓名校验
            unset($payload['user_name']);
        }

        if (isset($this->config['wechat']['default']['_type'])) {
            // 为了能正常获取 appid
            $payload['_type'] = $this->config['wechat']['default']['_type'];
        }
        $response = $this->tryPay('transfer', $payload);
        $tansferData = [
            'mchId' => $this->config['wechat']['default']['mch_id'],
            'subMchId' => $this->config['wechat']['default']['sub_mch_id'] ?? '',
            'appId' => $this->config['wechat']['default']['_app_id'],
            'subAppId' => $this->config['wechat']['default']['_sub_app_id'] ?? '',
            'package' => $response['package_info'] ?? '',
            'openId' => $payload['openid'] ?? '',
        ];

        return [
            $response,
            $tansferData

        ];
    }


    /**
     * 微信退款回调
     *
     * @param array $callback
     * @param array $config
     * @return array
     */
    public function notifyTransfer($callback, $config = [])
    {
        $this->init('wechat', $config, 'sub_mch');
        try {
            // 验签
            $originData = Pay::wechat()->callback();
            Log::write('pay-notify-transfer-origin-data：' . json_encode($originData, JSON_UNESCAPED_UNICODE));
            // {
            //     "id": "d00ceb4c-52fd-53f8-8b67-986c43be48ca",
            //     "create_time": "2025-05-14T18:15:40+08:00",
            //     "resource_type": "encrypt-resource",
            //     "event_type": "MCHTRANSFER.BILL.FINISHED",
            //     "summary": "商家转账单据终态通知",
            //     "resource": {
            //         "original_type": "mch_payment",
            //         "algorithm": "AEAD_AES_256_GCM",
            //         "ciphertext": {
            //             "mch_id": "1481069012",
            //             "out_bill_no": "W202506153689159328002000",
            //             "transfer_bill_no": "1330000294905592505140048567267811",
            //             "transfer_amount": 100,
            //             "state": "SUCCESS",
            //             "openid": "oD9ko4_R2Wt15xZZqLy5OY7aj_fY",
            //             "create_time": "2025-05-14T18:15:37+08:00",
            //             "update_time": "2025-05-14T18:15:40+08:00",
            //             "mchid": "1481069012"
            //         },
            //         "associated_data": "mch_payment",
            //         "nonce": "c34ZesPAKIBu"
            //     }
            // }

            if ($originData['event_type'] == 'MCHTRANSFER.BILL.FINISHED') {
                $data = $originData['resource']['ciphertext'] ?? [];
                Log::write('pppppp:' . json_encode($data, JSON_UNESCAPED_UNICODE));
                if (isset($data['state'])) {
                    $withdrawSn = $data['out_bill_no'];
                    $result = $callback($data['state'], $withdrawSn, $originData);
                    return $result;
                }
                return 'fail';
            } else {
                // 微信交易未成功，返回 false，让微信再次通知
                Log::error('pay-notify-transfer-error:转账未成功:' . $originData['event_type']);
                return 'fail';
            }
        } catch (\Exception $e) {
            format_log_error($e, 'wechatNotifyTransfer');
            return 'fail';
        }
    }

    // 查询商家转账提现订单
    public function queryTransferOrder($payload, $config = [])
    {
        $this->init('wechat', $config, 'sub_mch');
        $allPlugins = Pay::wechat()->mergeCommonPlugins([
            \Yansongda\Pay\Plugin\Wechat\V3\Marketing\MchTransfer\QueryPlugin::class,
        ]);

        try {
            return Pay::wechat()->pay($allPlugins, [
                'out_bill_no' => $payload['withdraw_sn'],
            ]);
        } catch (InvalidResponseException $e) {
            if ($e->response instanceof YansongdaCollection) {
                $message = $e->response->toString('|');
            } else {
                $message = $e->getMessage();
            }

            throw new CusException($message);
        }
    }

    // 撤销商家转账提现订单
    public function cancelTransferOrder($payload, $config = [])
    {
        $this->init('wechat', $config, 'sub_mch');
        $allPlugins = Pay::wechat()->mergeCommonPlugins([
            \Yansongda\Pay\Plugin\Wechat\V3\Marketing\MchTransfer\CancelPlugin::class,
        ]);

        try {
            return Pay::wechat()->pay($allPlugins, [
                'out_bill_no' => $payload['withdraw_sn'],
            ]);
        } catch (InvalidResponseException $e) {
            if ($e->response instanceof YansongdaCollection) {
                $message = $e->response->toString('|');
            } else {
                $message = $e->getMessage();
            }

            throw new CusException($message);
        }
    }



    public function notify($callback, $config = [])
    {
        $this->init('wechat', $config);
        try {
            $originData = Pay::wechat()->callback(); // 是的，验签就这么简单！
            // {
            //     "id": "a5c68a7c-5474-5151-825d-88b4143f8642",
            //     "create_time": "2022-06-20T16:16:12+08:00",
            //     "resource_type": "encrypt-resource",
            //     "event_type": "TRANSACTION.SUCCESS",
            //     "summary": "支付成功",
            //     "resource": {
            //         "original_type": "transaction",
            //         "algorithm": "AEAD_AES_256_GCM",
            //         "ciphertext": {
            //             "mchid": "1623831039",
            //             "appid": "wx43********d3d0",
            //             "out_trade_no": "P202204155176122100021000",
            //             "transaction_id": "4200001433202206201698588194",
            //             "trade_type": "JSAPI",
            //             "trade_state": "SUCCESS",
            //             "trade_state_desc": "支付成功",
            //             "bank_type": "OTHERS",
            //             "attach": "",
            //             "success_time": "2022-06-20T16:16:12+08:00",
            //             "payer": {
            //                 "openid": "oRj5A44G6lgCVENzVMxZtoMfNeww"
            //             },
            //             "amount": {
            //                 "total": 1,
            //                 "payer_total": 1,
            //                 "currency": "CNY",
            //                 "payer_currency": "CNY"
            //             }
            //         },
            //         "associated_data": "transaction",
            //         "nonce": "qoJzoS9MCNgu"
            //     }
            // }
            Log::write('pay-notify-origin-data：' . json_encode($originData));
            if ($originData['event_type'] == 'TRANSACTION.SUCCESS') {
                // 支付成功回调
                $data = $originData['resource']['ciphertext'] ?? [];
                if (isset($data['trade_state']) && $data['trade_state'] == 'SUCCESS') {
                    // 交易成功
                    $data['pay_fee'] = ($data['amount']['total'] / 100);
                    $data['notify_time'] = date('Y-m-d H:i:s', strtotime((string)$data['success_time']));
                    $data['buyer_info'] = $data['payer']['openid'] ?? ($data['payer']['sub_openid'] ?? '');

                    $result = $callback($data, $originData);
                    return $result;
                }

                return 'fail';
            } else {
                // 微信交易未成功，返回 false，让微信再次通知
                Log::error('notify-error:交易未成功:' . $originData['event_type']);
                return 'fail';
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'wechatNotify.HttpResponseException', $message);
            return 'fail';
        } catch (\Exception $e) {
            format_log_error($e, 'wechatNotify');
            return 'fail';
        }
    }


    /**
     * 退款
     *
     * @param array $order_data
     * @param array $config
     * @return array
     */
    public function refund($order_data, $config = [])
    {
        $config['notify_url'] = $config['notify_url'] ?? request()->domain() . '/addons/cus/pay/notifyRefund/payment/wechat/platform/' . $this->platform;
        $order_data['notify_url'] = $config['notify_url'];

        $this->init('wechat', $config);

        $order_data['amount']['total'] = intval(bcmul((string)$order_data['amount']['total'], '100'));
        $order_data['amount']['refund'] = intval(bcmul((string)$order_data['amount']['refund'], '100'));

        $result = $this->tryPay('refund', $order_data);
        Log::write('pay-refund-origin-data：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        // {   返回数据字段
        //     "amount": {
        //         "currency": "CNY",
        //         "discount_refund": 0,
        //         "from": [],
        //         "payer_refund": 1,
        //         "payer_total": 1,
        //         "refund": 1,
        //         "settlement_refund": 1,
        //         "settlement_total": 1,
        //         "total": 1
        //     },
        //     "channel": "ORIGINAL",
        //     "create_time": "2022-06-20T19:06:36+08:00",
        //     "funds_account": "AVAILABLE",
        //     "out_refund_no": "R202207063668479227002100",
        //     "out_trade_no": "P202205155977315528002100",
        //     "promotion_detail": [],
        //     "refund_id": "50301802252022062021833667769",
        //     "status": "PROCESSING",
        //     "transaction_id": "4200001521202206207964248014",
        //     "user_received_account": "\u652f\u4ed8\u7528\u6237\u96f6\u94b1"
        // }

        return $result;
    }



    /**
     * 微信退款回调
     *
     * @param array $callback
     * @param array $config
     * @return array
     */
    public function notifyRefund($callback, $config = [])
    {
        $this->init('wechat', $config);
        try {
            $originData = Pay::wechat()->callback(); // 是的，验签就这么简单！
            Log::write('pay-notifyrefund-callback-data:' . json_encode($originData));
            // {
            //     "id": "4a553265-1f28-53a3-9395-8d902b902462",
            //     "create_time": "2022-06-21T11:25:33+08:00",
            //     "resource_type": "encrypt-resource",
            //     "event_type": "REFUND.SUCCESS",
            //     "summary": "\u9000\u6b3e\u6210\u529f",
            //     "resource": {
            //         "original_type": "refund",
            //         "algorithm": "AEAD_AES_256_GCM",
            //         "ciphertext": {
            //             "mchid": "1623831039",
            //             "out_trade_no": "P202211233042122753002100",
            //             "transaction_id": "4200001417202206214219765470",
            //             "out_refund_no": "R202211252676008994002100",
            //             "refund_id": "50300002272022062121864292533",
            //             "refund_status": "SUCCESS",
            //             "success_time": "2022-06-21T11:25:33+08:00",
            //             "amount": {
            //                 "total": 1,
            //                 "refund": 1,
            //                 "payer_total": 1,
            //                 "payer_refund": 1
            //             },
            //             "user_received_account": "\u652f\u4ed8\u7528\u6237\u96f6\u94b1"
            //         },
            //         "associated_data": "refund",
            //         "nonce": "8xfQknYyLVop"
            //     }
            // }

            if ($originData['event_type'] == 'REFUND.SUCCESS') {
                // 支付成功回调
                $data = $originData['resource']['ciphertext'] ?? [];
                if (isset($data['refund_status']) && $data['refund_status'] == 'SUCCESS') {
                    // 退款成功
                    $result = $callback($data, $originData);
                    return $result;
                }

                return 'fail';
            } else {
                // 微信交易未成功，返回 false，让微信再次通知
                Log::error('notify-error:退款未成功:' . $originData['event_type']);
                return 'fail';
            }
        } catch (HttpResponseException $e) {
            $data = $e->getResponse()->getData();
            $message = $data ? ($data['msg'] ?? '') : $e->getMessage();
            format_log_error($e, 'wechatNotifyRefund.HttpResponseException', $message);
            return 'fail';
        } catch (\Exception $e) {
            format_log_error($e, 'wechatNotifyRefund');
            return 'fail';
        }
    }



    /**
     * 捕获异常  （解决 ResponsePlugin 状态码异常时候重新读取错误信息的 问题）
     *
     * @param string $method
     * @param array $payload
     * @return mixed
     */
    private function tryPay($method, $payload)
    {
        try {
            return Pay::wechat()->$method($payload);
        } catch (InvalidResponseException $e) {
            if ($e->response instanceof YansongdaCollection) {
                $message = $e->response->toString('|');
            } else {
                $message = $e->getMessage();
            }

            throw new CusException($message);
        }
    }



    /**
     * 格式化支付参数
     *
     * @param [type] $params
     * @return void
     */
    protected function formatConfig($config, $data = [], $type = 'normal')
    {
        if ($config['mode'] == 2 && $type == 'sub_mch') {
            // 服务商模式，但需要子商户直连 ，重新定义 config(商家转账到零钱)
            $config = [
                'mch_id' => $config['sub_mch_id'],
                'mch_secret_key' => $config['sub_mch_secret_key'],
                'mch_secret_cert' => $config['sub_mch_secret_cert'],
                'mch_public_cert_path' => $config['sub_mch_public_cert_path'],
            ];
            $config['mode'] = 0;        // 临时改为普通商户
        }

        if ($config['mode'] === 2) {
            // 首先将平台配置的 app_id 初始化到配置中
            $config['mp_app_id'] = $config['app_id'];       // 服务商关联的公众号的 appid   
            $config['mini_app_id'] = $config['app_id'];       // 服务商关联的小程序的 appid     （这里不管 appid 到底是不是小程序的，要给 mini_app_id 赋值,否则小程序服务商找不到 appid）
            $config['sub_app_id'] = $data['app_id'];        // 服务商特约子商户

            // 最新版提现时候需要组装 appid 参数
            $config['_app_id'] = $config['mp_app_id'];      // 最新版提现时候使用
            $config['_sub_app_id'] = $config['sub_app_id'];  // 最新版提现时候使用
        } else {
            $config['app_id'] = $data['app_id'];

            // 最新版提现时候需要组装 appid 参数
            $config['_app_id'] = $config['app_id'];      // 最新版提现时候使用
        }

        switch ($this->platform) {
            case 'WechatMiniProgram':
                $config['_type'] = 'mini';          // 小程序提现，需要传 _type = mini 才能正确获取到 appid
                if ($config['mode'] === 2) {
                    $config['sub_mini_app_id'] = $config['sub_app_id'];
                    unset($config['sub_app_id']);
                } else {
                    $config['mini_app_id'] = $config['app_id'];
                    unset($config['app_id']);
                }
                break;
            case 'WechatOfficialAccount':
                $config['_type'] = 'mp';          // 小程序提现，需要传 _type = mp 才能正确获取到 appid
                if ($config['mode'] === 2) {
                    $config['sub_mp_app_id'] = $config['sub_app_id'];
                    unset($config['sub_app_id']);
                } else {
                    $config['mp_app_id'] = $config['app_id'];
                    unset($config['app_id']);
                }
                break;
            case 'App':
            case 'H5':
            default:
                break;
        }

        $config['notify_url'] = request()->domain() . '/addons/cus/pay/notify/payment/wechat/platform/' . $this->platform;
        $config['mch_secret_cert'] = ROOT_PATH . 'public' . $config['mch_secret_cert'];
        $config['mch_public_cert_path'] = ROOT_PATH . 'public' . $config['mch_public_cert_path'];

        // 可手动配置微信支付公钥证书
        $config['wechat_public_cert_id'] = $config['wechat_public_cert_id'] ?? '';
        $config['wechat_public_cert'] = $config['wechat_public_cert'] ?? '';
        if ($config['wechat_public_cert_id'] && $config['wechat_public_cert']) {
            $config['wechat_public_cert_path'] = [
                $config['wechat_public_cert_id'] => ROOT_PATH . 'public' . $config['wechat_public_cert']
            ];
        }
        unset($config['wechat_public_cert_id'], $config['wechat_public_cert']);

        return $config;
    }
}
