<?php

namespace app\admin\model\cus\data;

use app\admin\model\cus\Common;

class Area extends Common
{
    protected $autoWriteTimestamp = false;
    
    // 表名
    protected $name = 'cus_data_area';


    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }
}
