<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 团队关系模型
 */
class FukaTeamRelation extends Model
{
    protected $name = 'fuka_team_relation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'team_id' => 'integer',
        'leader_id' => 'integer',
        'user_id' => 'integer',
        'level' => 'integer',
        'parent_id' => 'integer',
        'is_realname' => 'integer',
        'join_time' => 'timestamp',
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

    /**
     * 关联队长
     */
    public function leader()
    {
        return $this->belongsTo('app\common\model\User', 'leader_id');
    }
}

