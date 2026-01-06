<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 签到奖励领取记录模型
 */
class SigninRewardLog extends Model
{
    protected $name = 'fuka_signin_reward_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'signin_id' => 'integer',
        'rule_id' => 'integer',
        'days' => 'integer',
        'reward_type' => 'integer',
        'reward_money' => 'float',
        'reward_chance' => 'integer',
        'is_received' => 'integer',
        'receive_time' => 'timestamp',
        'wallet_log_id' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联奖励规则
     */
    public function rule()
    {
        return $this->belongsTo('app\common\model\fuka\FukaSigninRewardRule', 'rule_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}

