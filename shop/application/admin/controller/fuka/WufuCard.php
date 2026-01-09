<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 五福卡记录管理
 *
 * @icon fa fa-circle-o
 */
class WufuCard extends Backend
{

    /**
     * WufuCard模型对象
     * @var \app\admin\model\fuka\WufuCard
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\WufuCard;
        $this->view->assign("isUsedList", $this->model->getIsUsedList());
    }



    /**
     * 查看（重写index方法以支持关联查询）
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        
        // 使用with预加载关联数据，避免N+1查询
        $list = $this->model
            ->with(['user', 'type'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

}
