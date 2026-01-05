<?php

namespace app\admin\model\cus;

use app\admin\model\cus\Common;
use app\admin\model\cus\user\User;

class Feedback extends Common
{
    protected $name = 'cus_feedback';

    protected $type = [
        'images' => 'json'
    ];
    
    protected $append = [
        'status_text'
    ];


    /**
     * 类型列表
     *
     * @return array
     */
    public function statusList()
    {
        return ['0' => '待处理', '1' => '已处理'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id, nickname, avatar, mobile');
    }

}
