<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 金卡流程记录管理
 *
 * @icon fa fa-circle-o
 */
class CardFlowLog extends Backend
{

    /**
     * CardFlowLog模型对象
     * @var \app\admin\model\fuka\CardFlowLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\CardFlowLog;
        $this->view->assign("flowStatusList", $this->model->getFlowStatusList());
        $this->view->assign("isCompletedList", $this->model->getIsCompletedList());
        $this->view->assign("needFeeList", $this->model->getNeedFeeList());
        $this->view->assign("isPayFeeList", $this->model->getIsPayFeeList());
        $this->view->assign("needRefundList", $this->model->getNeedRefundList());
        $this->view->assign("isRefundFeeList", $this->model->getIsRefundFeeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}
