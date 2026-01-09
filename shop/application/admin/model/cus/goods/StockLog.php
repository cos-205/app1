<?php

namespace app\admin\model\cus\goods;

use app\admin\model\cus\Common;
use app\admin\model\Admin;

class StockLog extends Common
{
    protected $name = 'cus_goods_stock_log';

    protected $append = [
    ];


    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id')->field('id,title,image,is_sku');
    }


    public function skuPrice()
    {
        return $this->belongsTo(SkuPrice::class, 'goods_sku_price_id', 'id');
    }


    public function oper()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

}
