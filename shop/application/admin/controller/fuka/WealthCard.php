<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 财富金卡管理
 *
 * @icon fa fa-circle-o
 */
class WealthCard extends Backend
{

    /**
     * WealthCard模型对象
     * @var \app\admin\model\fuka\WealthCard
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\WealthCard;
        $this->view->assign("flowStatusList", $this->model->getFlowStatusList());
        $this->view->assign("applyStatusList", $this->model->getApplyStatusList());
        $this->view->assign("isActiveList", $this->model->getIsActiveList());
        $this->view->assign("isOpenLargePayList", $this->model->getIsOpenLargePayList());
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
            ->with(['user'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        // 处理每条记录，添加格式化数据
        foreach ($list as $k => $v) {
            // 用户信息已通过关联加载，可以直接使用
            // 格式化金额显示
            if (isset($v['card_balance'])) {
                $v['card_balance_text'] = '¥' . number_format($v['card_balance'], 2);
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

}
