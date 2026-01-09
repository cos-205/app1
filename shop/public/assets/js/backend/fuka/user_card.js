define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/user_card/index' + location.search,
                    add_url: 'fuka/user_card/add',
                    edit_url: 'fuka/user_card/edit',
                    del_url: 'fuka/user_card/del',
                    multi_url: 'fuka/user_card/multi',
                    import_url: 'fuka/user_card/import',
                    table: 'fuka_user_card',
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
                        {field: 'fuka_type_id', title: __('Fuka_type_id'), visible: false},
                        {field: 'type_code', title: __('Type_code'), operate: 'LIKE', visible: false},
                        {
                            field: 'type_name', 
                            title: __('Type_name'), 
                            operate: 'LIKE',
                            formatter: function(value, row, index) {
                                // 显示福卡类型信息：图标 + 类型名称
                                if (row.fuka_type && row.fuka_type.type_name) {
                                    var typeName = row.fuka_type.type_name;
                                    var icon = row.fuka_type.icon || '';
                                    if (icon) {
                                        // 如果图标不包含空格，添加 fa fa- 前缀
                                        icon = icon.indexOf(' ') > -1 ? icon : 'fa fa-' + icon;
                                        return '<i class="' + icon + '"></i> ' + typeName;
                                    }
                                    return typeName;
                                }
                                // 如果没有关联数据，使用type_name字段
                                if (row.type_name) {
                                    return row.type_name;
                                }
                                // 如果有fuka_type_info格式化字段，使用它
                                if (row.fuka_type_info) {
                                    return row.fuka_type_info;
                                }
                                return value || '-';
                            }
                        },
                        {
                            field: 'source_type', 
                            title: __('Source_type'), 
                            searchList: {"1":__('Source_type 1'),"2":__('Source_type 2'),"3":__('Source_type 3'),"4":__('Source_type 4'),"5":__('Source_type 5'),"6":__('Source_type 6')}, 
                            formatter: function(value, row, index) {
                                // 显示来源类型文字
                                if (row.source_type_text) {
                                    return row.source_type_text;
                                }
                                // 如果没有格式化字段，使用searchList映射
                                var sourceTypeMap = {
                                    "1": __('Source_type 1'),
                                    "2": __('Source_type 2'),
                                    "3": __('Source_type 3'),
                                    "4": __('Source_type 4'),
                                    "5": __('Source_type 5'),
                                    "6": __('Source_type 6')
                                };
                                if (value && sourceTypeMap[value]) {
                                    return sourceTypeMap[value];
                                }
                                return value || '-';
                            }
                        },
                        {field: 'source_id', title: __('Source_id'), visible: false},
                        {
                            field: 'is_used', 
                            title: __('Is_used'), 
                            searchList: {"1":__('Is_used 1'),"0":__('Is_used 0')}, 
                            formatter: function(value, row, index) {
                                // 显示使用状态文字
                                if (row.is_used_text) {
                                    return row.is_used_text;
                                }
                                return Table.api.formatter.normal(value, row, index);
                            }
                        },
                        {field: 'exchange_id', title: __('Exchange_id'), visible: false},
                        {field: 'used_time', title: __('Used_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, visible: false},
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
                url: 'fuka/user_card/recyclebin' + location.search,
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
                                    url: 'fuka/user_card/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/user_card/destroy',
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
