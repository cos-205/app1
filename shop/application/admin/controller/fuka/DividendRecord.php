<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 分红记录管理
 *
 * @icon fa fa-circle-o
 */
class DividendRecord extends Backend
{

    /**
     * DividendRecord模型对象
     * @var \app\admin\model\fuka\DividendRecord
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\DividendRecord;
        $this->view->assign("memberLevelList", $this->model->getMemberLevelList());
        $this->view->assign("sendStatusList", $this->model->getSendStatusList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
            ->with(['user', 'memberLevel'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        // 将模型集合转换为数组，确保关联数据和访问器属性正确序列化
        $rows = [];
        foreach ($list->items() as $item) {
            $rows[] = $item->toArray();
        }
        
        $result = ['total' => $list->total(), 'rows' => $rows];
        return json($result);
    }

}
