import request from '@/sheep/request';

export default {
  // 获取聊天token
  unifiedToken: () =>
    request({
      url: 'index/unifiedToken',
      custom: {
        showError: false,
        showLoading: false,
      },
    }),
};
