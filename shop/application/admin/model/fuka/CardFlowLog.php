<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class CardFlowLog extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_card_flow_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'flow_status_text',
        'is_completed_text',
        'complete_time_text',
        'audit_time_text',
        'need_fee_text',
        'is_pay_fee_text',
        'pay_fee_time_text',
        'need_refund_text',
        'is_refund_fee_text',
        'refund_fee_time_text',
        'status_text',
        'fee_text',
        'refund_fee_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    
    public function getFlowStatusList()
    {
        return ['0' => __('Flow_status 0'), '1' => __('Flow_status 1'), '2' => __('Flow_status 2'), '3' => __('Flow_status 3')];
    }

    public function getIsCompletedList()
    {
        return ['1' => __('Is_completed 1'), '0' => __('Is_completed 0')];
    }

    public function getNeedFeeList()
    {
        return ['1' => __('Need_fee 1'), '0' => __('Need_fee 0')];
    }

    public function getIsPayFeeList()
    {
        return ['1' => __('Is_pay_fee 1'), '0' => __('Is_pay_fee 0')];
    }

    public function getNeedRefundList()
    {
        return ['1' => __('Need_refund 1'), '0' => __('Need_refund 0')];
    }

    public function getIsRefundFeeList()
    {
        return ['1' => __('Is_refund_fee 1'), '0' => __('Is_refund_fee 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getFlowStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['flow_status'] ?? '');
        $list = $this->getFlowStatusList();
        return $list[$value] ?? '';
    }


    public function getIsCompletedTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_completed'] ?? '');
        $list = $this->getIsCompletedList();
        return $list[$value] ?? '';
    }


    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['complete_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getNeedFeeTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_fee'] ?? '');
        $list = $this->getNeedFeeList();
        return $list[$value] ?? '';
    }


    public function getIsPayFeeTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_pay_fee'] ?? '');
        $list = $this->getIsPayFeeList();
        return $list[$value] ?? '';
    }


    public function getPayFeeTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_fee_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getNeedRefundTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_refund'] ?? '');
        $list = $this->getNeedRefundList();
        return $list[$value] ?? '';
    }


    public function getIsRefundFeeTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_refund_fee'] ?? '');
        $list = $this->getIsRefundFeeList();
        return $list[$value] ?? '';
    }


    public function getRefundFeeTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_fee_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setCompleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPayFeeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRefundFeeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    /**
     * 关联财富金卡
     * @return \think\model\relation\BelongsTo
     */
    public function wealthCard()
    {
        return $this->belongsTo('app\admin\model\fuka\WealthCard', 'card_id', 'id')->field('id,card_no,holder_name,user_id');
    }

    /**
     * 获取金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getFeeTextAttr($value, $data)
    {
        $fee = $data['fee'] ?? 0;
        return '¥' . number_format($fee, 2);
    }

    /**
     * 获取退款金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getRefundFeeTextAttr($value, $data)
    {
        $refundFee = $data['refund_fee'] ?? 0;
        return '¥' . number_format($refundFee, 2);
    }

}
