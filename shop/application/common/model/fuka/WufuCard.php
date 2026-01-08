<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 五福卡记录模型
 */
class WufuCard extends Model
{
    protected $name = 'fuka_wufu_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'is_used' => 'integer',
        'exchange_id' => 'integer',
        'used_time' => 'timestamp',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];
    
    // JSON字段（ThinkPHP会自动处理JSON编码/解码）
    protected $json = ['fuka_ids'];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }

    /**
     * 关联兑换记录
     */
    public function exchangeRecord()
    {
        return $this->belongsTo('app\common\model\fuka\ExchangeRecord', 'exchange_id');
    }

    /**
     * 查询作用域：未使用的五福卡
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', 0);
    }

    /**
     * 查询作用域：已使用的五福卡
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', 1);
    }

    /**
     * 获取使用的福卡ID列表
     */
    public function getFukaIdsArray()
    {
        if (empty($this->fuka_ids)) {
            return [];
        }
        // 如果已经是数组（ThinkPHP自动解码），直接返回
        if (is_array($this->fuka_ids)) {
            return $this->fuka_ids;
        }
        // 如果是JSON字符串，解码
        return json_decode($this->fuka_ids, true) ?: [];
    }

    /**
     * 设置使用的福卡ID列表
     */
    public function setFukaIdsArray($ids)
    {
        // 确保是数组
        if (!is_array($ids)) {
            throw new \Exception('福卡ID必须是数组');
        }
        
        // 转换为整数并过滤无效值（保留0值，因为可能是有效的ID）
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) {
            return $id > 0; // 只保留大于0的ID
        });
        
        // 重新索引数组
        $ids = array_values($ids);
        
        // 验证数量
        if (count($ids) != 5) {
            throw new \Exception('五福卡必须包含5张福卡ID，当前数量：' . count($ids));
        }
        
        // 验证ID不重复
        if (count(array_unique($ids)) != 5) {
            throw new \Exception('福卡ID不能重复');
        }
        
        // ThinkPHP会自动将数组编码为JSON（因为设置了$json属性）
        $this->fuka_ids = $ids;
        return $this;
    }
}

