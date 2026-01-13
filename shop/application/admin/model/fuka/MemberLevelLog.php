<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class MemberLevelLog extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_member_level_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'old_level_text',
        'new_level_text',
        'status_text',
        'user_info',
        'level_change'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            
        });
    }

    
    public function getOldLevelList()
    {
        return ['0' => __('Old_level 0'), '1' => __('Old_level 1'), '2' => __('Old_level 2'), '3' => __('Old_level 3'), '4' => __('Old_level 4'), '5' => __('Old_level 5')];
    }

    public function getNewLevelList()
    {
        return ['0' => __('New_level 0'), '1' => __('New_level 1'), '2' => __('New_level 2'), '3' => __('New_level 3'), '4' => __('New_level 4'), '5' => __('New_level 5')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getOldLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['old_level'] ?? '');
        $list = $this->getOldLevelList();
        return $list[$value] ?? '';
    }


    public function getNewLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['new_level'] ?? '');
        $list = $this->getNewLevelList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
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
     * 关联旧会员等级
     * @return \think\model\relation\BelongsTo
     */
    public function oldMemberLevel()
    {
        return $this->belongsTo('app\admin\model\fuka\MemberLevel', 'old_level', 'level')->field('id,level,name');
    }

    /**
     * 关联新会员等级
     * @return \think\model\relation\BelongsTo
     */
    public function newMemberLevel()
    {
        return $this->belongsTo('app\admin\model\fuka\MemberLevel', 'new_level', 'level')->field('id,level,name');
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
     * 获取等级变化信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getLevelChangeAttr($value, $data)
    {
        $oldLevel = $data['old_level'] ?? 0;
        $newLevel = $data['new_level'] ?? 0;
        $oldLevelName = $this->getOldLevelList()[$oldLevel] ?? "等级{$oldLevel}";
        $newLevelName = $this->getNewLevelList()[$newLevel] ?? "等级{$newLevel}";
        return "{$oldLevelName} → {$newLevelName}";
    }

}
