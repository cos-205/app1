<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 分红记录模型
 */
class DividendRecord extends Model
{
    protected $name = 'fuka_dividend_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'member_level' => 'integer',
        'dividend_money' => 'float',
        'send_status' => 'integer',
        'send_time' => 'timestamp',
        'money_log_id' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}

