<?php

namespace addons\cus\filter\data;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 常见问题筛选
 */
class FaqFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title', 'content'];
}
