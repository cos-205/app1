define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'card/order/index' + location.search,
                    add_url: 'card/order/add',
                    edit_url: 'card/order/edit',
                    del_url: 'card/order/del',
                    multi_url: 'card/order/multi',
                    import_url: 'card/order/import',
                    table: 'card_order',
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
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'merchant_trade_no', title: __('Merchant_trade_no'), operate: 'LIKE'},
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
                            field: 'card_id', 
                            title: __('Card_id'),
                            formatter: function(value, row, index) {
                                // 显示金卡信息：卡号 (持卡人)
                                if (row.wealth_card && row.wealth_card.card_no) {
                                    var holder = row.wealth_card.holder_name ? ' (' + row.wealth_card.holder_name + ')' : '';
                                    return row.wealth_card.card_no + holder;
                                }
                                return value || '-';
                            }
                        },
                        {field: 'step_id', title: __('Step_id'), visible: false},
                        {field: 'step_name', title: __('Step_name'), operate: 'LIKE'},
                        {field: 'order_type', title: __('Order_type'), operate: 'LIKE'},
                        {field: 'related_id', title: __('Related_id'), visible: false},
                        {
                            field: 'amount', 
                            title: __('Amount'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'pay_type', title: __('Pay_type'), operate: 'LIKE'},
                        {
                            field: 'pay_status', 
                            title: __('Pay_status'), 
                            searchList: {"0":__('Pay_status 0'),"1":__('Pay_status 1'),"2":__('Pay_status 2')}, 
                            formatter: function(value, row, index) {
                                // 显示支付状态文字
                                if (row.pay_status_text) {
                                    return row.pay_status_text;
                                }
                                return Table.api.formatter.status(value, row, index);
                            }
                        },
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'transaction_id', title: __('Transaction_id'), operate: 'LIKE'},
                        {field: 'pay_url', title: __('Pay_url'), operate: 'LIKE', formatter: Table.api.formatter.url, visible: false},
                        {
                            field: 'refund_status', 
                            title: __('Refund_status'), 
                            searchList: {"0":__('Refund_status 0'),"1":__('Refund_status 1'),"2":__('Refund_status 2')}, 
                            formatter: function(value, row, index) {
                                // 显示退款状态文字
                                if (row.refund_status_text) {
                                    return row.refund_status_text;
                                }
                                return Table.api.formatter.status(value, row, index);
                            }
                        },
                        {field: 'refund_time', title: __('Refund_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'refund_transaction_id', title: __('Refund_transaction_id'), operate: 'LIKE'},
                        {
                            field: 'refund_amount', 
                            title: __('Refund_amount'), 
                            operate:'BETWEEN',
                            formatter: function(value, row, index) {
                                // 格式化金额显示
                                if (value || value === 0) {
                                    return '¥' + parseFloat(value).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return '¥0.00';
                            }
                        },
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, visible: false},
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
                url: 'card/order/recyclebin' + location.search,
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
                                    url: 'card/order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'card/order/destroy',
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
        statistics: function () {
            // 绑定表单事件（包括datetimepicker初始化）
            Controller.api.bindevent();
            
            // 确保时间选择器初始化（如果表单没有role="form"属性，手动初始化）
            if ($('.datetimepicker').length > 0) {
                require(['bootstrap-datetimepicker'], function () {
                    var options = {
                        format: 'YYYY-MM-DD HH:mm:ss',
                        icons: {
                            time: 'fa fa-clock-o',
                            date: 'fa fa-calendar',
                            up: 'fa fa-chevron-up',
                            down: 'fa fa-chevron-down',
                            previous: 'fa fa-chevron-left',
                            next: 'fa fa-chevron-right',
                            today: 'fa fa-history',
                            clear: 'fa fa-trash',
                            close: 'fa fa-remove'
                        },
                        showTodayButton: true,
                        showClose: true
                    };
                    $('.datetimepicker').parent().css('position', 'relative');
                    $('.datetimepicker').datetimepicker(options).on('dp.change', function (e) {
                        $(this, document).trigger("changed");
                    });
                });
            }
            
            // 处理表单AJAX提交
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                // 显示加载提示
                Layer.load();
                
                $.ajax({
                    url: '',
                    type: 'GET',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        Layer.closeAll();
                        
                        if (response.code === 1 && response.data) {
                            var data = response.data;
                            
                            // 更新统计卡片
                            $('#stat-total h2').text('¥' + parseFloat(data.total_amount).toFixed(2));
                            $('#stat-total p').text('总订单数：' + data.total_count + ' 笔');
                            
                            $('#stat-today h2').text('¥' + parseFloat(data.today_amount).toFixed(2));
                            $('#stat-today p').text('今日订单数：' + data.today_count + ' 笔');
                            
                            $('#stat-success h2').text('¥' + parseFloat(data.success_amount).toFixed(2));
                            $('#stat-success p').text('成功订单数：' + data.success_count + ' 笔');
                            
                            $('#stat-fail h2').text('¥' + parseFloat(data.fail_amount).toFixed(2));
                            $('#stat-fail p').text('失败订单数：' + data.fail_count + ' 笔');
                            
                            $('#stat-refund h2').text('¥' + parseFloat(data.refund_amount).toFixed(2));
                            $('#stat-refund p').text('退款订单数：' + data.refund_count + ' 笔');
                            
                            // 更新渠道统计表格
                            var channelTableBody = $('#channel-stats-table');
                            channelTableBody.empty();
                            
                            if (data.channel_stats && data.channel_stats.length > 0) {
                                var totalAmount = data.total_amount || 0;
                                var payTypeMap = {
                                    'wechat': '微信支付',
                                    'alipay': '支付宝',
                                    'unionpay': '银联支付'
                                };
                                $.each(data.channel_stats, function(index, item) {
                                    var percentage = totalAmount > 0 ? (item.total_amount / totalAmount * 100).toFixed(2) : 0;
                                    var payTypeName = payTypeMap[item.pay_type] || item.pay_type || '未知';
                                    var row = '<tr>' +
                                        '<td>' + payTypeName + '</td>' +
                                        '<td>' + item.count + ' 笔</td>' +
                                        '<td>¥' + parseFloat(item.total_amount).toFixed(2) + '</td>' +
                                        '<td>' + percentage + '%</td>' +
                                        '</tr>';
                                    channelTableBody.append(row);
                                });
                            } else {
                                channelTableBody.html('<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>');
                            }
                            
                            // 更新金额分组统计表格
                            var amountTableBody = $('#amount-group-table');
                            amountTableBody.empty();
                            
                            if (data.amount_group && data.amount_group.length > 0) {
                                var totalAmount = data.total_amount || 0;
                                $.each(data.amount_group, function(index, item) {
                                    var percentage = totalAmount > 0 ? (item.total_amount / totalAmount * 100).toFixed(2) : 0;
                                    var row = '<tr>' +
                                        '<td>¥' + parseFloat(item.amount).toFixed(2) + '</td>' +
                                        '<td>' + item.count + ' 笔</td>' +
                                        '<td>¥' + parseFloat(item.total_amount).toFixed(2) + '</td>' +
                                        '<td>' + percentage + '%</td>' +
                                        '</tr>';
                                    amountTableBody.append(row);
                                });
                            } else {
                                amountTableBody.html('<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>');
                            }
                            
                            Toastr.success('查询成功');
                        } else {
                            Toastr.error(response.msg || '查询失败');
                        }
                    },
                    error: function(xhr, status, error) {
                        Layer.closeAll();
                        Toastr.error('查询失败，请重试');
                    }
                });
                
                return false;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
