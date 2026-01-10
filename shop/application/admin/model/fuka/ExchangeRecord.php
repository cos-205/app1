<?php

namespace app\admin\model\fuka;

use think\Model;
use traits\model\SoftDelete;

class ExchangeRecord extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'fuka_exchange_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'prize_type_text',
        'exchange_status_text',
        'exchange_time_text',
        'audit_time_text',
        'is_get_pickup_code_text',
        'pay_pickup_time_text',
        'is_get_certificate_text',
        'pay_certificate_time_text',
        'complete_time_text',
        'status_text',
        'user_info',
        'prize_info',
        'used_cards_count'
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

    
    public function getPrizeTypeList()
    {
        return ['1' => __('Prize_type 1'), '2' => __('Prize_type 2'), '3' => __('Prize_type 3'), '4' => __('Prize_type 4')];
    }

    public function getExchangeStatusList()
    {
        return ['0' => __('Exchange_status 0'), '1' => __('Exchange_status 1'), '2' => __('Exchange_status 2'), '3' => __('Exchange_status 3'), '4' => __('Exchange_status 4'), '5' => __('Exchange_status 5'), '6' => __('Exchange_status 6')];
    }

    public function getIsGetPickupCodeList()
    {
        return ['1' => __('Is_get_pickup_code 1'), '0' => __('Is_get_pickup_code 0')];
    }

    public function getIsGetCertificateList()
    {
        return ['1' => __('Is_get_certificate 1'), '0' => __('Is_get_certificate 0')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }


    public function getPrizeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['prize_type'] ?? '');
        $list = $this->getPrizeTypeList();
        return $list[$value] ?? '';
    }


    public function getExchangeStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['exchange_status'] ?? '');
        $list = $this->getExchangeStatusList();
        return $list[$value] ?? '';
    }


    public function getExchangeTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['exchange_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsGetPickupCodeTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_get_pickup_code'] ?? '');
        $list = $this->getIsGetPickupCodeList();
        return $list[$value] ?? '';
    }


    public function getPayPickupTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_pickup_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsGetCertificateTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_get_certificate'] ?? '');
        $list = $this->getIsGetCertificateList();
        return $list[$value] ?? '';
    }


    public function getPayCertificateTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_certificate_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['complete_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    protected function setExchangeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPayPickupTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPayCertificateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompleteTimeAttr($value)
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
     * 关联奖品表
     * @return \think\model\relation\BelongsTo
     */
    public function prize()
    {
        return $this->belongsTo('app\admin\model\fuka\Prize', 'prize_id', 'id')->field('id,name,image,price,type');
    }

    /**
     * 关联使用的五福卡
     * @return \think\model\relation\HasMany
     */
    public function wufuCards()
    {
        return $this->hasMany('app\admin\model\fuka\WufuCard', 'exchange_record_id', 'id')->with(['type']);
    }

    /**
     * 关联物流信息
     * @return \think\model\relation\BelongsTo
     */
    public function logistics()
    {
        return $this->belongsTo('app\admin\model\fuka\Logistics', 'logistics_id', 'id');
    }

    /**
     * 获取用户信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getUserInfoAttr($value, $data)
    {
        // 支持驼峰和下划线命名
        $user = $data['user'] ?? $data['User'] ?? null;
        if ($user) {
            // 如果是模型对象，转换为数组
            if (is_object($user)) {
                $user = $user->toArray();
            }
            $nickname = $user['nickname'] ?? '';
            $mobile = $user['mobile'] ?? '';
            return $nickname ? ($nickname . ($mobile ? ' (' . $mobile . ')' : '')) : ($mobile ?: '');
        }
        return '';
    }

    /**
     * 获取奖品信息（格式化显示）
     * @param $value
     * @param $data
     * @return string
     */
    public function getPrizeInfoAttr($value, $data)
    {
        // 支持驼峰和下划线命名
        $prize = $data['prize'] ?? $data['Prize'] ?? null;
        if ($prize) {
            // 如果是模型对象，转换为数组
            if (is_object($prize)) {
                $prize = $prize->toArray();
            }
            $name = $prize['name'] ?? '';
            $image = $prize['image'] ?? '';
            return $name . ($image ? ' [有图]' : '');
        }
        // 如果没有关联数据，使用prize_name字段
        return $data['prize_name'] ?? '';
    }

    /**
     * 获取使用的五福卡数量
     * @param $value
     * @param $data
     * @return int
     */
    public function getUsedCardsCountAttr($value, $data)
    {
        // 支持驼峰和下划线命名
        $wufuCards = $data['wufu_cards'] ?? $data['wufuCards'] ?? $data['WufuCards'] ?? null;
        if ($wufuCards) {
            // 如果是模型集合，转换为数组
            if (is_object($wufuCards)) {
                $wufuCards = $wufuCards->toArray();
            }
            if (is_array($wufuCards)) {
                return count($wufuCards);
            }
        }
        return 0;
    }

}
