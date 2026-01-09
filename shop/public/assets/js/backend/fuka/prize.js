define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/prize/index' + location.search,
                    add_url: 'fuka/prize/add',
                    edit_url: 'fuka/prize/edit',
                    del_url: 'fuka/prize/del',
                    multi_url: 'fuka/prize/multi',
                    import_url: 'fuka/prize/import',
                    table: 'fuka_prize',
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
                        {field: 'prize_name', title: __('Prize_name'), operate: 'LIKE'},
                        {field: 'prize_type', title: __('Prize_type'), searchList: {"1":__('Prize_type 1'),"2":__('Prize_type 2'),"3":__('Prize_type 3'),"4":__('Prize_type 4')}, formatter: Table.api.formatter.normal},
                        {field: 'prize_image', title: __('Prize_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'prize_value', title: __('Prize_value'), operate:'BETWEEN'},
                        {field: 'need_fuka_set', title: __('Need_fuka_set')},
                        {field: 'need_pickup_code', title: __('Need_pickup_code'), searchList: {"1":__('Need_pickup_code 1'),"0":__('Need_pickup_code 0')}, formatter: Table.api.formatter.normal},
                        {field: 'pickup_code_fee', title: __('Pickup_code_fee'), operate:'BETWEEN'},
                        {field: 'need_certificate', title: __('Need_certificate'), searchList: {"1":__('Need_certificate 1'),"0":__('Need_certificate 0')}, formatter: Table.api.formatter.normal},
                        {field: 'certificate_type', title: __('Certificate_type'), operate: 'LIKE'},
                        {field: 'certificate_fee', title: __('Certificate_fee'), operate:'BETWEEN'},
                        {field: 'stock', title: __('Stock')},
                        {field: 'exchange_count', title: __('Exchange_count')},
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
                url: 'fuka/prize/recyclebin' + location.search,
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
                                    url: 'fuka/prize/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/prize/destroy',
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
