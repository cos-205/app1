<?php

namespace addons\cus\filter\data;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 页面筛选
 */
class PageFilter extends BaseFilter
{
    protected $keywordFields = ['name', 'path'];
}
