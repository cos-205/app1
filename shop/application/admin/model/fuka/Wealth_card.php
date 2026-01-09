<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class Wealth_card extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_wealth_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'flow_status_text',
        'apply_status_text',
        'apply_time_text',
        'audit_time_text',
        'make_time_text',
        'send_time_text',
        'receive_time_text',
        'is_active_text',
        'active_time_text',
        'is_open_large_pay_text',
        'status_text'
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
        return ['0' => __('Flow_status 0'), '1-8' => __('Flow_status 1-8')];
    }

    public function getApplyStatusList()
    {
        return ['0' => __('Apply_status 0'), '1' => __('Apply_status 1'), '2' => __('Apply_status 2'), '3' => __('Apply_status 3'), '4' => __('Apply_status 4'), '5' => __('Apply_status 5'), '6' => __('Apply_status 6')];
    }

    public function getIsActiveList()
    {
        return ['1' => __('Is_active 1'), '0' => __('Is_active 0')];
    }

    public function getIsOpenLargePayList()
    {
        return ['1' => __('Is_open_large_pay 1'), '0' => __('Is_open_large_pay 0')];
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


    public function getApplyStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['apply_status'] ?? '');
        $list = $this->getApplyStatusList();
        return $list[$value] ?? '';
    }


    public function getApplyTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['apply_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getMakeTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['make_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getSendTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['send_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getReceiveTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['receive_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsActiveTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_active'] ?? '');
        $list = $this->getIsActiveList();
        return $list[$value] ?? '';
    }


    public function getActiveTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['active_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsOpenLargePayTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_open_large_pay'] ?? '');
        $list = $this->getIsOpenLargePayList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setApplyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setMakeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setSendTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReceiveTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setActiveTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
