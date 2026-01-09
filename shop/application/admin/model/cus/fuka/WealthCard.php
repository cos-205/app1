<?php

namespace app\admin\model\cus\fuka;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;
use app\admin\model\cus\user\User;

/**
 * 财富金卡模型
 */
class WealthCard extends Common
{
    use SoftDelete;

    protected $name = 'fuka_wealth_card';
    
    protected $deleteTime = 'deletetime';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_balance' => 'float',
        'flow_status' => 'integer',
        'apply_status' => 'integer',
        'apply_time' => 'timestamp',
        'audit_time' => 'timestamp',
        'make_time' => 'timestamp',
        'send_time' => 'timestamp',
        'receive_time' => 'timestamp',
        'is_active' => 'integer',
        'active_time' => 'timestamp',
        'large_pay_limit' => 'float',
        'is_open_large_pay' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
        'deletetime' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'flow_status_text',
        'apply_status_text',
        'is_active_text',
    ];

    /**
     * 流程状态列表
     */
    public function getFlowStatusList()
    {
        return [
            0 => '未开始',
            1 => '步骤1-邀请好友',
            2 => '步骤2-实名认证',
            3 => '步骤3-填写地址',
            4 => '步骤4-申领审核',
            5 => '步骤5-制卡中',
            6 => '步骤6-已邮寄',
            7 => '步骤7-已签收',
            8 => '步骤8-激活',
        ];
    }

    /**
     * 申领状态列表
     */
    public function getApplyStatusList()
    {
        return [
            0 => '未申请',
            1 => '审核中',
            2 => '审核通过',
            3 => '审核失败',
            4 => '定制中',
            5 => '已邮寄',
            6 => '已签收',
        ];
    }

    /**
     * 流程状态文本
     */
    public function getFlowStatusTextAttr($value, $data)
    {
        $list = $this->getFlowStatusList();
        return isset($list[$data['flow_status']]) ? $list[$data['flow_status']] : '未知';
    }

    /**
     * 申领状态文本
     */
    public function getApplyStatusTextAttr($value, $data)
    {
        $list = $this->getApplyStatusList();
        return isset($list[$data['apply_status']]) ? $list[$data['apply_status']] : '未知';
    }

    /**
     * 激活状态文本
     */
    public function getIsActiveTextAttr($value, $data)
    {
        return $data['is_active'] == 1 ? '已激活' : '未激活';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\admin\model\cus\user\User', 'user_id');
    }

    /**
     * 关联流程记录
     */
    public function flowLogs()
    {
        return $this->hasMany('app\common\model\fuka\CardFlowLog', 'card_id');
    }

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany('app\common\model\fuka\CardOrder', 'card_id');
    }

    /**
     * 关联余额变动记录
     */
    public function balanceLogs()
    {
        return $this->hasMany('app\common\model\fuka\CardBalanceLog', 'card_id');
    }
}
