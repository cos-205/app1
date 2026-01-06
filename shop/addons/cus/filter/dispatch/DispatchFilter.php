<?php

namespace addons\cus\filter\dispatch;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 配送方式筛选
 */
class DispatchFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'name'];
}
