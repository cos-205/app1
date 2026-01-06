<?php

namespace addons\cus\filter\chat;

use addons\cus\filter\BaseFilter;

/**
 * 猜你想问筛选
 */
class QuestionFilter extends BaseFilter
{
    protected $keywordFields = ['id', 'title'];
}
