<?php

namespace app\admin\model\cus\wechat;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;

class Material extends Common
{
    use SoftDelete;
    protected $deleteTime = 'deletetime';

    protected $name = 'cus_wechat_material';

    protected $type = [
        'content' => 'json',
    ];

    // 追加属性
    protected $append = [
        'type_text'
    ];

    public function typeList()
    {
        return ['text' => '文字', 'link' => '链接'];
    }
}
