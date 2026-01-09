<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 福卡类型模型
 */
class Type extends Model
{
    protected $name = 'fuka_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'is_universal' => 'integer',
        'can_buy' => 'integer',
        'buy_price' => 'float',
        'drop_rate' => 'integer',
        'weigh' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    // 追加字段
    protected $append = ['image_url'];

    /**
     * 关联用户福卡
     */
    public function userCards()
    {
        return $this->hasMany('app\common\model\fuka\UserCard', 'fuka_type_id');
    }

    /**
     * 获取图片完整URL
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getImageUrlAttr($value, $data)
    {
        if (!empty($data['icon'])) {
            return cdnurl($data['icon'], true);
        }
        
        // 如果没有设置icon，返回默认图片路径（根据type_code）
        $defaultImages = [
            'aiguo' => '/static/fuka/爱国.png',
            'youshan' => '/static/fuka/友善.png',
            'jingye' => '/static/fuka/敬业.png',
            'hexie' => '/static/fuka/和谐.png',
            'fuqiang' => '/static/fuka/富强.png',
            'wanneng' => '/static/fuka/万能.png',
        ];
        
        $typeCode = $data['type_code'] ?? '';
        return $defaultImages[$typeCode] ?? '/static/fuka/default.png';
    }
}

