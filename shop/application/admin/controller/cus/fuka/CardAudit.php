<?php

namespace app\admin\controller\cus\fuka;

use app\common\controller\Backend;
use app\common\model\fuka\CardFlowLog;
use app\common\model\fuka\WealthCard;
use app\common\model\fuka\CardOrder;
use app\common\model\User;
use think\Db;
use think\Exception;

/**
 * 金卡流程审核管理
 */
class CardAudit extends Backend
{
    protected $model = null;
    protected $noNeedRight = [''];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CardFlowLog();
    }

    /**
     * 待审核列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // 只查询待审核的记录（flow_status=2）
            $list = $this->model
                ->with(['user', 'card', 'order'])
                ->where('flow_status', 2)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = [
                'total' => $list->total(),
                'rows' => $list->items()
            ];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 审核详情
     */
    public function detail($ids = null)
    {
        $row = $this->model
            ->with(['user', 'card', 'order'])
            ->find($ids);
        
        if (!$row) {
            $this->error('记录不存在');
        }

        // 解析额外数据
        if ($row->extra_data) {
            $row->extra_data_json = json_decode($row->extra_data, true);
        }

        // 获取订单详情
        if ($row->order_id) {
            $row->order_detail = CardOrder::find($row->order_id);
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 审核通过
     */
    public function approve($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $ids = $this->request->post('ids');
        $remark = $this->request->post('remark', '');

        if (!$ids) {
            $this->error('参数错误');
        }

        $ids = explode(',', $ids);
        
        Db::startTrans();
        try {
            foreach ($ids as $id) {
                $flowLog = CardFlowLog::find($id);
                if (!$flowLog) {
                    throw new Exception('流程记录不存在: ' . $id);
                }

                if ($flowLog->flow_status != 2) {
                    throw new Exception('该记录不是待审核状态');
                }

                // 更新流程记录状态为已完成
                $flowLog->flow_status = 3; // 已完成
                $flowLog->audit_time = time();
                $flowLog->auditor_id = $this->auth->id;
                $flowLog->audit_remark = $remark;
                $flowLog->save();

                // 更新金卡流程状态
                $card = WealthCard::find($flowLog->card_id);
                if ($card) {
                    // 检查是否所有步骤都完成
                    $completedCount = CardFlowLog::where('user_id', $flowLog->user_id)
                        ->where('card_id', $flowLog->card_id)
                        ->where('flow_status', 3)
                        ->count();
                    
                    if ($completedCount >= 9) {
                        // 所有9个步骤都完成，标记金卡为已激活
                        $card->status = 'active';
                        $card->active_time = time();
                    }
                    $card->save();
                }

                // TODO: 发送通知给用户
                // TODO: 如果所有步骤完成，触发退款流程

                $this->writeLog('审核通过金卡流程', [
                    'id' => $id,
                    'user_id' => $flowLog->user_id,
                    'step' => $flowLog->flow_step,
                    'remark' => $remark
                ]);
            }

            Db::commit();
            $this->success('审核通过');
            
        } catch (Exception $e) {
            Db::rollback();
            $this->error('审核失败: ' . $e->getMessage());
        }
    }

    /**
     * 审核拒绝
     */
    public function reject($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $ids = $this->request->post('ids');
        $reason = $this->request->post('reason', '');

        if (!$ids || !$reason) {
            $this->error('请填写拒绝原因');
        }

        $ids = explode(',', $ids);
        
        Db::startTrans();
        try {
            foreach ($ids as $id) {
                $flowLog = CardFlowLog::find($id);
                if (!$flowLog) {
                    throw new Exception('流程记录不存在: ' . $id);
                }

                if ($flowLog->flow_status != 2) {
                    throw new Exception('该记录不是待审核状态');
                }

                // 更新流程记录状态为审核拒绝
                $flowLog->flow_status = 4; // 审核拒绝
                $flowLog->audit_time = time();
                $flowLog->auditor_id = $this->auth->id;
                $flowLog->audit_remark = $reason;
                $flowLog->save();

                // 标记订单需要退款
                if ($flowLog->order_id) {
                    $order = CardOrder::find($flowLog->order_id);
                    if ($order && $order->pay_status == 1) {
                        // 标记为待退款
                        $order->refund_status = 1; // 退款中
                        $order->save();
                        
                        // TODO: 触发实际退款流程
                    }
                }

                // TODO: 发送通知给用户

                $this->writeLog('拒绝金卡流程审核', [
                    'id' => $id,
                    'user_id' => $flowLog->user_id,
                    'step' => $flowLog->flow_step,
                    'reason' => $reason
                ]);
            }

            Db::commit();
            $this->success('已拒绝');
            
        } catch (Exception $e) {
            Db::rollback();
            $this->error('操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量审核通过
     */
    public function batchApprove()
    {
        return $this->approve();
    }

    /**
     * 批量审核拒绝
     */
    public function batchReject()
    {
        return $this->reject();
    }

    /**
     * 查看用户的所有流程记录
     */
    public function userFlows($user_id = null)
    {
        if (!$user_id) {
            $this->error('参数错误');
        }

        $user = User::find($user_id);
        if (!$user) {
            $this->error('用户不存在');
        }

        $card = WealthCard::where('user_id', $user_id)->find();
        
        $flows = CardFlowLog::where('user_id', $user_id)
            ->with(['order'])
            ->order('flow_step', 'asc')
            ->select();

        $this->view->assign([
            'user' => $user,
            'card' => $card,
            'flows' => $flows
        ]);

        return $this->view->fetch();
    }

    /**
     * 写入操作日志
     */
    private function writeLog($title, $data = [])
    {
        $log = [
            'admin_id' => $this->auth->id,
            'username' => $this->auth->username,
            'title' => $title,
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'ip' => $this->request->ip(),
            'useragent' => $this->request->server('HTTP_USER_AGENT'),
            'createtime' => time()
        ];

        try {
            Db::name('admin_log')->insert($log);
        } catch (\Exception $e) {
            // 日志失败不影响主流程
        }
    }
}

