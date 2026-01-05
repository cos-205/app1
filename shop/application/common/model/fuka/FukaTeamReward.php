<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 团队奖励记录模型
 */
class FukaTeamReward extends Model
{
    protected $name = 'fuka_team_reward';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'team_id' => 'integer',
        'leader_id' => 'integer',
        'team_count' => 'integer',
        'reward_type' => 'integer',
        'leader_reward' => 'float',
        'member_reward' => 'float',
        'total_reward' => 'float',
        'is_sent' => 'integer',
        'send_time' => 'timestamp',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联队长
     */
    public function leader()
    {
        return $this->belongsTo('app\common\model\User', 'leader_id');
    }
}

