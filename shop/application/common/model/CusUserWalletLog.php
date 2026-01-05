<?php

namespace app\common\model;

use think\Model;

/**
 * 用户钱包日志模型
 */
class CusUserWalletLog extends Model
{
    protected $name = 'cus_user_wallet_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'change_money' => 'float',
        'before_money' => 'float',
        'after_money' => 'float',
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

