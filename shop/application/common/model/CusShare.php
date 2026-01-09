<?php

namespace app\common\model;

use think\Model;

/**
 * 推荐关系模型
 */
class CusShare extends Model
{
    protected $name = 'cus_share';
    
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    
    /**
     * 关联用户（被推荐人）
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 关联推荐人
     */
    public function sharer()
    {
        return $this->belongsTo('User', 'share_id');
    }
    
    /**
     * 查询作用域：指定推荐人的直接下级
     * @param \think\db\Query $query
     * @param int $shareId 推荐人ID
     */
    public function scopeDirectChildren($query, $shareId)
    {
        return $query->where('share_id', $shareId);
    }
    
    /**
     * 查询作用域：获取指定用户的推荐链
     * @param \think\db\Query $query
     * @param int $userId 用户ID
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

