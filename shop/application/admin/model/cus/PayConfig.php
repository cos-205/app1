<?php

namespace app\admin\model\cus;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;

class PayConfig extends Common
{
    use SoftDelete;

    protected $name = 'cus_pay_config';

    protected $deleteTime = 'deletetime';

    protected $type = [
        'params' => 'json',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];

    protected $hidden = [
        'params'
    ];

    public function statusList()
    {
        return [
            'normal' => '正常',
            'disabled' => '禁用',
        ];
    }


    public function typeList()
    {
        return [
            'wechat' => '微信支付',
            'alipay' => '支付宝支付',
        ];
    }
}
