<?php

namespace app\admin\controller\card;

use app\common\controller\Backend;
use fast\Date;

/**
 * 订单管理
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

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage,则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        $list = $this->model
                ->with(['user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

        foreach ($list as $row) {

            $row->getRelation('user')->visible(['id','username','nickname','mobile','avatar']);
        }

        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 订单统计
     */
    public function statistics()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        
        // 如果不是AJAX请求，返回初始视图
        if (false === $this->request->isAjax()) {
            // 为视图分配初始空数据
            $this->view->assign([
                'total_amount' => 0,
                'total_count' => 0,
                'today_amount' => 0,
                'today_count' => 0,
                'success_amount' => 0,
                'success_count' => 0,
                'fail_amount' => 0,
                'fail_count' => 0,
                'refund_amount' => 0,
                'refund_count' => 0,
                'amount_group' => [],
                'channel_stats' => [],
                'start_time' => '',
                'end_time' => '',
                'pay_status' => '',
                'pay_type' => '',
            ]);
            return $this->view->fetch();
        }
        
        // AJAX请求，处理数据并返回JSON
        // 获取筛选参数
        $startTime = $this->request->get('start_time', '');
        $endTime = $this->request->get('end_time', '');
        $payStatus = $this->request->get('pay_status', '');
        $payType = $this->request->get('pay_type', '');
        
        // 参数验证
        if ($startTime) {
            $startTimestamp = strtotime($startTime);
            if ($startTimestamp === false) {
                $this->error(__('Invalid start time'));
            }
        }
        if ($endTime) {
            $endTimestamp = strtotime($endTime);
            if ($endTimestamp === false) {
                $this->error(__('Invalid end time'));
            }
        }
        if ($startTime && $endTime) {
            $startTimestamp = strtotime($startTime);
            $endTimestamp = strtotime($endTime);
            if ($startTimestamp > $endTimestamp) {
                $this->error(__('Start time cannot be greater than end time'));
            }
        }
        
        // 构建查询条件
        $where = [];
        
        // 时间条件处理：优先使用between，否则单独处理
        if ($startTime && $endTime) {
            $startTimestamp = strtotime($startTime);
            $endTimestamp = strtotime($endTime . ' 23:59:59');
            if ($startTimestamp !== false && $endTimestamp !== false) {
                $where['createtime'] = ['between', [$startTimestamp, $endTimestamp]];
            }
        } else {
            if ($startTime) {
                $startTimestamp = strtotime($startTime);
                if ($startTimestamp !== false) {
                    $where['createtime'] = ['>=', $startTimestamp];
                }
            }
            if ($endTime) {
                $endTimestamp = strtotime($endTime . ' 23:59:59');
                if ($endTimestamp !== false) {
                    $where['createtime'] = ['<=', $endTimestamp];
                }
            }
        }
        
        // 支付状态筛选
        $filterPayStatus = '';
        if ($payStatus !== '') {
            $filterPayStatus = intval($payStatus);
            if (in_array($filterPayStatus, [0, 1, 2])) {
                $where['pay_status'] = $filterPayStatus;
            }
        }
        
        // 支付方式筛选
        if ($payType) {
            $where['pay_type'] = $payType;
        }
        
        // 今日时间范围
        $todayStart = Date::unixtime('day', 0);
        $todayEnd = Date::unixtime('day', 0, 'end');
        
        // 基础统计
        // 总金额：只统计已支付订单的金额（不受用户选择的支付状态影响）
        $totalAmountWhere = $where;
        // 如果存在支付状态条件，先移除，然后强制设置为已支付
        if (isset($totalAmountWhere['pay_status'])) {
            unset($totalAmountWhere['pay_status']);
        }
        $totalAmountWhere['pay_status'] = 1; // 强制只统计已支付
        $totalAmount = $this->model->where($totalAmountWhere)->sum('amount') ?: 0;
        
        // 总订单数：统计所有符合条件的订单
        $totalCount = $this->model->where($where)->count() ?: 0;
        
        // 今日统计（独立统计今天的数据，不受用户筛选时间影响）
        $todayWhere = [];
        if ($filterPayStatus !== '' && in_array($filterPayStatus, [0, 1, 2])) {
            $todayWhere['pay_status'] = $filterPayStatus;
        }
        if ($payType) {
            $todayWhere['pay_type'] = $payType;
        }
        $todayWhere['createtime'] = ['between', [$todayStart, $todayEnd]];
        
        // 今日金额：只统计已支付订单的金额
        $todayAmountWhere = $todayWhere;
        // 如果存在支付状态条件，先移除，然后强制设置为已支付
        if (isset($todayAmountWhere['pay_status'])) {
            unset($todayAmountWhere['pay_status']);
        }
        $todayAmountWhere['pay_status'] = 1; // 强制只统计已支付
        $todayAmount = $this->model->where($todayAmountWhere)->sum('amount') ?: 0;
        
        // 今日订单数：统计所有符合条件的订单
        $todayCount = $this->model->where($todayWhere)->count() ?: 0;
        
        // 支付状态统计（先移除pay_status，再设置新的值）
        $successWhere = $where;
        if (isset($successWhere['pay_status'])) {
            unset($successWhere['pay_status']);
        }
        $successWhere['pay_status'] = 1;
        $successAmount = $this->model->where($successWhere)->sum('amount') ?: 0;
        $successCount = $this->model->where($successWhere)->count() ?: 0;
        
        $failWhere = $where;
        if (isset($failWhere['pay_status'])) {
            unset($failWhere['pay_status']);
        }
        $failWhere['pay_status'] = 0;
        $failAmount = $this->model->where($failWhere)->sum('amount') ?: 0;
        $failCount = $this->model->where($failWhere)->count() ?: 0;
        
        $refundWhere = $where;
        if (isset($refundWhere['pay_status'])) {
            unset($refundWhere['pay_status']);
        }
        $refundWhere['pay_status'] = 2;
        $refundAmount = $this->model->where($refundWhere)->sum('amount') ?: 0;
        $refundCount = $this->model->where($refundWhere)->count() ?: 0;
        
        // 金额分组统计
        $amountGroupWhere = $where;
        if (isset($amountGroupWhere['pay_status'])) {
            unset($amountGroupWhere['pay_status']);
        }
        $amountGroupWhere['pay_status'] = 1;
        $amountGroup = $this->model
            ->where($amountGroupWhere)
            ->field('amount, COUNT(*) as count, SUM(amount) as total_amount')
            ->group('amount')
            ->order('amount', 'asc')
            ->select();
        
        // 处理金额分组数据，确保数据格式正确
        $amountGroup = $amountGroup ?: [];
        foreach ($amountGroup as $key => $item) {
            $amountGroup[$key]['amount'] = floatval($item['amount'] ?? 0);
            $amountGroup[$key]['count'] = intval($item['count'] ?? 0);
            $amountGroup[$key]['total_amount'] = floatval($item['total_amount'] ?? 0);
        }
        
        // 渠道统计
        $channelStatsWhere = $where;
        if (isset($channelStatsWhere['pay_status'])) {
            unset($channelStatsWhere['pay_status']);
        }
        $channelStatsWhere['pay_status'] = 1;
        $channelStats = $this->model
            ->where($channelStatsWhere)
            ->field('pay_type, COUNT(*) as count, SUM(amount) as total_amount')
            ->group('pay_type')
            ->select();
        
        // 处理渠道统计数据，确保数据格式正确
        $channelStats = $channelStats ?: [];
        foreach ($channelStats as $key => $item) {
            $channelStats[$key]['count'] = intval($item['count'] ?? 0);
            $channelStats[$key]['total_amount'] = floatval($item['total_amount'] ?? 0);
        }
        
        // 准备返回数据
        $result = [
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'total_amount' => floatval($totalAmount),
                'total_count' => intval($totalCount),
                'today_amount' => floatval($todayAmount),
                'today_count' => intval($todayCount),
                'success_amount' => floatval($successAmount),
                'success_count' => intval($successCount),
                'fail_amount' => floatval($failAmount),
                'fail_count' => intval($failCount),
                'refund_amount' => floatval($refundAmount),
                'refund_count' => intval($refundCount),
                'amount_group' => $amountGroup ?: [],
                'channel_stats' => $channelStats ?: [],
            ]
        ];
        
        return json($result);
    }

}
