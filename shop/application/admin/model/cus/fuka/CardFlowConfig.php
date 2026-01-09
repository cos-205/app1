<?php

namespace app\admin\model\cus\fuka;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;

/**
 * 金卡流程配置模型
 */
class CardFlowConfig extends Common
{
    use SoftDelete;

    protected $name = 'fuka_card_flow_config';
    
    protected $deleteTime = 'deletetime';

    protected $type = [
        'id' => 'integer',
        'step' => 'integer',
        'fee_amount' => 'float',
        'is_enabled' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
        'deletetime' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'is_enabled_text',
        'status_text',
    ];

    /**
     * 启用状态文本
     */
    public function getIsEnabledTextAttr($value, $data)
    {
        return $data['is_enabled'] == 1 ? '启用' : '禁用';
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 'normal' ? '正常' : '隐藏';
    }
}
