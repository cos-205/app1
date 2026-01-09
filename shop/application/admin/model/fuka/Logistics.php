<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class Logistics extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_logistics';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'order_type_text',
        'logistics_status_text',
        'send_time_text',
        'receive_time_text',
        'status_text',
        'user_info'
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

    
    public function getOrderTypeList()
    {
        return ['1' => __('Order_type 1'), '2' => __('Order_type 2')];
    }

    public function getLogisticsStatusList()
    {
        return ['0' => __('Logistics_status 0'), '1' => __('Logistics_status 1'), '2' => __('Logistics_status 2'), '3' => __('Logistics_status 3'), '4' => __('Logistics_status 4')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getOrderTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['order_type'] ?? '');
        $list = $this->getOrderTypeList();
        return $list[$value] ?? '';
    }


    public function getLogisticsStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['logistics_status'] ?? '');
        $list = $this->getLogisticsStatusList();
        return $list[$value] ?? '';
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


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setSendTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReceiveTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    /**
     * 关联用户表（通过订单关联）
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        // 根据order_type关联不同的订单表获取用户信息
        // 这里先不实现，因为需要根据order_type判断关联哪个表
        return $this->belongsTo('app\admin\model\cus\user\User', 'user_id', 'id')->field('id,username,nickname,mobile,avatar');
    }

    /**
     * 获取用户信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getUserInfoAttr($value, $data)
    {
        if (isset($data['user']) && $data['user']) {
            $user = $data['user'];
            $nickname = $user['nickname'] ?? '';
            $mobile = $user['mobile'] ?? '';
            return $nickname ? ($nickname . ($mobile ? ' (' . $mobile . ')' : '')) : ($mobile ?: '');
        }
        return '';
    }

}
