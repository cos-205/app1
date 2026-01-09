<?php

namespace app\admin\model\cus\goods;

use app\admin\model\cus\Common;

class Sku extends Common
{    
    protected $name = 'cus_goods_sku';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    protected $append = [
    ];

    public function children() 
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
