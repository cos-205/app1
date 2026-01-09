define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/card_balance_log/index' + location.search,
                    add_url: 'fuka/card_balance_log/add',
                    edit_url: 'fuka/card_balance_log/edit',
                    del_url: 'fuka/card_balance_log/del',
                    multi_url: 'fuka/card_balance_log/multi',
                    import_url: 'fuka/card_balance_log/import',
                    table: 'fuka_card_balance_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'card_id', title: __('Card_id')},
                        {field: 'change_type', title: __('Change_type'), searchList: {"1":__('Change_type 1'),"2":__('Change_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'change_money', title: __('Change_money'), operate:'BETWEEN'},
                        {field: 'before_balance', title: __('Before_balance'), operate:'BETWEEN'},
                        {field: 'after_balance', title: __('After_balance'), operate:'BETWEEN'},
                        {field: 'source_type', title: __('Source_type'), operate: 'LIKE'},
                        {field: 'source_id', title: __('Source_id')},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
