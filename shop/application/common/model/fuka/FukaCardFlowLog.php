<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 金卡流程记录模型
 */
class FukaCardFlowLog extends Model
{
    protected $name = 'fuka_card_flow_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_id' => 'integer',
        'flow_step' => 'integer',
        'is_completed' => 'integer',
        'complete_time' => 'timestamp',
        'need_fee' => 'integer',
        'fee_amount' => 'float',
        'is_pay_fee' => 'integer',
        'pay_fee_time' => 'timestamp',
        'money_log_id' => 'integer',
        'need_refund' => 'integer',
        'is_refund_fee' => 'integer',
        'refund_fee_time' => 'timestamp',
        'refund_wallet_log_id' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联金卡
     */
    public function card()
    {
        return $this->belongsTo('app\common\model\fuka\FukaWealthCard', 'card_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}

