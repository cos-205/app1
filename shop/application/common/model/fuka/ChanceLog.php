<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 集福机会记录模型
 */
class ChanceLog extends Model
{
    protected $name = 'fuka_chance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'change_type' => 'integer',
        'change_count' => 'integer',
        'before_count' => 'integer',
        'after_count' => 'integer',
        'source_type' => 'integer',
        'source_id' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}

