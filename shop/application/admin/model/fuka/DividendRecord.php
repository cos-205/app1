<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class DividendRecord extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_dividend_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'member_level_text',
        'send_status_text',
        'send_time_text',
        'status_text',
        'user_info',
        'dividend_money_text'
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

    
    public function getMemberLevelList()
    {
        return ['0' => __('Member_level 0'), '1' => __('Member_level 1'), '2' => __('Member_level 2'), '3' => __('Member_level 3'), '4' => __('Member_level 4'), '5' => __('Member_level 5')];
    }

    public function getSendStatusList()
    {
        return ['0' => __('Send_status 0'), '1' => __('Send_status 1'), '2' => __('Send_status 2')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getMemberLevelTextAttr($value, $data)
    {
        // 优先使用原始数据中的 member_level 字段值
        $memberLevel = $this->getData('member_level');
        if ($memberLevel === null) {
            $memberLevel = $data['member_level'] ?? '';
        }
        // 如果是对象（关联对象），尝试获取 level 属性
        if (is_object($memberLevel)) {
            $memberLevel = $memberLevel->level ?? '';
        }
        $value = $value ?: $memberLevel;
        $list = $this->getMemberLevelList();
        return $list[$value] ?? '';
    }


    public function getSendStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['send_status'] ?? '');
        $list = $this->getSendStatusList();
        return $list[$value] ?? '';
    }


    public function getSendTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['send_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setSendTimeAttr($value)
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
     * 获取分红金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getDividendMoneyTextAttr($value, $data)
    {
        $money = $data['dividend_money'] ?? 0;
        return '¥' . number_format($money, 2);
    }

}
