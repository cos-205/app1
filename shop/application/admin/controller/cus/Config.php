<?php

namespace app\admin\controller\cus;

use app\admin\model\cus\Config as CusConfig;

class Config extends Common
{
    protected $noNeedRight = ['index', 'platformStatus', 'getPlatformUrl'];

    public function index()
    {
        $configList = [
            [
                'label' => '基本信息',
                'name' => 'cus/config/basic',
                'status' => $this->auth->check('cus/config/basic')
            ],
            [
                'label' => '用户配置',
                'name' => 'cus/config/user',
                'status' => $this->auth->check('cus/config/user')
            ],
            [
                'label' => '平台配置',
                'name' => 'cus/config/platform',
                'status' => $this->auth->check('cus/config/platform')
            ],
            [
                'label' => '订单配置',
                'name' => 'cus/config/order',
                'status' => $this->auth->check('cus/config/order')
            ],
            [
                'label' => '商品配置',
                'name' => 'cus/config/goods',
                'status' => $this->auth->check('cus/config/goods')
            ],
            [
                'label' => '物流配置',
                'name' => 'cus/config/dispatch',
                'status' => $this->auth->check('cus/config/dispatch')
            ],
            [
                'label' => '充值提现',
                'name' => 'cus/config/rechargewithdraw',
                'status' => $this->auth->check('cus/config/rechargewithdraw')
            ],
            [
                'label' => '分销配置',
                'name' => 'cus/config/commission',
                'status' => $this->auth->check('cus/config/commission')
            ],
            [
                'label' => '支付配置',
                'name' => 'cus/pay_config',
                'status' => $this->auth->check('cus/pay_config')
            ],
            [
                'label' => '客服配置',
                'name' => 'cus/config/chat',
                'status' => $this->auth->check('cus/config/chat')
            ],
            [
                'label' => 'Redis配置',
                'name' => 'cus/config/redis',
                'status' => $this->auth->check('cus/config/redis')
            ]
        ];
        $this->assignconfig("configList", $configList);
        return $this->view->fetch();
    }

    /**
     * 基本配置
     */
    public function basic()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.basic', false);
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('shop.basic', $this->request->param());
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 用户默认配置
     */
    public function user()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.user', false);
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('shop.user', $this->request->param());
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 物流配置
     */
    public function dispatch()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.dispatch', false);
            $configs['callback'] = $this->request->domain() . '/addons/cus/order.express/push';
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('shop.dispatch', $this->request->param());
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 平台状态
     */
    public function platformStatus()
    {
        $status = [
            'H5' => CusConfig::getConfigs('shop.platform.H5.status', false),
            'App' => CusConfig::getConfigs('shop.platform.App.status', false),
            'WechatMiniProgram' => CusConfig::getConfigs('shop.platform.WechatMiniProgram.status', false),
            'WechatOfficialAccount' => CusConfig::getConfigs('shop.platform.WechatOfficialAccount.status', false),
        ];

        $this->success('操作成功', null, $status);
    }



    /**
     * 平台配置
     */
    public function platform($platform)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        if (!in_array($platform, ['App', 'H5', 'WechatMiniProgram', 'WechatOfficialAccount'])) {
            $this->error('平台不支持');
        }

        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.platform.' . $platform, false);
        } elseif ('POST' === $this->request->method()) {
            $params = $this->request->param();
            if (!isset($params['share']['methods'])) {
                $params['share']['methods'] = [];
            }
            if (!isset($params['payment']['methods'])) {
                $params['payment']['methods'] = [];
            }
            $configs = CusConfig::setConfigs('shop.platform.' . $platform, $params);
        }
        $this->success('操作成功', null, $configs);
    }


    public function commission()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.commission', false);
        } elseif ('POST' === $this->request->method()) {
            check_env('commission');
            $params = $this->request->param();

            $configs = CusConfig::setConfigs('shop.commission', $params);
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 订单配置
     */
    public function order()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.order', false);
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('shop.order', $this->request->param());
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 商品配置
     */
    public function goods()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.goods', false);
        } elseif ('POST' === $this->request->method()) {

            $configs = CusConfig::setConfigs('shop.goods', $this->request->param());
        }
        $this->success('操作成功', null, $configs);
    }


    /**
     * 充值提现配置
     */
    public function rechargeWithdraw()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('shop.recharge_withdraw', false);
        } elseif ('POST' === $this->request->method()) {
            $params = $this->request->param();
            if (!isset($params['recharge']['methods'])) {
                $params['recharge']['methods'] = [];
            }
            if (!isset($params['recharge']['quick_amounts'])) {
                $params['recharge']['quick_amounts'] = [];
            }
            if (!isset($params['withdraw']['methods'])) {
                $params['withdraw']['methods'] = [];
            }
            $configs = CusConfig::setConfigs('shop.recharge_withdraw', $params);
        }
        $this->success('操作成功', null, $configs);
    }



    /**
     * 客服配置
     */
    public function chat()
    {
        if ('GET' === $this->request->method()) {

            $configs = CusConfig::getConfigs('chat', false);
        } elseif ('POST' === $this->request->method()) {
            $configs = CusConfig::setConfigs('chat', $this->request->param());

            // 存文件
            file_put_contents(
                ROOT_PATH . 'application' . DS . 'extra' . DS . 'chat.php',
                '<?php' . "\n\nreturn " . short_var_export($this->request->param(), true) . ";"
            );
        }
        $this->success('操作成功', null, $configs);
    }



    /**
     * redis 配置
     */
    public function redis()
    {
        if ('GET' === $this->request->method()) {
            $default = [
                'host' => '127.0.0.1',              // redis 主机地址
                'password' => '',                   // redis 密码
                'port' => 6379,                     // redis 端口
                'select' => 1,                      // redis 数据库
                'timeout' => 0,                     // redis 超时时间
                'persistent' => false,              // redis 持续性，连接复用
            ];
            $redis = \think\Config::get('redis');
            $redis['empty_password'] = 0;
            $redis['password'] = '';                // 隐藏密码
            $configs = $redis ? array_merge($default, $redis) : $default;
        } elseif ('POST' === $this->request->method()) {
            operate_filter();
            $configs = $this->request->param();
            $empty_password = (int)$configs['empty_password'];      // 是否设置空密码
            unset($configs['empty_password']);

            if (isset($configs['password']) && empty($configs['password'])) {
                $redis = \think\Config::get('redis');
                // 不修改密码，保持为原始值
                $configs['password'] = $redis['password'] ?? '';
            } elseif ($empty_password) {
                $configs['password'] = '';
            }

            $configs['persistent'] = (isset($configs['persistent']) && ($configs['persistent'] === true || $configs['persistent'] == 'true')) ? true : false;

            // 存文件
            file_put_contents(
                ROOT_PATH . 'application' . DS . 'extra' . DS . 'redis.php',
                '<?php' . "\n\nreturn " . short_var_export($configs, true) . ";"
            );
        }
        $this->success('操作成功', null, $configs);
    }



    public function getPlatformUrl()
    {
        $h5Url = CusConfig::getConfigField('shop.basic.domain');
        $wechatMpAppid =  CusConfig::getConfigField('shop.platform.WechatMiniProgram.app_id');

        $this->success('', null, [
            'url' => $h5Url,
            'appid' => $wechatMpAppid
        ]);
    }

    /**
     * 金卡配置
     */
    public function card()
    {
        if ('GET' === $this->request->method()) {
            $configs = CusConfig::getConfigs('shop.card', false);
            // 如果配置不存在，返回默认值
            if (!$configs) {
                $configs = [
                    'hide_withdraw' => 0,
                    'hide_entry_ticket' => 0,
                    'specialist_name' => '陈亮',
                    'specialist_number' => 'chen520',
                ];
            }
        } elseif ('POST' === $this->request->method()) {
            $params = $this->request->param();
            $configs = CusConfig::setConfigs('shop.card', $params);
        }
        $this->success('操作成功', null, $configs);
    }
}
