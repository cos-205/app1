<?php

namespace app\admin\controller\cus\wechat;

use app\admin\model\cus\Config as CusConfig;
use app\admin\controller\cus\Common;

class Config extends Common
{
    /**
     * 公众号配置
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('wechat.officialAccount', false);
            $configs['server_url'] = request()->domain() . '/addons/cus/wechat.serve';
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('wechat.officialAccount', $this->request->param());
        }

        $this->success('操作成功', null, $configs);
    }
}
