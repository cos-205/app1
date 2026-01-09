<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class Signin_reward_log extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_signin_reward_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'reward_type_text',
        'is_received_text',
        'receive_time_text',
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

    
    public function getRewardTypeList()
    {
        return ['1' => __('Reward_type 1'), '2' => __('Reward_type 2')];
    }

    public function getIsReceivedList()
    {
        return ['1' => __('Is_received 1'), '0' => __('Is_received 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getRewardTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['reward_type'] ?? '');
        $list = $this->getRewardTypeList();
        return $list[$value] ?? '';
    }


    public function getIsReceivedTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_received'] ?? '');
        $list = $this->getIsReceivedList();
        return $list[$value] ?? '';
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

    protected function setReceiveTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
