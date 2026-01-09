<?php

namespace app\admin\model\cus\goods;

use app\admin\model\cus\Common;
use app\admin\model\cus\activity\SkuPrice as ActivitySkuPriceModel;
use app\admin\model\cus\app\ScoreSkuPrice;

class SkuPrice extends Common
{
    protected $name = 'cus_goods_sku_price';

    // 追加属性
    protected $append = [
        'goods_sku_text',
        'status_text'
    ];


    public function getGoodsSkuTextAttr($value, $data)
    {
        $arr = $this->attrFormatComma($value, $data, 'goods_sku_text', true);
        return $arr ? array_values(array_filter($arr)) : $arr;
    }



    public function activitySkuPrice() 
    {
        return $this->hasOne(ActivitySkuPriceModel::class, 'goods_sku_price_id', 'id');
    }


    public function scoreSkuPrice()
    {
        return $this->hasOne(ScoreSkuPrice::class, 'goods_sku_price_id', 'id');
    }
}
