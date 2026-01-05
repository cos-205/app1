<?php

namespace app\common\model;

use think\Model;

/**
 * 签到记录模型
 */
class CusActivitySignin extends Model
{
    protected $name = 'cus_activity_signin';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}

