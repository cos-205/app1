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
        'used_time_text',
        'user_info',
        'type_info'
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

    /**
     * 关联用户表
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('app\admin\model\cus\user\User', 'user_id', 'id')->field('id,username,nickname,mobile,avatar');
    }

    /**
     * 关联福卡类型表
     * @return \think\model\relation\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo('app\admin\model\fuka\Type', 'type_id', 'id')->field('id,type_name,type_code,icon,is_universal');
    }

    /**
     * 关联兑换记录
     * @return \think\model\relation\BelongsTo
     */
    public function exchangeRecord()
    {
        return $this->belongsTo('app\admin\model\fuka\ExchangeRecord', 'exchange_record_id', 'id');
    }

    /**
     * 获取用户信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getUserInfoAttr($value, $data)
    {
        if (isset($data['user']) && $data['user']) {
            $user = $data['user'];
            $nickname = $user['nickname'] ?? '';
            $mobile = $user['mobile'] ?? '';
            return $nickname ? ($nickname . ($mobile ? ' (' . $mobile . ')' : '')) : ($mobile ?: '');
        }
        return '';
    }

    /**
     * 获取福卡类型信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeInfoAttr($value, $data)
    {
        if (isset($data['type']) && $data['type']) {
            $type = $data['type'];
            $name = $type['name'] ?? '';
            $icon = $type['icon'] ?? '';
            return $icon ? ($icon . ' ' . $name) : $name;
        }
        return '';
    }

}
