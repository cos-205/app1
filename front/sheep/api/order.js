import request from '@/sheep/request';

export default {
  // 订单详情
  detail: (id) =>
    request({
      url: 'order.order/detail',
      method: 'GET',
      params: {
        id: id,
      },
    }),
  // 发货通知跳转详情
  merchant: (params) =>
    request({
      url: 'order.order/detail',
      method: 'GET',
      params,
    }),
  // 发票详情
  invoice: (id) =>
    request({
      url: 'order.invoice/detail',
      method: 'GET',
      params: {
        id: id,
      },
    }),
  // 获取支付结果
  // payResult: (id) =>
  //   request({
  //     url: 'order/order/' + id,
  //     method: 'GET',
  //     custom: {
  //       showLoading: false,
  //     },
  //   }),
  // 订单item详情
  itemDetail: (params) =>
    request({
      url: 'order.order/itemDetail',
      method: 'GET',
      params,
      custom: {
        showLoading: false,
      },
    }),
  // 订单列表
  list: (params) =>
    request({
      url: 'order.order',
      method: 'GET',
      params,
      custom: {
        showLoading: false,
      },
    }),
  // 计算订单信息
  calc: (data) =>
    request({
      url: 'order.order/calc',
      method: 'POST',
      data,
    }),
  // 创建订单
  create: (data) =>
    request({
      url: 'order.order/create',
      method: 'POST',
      data,
    }),
  //订单可用优惠券
  coupons: (data) =>
    request({
      url: 'order.order/coupons',
      method: 'POST',
      data,
    }),
  // 确认收货
  confirm: (id) =>
    request({
      url: 'order.order/confirm',
      method: 'POST',
      params: {
        id: id,
      },
    }),
  // 评价订单
  comment: (id, data) =>
    request({
      url: 'order.order/comment',
      method: 'POST',
      params: {
        id: id,
      },
      data,
    }),
  // 申请退款
  applyRefund: (id) =>
    request({
      url: 'order.order/applyRefund',
      method: 'POST',
      params: {
        id: id,
      },
    }),
  // 取消订单
  cancel: (id) =>
    request({
      url: 'order.order/cancel',
      method: 'POST',
      params: {
        id: id,
      },
    }),
  // 删除订单
  delete: (id) =>
    request({
      url: 'order.order/delete',
      method: 'DELETE',
      params: {
        id: id,
      },
    }),
  // 售后
  aftersale: {
    // 申请售后
    apply: (data) =>
      request({
        url: 'order.aftersale/add',
        method: 'POST',
        data,
      }),
    list: (params) =>
      request({
        url: 'order.aftersale',
        method: 'GET',
        params,
        custom: {
          showLoading: false,
        },
      }),
    //取消售后
    cancel: (id) =>
      request({
        url: 'order.aftersale/cancel',
        method: 'POST',
        params: {
          id: id,
        },
      }),
    //删除售后单
    delete: (id) =>
      request({
        url: 'order.aftersale/delete',
        method: 'DELETE',
        params: {
          id: id,
        },
      }),
    // 售后详情
    detail: (id) =>
      request({
        url: 'order.aftersale/detail',
        method: 'GET',
        params: {
          id: id,
        },
      }),
  },
  //订单包裹
  express: {
    detail: (id, orderId) =>
      request({
        url: 'order.express/detail',
        method: 'GET',
        params: {
          id: id,
          order_id: orderId,
        },
      }),
    list: (orderId) =>
      request({
        url: 'order.express',
        method: 'GET',
        params: {
          order_id: orderId,
        },
        custom: {
          showLoading: false,
        },
      }),
  },

  // 申请开票
  applyInvoice: (data) =>
    request({
      url: 'order.order/applyInvoice',
      method: 'POST',
      data,
    }),
};
