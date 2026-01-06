<?php

namespace addons\cus\filter\goods;

use addons\cus\filter\BaseFilter;
use think\db\Query;
use addons\cus\library\Tree;
use addons\cus\facade\Activity as ActivityFacade;
use app\admin\model\cus\activity\Activity;
use app\admin\model\cus\Category;

/**
 * 商品筛选
 */
class GoodsFilter extends BaseFilter
{
    protected $keywordFields = ['title', 'subtitle'];



    /**
     * 查询分类
     *
     * @param Query $query
     * @param string|array $category_ids   查询数据
     * @return void
     */
    public function categoryIds($query, $category_ids)
    {
        $category_ids = $this->getValue($category_ids);
        $categoryIds = explode(',', (string)$category_ids);
        $categoryIds = array_map('intval', $categoryIds);

        $childCategoryIds = [];
        // 查询传入的 分类的所有子分类
        foreach ($categoryIds as $category_id) {
            $currentCategoryIds = (new Tree(function () {
                // 组装搜索条件，排序等
                return (new Category);
            }))->getChildIds($category_id);

            $childCategoryIds = array_merge($childCategoryIds, $currentCategoryIds);
        }

        $categoryIds = array_values(array_filter(array_unique($childCategoryIds)));

        return $query->where(function ($query) use ($categoryIds) {
            // 所有子分类使用 find_in_set or 匹配，亲测速度并不慢
            foreach ($categoryIds as $key => $category_id) {
                $query->whereOrRaw("find_in_set($category_id, category_ids)");
            }
        });
    }



    /**
     * id 查询，并且按照 id 的顺序排列
     *
     * @param Query $query
     * @param string $id
     * @return void
     */
    public function id($query, $id)
    {
        $id = $this->getValue($id);

        $id = array_map('intval', is_array($id) ? $id : explode(',', $id));
        if ($id) {
            $query = $this->query->orderRaw('field(id, ' . implode(',', $id) . ')');      // 按照 ids 里面的 id 进行排序
            $query = $this->query->whereIn('id', $id);
        }

        return $query;
    }


    /**
     * 查询活动
     *
     * @param Query $query
     * @param string|array $activity_type   查询数据
     * @return void
     */
    public function activityType($query, $activity_type) 
    {
        $activity_type = $this->getValue($activity_type);
        $activityTypes = explode(',', $activity_type);

        // 获取活动类型
        $classify = (new Activity)->classifies();

        $goods_ids = [];
        // 获取正在进行中，或者正在预热的活动
        $activities = ActivityFacade::getActivities($activityTypes, ['prehead', 'ing']);

        $is_all = false;
        foreach ($activities as $key => $activity) {
            if (in_array($activity['type'], array_keys($classify['promo']))) {
                if (empty($activity['goods_ids'])) {
                    // 包含促销活动， 并且 goods_ids 为空（所有商品）
                    $is_all = true;
                    break;      // 可以终止了，不加任何搜索条件
                }
            }
            $ids = $activity['goods_ids'] ? explode(',', $activity['goods_ids']) : [];
            $goods_ids = array_merge($goods_ids, $ids);
        }

        $goods_ids = array_filter(array_unique($goods_ids));

        if (!$is_all) {         // 促销可以是所有商品，这时候 goods_ids 是空的，但是要查出来所有的商品, is_all 为 true 时 不加条件
            $query = $query->whereIn('id', $goods_ids);
        }

        return $query;
    }

    
    // -- commission code start --
    public function commissionGoods($query, $goods)
    {
        // 当前表名
        $current_name = $query->getTable();
        return $query->whereExists(function ($query) use ($current_name, $goods) {
            // 子查询表名
            $table_name = (new \app\admin\model\cus\commission\CommissionGoods())->getQuery()->getTable();
            // 子查询条件
            $query->table($table_name)->where($current_name . '.id=' . $table_name . '.goods_id');
            // 拼接查询条件
            foreach ($goods as $field => $value) {
                $query = $this->builderFilter($query, $field, $value);
            }

            return $query;
        });
    }
    // -- commission code end --

}
