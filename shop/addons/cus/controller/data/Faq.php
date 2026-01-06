<?php

namespace addons\cus\controller\data;

use addons\cus\controller\Common;
use app\admin\model\cus\data\Faq as FaqModel;


class Faq extends Common
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $list = FaqModel::where('status', 'normal')->order('id', 'asc')->select();

        $this->success('获取成功', $list);
    }
}
