<?php

namespace app\admin\model\cus;

use app\admin\model\cus\Common;

class Category extends Common
{
    protected $name = 'cus_category';

    // 追加属性
    protected $append = [
        'status_text',
    ];


    public function getChildrenString($category)
    {
        $style = $category->style;
        $string = 'children';
        if (strpos($style, 'second') === 0) {
            $string .= '.children';
        } else if (strpos($style, 'third') === 0) {
            $string .= '.children.children';
        }

        return $string;
    }


    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->normal()->order('weigh', 'desc')->order('id', 'asc');
    }
}
