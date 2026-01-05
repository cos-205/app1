<?php

namespace app\admin\model\cus\dispatch;

use app\admin\model\cus\Common;

class DispatchAutosend extends Common
{
    protected $name = 'cus_dispatch_autosend';

    protected $append = [
    ];


    public function getContentAttr($value, $data)
    {
        $value = $value ?: ($data['content'] ?? '');
        $type = $data['type'] ?? 'text';
        if ($type === 'params') {
            $value = json_decode($value, true);
        }
        return $value;
    }


    public function setContentAttr($value, $data)
    {
        $type = $data['type'] ?? 'text';
        if ($type == 'params') {
            $value = json_encode($value);
        }

        return $value;
    }
}
