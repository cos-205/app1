<?php

namespace app\admin\model\cus\user;

use app\admin\model\cus\Common;

class Invoice extends Common
{
    protected $name = 'cus_user_invoice';

    // 追加属性
    protected $append = [
        'type_text',
    ];


    public function typeList()
    {
        return [
            'person' => '个人',
            'company' => '企/事业单位',
        ];
    }

}
