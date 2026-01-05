<?php

namespace app\admin\model\cus\data;

use app\admin\model\cus\Common;

class Faq extends Common
{

    
    // 表名
    protected $name = 'cus_data_faq';
    

    // 追加属性
    protected $append = [
        'status_text'
    ];
    
}
