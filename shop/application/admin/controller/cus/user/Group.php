<?php

namespace app\admin\controller\cus\user;

use think\Db;
use app\admin\controller\cus\Common;
use app\admin\model\UserGroup as GroupModel;

class Group extends Common
{
    protected $noNeedRight = ['select'];

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new GroupModel;
    }


    public function select()
    {
        $list = \app\admin\model\UserGroup::field('id,name,status')->select();
        $this->success('', null, $list);
    }
}
