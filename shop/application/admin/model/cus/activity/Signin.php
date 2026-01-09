<?php

namespace app\admin\model\cus\activity;

use app\admin\model\cus\Common;

class Signin extends Common
{

    protected $name = 'cus_activity_signin';

    protected $type = [
        'rules' => 'json'
    ];

    // 追加属性
    protected $append = [
        
    ];

}
