<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 用户协议流程记录模型
 */
class UserAgreementFlow extends Model
{
    protected $name = 'user_agreement_flow';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'step_id' => 'integer',
        'flow_step' => 'integer',
        'status' => 'integer',
        'start_time' => 'timestamp',
        'completed_time' => 'timestamp',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id');
    }
    
    /**
     * 关联金卡流程配置
     */
    public function cardFlowConfig()
    {
        return $this->belongsTo('app\common\model\fuka\CardFlowConfig', 'step_id', 'step');
    }
    
    /**
     * 关联协议流程配置
     */
    public function agreementFlow()
    {
        return $this->belongsTo('app\common\model\fuka\CardAgreementFlow', 'flow_step', 'flow_step')
            ->where('step_id', '=', $this->getData('step_id'));
    }
    
    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        $statusArr = [
            0 => '未开始',
            1 => '进行中',
            2 => '已完成'
        ];
        return isset($statusArr[$status]) ? $statusArr[$status] : '';
    }
}

