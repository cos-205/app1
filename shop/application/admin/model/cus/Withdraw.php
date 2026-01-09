<?php

namespace app\admin\model\cus;

use app\admin\model\cus\Common;
use app\admin\model\cus\user\User;

class Withdraw extends Common
{
    protected $name = 'cus_withdraw';
    protected $type = [
        'withdraw_info' => 'json'
    ];
    // 追加属性
    protected $append = [
        'status_text',
        'charge_rate_format',
        'withdraw_info_hidden',
        'withdraw_type_text',
        'wechat_transfer_state_text',
    ];

    // 微信商家转账状态
    const WECHAT_TRANSFER_STATE = [
        'ACCEPTED' => '单据已受理，请稍等',
        'PROCESSING' => '单据处理中，请稍等',
        'SUCCESS' => '转账成功',
        'FAIL' => '转账失败',
        'CANCELING' => '单据撤销中',
        'CANCELLED' => '单据已撤销',
        'WAIT_USER_CONFIRM' => '待收款用户确认',
        'TRANSFERING' => '转账中',
        'NOT_FOUND' => '未申请微信提现',
    ];

    // 可以安全退还佣金的状态
    const CAN_CANCEL_STATE = [
        'FAIL',
        'WAIT_USER_CONFIRM',
        'CANCELLED'
    ];

    public function statusList()
    {
        return [
            -3 => '撤销提现',
            -2 => '提现失败',
            -1 => '已拒绝',
            0 => '待审核',
            1 => '处理中',
            2 => '已处理'
        ];
    }


    public function withdrawTypeList()
    {
        return [
            'wechat' => '微信零钱',
            'alipay' => '支付包账户',
            'bank' => '银行卡',
        ];
    }

    /**
     * 类型获取器
     */
    public function getWithdrawTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['withdraw_type'] ?? null);

        $list = $this->withdrawTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getWechatTransferStateTextAttr($value, $data)
    {
        $value = $value ?: ($data['wechat_transfer_state'] ?? null);

        $list = self::WECHAT_TRANSFER_STATE;
        return isset($list[$value]) ? $list[$value] : $value;
    }



    public function getChargeRateFormatAttr($value, $data)
    {
        $value = $value ?: ($data['charge_rate'] ?? null);

        return bcmul((string)$value, '100', 1) . '%';
    }

    public function getWithdrawInfoHiddenAttr($value, $data)
    {
        $withdraw_info = $value ?: ($this->withdraw_info ?? null);

        foreach ($withdraw_info as $key => &$info) {
            if (in_array($key, ['微信用户', '真实姓名'])) {
                $info = string_hide($info, 2);
            } elseif (in_array($key, ['银行卡号', '支付宝账户', '微信ID'])) {
                $info = account_hide($info);
            }
        }

        return $withdraw_info;
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id, nickname, avatar, total_consume');
    }
}
