<?php

namespace app\admin\model\cus\activity;

use app\admin\model\cus\Common;
use app\admin\model\cus\user\User;
use app\admin\model\cus\goods\Goods;
use app\admin\model\cus\order\Order;
use app\admin\model\cus\order\OrderItem;

class GrouponLog extends Common
{
    protected $name = 'cus_activity_groupon_log';

    public function getNicknameAttr($value, $data)
    {
        $value = $value ?: ($data['nickname'] ?? '');
        return $value ? string_hide($value, 2) : $value;
    }


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }


    public function groupon()
    {
        return $this->belongsTo(Groupon::class, 'groupon_id');
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }


    public function orderItem()
    {
        return $this->hasOne(OrderItem::class, 'order_id', 'order_id');
    }
}
