define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/wealth_card/index' + location.search,
                    add_url: 'fuka/wealth_card/add',
                    edit_url: 'fuka/wealth_card/edit',
                    del_url: 'fuka/wealth_card/del',
                    multi_url: 'fuka/wealth_card/multi',
                    import_url: 'fuka/wealth_card/import',
                    table: 'fuka_wealth_card',
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
                        {field: 'card_no', title: __('Card_no'), operate: 'LIKE'},
                        {field: 'holder_name', title: __('Holder_name'), operate: 'LIKE'},
                        {field: 'holder_idcard', title: __('Holder_idcard'), operate: 'LIKE'},
                        {
                            field: 'card_balance', 
                            title: __('Card_balance'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'card_password', title: __('Card_password'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content, visible: false},
                        {field: 'withdraw_password', title: __('Withdraw_password'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content, visible: false},
                        {
                            field: 'flow_status', 
                            title: __('Flow_status'), 
                            searchList: {"0":__('Flow_status 0'),"1-8":__('Flow_status 1-8')}, 
                            formatter: function(value, row, index) {
                                // 显示流程状态文字
                                if (row.flow_status_text) {
                                    return row.flow_status_text;
                                }
                                return Table.api.formatter.status(value, row, index);
                            }
                        },
                        {
                            field: 'apply_status', 
                            title: __('Apply_status'), 
                            searchList: {"0":__('Apply_status 0'),"1":__('Apply_status 1'),"2":__('Apply_status 2'),"3":__('Apply_status 3'),"4":__('Apply_status 4'),"5":__('Apply_status 5'),"6":__('Apply_status 6')}, 
                            formatter: function(value, row, index) {
                                // 显示申领状态文字
                                if (row.apply_status_text) {
                                    return row.apply_status_text;
                                }
                                return Table.api.formatter.status(value, row, index);
                            }
                        },
                        {field: 'apply_time', title: __('Apply_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_time', title: __('Audit_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_remark', title: __('Audit_remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content, visible: false},
                        {field: 'make_time', title: __('Make_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'logistics_no', title: __('Logistics_no'), operate: 'LIKE'},
                        {field: 'logistics_company', title: __('Logistics_company'), operate: 'LIKE'},
                        {field: 'send_time', title: __('Send_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'receive_time', title: __('Receive_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'is_active', title: __('Is_active'), searchList: {"1":__('Is_active 1'),"0":__('Is_active 0')}, formatter: Table.api.formatter.normal},
                        {field: 'active_time', title: __('Active_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'large_pay_limit', title: __('Large_pay_limit'), operate:'BETWEEN'},
                        {field: 'is_open_large_pay', title: __('Is_open_large_pay'), searchList: {"1":__('Is_open_large_pay 1'),"0":__('Is_open_large_pay 0')}, formatter: Table.api.formatter.normal},
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
                url: 'fuka/wealth_card/recyclebin' + location.search,
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
                                    url: 'fuka/wealth_card/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/wealth_card/destroy',
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
