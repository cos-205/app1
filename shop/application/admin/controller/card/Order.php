<?php

namespace app\admin\controller\card;

use app\common\controller\Backend;

/**
 * 金卡支付订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\card\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\card\Order;
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("refundStatusList", $this->model->getRefundStatusList());
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
            ->with(['user', 'wealthCard'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        // 处理每条记录，添加格式化数据
        foreach ($list as $k => $v) {
            // 格式化金额字段
            if (isset($v['amount'])) {
                $v['amount_text'] = '¥' . number_format($v['amount'], 2);
            }
            if (isset($v['refund_amount'])) {
                $v['refund_amount_text'] = '¥' . number_format($v['refund_amount'], 2);
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

}
