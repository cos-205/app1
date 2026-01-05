<?php

namespace app\admin\controller\cus;

use app\common\controller\Backend;
use addons\cus\controller\traits\Util;
use addons\cus\controller\traits\UnifiedToken;

/**
 * cus 基础控制器
 */
class Common extends Backend
{

    use Util, UnifiedToken;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();

        if (check_env('yansongda', false)) {
            set_addon_config('epay', ['version' => 'v3'], false);
        }

        $is_pro = check_env('commission', false);
        \think\View::share('is_pro', $is_pro);
        $this->assignconfig("is_pro", $is_pro);
    }
}
