<?php

namespace app\admin\model\cus\commission;

use app\admin\model\cus\Common;

class Level extends Common
{
    protected $pk = 'level';

    protected $name = 'cus_commission_level';
    
    protected $autoWriteTimestamp = false;

    protected $type = [
        'commission_rules' => 'json',
        'upgrade_rules' => 'json'
    ];

}
