<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class Chance_log extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_chance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'change_type_text',
        'source_type_text',
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

    
    public function getChangeTypeList()
    {
        return ['1' => __('Change_type 1'), '2' => __('Change_type 2')];
    }

    public function getSourceTypeList()
    {
        return ['1' => __('Source_type 1'), '2' => __('Source_type 2'), '3' => __('Source_type 3'), '4' => __('Source_type 4'), '5' => __('Source_type 5')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getChangeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['change_type'] ?? '');
        $list = $this->getChangeTypeList();
        return $list[$value] ?? '';
    }


    public function getSourceTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['source_type'] ?? '');
        $list = $this->getSourceTypeList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
