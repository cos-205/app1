define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/card_flow_config/index' + location.search,
                    add_url: 'fuka/card_flow_config/add',
                    edit_url: 'fuka/card_flow_config/edit',
                    del_url: 'fuka/card_flow_config/del',
                    multi_url: 'fuka/card_flow_config/multi',
                    import_url: 'fuka/card_flow_config/import',
                    table: 'fuka_card_flow_config',
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
                        {field: 'step', title: __('Step')},
                        {field: 'step_type', title: __('Step_type')},
                        {field: 'step_name', title: __('Step_name'), operate: 'LIKE'},
                        {field: 'step_desc', title: __('Step_desc'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'button_text', title: __('Button_text'), operate: 'LIKE'},
                        {field: 'completed_text', title: __('Completed_text'), operate: 'LIKE'},
                        {field: 'completed_title', title: __('Completed_title'), operate: 'LIKE'},
                        {field: 'need_fee', title: __('Need_fee'), searchList: {"1":__('Need_fee 1'),"0":__('Need_fee 0')}, formatter: Table.api.formatter.normal},
                        {field: 'fee_amount', title: __('Fee_amount'), operate:'BETWEEN'},
                        {field: 'fee_name', title: __('Fee_name'), operate: 'LIKE'},
                        {field: 'fee_desc', title: __('Fee_desc'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'fee_receiver', title: __('Fee_receiver'), operate: 'LIKE'},
                        {field: 'fee_purpose', title: __('Fee_purpose'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'need_refund', title: __('Need_refund'), searchList: {"1":__('Need_refund 1'),"0":__('Need_refund 0')}, formatter: Table.api.formatter.normal},
                        {field: 'refund_rule', title: __('Refund_rule'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'refund_days', title: __('Refund_days')},
                        {field: 'sort_order', title: __('Sort_order')},
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
                url: 'fuka/card_flow_config/recyclebin' + location.search,
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
                                    url: 'fuka/card_flow_config/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/card_flow_config/destroy',
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
