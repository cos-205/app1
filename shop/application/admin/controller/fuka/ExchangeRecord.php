<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 兑换记录管理
 *
 * @icon fa fa-circle-o
 */
class ExchangeRecord extends Backend
{

    /**
     * ExchangeRecord模型对象
     * @var \app\admin\model\fuka\ExchangeRecord
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\ExchangeRecord;
        $this->view->assign("prizeTypeList", $this->model->getPrizeTypeList());
        $this->view->assign("exchangeStatusList", $this->model->getExchangeStatusList());
        $this->view->assign("isGetPickupCodeList", $this->model->getIsGetPickupCodeList());
        $this->view->assign("isGetCertificateList", $this->model->getIsGetCertificateList());
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
            ->with(['user', 'prize', 'wufuCards'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        // 处理每条记录，添加格式化数据
        foreach ($list as $k => $v) {
            // 格式化费用字段
            if (isset($v['pickup_code_fee'])) {
                $v['pickup_code_fee_text'] = '¥' . number_format($v['pickup_code_fee'], 2);
            }
            if (isset($v['certificate_fee'])) {
                $v['certificate_fee_text'] = '¥' . number_format($v['certificate_fee'], 2);
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

}
