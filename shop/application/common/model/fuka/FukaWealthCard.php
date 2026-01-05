<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 财富金卡模型
 */
class FukaWealthCard extends Model
{
    protected $name = 'fuka_wealth_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_balance' => 'float',
        'flow_status' => 'integer',
        'apply_status' => 'integer',
        'apply_time' => 'timestamp',
        'audit_time' => 'timestamp',
        'make_time' => 'timestamp',
        'send_time' => 'timestamp',
        'receive_time' => 'timestamp',
        'is_active' => 'integer',
        'active_time' => 'timestamp',
        'large_pay_limit' => 'float',
        'is_open_large_pay' => 'integer',
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
     * 关联流程记录
     */
    public function flowLogs()
    {
        return $this->hasMany('app\common\model\fuka\FukaCardFlowLog', 'card_id');
    }
}

