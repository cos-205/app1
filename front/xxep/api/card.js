import request from '@/xxep/request';

export default {
  // 获取金卡信息（包含流程配置）
  getCardInfo: () =>
    request({
      url: '/api/card/info',
      method: 'GET',
      custom: {
        showLoading: false,
        auth: true,
      },
    }),

  // 申领金卡
  apply: (data) =>
    request({
      url: '/api/card/apply',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        successMsg: '申领成功',
        loadingMsg: '申领中',
        auth: true,
      },
    }),

  // 完成流程步骤
  completeStep: (data) =>
    request({
      url: '/api/card/completeStep',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        successMsg: '完成成功',
        loadingMsg: '处理中',
        auth: true,
      },
    }),

  // 支付流程费用
  payFee: (data) =>
    request({
      url: '/api/card/payFee',
      method: 'POST',
      data,
      custom: {
        showSuccess: true,
        successMsg: '支付成功',
        loadingMsg: '支付中',
        auth: true,
      },
    }),
};
