import request from '@/xxep/request';

export default {
  // 预支付
  prepay: (data) =>
    request({
      url: 'pay/prepay',
      method: 'POST',
      data,
      custom: {
        loadingMsg: '支付中',
      },
    }),
  // 获取收款渠道列表
  channelList: () =>
    request({
      url: '/api/payment/channelList',
      method: 'GET',
    }),
  // 上传支付凭证（商品订单）
  uploadScreenshot: (data) =>
    request({
      url: '/api/payment/uploadScreenshot',
      method: 'POST',
      data,
      custom: {
        loadingMsg: '上传中',
        auth: true,
      },
    }),
  // 上传支付凭证（金卡订单）
  uploadCardScreenshot: (data) =>
    request({
      url: '/api/payment/uploadCardScreenshot',
      method: 'POST',
      data,
      custom: {
        loadingMsg: '上传中',
        auth: true,
      },
    }),
  // 查询支付凭证审核状态
  screenshotStatus: (params) =>
    request({
      url: '/api/payment/screenshotStatus',
      method: 'GET',
      params,
      custom: {
        auth: true,
      },
    }),
  // 发起提现 
  withdraw: {
    list: (params) =>
      request({
        url: 'withdraw',
        method: 'GET',
        params,
        custom: {
          auth: true,
        },
      }),
    rules: () =>
      request({
        url: 'withdraw/rules',
        method: 'GET',
        custom: {
          auth: true,
        },
      }),
    apply: (data) =>
      request({
        url: 'withdraw/apply',
        method: 'POST',
        data,
        custom: {
          loadingMsg: '申请中',
          auth: true,
        },
      }),
    cancel: (data) =>
      request({
        url: 'withdraw/cancel',
        method: 'POST',
        data,
        custom: {
          loadingMsg: '取消中',
          auth: true,
        },
      }),
    retry: (data) =>
      request({
        url: 'withdraw/retry',
        method: 'POST',
        data,
        custom: {
          loadingMsg: '提现中',
          auth: true,
        },
      }),
  },
};
