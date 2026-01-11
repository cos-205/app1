<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 加载初始化
     */
    public function init()
    {

        //组装APP更新数据
        $updateData = [
            'version' => \app\common\model\Config::where('name','version')->value('value'),
            'downloadurl' => \app\common\model\Config::where('name','downloadurl')->value('value'),
            'upgradetext' => \app\common\model\Config::where('name','upgradetext')->value('value'),
            'enforce' => \app\common\model\Config::where('name','enforce')->value('value') ? true : false,
        ];
        $this->success('请求成功', $updateData);
    }   
}
