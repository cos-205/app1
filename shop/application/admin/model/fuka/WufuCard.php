<?php

namespace app\admin\model\fuka;

use think\Model;


class WufuCard extends Model
{

    

    

    // 表名
    protected $name = 'fuka_wufu_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_used_text',
        'used_time_text'
    ];
    

    
    public function getIsUsedList()
    {
        return ['1' => __('Is_used 1'), '0' => __('Is_used 0')];
    }


    public function getIsUsedTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_used'] ?? '');
        $list = $this->getIsUsedList();
        return $list[$value] ?? '';
    }


    public function getUsedTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['used_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setUsedTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
