<?php

namespace addons\cus\filter\data;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 富文本筛选
 */
class RichtextFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title'];
}
