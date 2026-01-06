<?php

namespace addons\cus\filter\user;

use addons\cus\filter\BaseFilter;
use think\db\Query;

/**
 * 用户管理
 */
class UserFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'username', 'nickname', 'mobile', 'email'];
}
