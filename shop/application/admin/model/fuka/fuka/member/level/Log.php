<?php

namespace app\admin\model\fuka\fuka\member\level;

use think\Model;
use traits\model\SoftDelete;

class Log extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_member_level_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'old_level_text',
        'new_level_text',
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

    
    public function getOldLevelList()
    {
        return ['0' => __('Old_level 0'), '1' => __('Old_level 1'), '2' => __('Old_level 2'), '3' => __('Old_level 3'), '4' => __('Old_level 4'), '5' => __('Old_level 5')];
    }

    public function getNewLevelList()
    {
        return ['0' => __('New_level 0'), '1' => __('New_level 1'), '2' => __('New_level 2'), '3' => __('New_level 3'), '4' => __('New_level 4'), '5' => __('New_level 5')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getOldLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['old_level'] ?? '');
        $list = $this->getOldLevelList();
        return $list[$value] ?? '';
    }


    public function getNewLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['new_level'] ?? '');
        $list = $this->getNewLevelList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
