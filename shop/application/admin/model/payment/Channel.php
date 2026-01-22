<?php

namespace app\admin\model\payment;

use think\Model;


class Channel extends Model
{

    

    

    // 表名
    protected $name = 'payment_channel';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'channel_type_text',
        'status_text'
    ];
    

    
    public function getChannelTypeList()
    {
        return ['wechat' => __('Channel_type wechat'), 'alipay' => __('Channel_type alipay'), 'bank' => __('Channel_type bank'), 'other' => __('Channel_type other')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getChannelTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['channel_type'] ?? '');
        $list = $this->getChannelTypeList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
