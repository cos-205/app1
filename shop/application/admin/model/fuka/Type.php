<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class Type extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'is_universal_text',
        'can_buy_text',
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

    
    public function getIsUniversalList()
    {
        return ['1' => __('Is_universal 1'), '0' => __('Is_universal 0')];
    }

    public function getCanBuyList()
    {
        return ['1' => __('Can_buy 1'), '0' => __('Can_buy 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getIsUniversalTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_universal'] ?? '');
        $list = $this->getIsUniversalList();
        return $list[$value] ?? '';
    }


    public function getCanBuyTextAttr($value, $data)
    {
        $value = $value ?: ($data['can_buy'] ?? '');
        $list = $this->getCanBuyList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
