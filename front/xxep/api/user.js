import request from '@/xxep/request';
import $platform from '@/xxep/platform';

export default {
  profile: () =>
    request({
      url: 'user.user/profile',
      method: 'GET',
      custom: {
        showLoading: false,
        auth: true,
      },
    }),
  update: (data) =>
    request({
      url: 'user.user/update',
      method: 'POST',
      custom: {
        showSuccess: true,
        auth: true,
      },
      data,
    }),
  // 账号登录
  accountLogin: (data) =>
    request({
      url: 'user.user/accountLogin',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '登录中',
      },
    }),
  // 短信登录
  smsLogin: (data) =>
    request({
      url: 'user.user/smsLogin',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '登录中',
      },
    }),
  // 短信注册
  smsRegister: (data) =>
    request({
      url: 'user.user/smsRegister',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '正在注册',
      },
    }),
  // 重置密码
  resetPassword: (data) =>
    request({
      url: 'user.user/resetPassword',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '验证中',
      },
    }),
  // 修改密码
  changePassword: (data) =>
    request({
      url: 'user.user/changePassword',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '验证中',
      },
    }),
  // 绑定、更换手机号
  changeMobile: (data) =>
    request({
      url: 'user.user/changeMobile',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '验证中',
      },
    }),
  // 修改用户名
  changeUsername: (data) =>
    request({
      url: 'user.user/changeUsername',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '验证中',
      },
    }),
  // 更新小程序信息
  updateMpUserInfo: (data) =>
    request({
      url: 'user.user/updateMpUserInfo',
      method: 'POST',
      data,
    }),

  // 第三方授权信息
  thirdOauthInfo: () =>
    request({
      url: 'user.user/thirdOauth',
      method: 'GET',
      params: {
        provider: $platform.provider,
        platform: $platform.platform,
      },
      custom: {
        showLoading: false,
      },
    }),

  // 添加分享记录
  addShareLog: (data) =>
    request({
      url: 'share/add',
      method: 'POST',
      data,
      custom: {
        showError: false,
      },
    }),
  share: {
    list: (params) =>
      request({
        url: 'share',
        method: 'GET',
        params,
      }),
  },
  // 账号登出
  logout: (data) =>
    request({
      url: 'user.user/logout',
      method: 'POST',
      data,
    }),
  // 账号注销
  logoff: (data) =>
    request({
      url: 'user.user/logoff',
      method: 'POST',
      data,
    }),
  // 提交实名认证
  submitRealname: (data) =>
    request({
      url: '/api/user/submitRealname',
      method: 'POST',
      data,
      custom: {
        showLoading: true,
        loadingMsg: '认证中',
        auth: true,
      },
    }),
  
  // 完善信息（实名认证+收货地址）
  setupRequired: (data) =>
    request({
      url: '/api/user/setupRequired',
      method: 'POST',
      data,
      custom: {
        showLoading: true,
        loadingMsg: '提交中',
        auth: true,
      },
    }),
  
  // 绑定支付宝
  bindAlipay: (data) =>
    request({
      url: '/api/user/bindAlipay',
      method: 'POST',
      data,
      custom: {
        showLoading: true,
        loadingMsg: '绑定中',
        auth: true,
      },
    }),
  
  // 绑定微信
  bindWechat: (data) =>
    request({
      url: '/api/user/bindWechat',
      method: 'POST',
      data,
      custom: {
        showLoading: true,
        loadingMsg: '绑定中',
        auth: true,
      },
    }),

  address: {
    default: () =>
      request({
        url: 'user.address/default',
        method: 'GET',
        custom: {
          showError: false,
        },
      }),
    list: () =>
      request({
        url: 'user.address',
        method: 'GET',
        custom: {},
      }),
    create: (data) =>
      request({
        url: 'user.address/add',
        method: 'POST',
        data,
        custom: {
          showSuccess: true,
        },
      }),
    update: (id, data) =>
      request({
        url: 'user.address/edit',
        method: 'POST',
        params: {
          id: id,
        },
        data,
        custom: {
          showSuccess: true,
        },
      }),
    detail: (id) =>
      request({
        url: 'user.address/detail',
        method: 'GET',
        params: {
          id: id,
        },
      }),
    delete: (id) =>
      request({
        url: 'user.address/delete',
        method: 'DELETE',
        params: {
          id: id,
        },
      }),
  },
  invoice: {
    list: () =>
      request({
        url: 'user.invoice',
        method: 'GET',
        custom: {},
      }),
    create: (data) =>
      request({
        url: 'user.invoice/add',
        method: 'POST',
        data,
        custom: {
          showSuccess: true,
        },
      }),
    update: (id, data) =>
      request({
        url: 'user.invoice/edit',
        method: 'POST',
        params: {
          id: id,
        },
        data,
        custom: {
          showSuccess: true,
        },
      }),
    detail: (id) =>
      request({
        url: 'user.invoice/detail',
        method: 'GET',
        params: {
          id: id,
        },
      }),
    delete: (id) =>
      request({
        url: 'user.invoice/delete',
        method: 'DELETE',
        params: {
          id: id,
        },
      }),
  },
  goodsLog: {
    list: (params) =>
      request({
        url: 'user.goods_log',
        method: 'GET',
        params,
      }),
  },
  favorite: {
    do: (id) =>
      request({
        url: 'user.goods_log/favorite',
        method: 'POST',
        data: {
          goods_id: id,
        },
        custom: {
          showSuccess: true,
          auth: true,
        },
      }),
    cancel: (id) =>
      request({
        url: 'user.goods_log/favorite',
        method: 'POST',
        data: {
          goods_ids: id,
        },
        custom: {
          showSuccess: true,
          auth: true,
        },
      }),
  },
  view: {
    delete: (data) =>
      request({
        url: 'user.goods_log/viewDel',
        method: 'DELETE',
        data,
        custom: {
          showSuccess: true,
        },
      }),
  },
  wallet: {
    log: (params) =>
      request({
        url: 'user.wallet_log',
        method: 'GET',
        params,
        custom: {},
      }),
  },
  account: {
    info: (params) =>
      request({
        url: 'user.account',
        method: 'GET',
        params,
        custom: {
          showError: false,
          auth: true,
        },
      }),
    save: (data) =>
      request({
        url: 'user.account/save',
        method: 'POST',
        data,
        custom: {
          showSuccess: true,
          auth: true,
        },
      }),
  },
  //数量接口
  data: () =>
    request({
      url: 'user.user/data',
      method: 'GET',
      custom: {
        showLoading: false,
        auth: true,
      },
    }),
};
