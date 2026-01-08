<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 奖品模型
 */
class Prize extends Model
{
    protected $name = 'fuka_prize';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'prize_type' => 'integer',
        'prize_value' => 'float',
        'need_fuka_set' => 'integer',
        'need_pickup_code' => 'integer',
        'pickup_code_fee' => 'float',
        'need_certificate' => 'integer',
        'certificate_fee' => 'float',
        'stock' => 'integer',
        'exchange_count' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联兑换记录
     */
    public function exchangeRecords()
    {
        return $this->hasMany('app\common\model\fuka\ExchangeRecord', 'prize_id');
    }
    
    /**
     * 获取奖品图片完整URL
     * @param $value
     * @param $data
     * @return string
     */
    public function getImageUrlAttr($value, $data)
    {
        if (empty($data['prize_image'])) {
            return '';
        }
        
        // 如果已经是完整URL，直接返回
        if (strpos($data['prize_image'], 'http') === 0) {
            return $data['prize_image'];
        }
        
        // 拼接CDN或静态资源路径
        $cdnUrl = config('site.cdnurl') ?: '';
        return $cdnUrl . $data['prize_image'];
    }
}

