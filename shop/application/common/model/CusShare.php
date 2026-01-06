<?php

namespace app\common\model;

use think\Model;

class CusShare extends Model
{
    protected $name = 'cus_share';
    
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 关联分享者
     */
    public function sharer()
    {
        return $this->belongsTo('User', 'share_id');
    }
}

