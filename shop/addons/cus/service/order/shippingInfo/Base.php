<?php

namespace addons\cus\service\order\shippingInfo;

use addons\cus\exception\CusException;
use app\admin\model\cus\Pay as PayModel;
use think\helper\Str;

class Base
{
    protected $order = null;

    public function __construct($order)
    {
        $this->order = $order;
        
        
    }
    

    /**
     * 设置微信支付相关的参数
     *
     * @param array $uploadParams
     * @param \think\Model $wechatPay
     * @return array
     */
    protected function setWechatParams($uploadParams, $wechatPay)
    {
        $order_key = [
            'order_number_type' => 2,
            'transaction_id' => $wechatPay->transaction_id,
            'out_trade_no' => $wechatPay->pay_sn,
        ];

        $payer = [
            'openid' => $wechatPay['buyer_info']
        ];

        foreach ($uploadParams as &$params) {
            $params['order_key'] = $order_key;
            $params['payer'] = $payer;
        }

        return $uploadParams;
    }



    /**
     * 获取订单中的微信支付 pay 记录
     *
     * @return think\Model
     */
    protected function getWechatPay($type = 'order')
    {
        $wechatPay = PayModel::{'type' . Str::studly($type)}()->where('order_id', $this->order['id'])
        ->where('status', '<>', PayModel::PAY_STATUS_UNPAID)
            ->where('pay_type', 'wechat')->order('id', 'desc')->find();

        if (!$wechatPay) {
            throw new CusException('未找到订单微信支付记录');
        }

        return $wechatPay;
    }



    /**
     * 配送方式转换
     *
     * @param string $dispatch_type
     * @return integer
     */
    protected function getLogisticsType($dispatch_type)
    {
        switch ($dispatch_type) {
            case 'express':
                $logistics_type = 1;
                break;
            case 'store_delivery':
                $logistics_type = 2;
                break;
            case 'autosend':
                $logistics_type = 3;
                break;
            case 'custom':
                $logistics_type = 3;
                break;
            case 'selfetch':
                $logistics_type = 4;
                break;
            default:
                $logistics_type = 1;
                break;
        }

        return $logistics_type;
    }
    
}
