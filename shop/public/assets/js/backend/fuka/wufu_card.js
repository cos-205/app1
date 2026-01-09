define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/wufu_card/index' + location.search,
                    add_url: 'fuka/wufu_card/add',
                    edit_url: 'fuka/wufu_card/edit',
                    del_url: 'fuka/wufu_card/del',
                    multi_url: 'fuka/wufu_card/multi',
                    import_url: 'fuka/wufu_card/import',
                    table: 'fuka_wufu_card',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
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
                        {
                            field: 'type_id',
                            title: __('Type_id'),
                            formatter: function(value, row, index) {
                                // 显示福卡类型：图标 + 名称
                                if (row.type && row.type.type_name) {
                                    var typeName = row.type.type_name;
                                    var icon = row.type.icon || '';
                                    if (icon) {
                                        // 如果图标不包含空格，添加 fa fa- 前缀
                                        icon = icon.indexOf(' ') > -1 ? icon : 'fa fa-' + icon;
                                        return '<i class="' + icon + '"></i> ' + typeName;
                                    }
                                    return typeName;
                                }
                                return value || '-';
                            }
                        },
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
                        {
                            field: 'exchange_id', 
                            title: __('Exchange_id'),
                            formatter: function(value, row, index) {
                                // 显示兑换记录ID，如果有兑换记录则显示详情
                                if (row.exchange_record && row.exchange_record.id) {
                                    return value + ' (已兑换)';
                                }
                                return value || '-';
                            }
                        },
                        {field: 'used_time', title: __('Used_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, visible: false},
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
