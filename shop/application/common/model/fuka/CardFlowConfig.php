<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 金卡流程状态配置模型
 */
class CardFlowConfig extends Model
{
    protected $name = 'fuka_card_flow_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'step' => 'integer',
        'need_fee' => 'integer',
        'fee_amount' => 'float',
        'need_refund' => 'integer',
        'refund_days' => 'integer',
        'sort_order' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联流程记录
     */
    public function flowLogs()
    {
        return $this->hasMany('app\common\model\fuka\CardFlowLog', 'flow_step', 'step');
    }
}

