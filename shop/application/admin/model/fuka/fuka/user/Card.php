<?php

namespace app\admin\model\fuka\fuka\user;

use think\Model;
use traits\model\SoftDelete;

class Card extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_user_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'source_type_text',
        'is_used_text',
        'used_time_text',
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

    
    public function getSourceTypeList()
    {
        return ['1' => __('Source_type 1'), '2' => __('Source_type 2'), '3' => __('Source_type 3'), '4' => __('Source_type 4'), '5' => __('Source_type 5'), '6' => __('Source_type 6')];
    }

    public function getIsUsedList()
    {
        return ['1' => __('Is_used 1'), '0' => __('Is_used 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getSourceTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['source_type'] ?? '');
        $list = $this->getSourceTypeList();
        return $list[$value] ?? '';
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


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setUsedTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
