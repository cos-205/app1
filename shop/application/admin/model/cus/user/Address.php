<?php

namespace app\admin\model\cus\user;

use app\admin\model\cus\Common;

class Address extends Common
{
    protected $name = 'cus_user_address';

    protected $type = [
        'is_default' => 'boolean'
    ];

    // 追加属性
    protected $append = [];


    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

}
