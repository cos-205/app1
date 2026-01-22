<?php

namespace app\admin\controller\payment;

use app\common\controller\Backend;
use think\Db;

/**
 * 支付凭证审核
 *
 * @icon fa fa-image
 * @remark 审核用户提交的支付凭证
 */
class Screenshot extends Backend
{
    protected $model = null;
    protected $noNeedRight = [];
    
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 商品订单支付凭证列表
     */
    public function orderList()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // 只查询有支付凭证的订单
            $list = Db::name('cus_order')
                ->alias('o')
                ->field('o.*, u.nickname, u.mobile')
                ->join('user u', 'o.user_id = u.id', 'LEFT')
                ->where('o.payment_screenshot', '<>', '')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            $items = $list->items();
            foreach ($items as &$item) {
                // 处理截图地址
                if (!empty($item['payment_screenshot'])) {
                    $item['payment_screenshot_url'] = cdnurl($item['payment_screenshot'], true);
                }
                
                // 状态文本
                $statusText = ['待审核', '审核通过', '审核拒绝'];
                $item['screenshot_status_text'] = $statusText[$item['screenshot_status']] ?? '未知';
                
                // 获取渠道信息
                if ($item['payment_channel_id']) {
                    $channel = Db::name('payment_channel')->where('id', $item['payment_channel_id'])->find();
                    $item['channel_name'] = $channel ? $channel['channel_name'] : '';
                }
            }
            
            $result = [
                "total" => $list->total(),
                "rows" => $items
            ];
            
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 金卡订单支付凭证列表
     */
    public function cardList()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // 只查询有支付凭证的订单
            $list = Db::name('card_order')
                ->alias('o')
                ->field('o.*, u.nickname, u.mobile')
                ->join('user u', 'o.user_id = u.id', 'LEFT')
                ->where('o.payment_screenshot', '<>', '')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            $items = $list->items();
            foreach ($items as &$item) {
                // 处理截图地址
                if (!empty($item['payment_screenshot'])) {
                    $item['payment_screenshot_url'] = cdnurl($item['payment_screenshot'], true);
                }
                
                // 状态文本
                $statusText = ['待审核', '审核通过', '审核拒绝'];
                $item['screenshot_status_text'] = $statusText[$item['screenshot_status']] ?? '未知';
                
                // 获取渠道信息
                if ($item['payment_channel_id']) {
                    $channel = Db::name('payment_channel')->where('id', $item['payment_channel_id'])->find();
                    $item['channel_name'] = $channel ? $channel['channel_name'] : '';
                }
            }
            
            $result = [
                "total" => $list->total(),
                "rows" => $items
            ];
            
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 审核商品订单截图
     */
    public function auditOrder()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id/d', 0);
            $status = $this->request->post('status/d', 0); // 1=通过, 2=拒绝
            $remark = $this->request->post('remark', '');
            
            if (!$id) {
                $this->error('订单ID不能为空');
            }
            
            if (!in_array($status, [1, 2])) {
                $this->error('审核状态错误');
            }
            
            $order = Db::name('cus_order')->where('id', $id)->find();
            if (!$order) {
                $this->error('订单不存在');
            }
            
            if (empty($order['payment_screenshot'])) {
                $this->error('该订单没有支付凭证');
            }
            
            Db::startTrans();
            try {
                // 更新订单审核状态
                Db::name('cus_order')->where('id', $id)->update([
                    'screenshot_status' => $status,
                    'screenshot_audit_time' => time(),
                    'screenshot_audit_remark' => $remark,
                    'updatetime' => time()
                ]);
                
                // 如果审核通过，更新订单为已支付
                if ($status == 1) {
                    Db::name('cus_order')->where('id', $id)->update([
                        'status' => 'paid',
                        'paid_time' => time(),
                    ]);
                }
                
                Db::commit();
                
                output_log('info', [
                    'title' => '支付凭证审核',
                    'admin_id' => $this->auth->id,
                    'order_id' => $id,
                    'status' => $status,
                    'remark' => $remark
                ]);
                
                $this->success('审核成功');
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('审核失败：' . $e->getMessage());
            }
        }
    }
    
    /**
     * 审核金卡订单截图
     */
    public function auditCard()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id/d', 0);
            $status = $this->request->post('status/d', 0); // 1=通过, 2=拒绝
            $remark = $this->request->post('remark', '');
            
            if (!$id) {
                $this->error('订单ID不能为空');
            }
            
            if (!in_array($status, [1, 2])) {
                $this->error('审核状态错误');
            }
            
            $order = Db::name('card_order')->where('id', $id)->find();
            if (!$order) {
                $this->error('订单不存在');
            }
            
            if (empty($order['payment_screenshot'])) {
                $this->error('该订单没有支付凭证');
            }
            
            Db::startTrans();
            try {
                // 更新订单审核状态
                Db::name('card_order')->where('id', $id)->update([
                    'screenshot_status' => $status,
                    'screenshot_audit_time' => time(),
                    'screenshot_audit_remark' => $remark,
                    'updatetime' => time()
                ]);
                
                // 如果审核通过，更新订单为已支付，并执行支付成功的业务逻辑
                if ($status == 1) {
                    Db::name('card_order')->where('id', $id)->update([
                        'pay_status' => 1,
                        'pay_time' => time(),
                        'pay_type' => 'screenshot',
                    ]);
                    
                    // 执行支付成功后的业务逻辑（参考PaymentService）
                    $this->processCardOrderPayment($order);
                }
                
                Db::commit();
                
                output_log('info', [
                    'title' => '金卡支付凭证审核',
                    'admin_id' => $this->auth->id,
                    'order_id' => $id,
                    'status' => $status,
                    'remark' => $remark
                ]);
                
                $this->success('审核成功');
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('审核失败：' . $e->getMessage());
            }
        }
    }
    
    /**
     * 处理金卡订单支付成功后的业务逻辑
     */
    private function processCardOrderPayment($order)
    {
        // 查询流程记录
        $flowLog = Db::name('card_flow_log')
            ->where('user_id', $order['user_id'])
            ->where('card_id', $order['card_id'])
            ->where('flow_step', $order['step_id'])
            ->find();
        
        if (!$flowLog) {
            // 创建流程记录
            Db::name('card_flow_log')->insert([
                'user_id' => $order['user_id'],
                'card_id' => $order['card_id'],
                'flow_step' => $order['step_id'],
                'step_name' => $order['step_name'],
                'need_fee' => 1,
                'fee_amount' => $order['amount'],
                'fee_name' => $order['step_name'] . '费用',
                'order_id' => $order['id'],
                'flow_status' => 3, // 已完成
                'is_pay_fee' => 1,
                'pay_fee_time' => time(),
                'pay_trade_no' => 'screenshot_' . $order['order_no'],
                'complete_time' => time(),
                'createtime' => time(),
                'updatetime' => time(),
            ]);
        } else {
            // 更新流程记录
            Db::name('card_flow_log')
                ->where('id', $flowLog['id'])
                ->update([
                    'order_id' => $order['id'],
                    'flow_status' => 3, // 已完成
                    'is_pay_fee' => 1,
                    'pay_fee_time' => time(),
                    'pay_trade_no' => 'screenshot_' . $order['order_no'],
                    'complete_time' => time(),
                    'updatetime' => time(),
                ]);
        }
        
        output_log('info', [
            'title' => '金卡订单支付成功处理',
            'order_id' => $order['id'],
            'user_id' => $order['user_id'],
            'card_id' => $order['card_id'],
            'step_id' => $order['step_id']
        ]);
    }
}
