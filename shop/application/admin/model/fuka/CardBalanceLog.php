<?php

namespace app\admin\model\fuka;

use think\Model;


class CardBalanceLog extends Model
{

    

    

    // 表名
    protected $name = 'fuka_card_balance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'change_type_text'
    ];
    

    
    public function getChangeTypeList()
    {
        return ['1' => __('Change_type 1'), '2' => __('Change_type 2')];
    }


    public function getChangeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['change_type'] ?? '');
        $list = $this->getChangeTypeList();
        return $list[$value] ?? '';
    }




}
