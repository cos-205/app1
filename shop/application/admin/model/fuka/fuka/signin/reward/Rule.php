<?php

namespace app\admin\model\fuka\fuka\signin\reward;

use think\Model;
use traits\model\SoftDelete;

class Rule extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_signin_reward_rule';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'reward_type_text',
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


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
