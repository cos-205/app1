<?php

namespace app\admin\model\fuka\fuka;

use think\Model;
use traits\model\SoftDelete;

class Prize extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_prize';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'prize_type_text',
        'need_pickup_code_text',
        'need_certificate_text',
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

    
    public function getPrizeTypeList()
    {
        return ['1' => __('Prize_type 1'), '2' => __('Prize_type 2'), '3' => __('Prize_type 3'), '4' => __('Prize_type 4')];
    }

    public function getNeedPickupCodeList()
    {
        return ['1' => __('Need_pickup_code 1'), '0' => __('Need_pickup_code 0')];
    }

    public function getNeedCertificateList()
    {
        return ['1' => __('Need_certificate 1'), '0' => __('Need_certificate 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getPrizeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['prize_type'] ?? '');
        $list = $this->getPrizeTypeList();
        return $list[$value] ?? '';
    }


    public function getNeedPickupCodeTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_pickup_code'] ?? '');
        $list = $this->getNeedPickupCodeList();
        return $list[$value] ?? '';
    }


    public function getNeedCertificateTextAttr($value, $data)
    {
        $value = $value ?: ($data['need_certificate'] ?? '');
        $list = $this->getNeedCertificateList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
