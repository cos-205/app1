<?php

namespace app\admin\model\cus\fuka;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;
use app\admin\model\cus\user\User;

/**
 * 金卡订单模型
 */
class CardOrder extends Common
{
    use SoftDelete;

    protected $name = 'card_order';
    
    protected $deleteTime = 'deletetime';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_id' => 'integer',
        'step_id' => 'integer',
        'amount' => 'float',
        'pay_status' => 'integer',
        'pay_time' => 'timestamp',
        'refund_status' => 'integer',
        'refund_time' => 'timestamp',
        'refund_amount' => 'float',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
        'deletetime' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'pay_status_text',
        'refund_status_text',
    ];

    /**
     * 支付状态列表
     */
    public function getPayStatusList()
    {
        return [
            0 => '未支付',
            1 => '已支付',
            2 => '已退款',
        ];
    }

    /**
     * 退款状态列表
     */
    public function getRefundStatusList()
    {
        return [
            0 => '未退款',
            1 => '退款中',
            2 => '已退款',
        ];
    }

    /**
     * 支付状态文本
     */
    public function getPayStatusTextAttr($value, $data)
    {
        $list = $this->getPayStatusList();
        return isset($list[$data['pay_status']]) ? $list[$data['pay_status']] : '未知';
    }

    /**
     * 退款状态文本
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        $list = $this->getRefundStatusList();
        return isset($list[$data['refund_status']]) ? $list[$data['refund_status']] : '未知';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\admin\model\cus\user\User', 'user_id');
    }

    /**
     * 关联金卡
     */
    public function card()
    {
        return $this->belongsTo('app\common\model\fuka\WealthCard', 'card_id');
    }

    /**
     * 关联流程记录
     */
    public function flowLog()
    {
        return $this->hasOne('app\common\model\fuka\CardFlowLog', 'order_id');
    }
}
