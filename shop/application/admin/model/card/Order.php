<?php

namespace app\admin\model\card;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'card_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'pay_status_text',
        'pay_time_text',
        'refund_status_text',
        'refund_time_text',
        'user_info',
        'order_amount_text',
        'pay_amount_text',
        'refund_amount_text'
    ];
    

    
    public function getPayStatusList()
    {
        return ['0' => __('Pay_status 0'), '1' => __('Pay_status 1'), '2' => __('Pay_status 2')];
    }

    public function getRefundStatusList()
    {
        return ['0' => __('Refund_status 0'), '1' => __('Refund_status 1'), '2' => __('Refund_status 2')];
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_status'] ?? '');
        $list = $this->getPayStatusList();
        return $list[$value] ?? '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRefundStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_status'] ?? '');
        $list = $this->getRefundStatusList();
        return $list[$value] ?? '';
    }


    public function getRefundTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['refund_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRefundTimeAttr($value)
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
     * 关联财富金卡
     * @return \think\model\relation\BelongsTo
     */
    public function wealthCard()
    {
        return $this->belongsTo('app\admin\model\fuka\WealthCard', 'card_id', 'id')->field('id,card_no,holder_name,user_id');
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
     * 获取订单金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderAmountTextAttr($value, $data)
    {
        $amount = $data['order_amount'] ?? 0;
        return '¥' . number_format($amount, 2);
    }

    /**
     * 获取支付金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayAmountTextAttr($value, $data)
    {
        $amount = $data['pay_amount'] ?? 0;
        return '¥' . number_format($amount, 2);
    }

    /**
     * 获取退款金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getRefundAmountTextAttr($value, $data)
    {
        $amount = $data['refund_amount'] ?? 0;
        return '¥' . number_format($amount, 2);
    }

}
