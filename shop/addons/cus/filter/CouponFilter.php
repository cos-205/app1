<?php

namespace addons\cus\filter;

use think\db\Query;

/**
 * 优惠券
 */
class CouponFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'name'];
}
