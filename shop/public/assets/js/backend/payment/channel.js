define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'payment/channel/index' + location.search,
                    add_url: 'payment/channel/add',
                    edit_url: 'payment/channel/edit',
                    del_url: 'payment/channel/del',
                    multi_url: 'payment/channel/multi',
                    import_url: 'payment/channel/import',
                    table: 'payment_channel',
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
                        {field: 'channel_name', title: __('Channel_name'), operate: 'LIKE'},
                        {field: 'channel_type', title: __('Channel_type'), searchList: {"wechat":__('Channel_type wechat'),"alipay":__('Channel_type alipay'),"bank":__('Channel_type bank'),"other":__('Channel_type other')}, formatter: Table.api.formatter.normal},
                        {field: 'account_name', title: __('Account_name'), operate: 'LIKE'},
                        {field: 'account_number', title: __('Account_number'), operate: 'LIKE'},
                        {field: 'qrcode_image', title: __('Qrcode_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'sort', title: __('Sort')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
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
