define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/card_flow_log/index' + location.search,
                    add_url: 'fuka/card_flow_log/add',
                    edit_url: 'fuka/card_flow_log/edit',
                    del_url: 'fuka/card_flow_log/del',
                    multi_url: 'fuka/card_flow_log/multi',
                    import_url: 'fuka/card_flow_log/import',
                    table: 'fuka_card_flow_log',
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
                        {field: 'user_id', title: __('User_id')},
                        {field: 'card_id', title: __('Card_id')},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'flow_step', title: __('Flow_step')},
                        {field: 'step_name', title: __('Step_name'), operate: 'LIKE'},
                        {field: 'flow_status', title: __('Flow_status'), searchList: {"0":__('Flow_status 0'),"1":__('Flow_status 1'),"2":__('Flow_status 2'),"3":__('Flow_status 3')}, formatter: Table.api.formatter.status},
                        {field: 'is_completed', title: __('Is_completed'), searchList: {"1":__('Is_completed 1'),"0":__('Is_completed 0')}, formatter: Table.api.formatter.normal},
                        {field: 'complete_time', title: __('Complete_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'auditor_id', title: __('Auditor_id')},
                        {field: 'audit_time', title: __('Audit_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_remark', title: __('Audit_remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'need_fee', title: __('Need_fee'), searchList: {"1":__('Need_fee 1'),"0":__('Need_fee 0')}, formatter: Table.api.formatter.normal},
                        {field: 'fee_amount', title: __('Fee_amount'), operate:'BETWEEN'},
                        {field: 'fee_name', title: __('Fee_name'), operate: 'LIKE'},
                        {field: 'is_pay_fee', title: __('Is_pay_fee'), searchList: {"1":__('Is_pay_fee 1'),"0":__('Is_pay_fee 0')}, formatter: Table.api.formatter.normal},
                        {field: 'pay_fee_time', title: __('Pay_fee_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'pay_trade_no', title: __('Pay_trade_no'), operate: 'LIKE'},
                        {field: 'money_log_id', title: __('Money_log_id')},
                        {field: 'need_refund', title: __('Need_refund'), searchList: {"1":__('Need_refund 1'),"0":__('Need_refund 0')}, formatter: Table.api.formatter.normal},
                        {field: 'is_refund_fee', title: __('Is_refund_fee'), searchList: {"1":__('Is_refund_fee 1'),"0":__('Is_refund_fee 0')}, formatter: Table.api.formatter.normal},
                        {field: 'refund_fee_time', title: __('Refund_fee_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'refund_trade_no', title: __('Refund_trade_no'), operate: 'LIKE'},
                        {field: 'refund_wallet_log_id', title: __('Refund_wallet_log_id')},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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
                url: 'fuka/card_flow_log/recyclebin' + location.search,
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
                                    url: 'fuka/card_flow_log/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/card_flow_log/destroy',
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
