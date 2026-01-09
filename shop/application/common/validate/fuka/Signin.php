<?php

namespace app\common\validate\fuka;

use think\Validate;

/**
 * 签到验证器
 */
class Signin extends Validate
{
    protected $rule = [
        'rule_id' => 'require|integer|gt:0',
    ];
    
    protected $message = [
        'rule_id.require' => '请选择要领取的奖励',
        'rule_id.integer' => '奖励信息无效',
        'rule_id.gt' => '奖励信息无效',
    ];
    
    protected $scene = [
        'receiveReward' => ['rule_id'],
    ];
}

