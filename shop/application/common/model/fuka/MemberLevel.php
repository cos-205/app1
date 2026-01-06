<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 会员等级配置模型
 */
class MemberLevel extends Model
{
    protected $name = 'fuka_member_level';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'level' => 'integer',
        'invite_count' => 'integer',
        'can_get_card' => 'integer',
        'dividend_money' => 'float',
        'rights' => 'json',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];
}

