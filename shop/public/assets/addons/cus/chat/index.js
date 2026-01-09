define(['io', 'moment', 'moment/locale/zh-cn'], function (io, Moment) {

    // 表情数据
    const emojiList = [
        { "name": "[笑掉牙]", "file": "xiaodiaoya.png" },
        { "name": "[可爱]", "file": "keai.png" },
        { "name": "[冷酷]", "file": "lengku.png" },
        { "name": "[闭嘴]", "file": "bizui.png" },
        { "name": "[生气]", "file": "shengqi.png" },
        { "name": "[惊恐]", "file": "jingkong.png" },
        { "name": "[瞌睡]", "file": "keshui.png" },
        { "name": "[大笑]", "file": "daxiao.png" },
        { "name": "[爱心]", "file": "aixin.png" },
        { "name": "[坏笑]", "file": "huaixiao.png" },
        { "name": "[飞吻]", "file": "feiwen.png" },
        { "name": "[疑问]", "file": "yiwen.png" },
        { "name": "[开心]", "file": "kaixin.png" },
        { "name": "[发呆]", "file": "fadai.png" },
        { "name": "[流泪]", "file": "liulei.png" },
        { "name": "[汗颜]", "file": "hanyan.png" },
        { "name": "[惊悚]", "file": "jingshu.png" },
        { "name": "[困~]", "file": "kun.png" },
        { "name": "[心碎]", "file": "xinsui.png" },
        { "name": "[天使]", "file": "tianshi.png" },
        { "name": "[晕]", "file": "yun.png" },
        { "name": "[啊]", "file": "a.png" },
        { "name": "[愤怒]", "file": "fennu.png" },
        { "name": "[睡着]", "file": "shuizhuo.png" },
        { "name": "[面无表情]", "file": "mianwubiaoqing.png" },
        { "name": "[难过]", "file": "nanguo.png" },
        { "name": "[犯困]", "file": "fankun.png" },
        { "name": "[好吃]", "file": "haochi.png" },
        { "name": "[呕吐]", "file": "outu.png" },
        { "name": "[龇牙]", "file": "ziya.png" },
        { "name": "[懵比]", "file": "mengbi.png" },
        { "name": "[白眼]", "file": "baiyan.png" },
        { "name": "[饿死]", "file": "esi.png" },
        { "name": "[凶]", "file": "xiong.png" },
        { "name": "[感冒]", "file": "ganmao.png" },
        { "name": "[流汗]", "file": "liuhan.png" },
        { "name": "[笑哭]", "file": "xiaoku.png" },
        { "name": "[流口水]", "file": "liukoushui.png" },
        { "name": "[尴尬]", "file": "ganga.png" },
        { "name": "[惊讶]", "file": "jingya.png" },
        { "name": "[大惊]", "file": "dajing.png" },
        { "name": "[不好意思]", "file": "buhaoyisi.png" },
        { "name": "[大闹]", "file": "danao.png" },
        { "name": "[不可思议]", "file": "bukesiyi.png" },
        { "name": "[爱你]", "file": "aini.png" },
        { "name": "[红心]", "file": "hongxin.png" },
        { "name": "[点赞]", "file": "dianzan.png" },
        { "name": "[恶魔]", "file": "emo.png" }
    ]

    // 通知消息
    function audioPlay(type) {
        let ex = new Audio(`/assets/addons/cus/img/chat/${type}.mp3`);
        if (ex) {
            ex.play();
        }
    }

    // 客服错误通知
    function callBackNotice(res, flag = false) {
        flag &&
            res.msg &&
            ElementPlus.ElNotification({
                title: 'socket',
                message: `客服错误：${res.msg}`,
                showClose: true,
                type: res.code == 1 ? 'success' : 'warning',
                duration: 3000,
            });
    }

    const IS_DEBUG = true; // DEBUG开关

    const debug = (...args) => {
        if (IS_DEBUG) {
            console.info('%c%s', 'color: blue; background: yellow; font-size: 11px;', ...args);
        }
        return;
    };

    // 重新连接尝试次数
    const reconnectionAttempts = 5; //次
    // 重新连接间隔时间
    const reconnectionDelay = 10; // 秒

    const SaChat = {
        template: `#saChatTemplate`,
        props: {},
        setup(props) {

            const { ref, reactive, computed, getCurrentInstance, nextTick } = Vue

            const { proxy } = getCurrentInstance();

            const chat = {
                state: reactive({
                    config: {}, // 配置信息
                    chatService: null, // 示例
                    sessionList: [], // 会话列表
                    customerServicesList: [], // 客服列表
                    sessionType: 'ing', // 列表状态 ing=会话中|waiting=排队中| history=历史
                    customerOnLineList: [], // 会话中
                    customerWaitingList: [], // 排队中
                    customerHistoryList: [], // 历史
                    chatList: [], // 会话信息
                    commonWords: [], // 常用语列表
                    currentCustomerService: {}, // 当前客服信息
                    customerServiceIdentityList: [], // 客服身份列表
                    currentCustomer: null, // 当前顾客信息
                    historyPagination: {
                        page: 0,
                        list_rows: 10,
                        last_id: 0,
                        lastPage: 0,
                        loadStatus: 'loadmore', //loadmore-加载前的状态，loading-加载中的状态，nomore-没有更多的状态
                    },
                    connection: {
                        status: '',
                        attempts: 0,
                        reconnectionAttempts,
                        delay: 0,
                        showTip: true,
                        isFlag: false,
                    }, // 连接状态
                    isSendSucces: 0, // 是否发送成功 -1=发送中|0=发送成功|1发送失败

                    notificationType: '', // 站内信类型
                    notificationTypeList: [], // 站内信类型列表
                    notificationList: [], // 站内信列表
                }),

                // 打开客服弹窗
                open_chat() {
                    chat.state.currentCustomer = chat.state.customerOnLineList[0];
                    chat.state.customerOnLineList.length && chat.socket_change_customer_list({
                        session: chat.state.sessionType,
                    })
                },

                // 初始化chat配置请求
                async chatInit() {
                    Fast.api.ajax({
                        url: 'cus/chat/index/init',
                        type: 'GET',
                    }, function (ret, res) {
                        chat.state.config = res.data;
                        chat.socket_init(chat.state.config.chat_domain);
                        return false
                    }, function (ret, res) { })
                },

                // 1.初始化socket
                async socket_init(connectionUrl, options) {
                    chat.state.connection.status = 'connecting';
                    chat.state.connection.isFlag = true
                    // 连接
                    try {
                        chat.state.chatService = io(connectionUrl, {
                            reconnection: true, // 默认 true    是否断线重连
                            reconnectionAttempts: reconnectionAttempts, // 默认无限次   断线尝试次数
                            reconnectionDelay: reconnectionDelay * 1000, // 默认 1000，进行下一次重连的间隔。
                            reconnectionDelayMax: reconnectionDelay * 1000, // 默认 5000， 重新连接等待的最长时间 默认 5000
                            randomizationFactor: 0.5, // 默认 0.5 [0-1]，随机重连延迟时间
                            timeout: 20000, // 默认 20s
                            transports: ['websocket', 'polling'], // websocket | polling,
                            ...options,
                        });
                    } catch (error) {
                        debug('Socket连接错误', error);
                    }

                    // 连接成功
                    chat.state.chatService.on('connect', (res) => {
                        debug('connect', res);
                        chat.state.connection.status = 'connect';
                        chat.socket_connection();
                    });

                    // 监听会话
                    chat.state.chatService.on('message', (res) => {
                        debug('message', res);
                        if (res.code == 1) {
                            audioPlay('chat');
                            const { message, sender } = res.data;
                            if (chat.state.currentCustomer && chat.state.currentCustomer?.session_id == sender.session_id) {
                                // 如果是当前会话，数字角标不增加，chatList，push
                                chat.state.chatList.push(message);
                                scrollBottom();
                            } else {
                                // 非当前会话，顾客角标+1
                                const index = chat.state.customerOnLineList.findIndex(
                                    (item) => item.session_id == sender.session_id,
                                );
                                if (~index) chat.state.customerOnLineList[index].unread_num += 1;
                            }
                        }
                    });

                    // 用户上线
                    chat.state.chatService.on('customer_online', (res) => {
                        res.code == 1 && chat.socket_change_customer_state(res.data);
                    });

                    // 用户下线
                    chat.state.chatService.on('customer_offline', (res) => {
                        res.code == 1 && chat.socket_change_customer_state(res.data);
                    });

                    // 用户被接入
                    chat.state.chatService.on('customer_accessed', (res) => {
                        if (res.code == 1) {
                            const { session_id, chat_user } = res.data;
                            const index = chat.state.customerWaitingList.findIndex((item) => item.session_id == session_id);
                            index >= 0 && chat.state.customerWaitingList.splice(index, 1);
                            if (chat.state.sessionType == 'waiting') {
                                chat.state.currentCustomer =
                                    chat.state.customerWaitingList.length >= 1
                                        ? chat.state.customerWaitingList[0]
                                        : chat.socket_reset();
                            }
                        }
                    });

                    // 新用户接入
                    chat.state.chatService.on('customer_access', (res) => {
                        if (res.code == 1) {
                            const { session_id, chat_user } = res.data;
                            const index = chat.state.customerOnLineList.findIndex((item) => item.session_id == session_id);
                            // 如果用户存在，修改会话中用户状态
                            if (index >= 0) {
                                chat.state.customerOnLineList[index].status = chat_user.status;
                            } else {
                                // 如果用户不存在
                                chat.state.customerOnLineList.unshift(chat_user);
                                // 如果当前会话列表有且只有一个新增会话
                                if (chat.state.customerOnLineList.length > 0 && chat.state.sessionType == 'ing') {
                                    chat.state.currentCustomer = chat_user;
                                    chat.socket_customer_history();
                                }
                            }

                            callBackNotice(res);
                        }
                    });

                    // 更新客服列表customer_service_update
                    chat.state.chatService.on('customer_service_update', (res) => {
                        if (res.code == 1) {
                            chat.state.customerServicesList = res.data.customer_services.map((item) => ({
                                label: item.name,
                                value: item.id,
                            }));
                        }
                    });

                    // 新顾客等待，更新等待列表
                    chat.state.chatService.on('customer_waiting', (res) => {
                        if (res.code == 1) {
                            chat.state.customerWaitingList = res.data.waitings;
                            audioPlay('chat');
                            if (chat.state.sessionType == 'waiting') {
                                chat.state.sessionList = res.data.waitings;
                                chat.state.currentCustomer = chat.state.sessionList.length
                                    ? res.data.waitings[0]
                                    : chat.socket_reset();
                            }
                        }
                    });

                    // 消息通知
                    chat.state.chatService.on('notification', (res) => {
                        if (res.code == 1) {
                            audioPlay('notice');
                            if (chat.state.notificationType == res.data.notification_type) {
                                chat.state.notificationList.unshift(res.data);
                            } else {
                                chat.state.notificationTypeList.map((item) => {
                                    if (item.value == res.data.notification_type) {
                                        item.unread_num += 1;
                                    }
                                });
                            }
                        }
                    });

                    // 自定义错误
                    chat.state.chatService.on('custom_error', (error) => {
                        callBackNotice(error, true);
                    });

                    chat.state.chatService.on('error', (error) => {
                        chat.state.connection.status = 'error';
                        debug('error', error);
                        scrollBottom();
                    });

                    // 连接失败
                    chat.state.chatService.on('connect_error', (error) => {
                        debug('connect_error:', error);
                        scrollBottom();
                    });

                    // 连接超时
                    chat.state.chatService.on('connect_timeout', (error) => {
                        debug('connect_timeout:', error);
                    });

                    // 断开连接
                    chat.state.chatService.on('disconnect', (error) => {
                        debug('disconnect:', error);
                    });

                    // 服务重启重连上reconnect
                    chat.state.chatService.on('reconnect', (error) => {
                        debug('disconnect:', error);
                    });

                    // 尝试重连
                    chat.state.chatService.on('reconnect_attempt', (counter) => {
                        debug('reconnect_attempt', counter);
                        chat.state.connection.status = 'reconnect_attempt';
                    });

                    // 重新连接中
                    chat.state.chatService.on('reconnecting', (counter) => {
                        debug('reconnecting', counter);
                        chat.state.connection.status = 'reconnecting';
                        chat.state.connection.attempts = counter;
                    });

                    // 重连失败
                    chat.state.chatService.on('reconnect_error', (error) => {
                        debug('reconnect_error', error);
                        if (chat.state.connection.attempts >= chat.state.connection.reconnectionAttempts) {
                            return;
                        }
                        chat.state.connection.status = 'reconnect_error';
                        // 设置倒计时
                        chat.state.connection.delay = reconnectionDelay;
                        const timer = setInterval(() => {
                            chat.state.connection.delay -= 1;
                            if (chat.state.connection.delay <= 1) {
                                clearInterval(timer);
                            }
                        }, 1000);
                    });

                    // 重连失败 达到最大重试次数
                    chat.state.chatService.on('reconnect_failed', () => {
                        debug('reconnect_failed');
                        chat.state.connection.status = 'reconnect_failed';
                        chat.state.isSendSucces = 1;
                        chat.state.connection.isFlag = false
                    });

                    // 与服务器连接失败
                    chat.state.chatService.on('connect_failed', (error) => {
                        debug('connect_failed', error);
                    });
                },

                // socket 连接
                socket_connection(token) {
                    chat.state.chatService.emit(
                        'connection',
                        {
                            auth: 'admin',
                            token: chat.state.config.token,
                        },
                        (res) => {
                            debug('socket_connection:', res);
                            if (res.code == 1) {
                                chat.socket_check_identify();
                            }
                        },
                    );
                },

                // 检测是否是客服 获取客服身份
                socket_check_identify() {
                    chat.state.chatService.emit('check_identify', {}, (res) => {
                        debug('socket_check_identify:', res);
                        if (res.code == 1) {
                            chat.socket_customer_login(res.data.customer_services[0]);
                            chat.state.customerServiceIdentityList = res.data.customer_services.map((item) => ({
                                label: item.name,
                                value: item.id,
                                room_id: item.room_id,
                            }));
                        }
                    });
                },

                // 客服登录
                socket_customer_login(data) {
                    chat.state.chatService.emit(
                        'customer_service_login',
                        {
                            room_id: data?.room_id || 'admin',
                        },
                        (res) => {
                            debug('customer_service_login:', res);
                            if (res.code == 1) {
                                chat.state.currentCustomerService = res.data.customer_service;
                                chat.state.commonWords = res.data.common_words;
                                chat.socket_customer_init();
                            }
                            callBackNotice(res);
                        },
                    );
                },
                // 初始化客服
                socket_customer_init() {
                    chat.state.chatService.emit('customer_service_init', {}, (res) => {
                        if (res.code == 1) {
                            chat.state.customerOnLineList = res.data.onlines;
                            chat.state.customerWaitingList = res.data.waitings;
                            if (chat.state.customerWaitingList.length > 0) {
                                // audioPlay('chat');
                            }
                            chat.state.customerHistoryList = res.data.histories;
                            chat.state.sessionList = res.data.onlines;
                            // 当前顾客选中
                            if (chat.state.sessionList.length) {
                                if (!currentSessionTypeIndexs.hasOwnProperty(chat.state.sessionType)) {
                                    currentSessionTypeIndexs[chat.state.sessionType] = 0;
                                }
                            }
                            chat.socket_change_customer_list({
                                session: chat.state.sessionType,
                            });
                        }
                    });
                },

                // 切换客服身份
                socket_change_customer_identity(data) {
                    // 退出客服
                    chat.socket_logout_customer();
                    // 登录客服
                    let info = chat.state.customerServiceIdentityList.filter((item) => data == item.value);
                    chat.socket_customer_login(info[0]);
                    // 初始化客服
                },

                // 切换会话列表
                socket_change_customer_list(data) {
                    chat.socket_reset();
                    chat.state.sessionType = data.session;
                    switch (data.session) {
                        case 'ing':
                            chat.state.sessionList = chat.state.customerOnLineList;
                            break;
                        case 'waiting':
                            chat.state.sessionList = chat.state.customerWaitingList;
                            break;
                        case 'history':
                            chat.state.sessionList = chat.state.customerHistoryList;
                            break;
                        default:
                            break;
                    }
                    if (!data.type) {
                        // 默认显示第一个人的会话信息
                        chat.state.sessionList.length && chat.socket_change_customer_info(data.index || 0);
                    }
                },

                // 修改顾客信息，获取历史记录
                socket_change_customer_info(index = 0) {
                    chat.socket_reset();
                    chat.state.currentCustomer = chat.state.sessionList[index];
                    if (chat.state.sessionList[index]) {
                        chat.state.sessionList[index].unread_num = 0;
                    }
                    chat.socket_customer_history();
                },

                // 重置
                socket_reset() {
                    chat.state.currentCustomer = null;
                    chat.state.chatList = [];
                    chat.state.historyPagination = {
                        page: 0,
                        list_rows: 10,
                        last_id: 0,
                        totalPage: 0,
                        loadStatus: 'loadmore',
                    };
                },

                // 退出客服
                socket_logout_customer() {
                    debug('socket_logout_start');
                    chat.state.chatService.emit('customer_service_logout', {}, (res) => {
                        debug('socket_logout_res:', res);
                    });
                },

                // 获取用户历史消息
                socket_customer_history() {
                    if (!chat.state.currentCustomer) return;
                    chat.state.historyPagination.loadStatus = 'loading';
                    chat.state.historyPagination.page += 1;
                    chat.state.chatService.emit(
                        'messages',
                        {
                            session_id: chat.state.currentCustomer?.session_id || 0,
                            ...chat.state.historyPagination,
                        },
                        (res) => {
                            if (res.code == 1) {
                                chat.state.historyPagination.total = res.data.messages.total;
                                chat.state.historyPagination.lastPage = res.data.messages.last_page;
                                chat.state.historyPagination.page = res.data.messages.current_page;
                                if (res.data.messages.current_page == 1) {
                                    chat.state.chatList = [];
                                }
                                res.data.messages.data.forEach((item) => {
                                    chat.state.chatList.unshift(item);
                                });
                                chat.state.historyPagination.loadStatus =
                                    chat.state.historyPagination.page < chat.state.historyPagination.lastPage
                                        ? 'loadmore'
                                        : 'nomore';
                                chat.state.historyPagination.page == 1 && scrollBottom();
                                if (chat.state.historyPagination.last_id == 0) {
                                    chat.state.historyPagination.last_id = res.data.messages.data.length
                                        ? res.data.messages.data[0].id
                                        : 0;
                                }
                            }
                        },
                    );
                },

                // 修改顾客状态
                socket_change_customer_state(data) {
                    const { session_id, chat_user } = data;
                    let waitingIndex = -1,
                        onlineIndex = -1,
                        historyIndex = -1;

                    if (chat.state.customerWaitingList.length) {
                        waitingIndex = chat.state.customerWaitingList.findIndex((item) => item.session_id == session_id);
                        if (waitingIndex >= 0) {
                            chat.state.customerWaitingList[waitingIndex].status = chat_user.status;
                        }
                    }
                    if (chat.state.customerOnLineList.length) {
                        onlineIndex = chat.state.customerOnLineList.findIndex((item) => item.session_id == session_id);
                        if (onlineIndex >= 0) {
                            chat.state.customerOnLineList[onlineIndex].status = chat_user.status;
                        }
                    }
                    if (chat.state.customerHistoryList.length) {
                        historyIndex = chat.state.customerHistoryList.findIndex((item) => item.session_id == session_id);
                        if (historyIndex >= 0) {
                            chat.state.customerHistoryList[historyIndex].status = chat_user.status;
                        }
                    }
                },

                // 发送消息
                socket_send(data) {
                    // 给会话列表
                    chat.state.isSendSucces = -1;
                    chat.state.chatList.push(data);
                    // 给socket
                    chat.state.chatService.emit(
                        'message',
                        {
                            session_id: chat.state.currentCustomer?.session_id || 0,
                            message: {
                                message: data.message,
                                message_type: data.message_type,
                            },
                        },
                        (res) => {
                            chat.state.isSendSucces = res.error;
                        },
                    );
                },

                // 断开，删除顾客
                socket_change_customer(session_id, index, sessionType, is_del_record) {
                    if (sessionType == 'ing') {
                        chat.state.chatService.emit('break_customer', { session_id }, (res) => {
                            if (res.code == 1) {
                                chat.state.customerOnLineList.splice(index, 1);
                                chat.socket_reset();
                            }
                            callBackNotice(res);
                        });
                    }
                    if (sessionType == 'history') {
                        chat.state.chatService.emit('del_customer', { session_id, is_del_record }, (res) => {
                            if (res.code == 1) {
                                chat.state.customerHistoryList.splice(index, 1);
                                chat.socket_reset();
                            }
                            callBackNotice(res);
                        });
                    }
                },

                // 切换客服状态
                socket_change_customer_service_status(status) {
                    console.log(chat.state, 'chat.state')
                    // return 
                    switch (status) {
                        case 'online':
                            chat.state.chatService.emit('customer_service_online', {}, (res) => {
                                changeBack(res);
                            });
                            break;
                        case 'busy':
                            chat.state.chatService.emit('customer_service_busy', {}, (res) => {
                                changeBack(res);
                            });
                            chat.state.sessionList = chat.state.customerWaitingList;
                            break;
                        case 'offline':
                            chat.state.chatService.emit('customer_service_offline', {}, (res) => {
                                changeBack(res);
                            });
                            chat.state.sessionList = chat.state.customerHistoryList;
                            break;
                        default:
                            break;
                    }
                    // 切换回调
                    function changeBack(res) {
                        if (res.code == 1) chat.state.currentCustomerService = res.data.customer_service;
                        callBackNotice(res);
                    }
                },

                // access 接入客户，会触发监听接入，被接入
                socket_customer_access(index) {
                    chat.state.chatService.emit(
                        'access',
                        { session_id: chat.state.currentCustomer?.session_id },
                        (res) => { },
                    );
                },

                // transfer 转接顾客
                socket_transfer(customer_service_id) {
                    chat.state.chatService.emit(
                        'transfer',
                        {
                            session_id: chat.state.currentCustomer?.session_id,
                            customer_service_id,
                        },
                        (res) => {
                            callBackNotice(res);
                            if (res.code == 1) {
                                const index = chat.state.customerOnLineList.findIndex(
                                    (item) => item.session_id == chat.state.currentCustomer?.session_id,
                                );
                                chat.state.customerOnLineList.splice(index, 1);
                                chat.socket_reset();
                            }
                        },
                    );
                },
            }

            const state = reactive({})

            // 打开客服
            const showChat = ref(false)
            function onShowChat() {
                showChat.value = !showChat.value;

                // 初始化聊天
                if (chat.state.connection.status != 'connect') {
                    if (!chat.state.connection.isFlag) {
                        chat.chatInit();
                    }
                    chat.open_chat();
                }
            }

            // 客服是否有未读消息
            const isChatUnreadNum = computed(() => {
                return chat.state.customerOnLineList.reduce((pre, cur) => {
                    return pre + cur?.unread_num;
                }, chat.state.customerWaitingList.length);
            })

            // 修改客服状态
            const customerServiceStatus = {
                online: '在线',
                offline: '离线',
                busy: '忙碌',
                // disconnect: 'sa-duankailianjie',
            }
            function onChangeCustomerServiceStatus(status) {
                if (chat.state.currentCustomerService.status == status) return;
                chat.socket_change_customer_service_status(status);
            };

            // 切换客服身份
            function onChangeCustomerServiceIdentity(e) {
                chat.socket_change_customer_identity(e);
            };

            // 修改会话类型
            const sessionTypeList = {
                ing: {
                    label: '会话中',
                    left: '2px'
                },
                waiting: {
                    label: '排队中',
                    left: '52px'
                },
                history: {
                    label: '历史',
                    left: '102px'
                }
            }
            function onChangeSessionType(session, type = '') {
                if (chat.state.sessionType == session) return;
                if (!currentSessionTypeIndexs.hasOwnProperty(session)) {
                    currentSessionTypeIndexs[session] = 0;
                }
                chat.socket_change_customer_list({
                    session,
                    type,
                    index: currentSessionTypeIndexs[session],
                });
            }

            // 操作当前顾客
            const currentSessionTypeIndexs = reactive({});
            function onChangeCurrentSessionTypeIndex(index) {
                if (currentSessionTypeIndexs[chat.state.sessionType] == index) return;
                currentSessionTypeIndexs[chat.state.sessionType] = index;
                chat.socket_change_customer_info(index);
            };

            // 删除会话中顾客
            function onDeleteSession(session_id, index, sessionType) {
                chat.socket_change_customer(session_id, index, sessionType);
            }

            // 操作历史顾客
            const historyDeletePopover = reactive({
                flag: {},
                is_del_record: 0,
            });
            function onCancelHistoryDeletePopover(index) {
                historyDeletePopover.flag[index] = false;
                historyDeletePopover.is_del_record = 0;
            }
            function onConfirmHistoryDeletePopover(session_id, index, sessionType) {
                chat.socket_change_customer(
                    session_id,
                    index,
                    sessionType,
                    historyDeletePopover.is_del_record,
                );
                onCancelHistoryDeletePopover(index);
            }

            // 获取除了自己的客服列表
            const avaliableCustomerServicesList = computed(() => {
                return chat.state.customerServicesList.filter((item) => {
                    return item.value != chat.state.currentCustomerService.id;
                });
            })

            // 转接客服
            const transferCustomer = ref(null);
            function onTransferCommand(val) {
                transferCustomer.value = val
            }
            function onTransferCustomer() {
                chat.socket_transfer(transferCustomer.value);
            };

            // 立即接入
            function onAccessCustomer() {
                chat.socket_customer_access();
                // 获取历史记录
                onChangeSessionType('ing', 'access');
            }

            // 加载更多
            function onLoadMore() {
                chat.state.historyPagination.page < chat.state.historyPagination.lastPage &&
                    chat.socket_customer_history();
            };

            const showTime = (item, index) => {
                if (chat.state.chatList[index + 1]) {
                    let dateString = Moment(chat.state.chatList[index + 1].createtime * 1000).fromNow();
                    if (dateString == Moment(item.createtime * 1000).fromNow()) {
                        return false;
                    } else {
                        dateString = Moment(item.createtime * 1000).fromNow();
                        return true;
                    }
                }
                return false;
            };

            // 格式化时间
            const formatTime = (time) => {
                let diffTime = Moment().unix() - time;
                if (diffTime > 28 * 24 * 60) {
                    return Moment(time * 1000).format('MM/DD HH:mm');
                }
                if (diffTime > 360 * 28 * 24 * 60) {
                    return Moment(time * 1000).format('YYYY/MM/DD HH:mm');
                }
                return Moment(time * 1000).fromNow();
            };

            function replaceEmoji(data) {
                let newData = data;
                if (typeof newData != 'object') {
                    let reg = /\[(.+?)\]/g; // [] 中括号
                    let zhEmojiName = newData.match(reg);
                    if (zhEmojiName) {
                        zhEmojiName.forEach((item) => {
                            let emojiFile = selEmojiFile(item);
                            newData = newData.replace(
                                item,
                                `<img class="message-emoji" src="${Fast.api.cdnurl(`/assets/addons/cus/img/chat/emoji/${emojiFile}`)}" />`,
                            );
                        });
                    }
                }
                return newData;
            }
            function selEmojiFile(name) {
                for (let index in emojiList) {
                    if (emojiList[index].name == name) {
                        return emojiList[index].file;
                    }
                }
                return false;
            }

            const messageInput = ref('');
            function getMessageInput(e) {
                if (
                    e.target.innerHTML.replace(/&nbsp;|\s/g, '') &&
                    e.target.innerHTML.replace(/&nbsp;|\s/g, '').indexOf('<br>') != 0
                ) {
                    messageInput.value = e.target;
                } else {
                    messageInput.value = '';
                }
            };
            // 获取焦点
            function getMessageInputFocus() {
                if (window.getSelection) {
                    let chatInput = proxy.$refs.messageInputRef;
                    chatInput.focus();
                    let range = window.getSelection();
                    range.selectAllChildren(chatInput);
                    range.collapseToEnd();
                } else if (document.selection) {
                    let range = document.selection.createRange();
                    range.moveToElementText(chatInput);
                    range.collapse(false);
                    range.select();
                }
            };
            // ctrl + enter 换行 ,enter发送
            function onKeyDown(e) {
                if (e.ctrlKey && e.keyCode == 13) {
                    document.execCommand('insertHTML', false, '<br></br>');
                } else if (e.keyCode == 13) {
                    // 阻止默认enter
                    e.preventDefault();
                    onSendMessage();
                    return false;
                }
            }

            function onSendMessage() {
                if (!messageInput.value || !chat.state.currentCustomer) return;
                let res = '';
                let elemArr = Array.from(messageInput.value.childNodes);
                elemArr.forEach((child, index) => {
                    if (child.nodeName == '#text') {
                        res += child.nodeValue;
                        if (elemArr[index + 1]?.nodeName == 'IMG' && elemArr[index + 1]?.name != 'emoji') {
                            const data = {
                                sender_identify: 'customer_service',
                                message_type: 'text',
                                message: res,
                                createtime: Moment().unix(),
                            };
                            chat.socket_send(data);
                            scrollBottom();
                            res = '';
                        }
                    } else if (child.nodeName == 'BR') {
                        res += '<br/>';
                    } else if (child.nodeName == 'IMG') {
                        if (child.name != 'emoji') {
                            let srcReg = /src=[\'\"]?([^\'\"]*)[\'\"]?/i;
                            let src = child.outerHTML.match(srcReg);
                            const data = {
                                sender_identify: 'customer_service',
                                message_type: 'image',
                                message: {
                                    url: Fast.api.cdnurl(src[1].replace(/http:\/\/[^\/]*/, ''), true),
                                    path: src[1].replace(/http:\/\/[^\/]*/, ''),
                                },
                                createtime: Moment().unix(),
                            };
                            chat.socket_send(data);
                            scrollBottom();
                        } else {
                            res += child.outerHTML;
                        }
                    } else if (child.nodeName == 'DIV') {
                        res += `<div style="width:200px; white-space: nowrap;">${child.outerHTML}</div>`;
                    }
                });
                if (res) {
                    const data = {
                        sender_identify: 'customer_service',
                        message_type: 'text',
                        message: res,
                        createtime: Moment().unix(),
                    };
                    chat.socket_send(data);
                }
                messageInput.value = '';
                proxy.$refs.messageInputRef.innerHTML = '';
                scrollBottom();
            }

            function onSelectToolbar(message_type, message) {
                !messageInput.value && getMessageInputFocus();
                let obj
                switch (message_type) {
                    case 'emoji':
                        let img = `<img src="${Fast.api.cdnurl('/assets/addons/cus/img/chat/emoji/' + message.file, true)}" name="emoji" style="object-fit: cover;vertical-align:bottom; display: inline-block;width:20px !important;height:20px;margin:2px">`;
                        document.execCommand('insertHTML', false, img);
                        break;
                    case 'text':
                        obj = {
                            sender_identify: 'customer_service',
                            message_type,
                            message,
                            createtime: Moment().unix(),
                        };
                        chat.socket_send(obj);
                        scrollBottom();
                        break;
                    case 'image':
                        Fast.api.open(`general/attachment/select`, "选择", {
                            callback: function (data) {
                                obj = {
                                    sender_identify: 'customer_service',
                                    message_type,
                                    message: data.url,
                                    createtime: Moment().unix(),
                                }
                                chat.socket_send(obj);
                                scrollBottom();
                            }
                        });
                        break;
                    case 'goods':
                        Fast.api.open(`cus/goods/goods/select`, "选择商品", {
                            callback(data) {
                                obj = {
                                    sender_identify: 'customer_service',
                                    message_type,
                                    message: {
                                        id: data.id,
                                        title: data.title,
                                        image: data.image,
                                        price: data.price,
                                        stock: data.stock,
                                    },
                                    createtime: Moment().unix(),
                                };
                                chat.socket_send(obj);
                                scrollBottom();
                            }
                        })
                        break;
                    default:
                        break;
                }
            }

            function onOpenGoodsDetail(id) {
                Fast.api.open(`cus/goods/goods/add?type=edit&id=${id}`, "商品详情")
            }

            function onOpenOrderDetail(id) {
                Fast.api.open(`cus/order/order/detail?id=${id}`, "订单详情")
            }

            function scrollBottom() {
                chat.state.connection.status == 'connect' &&
                    nextTick(() => {
                        console.log(proxy.$refs.chatScrollRef, '000')
                        console.log(proxy.$refs.chatScrollRef['wrap$'].childNodes[0].offsetHeight, 1)
                        console.log(proxy.$refs.chatScrollRef['wrap$'].offsetHeight, 2)
                        let scrollTop = proxy.$refs.chatScrollRef['wrap$'].childNodes[0].offsetHeight - proxy.$refs.chatScrollRef['wrap$'].offsetHeight + 20
                        console.log(scrollTop, 'scrollTop')
                        proxy.$refs.chatScrollRef.setScrollTop(scrollTop);
                    });
            }



            // 消息通知
            const showNotification = ref(false)
            function onShowNotification() {
                showNotification.value = true
                chat.state.notificationList = [];
                getNotificationType()
            }

            // 消息通知-是否有未读数据
            const isNotificationUnreadNum = computed(() => {
                return chat.state.notificationTypeList.reduce((pre, cur) => {
                    return pre + cur.unread_num;
                }, 0);
            })

            // 消息通知-类型
            function getNotificationType() {
                Fast.api.ajax({
                    url: 'cus/notification/notification/notificationType',
                    type: 'GET',
                }, function (ret, res) {
                    chat.state.notificationTypeList = res.data.notification_type;
                    chat.state.notificationType = res.data.notification_type[0].value

                    // 请求列表
                    pagination.page = 1;
                    getNotificationList()
                    return false
                }, function (ret, res) { })
            };

            // 消息通知-切换类型
            function onChangeNotificationType(type) {
                chat.state.notificationType = type
                chat.state.notificationList = [];
                pagination.page = 1;
                getNotificationList();
            };

            // 消息通知-列表
            const pagination = reactive({
                page: 0,
                lastPage: 0,
                loadStatus: 'loadmore', //loadmore-加载前的状态，loading-加载中的状态，nomore-没有更多的状态
            });
            function getNotificationList() {
                pagination.loadStatus = 'loading';
                Fast.api.ajax({
                    url: 'cus/notification/notification',
                    type: 'GET',
                    data: {
                        search: JSON.stringify({ notification_type: chat.state.notificationType }),
                        page: pagination.page,
                    },
                }, function (ret, res) {
                    if (pagination.page == 1) {
                        chat.state.notificationList = []
                    }
                    res.data.data.forEach((item) => {
                        chat.state.notificationList.push(item);
                    });

                    pagination.page = res.data.current_page;
                    pagination.lastPage = res.data.last_page;
                    pagination.loadStatus = pagination.page < pagination.lastPage ? 'loadmore' : 'nomore';
                    // 请求之后 把未读改为0
                    chat.state.notificationTypeList.map((item) => {
                        if (item.value == chat.state.notificationType) {
                            item.unread_num = 0;
                        }
                    });

                    return false
                }, function (ret, res) { })
            };
            function onLoadMoreNotification() {
                if (pagination.page < pagination.lastPage) {
                    pagination.page += 1;
                    getNotificationList();
                }
            };

            // 消息通知-标记为已读消息
            function onReadNotification(id, index) {
                Fast.api.ajax({
                    url: `cus/notification/notification/read/id/${id}`,
                    type: 'POST',
                }, function (ret, res) {
                    chat.state.notificationList[index] = res.data;
                    return false
                }, function (ret, res) { })
            }

            // 消息通知-清空已读消息
            function onClearNotification() {
                Fast.api.ajax({
                    url: 'cus/notification/notification/delete',
                    type: 'DELETE',
                }, function (ret, res) {
                    pagination.page = 1;
                    chat.state.notificationList = []
                    getNotificationList();
                    return false
                }, function (ret, res) { })
            };

            return {
                Fast,
                emojiList,
                chat,
                state,
                showChat,
                onShowChat,
                isChatUnreadNum,
                customerServiceStatus,
                onChangeCustomerServiceStatus,
                onChangeCustomerServiceIdentity,
                sessionTypeList,
                onChangeSessionType,
                currentSessionTypeIndexs,
                onChangeCurrentSessionTypeIndex,
                onDeleteSession,
                historyDeletePopover,
                onCancelHistoryDeletePopover,
                onConfirmHistoryDeletePopover,
                avaliableCustomerServicesList,
                transferCustomer,
                onTransferCommand,
                onTransferCustomer,
                onAccessCustomer,
                onLoadMore,
                showTime,
                formatTime,
                replaceEmoji,
                selEmojiFile,
                messageInput,
                getMessageInput,
                getMessageInputFocus,
                onKeyDown,
                onSendMessage,
                onSelectToolbar,
                onOpenGoodsDetail,
                onOpenOrderDetail,
                scrollBottom,

                showNotification,
                onShowNotification,
                isNotificationUnreadNum,
                onChangeNotificationType,
                pagination,
                onLoadMoreNotification,
                onReadNotification,
                onClearNotification,
                loadingMap: {
                    loadmore: {
                        title: '查看更多',
                        icon: 'el-icon-arrow-left',
                    },
                    nomore: {
                        title: '没有更多了',
                        icon: '',
                    },
                    loading: {
                        title: '加载中... ',
                        icon: 'el-icon-loading',
                    },
                },
            }
        }
    }
    return SaChat
})
