<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 会员升级记录模型
 */
class FukaMemberLevelLog extends Model
{
    protected $name = 'fuka_member_level_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'old_level' => 'integer',
        'new_level' => 'integer',
        'invite_count' => 'integer',
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

