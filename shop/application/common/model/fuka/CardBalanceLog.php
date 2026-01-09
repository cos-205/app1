<?php

namespace app\common\model\fuka;

use think\Model;

/**
 * 金卡余额变动日志模型
 */
class CardBalanceLog extends Model
{
    protected $name = 'fuka_card_balance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'card_id' => 'integer',
        'change_type' => 'integer',
        'change_money' => 'float',
        'before_balance' => 'float',
        'after_balance' => 'float',
        'source_id' => 'integer',
        'createtime' => 'timestamp',
        'updatetime' => 'timestamp',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }

    /**
     * 关联金卡
     */
    public function card()
    {
        return $this->belongsTo('app\common\model\fuka\WealthCard', 'card_id');
    }

    /**
     * 变动类型文字
     */
    public function getChangeTypeTextAttr($value, $data)
    {
        $status = [1 => '增加', 2 => '减少'];
        return $status[$data['change_type']] ?? '';
    }

    /**
     * 来源类型文字
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
        return $types[$data['source_type']] ?? '其他';
    }
}

