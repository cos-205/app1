define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fuka/member_level_log/index' + location.search,
                    add_url: 'fuka/member_level_log/add',
                    edit_url: 'fuka/member_level_log/edit',
                    del_url: 'fuka/member_level_log/del',
                    multi_url: 'fuka/member_level_log/multi',
                    import_url: 'fuka/member_level_log/import',
                    table: 'fuka_member_level_log',
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
                        {
                            field: 'old_level', 
                            title: __('Old_level'), 
                            searchList: {"0":__('Old_level 0'),"1":__('Old_level 1'),"2":__('Old_level 2'),"3":__('Old_level 3'),"4":__('Old_level 4'),"5":__('Old_level 5')}, 
                            formatter: function(value, row, index) {
                                // 显示旧等级文字
                                if (row.old_level_text) {
                                    return row.old_level_text;
                                }
                                return Table.api.formatter.normal(value, row, index);
                            }
                        },
                        {
                            field: 'new_level', 
                            title: __('New_level'), 
                            searchList: {"0":__('New_level 0'),"1":__('New_level 1'),"2":__('New_level 2'),"3":__('New_level 3'),"4":__('New_level 4'),"5":__('New_level 5')}, 
                            formatter: function(value, row, index) {
                                // 显示新等级文字
                                if (row.new_level_text) {
                                    return row.new_level_text;
                                }
                                return Table.api.formatter.normal(value, row, index);
                            }
                        },
                        {
                            field: 'level_change',
                            title: '等级变化',
                            formatter: function(value, row, index) {
                                // 显示等级变化：旧等级 → 新等级
                                if (row.level_change) {
                                    return row.level_change;
                                }
                                var oldLevel = row.old_level_text || row.old_level || '0';
                                var newLevel = row.new_level_text || row.new_level || '0';
                                return oldLevel + ' → ' + newLevel;
                            }
                        },
                        {field: 'invite_count', title: __('Invite_count')},
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
                url: 'fuka/member_level_log/recyclebin' + location.search,
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
                                    url: 'fuka/member_level_log/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'fuka/member_level_log/destroy',
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
