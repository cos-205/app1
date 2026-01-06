<?php

namespace addons\cus\listener;

use addons\cus\library\notify\Notify;
use app\admin\model\cus\user\User;
use app\admin\model\cus\Admin;

class OrderAftersale
{

    public function orderAftersaleChange($params)
    {
        $aftersale = $params['aftersale'];
        $order = $params['order'];
        $aftersaleLog = $params['aftersaleLog'];

        // 通知用户售后处理过程
        $user = User::where('id', $aftersale['user_id'])->find();
        $user && $user->notify(
            new \addons\cus\notification\order\aftersale\OrderAftersaleChange([
                'aftersale' => $aftersale,
                'order' => $order,
                'aftersaleLog' => $aftersaleLog,
            ])
        );

        // 通知管理员售后变动
        $admins = collection(Admin::select())->filter(function ($admin) {
            return $admin->hasAccess($admin, [      // 售后所有权限
                'cus/order/afersale/index',
                'cus/order/aftersale/detail',
                'cus/order/aftersale/completed',
                'cus/order/aftersale/refuse',
                'cus/order/aftersale/refund',
                'cus/order/aftersale/addlog',
            ]);
        });
        if (!$admins->isEmpty()) {
            Notify::send(
                $admins,
                new \addons\cus\notification\order\aftersale\OrderAdminAftersaleChange([
                    'aftersale' => $aftersale,
                    'order' => $order,
                    'aftersaleLog' => $aftersaleLog,
                ])
            );
        }
    }


    public function orderAftersaleCompleted()
    {
    }

    public function orderAftersaleRefuse()
    {
    }
}
