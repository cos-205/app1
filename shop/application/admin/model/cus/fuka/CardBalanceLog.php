<?php

namespace app\admin\model\cus\fuka;

use app\admin\model\cus\Common;
use traits\model\SoftDelete;
use app\admin\model\cus\user\User;

/**
 * 金卡余额变动记录模型
 */
class CardBalanceLog extends Common
{
    use SoftDelete;

    protected $name = 'fuka_card_balance_log';
    
    protected $deleteTime = 'deletetime';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_id' => 'integer',
        'change_type' => 'integer',
        'change_money' => 'float',
        'before_balance' => 'float',
        'after_balance' => 'float',
        'source_id' => 'integer',
        'source_type' => 'string',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
        'deletetime' => 'timestamp',
    ];

    // 追加属性
    protected $append = [
        'change_type_text',
        'source_type_text',
    ];

    /**
     * 变动类型文本
     */
    public function getChangeTypeTextAttr($value, $data)
    {
        $status = [1 => '增加', 2 => '减少'];
        return isset($status[$data['change_type']]) ? $status[$data['change_type']] : '未知';
    }

    /**
     * 来源类型文本
     */
    public function getSourceTypeTextAttr($value, $data)
    {
        $types = [
            'signin_reward' => '签到奖励',
            'member_dividend' => '会员分红',
            'team_reward' => '团队奖励',
            'withdraw' => '提现',
            'recharge' => '充值',
            'exchange_refund' => '兑换退款',
            'system_adjust' => '系统调整',
        ];
        return isset($types[$data['source_type']]) ? $types[$data['source_type']] : '其他';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\admin\model\cus\user\User', 'user_id');
    }

    /**
     * 关联金卡
     */
    public function card()
    {
        return $this->belongsTo('app\common\model\fuka\WealthCard', 'card_id');
    }
}
