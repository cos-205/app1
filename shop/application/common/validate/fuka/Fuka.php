<?php

namespace app\common\validate\fuka;

use think\Validate;

/**
 * 集福卡验证器
 */
class Fuka extends Validate
{
    protected $rule = [
        'fuka_type_id' => 'require|integer|gt:0',
        'prize_id' => 'require|integer|gt:0',
    ];
    
    protected $message = [
        'fuka_type_id.require' => '请选择福卡类型',
        'fuka_type_id.integer' => '福卡类型无效',
        'fuka_type_id.gt' => '福卡类型无效',
        'prize_id.require' => '请选择要兑换的奖品',
        'prize_id.integer' => '奖品信息无效',
        'prize_id.gt' => '奖品信息无效',
    ];
    
    protected $scene = [
        'useChance' => ['fuka_type_id'],
        'exchange' => ['prize_id'],
    ];
}

