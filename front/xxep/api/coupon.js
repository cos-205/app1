import request from '@/xxep/request';

export default {
  // 优惠券
  list: (params) =>
    request({
      url: 'coupon',
      method: 'GET',
      params,
      custom: {
        showLoading: false,
      },
    }),
  userCoupon: (params) =>
    request({
      url: 'user.coupon',
      method: 'GET',
      params,
    }),
  detail: (id, user_coupon_id) =>
    request({
      url: 'coupon/detail',
      method: 'GET',
      params: {
        id: id,
        user_coupon_id,
      },
    }),
  get: (id) =>
    request({
      url: 'coupon/get',
      method: 'POST',
      params: {
        id: id,
      },
    }),
  listByGoods: (id) =>
    request({
      url: 'coupon/listByGoods',
      method: 'GET',
      params: {
        goods_id: id,
      },
    }),
};
