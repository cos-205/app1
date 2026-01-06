<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 福卡类型模型
 */
class Type extends Model
{
    protected $name = 'fuka_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'is_universal' => 'integer',
        'can_buy' => 'integer',
        'buy_price' => 'float',
        'drop_rate' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联用户福卡
     */
    public function userCards()
    {
        return $this->hasMany('app\common\model\fuka\UserCard', 'fuka_type_id');
    }
}

