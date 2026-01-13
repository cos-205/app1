<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class UserCard extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_user_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'source_type_text',
        'is_used_text',
        'used_time_text',
        'status_text',
        'user_info',
        'fuka_type_info'
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

    
    public function getSourceTypeList()
    {
        return ['1' => __('Source_type 1'), '2' => __('Source_type 2'), '3' => __('Source_type 3'), '4' => __('Source_type 4'), '5' => __('Source_type 5'), '6' => __('Source_type 6')];
    }

    public function getIsUsedList()
    {
        return ['1' => __('Is_used 1'), '0' => __('Is_used 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getSourceTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['source_type'] ?? '');
        $list = $this->getSourceTypeList();
        return $list[$value] ?? '';
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


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
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
    public function fukaType()
    {
        return $this->belongsTo('app\admin\model\fuka\Type', 'fuka_type_id', 'id')->field('id,type_name,type_code,icon,is_universal');
    }

    /**
     * 关联五福卡
     * @return \think\model\relation\BelongsTo
     */
    public function wufuCard()
    {
        return $this->belongsTo('app\admin\model\fuka\WufuCard', 'wufu_card_id', 'id')->with(['type']);
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
    public function getFukaTypeInfoAttr($value, $data)
    {
        // 优先使用关联数据
        if (isset($data['fuka_type']) && $data['fuka_type']) {
            $type = $data['fuka_type'];
            $name = $type['type_name'] ?? '';
            $icon = $type['icon'] ?? '';
            return $icon ? ($icon . ' ' . $name) : $name;
        }
        // 如果没有关联数据，使用冗余字段
        if (isset($data['type_name']) && $data['type_name']) {
            return $data['type_name'];
        }
        return '';
    }

}
