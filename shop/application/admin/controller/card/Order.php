<?php

namespace app\admin\controller\card;

use app\common\controller\Backend;
use fast\Date;

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

    /**
     * 订单统计
     */
    public function statistics()
    {
        // 获取筛选参数
        $startTime = $this->request->get('start_time', '');
        $endTime = $this->request->get('end_time', '');
        $payStatus = $this->request->get('pay_status', '');
        $payType = $this->request->get('pay_type', '');
        
        // 构建查询条件
        $where = [];
        if ($startTime) {
            $where['createtime'] = ['>=', strtotime($startTime)];
        }
        if ($endTime) {
            $where['createtime'] = ['<=', strtotime($endTime . ' 23:59:59')];
        }
        if ($startTime && $endTime) {
            $where['createtime'] = ['between', [strtotime($startTime), strtotime($endTime . ' 23:59:59')]];
        }
        if ($payStatus !== '') {
            $where['pay_status'] = $payStatus;
        }
        if ($payType) {
            $where['pay_type'] = $payType;
        }
        
        // 今日时间范围
        $todayStart = Date::unixtime('day', 0);
        $todayEnd = Date::unixtime('day', 0, 'end');
        
        // 基础统计
        $totalAmount = $this->model->where($where)->where('pay_status', 1)->sum('amount') ?: 0;
        $totalCount = $this->model->where($where)->count() ?: 0;
        
        // 今日统计
        $todayWhere = $where;
        $todayWhere['createtime'] = ['between', [$todayStart, $todayEnd]];
        $todayAmount = $this->model->where($todayWhere)->where('pay_status', 1)->sum('amount') ?: 0;
        $todayCount = $this->model->where($todayWhere)->count() ?: 0;
        
        // 支付状态统计
        $successWhere = $where;
        $successWhere['pay_status'] = 1;
        $successAmount = $this->model->where($successWhere)->sum('amount') ?: 0;
        $successCount = $this->model->where($successWhere)->count() ?: 0;
        
        $failWhere = $where;
        $failWhere['pay_status'] = 0;
        $failAmount = $this->model->where($failWhere)->sum('amount') ?: 0;
        $failCount = $this->model->where($failWhere)->count() ?: 0;
        
        $refundWhere = $where;
        $refundWhere['pay_status'] = 2;
        $refundAmount = $this->model->where($refundWhere)->sum('amount') ?: 0;
        $refundCount = $this->model->where($refundWhere)->count() ?: 0;
        
        // 金额分组统计
        $amountGroupWhere = $where;
        $amountGroupWhere['pay_status'] = 1;
        $amountGroup = $this->model
            ->where($amountGroupWhere)
            ->field('amount, COUNT(*) as count, SUM(amount) as total_amount')
            ->group('amount')
            ->order('amount', 'asc')
            ->select();
        
        // 渠道统计
        $channelStatsWhere = $where;
        $channelStatsWhere['pay_status'] = 1;
        $channelStats = $this->model
            ->where($channelStatsWhere)
            ->field('pay_type, COUNT(*) as count, SUM(amount) as total_amount')
            ->group('pay_type')
            ->select();
        
        // 准备视图数据
        $this->view->assign([
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'today_amount' => $todayAmount,
            'today_count' => $todayCount,
            'success_amount' => $successAmount,
            'success_count' => $successCount,
            'fail_amount' => $failAmount,
            'fail_count' => $failCount,
            'refund_amount' => $refundAmount,
            'refund_count' => $refundCount,
            'amount_group' => $amountGroup,
            'channel_stats' => $channelStats,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'pay_status' => $payStatus,
            'pay_type' => $payType,
        ]);
        
        return $this->view->fetch();
    }

}
