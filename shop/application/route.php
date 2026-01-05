<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'   => [
    ],
    //变量规则
    '__pattern__' => [
    ],
//        域名绑定到模块
//        '__domain__'  => [
//            'admin' => 'admin',
//            'api'   => 'api',
//        ],

    // ============================================
    // 集福卡系统 API 路由配置
    // ============================================
    
    // 集福卡模块路由
    'api/fuka/typeList' => 'api/fuka/typeList',                    // GET - 获取福卡类型列表
    'api/fuka/myCards' => 'api/fuka/myCards',                     // GET - 获取我的福卡列表
    'api/fuka/useChance' => 'api/fuka/useChance',                 // POST - 使用集福机会
    'api/fuka/exchange' => 'api/fuka/exchange',                   // POST - 一键兑换
    'api/fuka/exchangeList' => 'api/fuka/exchangeList',           // GET - 获取兑换记录列表
    'api/fuka/rankList' => 'api/fuka/rankList',                   // GET - 获取集福排行榜
    
    // 签到模块路由
    'api/signin/doSignin' => 'api/signin/doSignin',               // POST - 每日签到
    'api/signin/receiveReward' => 'api/signin/receiveReward',     // POST - 领取签到奖励
    'api/signin/rewardList' => 'api/signin/rewardList',           // GET - 获取签到奖励列表
    
    // 团队模块路由
    'api/team/myTeam' => 'api/team/myTeam',                        // GET - 获取我的团队信息
    'api/team/memberList' => 'api/team/memberList',                // GET - 获取团队成员列表
    'api/team/inviteList' => 'api/team/inviteList',                // GET - 获取邀请记录列表
    'api/team/rewardList' => 'api/team/rewardList',               // GET - 获取团队奖励记录
    
    // 财富金卡模块路由
    'api/wealthCard/info' => 'api/wealthCard/info',               // GET - 获取金卡信息
    'api/wealthCard/apply' => 'api/wealthCard/apply',             // POST - 申请财富金卡
    'api/wealthCard/completeStep' => 'api/wealthCard/completeStep', // POST - 完成流程步骤
    'api/wealthCard/payFee' => 'api/wealthCard/payFee',           // POST - 支付流程费用
];
