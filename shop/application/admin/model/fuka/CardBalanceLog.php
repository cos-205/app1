<?php

namespace app\admin\model\fuka;

use think\Model;


class CardBalanceLog extends Model
{

    

    

    // 表名
    protected $name = 'fuka_card_balance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'change_type_text',
        'change_amount_text',
        'balance_text'
    ];
    

    
    public function getChangeTypeList()
    {
        return ['1' => __('Change_type 1'), '2' => __('Change_type 2')];
    }


    public function getChangeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['change_type'] ?? '');
        $list = $this->getChangeTypeList();
        return $list[$value] ?? '';
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
     * 获取变动金额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getChangeAmountTextAttr($value, $data)
    {
        $amount = $data['change_amount'] ?? 0;
        $prefix = $amount >= 0 ? '+' : '';
        return $prefix . '¥' . number_format(abs($amount), 2);
    }

    /**
     * 获取余额格式化显示
     * @param $value
     * @param $data
     * @return string
     */
    public function getBalanceTextAttr($value, $data)
    {
        $balance = $data['balance'] ?? 0;
        return '¥' . number_format($balance, 2);
    }

}
