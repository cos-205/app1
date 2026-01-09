define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/user_statistics/index' + location.search,
                    add_url: 'fuka/user_statistics/add',
                    edit_url: 'fuka/user_statistics/edit',
                    del_url: 'fuka/user_statistics/del',
                    multi_url: 'fuka/user_statistics/multi',
                    import_url: 'fuka/user_statistics/import',
                    table: 'fuka_user_statistics',
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
                        {field: 'id', title: __('Id')},
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
                        {field: 'team_id', title: __('Team_id')},
                        {field: 'is_team_leader', title: __('Is_team_leader'), searchList: {"1":__('Is_team_leader 1'),"0":__('Is_team_leader 0')}, formatter: Table.api.formatter.normal},
                        {field: 'total_invite_count', title: __('Total_invite_count')},
                        {field: 'valid_invite_count', title: __('Valid_invite_count')},
                        {field: 'total_fuka_count', title: __('Total_fuka_count')},
                        {field: 'current_fuka_count', title: __('Current_fuka_count')},
                        {field: 'current_wufu_card_count', title: __('Current_wufu_card_count')},
                        {field: 'fuka_chance', title: __('Fuka_chance')},
                        {
                            field: 'dividend_money', 
                            title: __('Dividend_money'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {
                            field: 'total_dividend', 
                            title: __('Total_dividend'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'last_update_time', title: __('Last_update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'weigh', title: __('Weigh'), operate: false},
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
                url: 'fuka/user_statistics/recyclebin' + location.search,
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
                                    url: 'fuka/user_statistics/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/user_statistics/destroy',
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
