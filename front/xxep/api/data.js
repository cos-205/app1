import request from '@/xxep/request';

export default {
  //省市区列表
  area: () =>
    request({
      url: 'data.area',
      method: 'GET',
    }),
  //常见问题
  faq: () =>
    request({
      url: 'data.faq',
      method: 'GET',
    }),
  //富文本
  richtext: (id) =>
    request({
      url: 'data.richtext',
      method: 'GET',
      params: {
        id: id,
      },
    }),
};
