define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/signin_reward_log/index' + location.search,
                    add_url: 'fuka/signin_reward_log/add',
                    edit_url: 'fuka/signin_reward_log/edit',
                    del_url: 'fuka/signin_reward_log/del',
                    multi_url: 'fuka/signin_reward_log/multi',
                    import_url: 'fuka/signin_reward_log/import',
                    table: 'fuka_signin_reward_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), visible: false},
                        {
                            field: 'user_id', 
                            title: __('User_id'),
                            formatter: function(value, row, index) {
                                // 显示用户信息：用户名 (手机号)
                                if (row.user && row.user.nickname) {
                                    var mobile = row.user.mobile ? ' (' + row.user.mobile + ')' : '';
                                    return row.user.nickname + mobile;
                                }
                                return value || '-';
                            }
                        },
                        {field: 'signin_id', title: __('Signin_id'), visible: false},
                        {
                            field: 'rule_id', 
                            title: __('Rule_id'),
                            formatter: function(value, row, index) {
                                // 显示规则信息：规则名称 (连续X天)
                                if (row.signin_reward_rule && row.signin_reward_rule.name) {
                                    var days = row.signin_reward_rule.days ? ' (连续' + row.signin_reward_rule.days + '天)' : '';
                                    return row.signin_reward_rule.name + days;
                                }
                                return value || '-';
                            }
                        },
                        {field: 'days', title: __('Days')},
                        {
                            field: 'reward_type', 
                            title: __('Reward_type'), 
                            searchList: {"1":__('Reward_type 1'),"2":__('Reward_type 2')}, 
                            formatter: function(value, row, index) {
                                // 显示奖励类型文字
                                if (row.reward_type_text) {
                                    return row.reward_type_text;
                                }
                                return Table.api.formatter.normal(value, row, index);
                            }
                        },
                        {
                            field: 'reward_money', 
                            title: __('Reward_money'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'reward_chance', title: __('Reward_chance')},
                        {
                            field: 'is_received', 
                            title: __('Is_received'), 
                            searchList: {"1":__('Is_received 1'),"0":__('Is_received 0')}, 
                            formatter: function(value, row, index) {
                                // 显示领取状态文字
                                if (row.is_received_text) {
                                    return row.is_received_text;
                                }
                                return Table.api.formatter.normal(value, row, index);
                            }
                        },
                        {field: 'receive_time', title: __('Receive_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'wallet_log_id', title: __('Wallet_log_id'), visible: false},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content, visible: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, visible: false},
                        {field: 'weigh', title: __('Weigh'), operate: false, visible: false},
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Status normal'),"hidden":__('Status hidden')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'fuka/signin_reward_log/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'fuka/signin_reward_log/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/signin_reward_log/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
