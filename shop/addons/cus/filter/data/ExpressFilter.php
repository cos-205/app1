<?php

namespace addons\cus\filter\data;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 快递公司筛选
 */
class ExpressFilter extends BaseFilter
{
    protected $keywordFields = ['id','name','code'];
}
