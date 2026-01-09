<?php

namespace app\admin\model\card;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'card_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'pay_status_text',
        'pay_time_text',
        'refund_status_text',
        'refund_time_text'
    ];
    

    
    public function getPayStatusList()
    {
        return ['0' => __('Pay_status 0'), '1' => __('Pay_status 1'), '2' => __('Pay_status 2')];
    }

    public function getRefundStatusList()
    {
        return ['0' => __('Refund_status 0'), '1' => __('Refund_status 1'), '2' => __('Refund_status 2')];
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_status'] ?? '');
        $list = $this->getPayStatusList();
        return $list[$value] ?? '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRefundStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_status'] ?? '');
        $list = $this->getRefundStatusList();
        return $list[$value] ?? '';
    }


    public function getRefundTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRefundTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
