<?php

namespace app\admin\model\cus\dispatch;

use app\admin\model\cus\Common;

class Dispatch extends Common
{
    protected $name = 'cus_dispatch';

    protected $append = [
        'status_text'
    ];


    public function scopeShow($query)
    {
        return $query->where('status', 'normal');
    }

    public function express()
    {
        return $this->hasMany(DispatchExpress::class, 'dispatch_id')->order('weigh', 'desc')->order('id', 'asc');
    }


    public function autosend()
    {
        return $this->hasOne(DispatchAutosend::class, 'dispatch_id');
    }
}
