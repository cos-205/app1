<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 用户统计模型
 */
class UserStatistics extends Model
{
    protected $name = 'fuka_user_statistics';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'team_id' => 'integer',
        'is_team_leader' => 'integer',
        'total_invite_count' => 'integer',
        'valid_invite_count' => 'integer',
        'total_fuka_count' => 'integer',
        'current_fuka_count' => 'integer',
        'fuka_chance' => 'integer',
        'dividend_money' => 'float',
        'total_dividend' => 'float',
        'last_update_time' => 'timestamp',
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
     * 查询作用域：队长
     */
    public function scopeTeamLeader($query)
    {
        return $query->where('is_team_leader', 1);
    }
}

