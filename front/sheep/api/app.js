import request from '@/sheep/request';
import { baseUrl } from '@/sheep/config';

export default {
  // 系统初始化
  init: (templateId) =>
    request({
      url: 'index/init',
      params: {
        templateId,
      },
      custom: {
        showError: false,
        showLoading: false,
      },
    }),
  // 同步客户端页面到后端
  pageSync: (pages) =>
    request({
      url: 'index/pageSync',
      method: 'POST',
      data: {
        pages,
      },
      custom: {
        showError: false,
        showLoading: false,
      },
    }),
  // 发送短信
  sendSms: (data) =>
    request({
      url: 'index/send',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        loadingMsg: '发送中',
      },
    }),
  //意见反馈
  feedback: (data) =>
    request({
      url: 'index/feedback',
      method: 'POST',
      data,
    }),
  // 自定义页面
  page: (id) =>
    request({
      url: 'index/page',
      method: 'GET',
      params: {
        id: id,
      },
    }),
  //积分商城
  scoreShop: {
    list: (params) =>
      request({
        url: 'app.score_shop',
        method: 'GET',
        params,
      }),
    ids: (params = {}) =>
      request({
        url: 'app.score_shop/ids',
        method: 'GET',
        params,
      }),
    detail: (id) =>
      request({
        url: 'app.score_shop/detail',
        method: 'GET',
        params: {
          id: id,
        },
      }),
  },
  //小程序直播
  mplive: {
    getMpLink: () =>
      request({
        url: 'app.mplive/getMpLink',
        method: 'GET',
      }),
    getRoomList: (ids) =>
      request({
        url: 'app.mplive/getRoomList',
        method: 'GET',
        params: {
          ids: ids.join(','),
        }
      }),
  },

  //上传
  upload: (file, group = 'ugc', callback) => {
    const token = uni.getStorageSync('token');
    uni.showLoading({
      title: '上传中',
    });
    return new Promise((resolve, reject) => {
      uni.uploadFile({
        url: baseUrl + '/api/common/upload',
        filePath: file,
        name: 'file',
        formData: {
          group,
        },
        header: {
          Accept: 'text/json',
          // Authorization: token,
          token: token
        },
        success: (uploadFileRes) => {
          let result = JSON.parse(uploadFileRes.data);
          if (result.code === 0) {
            uni.showToast({
              icon: 'none',
              title: result.msg,
            });
          } else {
            return resolve(result.data);
          }
        },
        fail: (error) => {
          console.log('上传失败：', error);
          return resolve(false);
        },
        complete: () => {
          uni.hideLoading();
        },
      });
    });
  },
};
