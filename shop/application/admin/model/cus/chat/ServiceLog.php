<?php

namespace app\admin\model\cus\chat;

use app\admin\model\cus\Common;
use app\admin\model\cus\chat\traits\ChatCommon;

class ServiceLog extends Common
{
    use ChatCommon;

    protected $name = 'cus_chat_service_log';

    protected $append = [
        'room_name'
    ];

    public function chatUser() 
    {
        return $this->belongsTo(User::class, 'chat_user_id');
    }
}
