define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/exchange_record/index' + location.search,
                    add_url: 'fuka/exchange_record/add',
                    edit_url: 'fuka/exchange_record/edit',
                    del_url: 'fuka/exchange_record/del',
                    multi_url: 'fuka/exchange_record/multi',
                    import_url: 'fuka/exchange_record/import',
                    table: 'fuka_exchange_record',
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
                        {
                            field: 'prize_id', 
                            title: __('Prize_id'),
                            formatter: function(value, row, index) {
                                // 显示奖品信息：奖品名称 [有图]
                                if (row.prize && row.prize.name) {
                                    var image = row.prize.image ? ' [有图]' : '';
                                    return row.prize.name + image;
                                }
                                // 如果没有关联数据，使用prize_name字段
                                if (row.prize_name) {
                                    return row.prize_name;
                                }
                                return value || '-';
                            }
                        },
                        {field: 'prize_name', title: __('Prize_name'), operate: 'LIKE', visible: false},
                        {field: 'prize_type', title: __('Prize_type'), searchList: {"1":__('Prize_type 1'),"2":__('Prize_type 2'),"3":__('Prize_type 3'),"4":__('Prize_type 4')}, formatter: Table.api.formatter.normal},
                        {field: 'fuka_set_count', title: __('Fuka_set_count')},
                        {
                            field: 'exchange_status', 
                            title: __('Exchange_status'), 
                            searchList: {"0":__('Exchange_status 0'),"1":__('Exchange_status 1'),"2":__('Exchange_status 2'),"3":__('Exchange_status 3'),"4":__('Exchange_status 4'),"5":__('Exchange_status 5'),"6":__('Exchange_status 6')}, 
                            formatter: function(value, row, index) {
                                // 显示兑换状态文字
                                if (row.exchange_status_text) {
                                    return row.exchange_status_text;
                                }
                                return Table.api.formatter.status(value, row, index);
                            }
                        },
                        {field: 'exchange_time', title: __('Exchange_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_time', title: __('Audit_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_remark', title: __('Audit_remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'logistics_id', title: __('Logistics_id')},
                        {field: 'pickup_code', title: __('Pickup_code'), operate: 'LIKE'},
                        {field: 'is_get_pickup_code', title: __('Is_get_pickup_code'), searchList: {"1":__('Is_get_pickup_code 1'),"0":__('Is_get_pickup_code 0')}, formatter: Table.api.formatter.normal},
                        {
                            field: 'pickup_code_fee', 
                            title: __('Pickup_code_fee'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'pay_pickup_time', title: __('Pay_pickup_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'certificate_no', title: __('Certificate_no'), operate: 'LIKE'},
                        {field: 'is_get_certificate', title: __('Is_get_certificate'), searchList: {"1":__('Is_get_certificate 1'),"0":__('Is_get_certificate 0')}, formatter: Table.api.formatter.normal},
                        {
                            field: 'certificate_fee', 
                            title: __('Certificate_fee'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'pay_certificate_time', title: __('Pay_certificate_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'complete_time', title: __('Complete_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
                url: 'fuka/exchange_record/recyclebin' + location.search,
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
                                    url: 'fuka/exchange_record/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/exchange_record/destroy',
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
