<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 集福排行榜模型
 */
class Rank extends Model
{
    protected $name = 'fuka_rank';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'fuka_count' => 'integer',
        'rank' => 'integer',
        'update_time' => 'timestamp',
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

