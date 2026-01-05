<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 邀请记录模型
 */
class FukaUserInvite extends Model
{
    protected $name = 'fuka_user_invite';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'inviter_id' => 'integer',
        'invitee_id' => 'integer',
        'is_realname' => 'integer',
        'realname_time' => 'timestamp',
        'is_valid' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联邀请人
     */
    public function inviter()
    {
        return $this->belongsTo('app\common\model\User', 'inviter_id');
    }

    /**
     * 关联被邀请人
     */
    public function invitee()
    {
        return $this->belongsTo('app\common\model\User', 'invitee_id');
    }
}

