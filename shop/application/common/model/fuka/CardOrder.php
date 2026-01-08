<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 金卡支付订单模型
 */
class CardOrder extends Model
{
    protected $name = 'card_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    
    // 字段类型
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
    
    // 允许写入的字段
    protected $field = [
        'order_no', 'merchant_trade_no', 'user_id', 'card_id', 'step_id', 'step_name',
        'amount', 'pay_type', 'pay_status', 'pay_time', 'transaction_id', 'pay_url',
        'refund_status', 'refund_time', 'refund_transaction_id', 'refund_amount'
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }
    
    /**
     * 关联金卡
     */
    public function card()
    {
        return $this->belongsTo('app\common\model\fuka\WealthCard', 'card_id', 'id');
    }
    
    /**
     * 关联流程配置
     */
    public function flowConfig()
    {
        return $this->belongsTo('app\common\model\fuka\CardFlowConfig', 'step_id', 'step');
    }
    
    /**
     * 关联流程记录
     */
    public function flowLog()
    {
        return $this->hasOne('app\common\model\fuka\CardFlowLog', 'order_id', 'id');
    }
    
    /**
     * 支付状态文本
     */
    public function getPayStatusTextAttr($value, $data)
    {
        $status = isset($data['pay_status']) ? $data['pay_status'] : 0;
        $statusArr = [
            0 => '未支付',
            1 => '已支付',
            2 => '已退款'
        ];
        return isset($statusArr[$status]) ? $statusArr[$status] : '';
    }
    
    /**
     * 退款状态文本
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        $status = isset($data['refund_status']) ? $data['refund_status'] : 0;
        $statusArr = [
            0 => '未退款',
            1 => '退款中',
            2 => '已退款'
        ];
        return isset($statusArr[$status]) ? $statusArr[$status] : '';
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return 'NO' . date('YmdHis') . rand(1000, 9999);
    }
}

