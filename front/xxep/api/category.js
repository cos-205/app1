import request from '@/xxep/request';

export default {
  list: (params) =>
    request({
      url: 'category',
      method: 'GET',
      params,
    }),
};
