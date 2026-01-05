<?php

namespace app\admin\model\cus\chat;

use app\admin\model\cus\Common;
use app\admin\model\cus\chat\Record;
use app\admin\model\cus\chat\CustomerServiceUser;
use app\admin\model\cus\chat\traits\ChatCommon;

class CustomerService extends Common
{
    use ChatCommon;

    protected $name = 'cus_chat_customer_service';

    protected $append = [
        'auth_model',
        'auth_text',
        'status_text',
        'room_name'
    ];

    // 自动数据类型转换
    protected $type = [
        'last_time' => 'timestamp',
    ];


    public function statusList()
    {
        return ['offline' => '离线', 'online' => '在线', 'busy' => '忙碌'];
    }


    public function getAuthModelAttr($value, $data) 
    {
        return $this->customer_service_user['auth_model'] ?? null;
    }

    public function getAuthTextAttr($value, $data)
    {
        return $this->customer_service_user['auth_text'] ?? null;
    }


    public function customerService()
    {
        return $this->morphMany(Record::class, ['sender_identify', 'sender_id'], 'customer_service');
    }

    public function customerServiceUser()
    {
        return $this->HasOne(CustomerServiceUser::class, 'customer_service_id');
    }

    
}
