<?php

namespace app\admin\model\cus\chat;

use app\admin\model\cus\Common;
use app\admin\model\cus\chat\traits\ChatCommon;

class Question extends Common
{
    use ChatCommon;

    protected $name = 'cus_chat_question';

    protected $append = [
        'status_text',
        'room_name'
    ];

    public function scopeRoomId($query, $room_id)
    {
        return $query->where('room_id', $room_id);
    }
}
