<?php

namespace app\common\validate\fuka;

use think\Validate;

/**
 * 财富金卡验证器
 */
class WealthCard extends Validate
{
    protected $rule = [
        'step' => 'require|integer|between:1,8',
    ];
    
    protected $message = [
        'step.require' => '请选择流程步骤',
        'step.integer' => '流程步骤无效',
        'step.between' => '流程步骤无效',
    ];
    
    protected $scene = [
        'completeStep' => ['step'],
        'payFee' => ['step'],
    ];
}

