<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 奖品模型
 */
class Prize extends Model
{
    protected $name = 'fuka_prize';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'prize_type' => 'integer',
        'prize_value' => 'float',
        'need_fuka_set' => 'integer',
        'need_pickup_code' => 'integer',
        'pickup_code_fee' => 'float',
        'need_certificate' => 'integer',
        'certificate_fee' => 'float',
        'stock' => 'integer',
        'exchange_count' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联兑换记录
     */
    public function exchangeRecords()
    {
        return $this->hasMany('app\common\model\fuka\FukaExchangeRecord', 'prize_id');
    }
}

