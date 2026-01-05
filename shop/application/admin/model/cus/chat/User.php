<?php

namespace app\admin\model\cus\chat;

use app\admin\model\cus\Common;
use app\admin\model\cus\user\User as ShopUser;


class User extends Common
{
    protected $name = 'cus_chat_user';

    // 自动数据类型转换
    protected $type = [
        'last_time' => 'timestamp',
    ];

    public function customer()
    {
        return $this->morphMany(Record::class, ['sender_identify', 'sender_id'], 'customer');
    }

    public function user() 
    {
        return $this->belongsTo(ShopUser::class, 'auth_id');
    }


    public function customerService() 
    {
        return $this->belongsTo(CustomerService::class, 'customer_service_id');
    }
}
