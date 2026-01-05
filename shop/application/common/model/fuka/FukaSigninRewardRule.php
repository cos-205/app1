<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 签到奖励规则模型
 */
class FukaSigninRewardRule extends Model
{
    protected $name = 'fuka_signin_reward_rule';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'days' => 'integer',
        'reward_type' => 'integer',
        'reward_money' => 'float',
        'reward_chance' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联奖励记录
     */
    public function rewardLogs()
    {
        return $this->hasMany('app\common\model\fuka\FukaSigninRewardLog', 'rule_id');
    }
}

