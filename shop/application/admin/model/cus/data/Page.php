<?php

namespace app\admin\model\cus\data;

use app\admin\model\cus\Common;

class Page extends Common
{

    // 表名
    protected $name = 'cus_data_page';
    
    // 追加属性
    protected $append = [
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'group', 'group')->order('id asc');
    }
}
