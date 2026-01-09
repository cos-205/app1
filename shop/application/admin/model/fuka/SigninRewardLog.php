<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class SigninRewardLog extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_signin_reward_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'reward_type_text',
        'is_received_text',
        'receive_time_text',
        'status_text',
        'user_info',
        'reward_money_text'
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

    
    public function getRewardTypeList()
    {
        return ['1' => __('Reward_type 1'), '2' => __('Reward_type 2')];
    }

    public function getIsReceivedList()
    {
        return ['1' => __('Is_received 1'), '0' => __('Is_received 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getRewardTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['reward_type'] ?? '');
        $list = $this->getRewardTypeList();
        return $list[$value] ?? '';
    }


    public function getIsReceivedTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_received'] ?? '');
        $list = $this->getIsReceivedList();
        return $list[$value] ?? '';
    }


    public function getReceiveTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['receive_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setReceiveTimeAttr($value)
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
     * 关联签到奖励规则
     * @return \think\model\relation\BelongsTo
     */
    public function signinRewardRule()
    {
        return $this->belongsTo('app\admin\model\fuka\SigninRewardRule', 'rule_id', 'id')->field('id,name,days,reward_type,reward_money,reward_chance');
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
     * 获取奖励金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getRewardMoneyTextAttr($value, $data)
    {
        $money = $data['reward_money'] ?? 0;
        return '¥' . number_format($money, 2);
    }

}
