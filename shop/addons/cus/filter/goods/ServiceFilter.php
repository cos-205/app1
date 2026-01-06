<?php

namespace addons\cus\filter\goods;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 服务保障筛选
 */
class ServiceFilter extends BaseFilter
{
    protected $keywordFields = ['name', 'description'];
}
