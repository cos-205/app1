<?php

namespace app\admin\controller\cus\decorate;

use app\admin\controller\cus\Common;
use think\Db;
use app\admin\model\cus\decorate\Decorate as DecorateModel;
use app\admin\model\cus\decorate\Page as PageModel;

class Designer extends Common
{

    /**
     * 使用设计师模板
     */
    public function use()
    {
        $params = $this->request->param();
        Db::transaction(function () use ($params) {
            $decorate = DecorateModel::create([
                'name' => $params['name'],
                'type' => $params['type'],
                'memo' => $params['memo'],
                'platform' => $params['platform'],
                'status' => 'disabled'
            ]);
            $pageList = [];
            $params['page'] = json_decode($params['page'], true);
            foreach ($params['page'] as $page) {
                array_push($pageList, [
                    'decorate_id' => $decorate->id,
                    'image' => $page['image'] ?? '',
                    'type' => $page['type'],
                    'page' => json_encode($page['page'], JSON_UNESCAPED_UNICODE)
                ]);
            }

            PageModel::insertAll($pageList);
            $this->downLoadDecorateImages($params['imageList']);
        });
        $this->success();
    }

    private function downLoadDecorateImages($imageList)
    {
        \think\Queue::push('\addons\cus\job\Designer@redeposit', ['imageList' => $imageList], 'cus');
    }
}
