<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class MemberLevel extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_member_level';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'level_text',
        'can_get_card_text',
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

    
    public function getLevelList()
    {
        return ['0' => __('Level 0'), '1' => __('Level 1'), '2' => __('Level 2'), '3' => __('Level 3'), '4' => __('Level 4'), '5' => __('Level 5')];
    }

    public function getCanGetCardList()
    {
        return ['1' => __('Can_get_card 1'), '0' => __('Can_get_card 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['level'] ?? '');
        $list = $this->getLevelList();
        return $list[$value] ?? '';
    }


    public function getCanGetCardTextAttr($value, $data)
    {
        $value = $value ?: ($data['can_get_card'] ?? '');
        $list = $this->getCanGetCardList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
