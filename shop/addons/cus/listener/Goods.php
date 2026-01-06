<?php

namespace addons\cus\listener;

use addons\cus\library\notify\Notify;
use app\admin\model\cus\Admin;
use app\admin\model\cus\goods\Goods as GoodsModel;

class Goods
{

    public function goodsStockWarning($params)
    {
        $goodsSkuPrice = $params['goodsSkuPrice'];
        $stock_warning = $params['stock_warning'];

        $goods = GoodsModel::get($goodsSkuPrice['goods_id']);

        // 商品库存不足，请及时补货
        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 商品列表补货 和 库存预警补货
                'cus/goods/goods/addstock',
                'cus/goods/stock_warning/addstock',
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\cus\notification\goods\StockWarning([
                    'goods' => $goods,
                    'goodsSkuPrice' => $goodsSkuPrice,
                    'stock_warning' => $stock_warning
                ])
            );
        }
    }
}
