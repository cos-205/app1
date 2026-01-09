<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class CardFlowConfig extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_card_flow_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'need_fee_text',
        'need_refund_text',
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

    
    public function getNeedFeeList()
    {
        return ['1' => __('Need_fee 1'), '0' => __('Need_fee 0')];
    }

    public function getNeedRefundList()
    {
        return ['1' => __('Need_refund 1'), '0' => __('Need_refund 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getNeedFeeTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_fee'] ?? '');
        $list = $this->getNeedFeeList();
        return $list[$value] ?? '';
    }


    public function getNeedRefundTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_refund'] ?? '');
        $list = $this->getNeedRefundList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
