<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 协议流程配置模型
 */
class CardAgreementFlow extends Model
{
    protected $name = 'card_agreement_flow';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'step_id' => 'integer',
        'flow_step' => 'integer',
        'sort' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];
    
    /**
     * 关联金卡流程配置
     */
    public function cardFlowConfig()
    {
        return $this->belongsTo('app\common\model\fuka\CardFlowConfig', 'step_id', 'step');
    }
    
    /**
     * 关联用户协议流程记录
     */
    public function userAgreementFlows()
    {
        return $this->hasMany('app\common\model\fuka\UserAgreementFlow', 'flow_step', 'flow_step')
            ->where('step_id', '=', $this->getData('step_id'));
    }
}

