<?php

namespace app\admin\model\cus\notification;

use app\admin\model\cus\Common;

class Config extends Common
{

    protected $name = 'cus_notification_config';

    protected $type = [
        'content' => 'json',
    ];


    protected $append = [
        'status_text',
    ];
}
