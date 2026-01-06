<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 兑换记录模型
 */
class ExchangeRecord extends Model
{
    protected $name = 'fuka_exchange_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'prize_id' => 'integer',
        'prize_type' => 'integer',
        'fuka_set_count' => 'integer',
        'fuka_ids' => 'json',
        'exchange_status' => 'integer',
        'exchange_time' => 'timestamp',
        'audit_time' => 'timestamp',
        'logistics_id' => 'integer',
        'is_get_pickup_code' => 'integer',
        'pickup_code_fee' => 'float',
        'pay_pickup_time' => 'timestamp',
        'is_get_certificate' => 'integer',
        'certificate_fee' => 'float',
        'pay_certificate_time' => 'timestamp',
        'complete_time' => 'timestamp',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联奖品
     */
    public function prize()
    {
        return $this->belongsTo('app\common\model\fuka\FukaPrize', 'prize_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}

