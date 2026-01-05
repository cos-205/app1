<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 用户福卡记录模型
 */
class FukaUserCard extends Model
{
    protected $name = 'fuka_user_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'fuka_type_id' => 'integer',
        'source_type' => 'integer',
        'source_id' => 'integer',
        'is_used' => 'integer',
        'exchange_id' => 'integer',
        'used_time' => 'timestamp',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联福卡类型
     */
    public function fukaType()
    {
        return $this->belongsTo('app\common\model\fuka\FukaType', 'fuka_type_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }

    /**
     * 查询作用域：未使用的福卡
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', 0);
    }

    /**
     * 查询作用域：已使用的福卡
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', 1);
    }
}

