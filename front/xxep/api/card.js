/**
 * 福卡相关API接口 + 金卡相关API接口
 */
import $request from '../request'

export default {
  // ========== 金卡相关API ==========
  
  /**
   * 获取流程配置
   * @returns {Promise}
   */
  flowConfig: () => $request({
    url: '/api/card/flowConfig',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 申领金卡
   * @param {Object} data 申领数据
   * @returns {Promise}
   */
  apply: (data) => $request({
    url: '/api/card/apply',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '申领中...',
      showSuccess: true,
      auth: true,
    },
  }),

  /**
   * 创建支付订单
   * @param {Object} data { step: 步骤ID }
   * @returns {Promise}
   */
  createOrder: (data) => $request({
    url: '/api/card/createOrder',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '创建订单中...',
      auth: true,
    },
  }),

  /**
   * 获取订单信息
   * @param {Object} params { order_id: 订单ID }
   * @returns {Promise}
   */
  getOrderInfo: (params) => $request({
    url: '/api/card/getOrderInfo',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取支付参数
   * @param {Object} data { order_id: 订单ID, pay_type: 支付类型 }
   * @returns {Promise}
   */
  getPaymentParams: (data) => $request({
    url: '/api/card/getPaymentParams',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '获取支付参数...',
      auth: true,
    },
  }),

  /**
   * 查询支付结果
   * @param {Object} params { order_no: 订单号 }
   * @returns {Promise}
   */
  paymentResult: (params) => $request({
    url: '/api/card/paymentResult',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 签署协议（步骤1和步骤4）
   * @param {Object} data { step: 步骤ID（1或4） }
   * @returns {Promise}
   */
  signAgreement: (data) => $request({
    url: '/api/card/signAgreement',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '签署中...',
      auth: true,
    },
  }),

  /**
   * 获取协议内容（用于协议签署页面）
   * @param {Object} params { step: 步骤ID（1或4） }
   * @returns {Promise}
   */
  agreementContent: (params) => $request({
    url: '/api/card/agreementContent',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 提交步骤数据（用于步骤3、4等需要提交数据的步骤）
   * @param {Object} data { step: 步骤ID, data: 步骤数据（如密码、限额等） }
   * @returns {Promise}
   */
  submitStepData: (data) => $request({
    url: '/api/card/submitStepData',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '提交中...',
      auth: true,
    },
  }),

  /**
   * 完成某个流程步骤（支持额外数据）
   * @param {Object} data { step: 步骤ID, extra_data: 额外数据 }
   * @returns {Promise}
   */
  completeStepV2: (data) => $request({
    url: '/api/card/completeStepV2',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '提交中...',
      auth: true,
    },
  }),

  /**
   * 获取协议流程列表
   * @param {Object} params { step_id: 步骤ID }
   * @returns {Promise}
   */
  agreementList: (params) => $request({
    url: '/api/card/agreementList',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取协议处理流程（用于金卡界面显示）
   * @param {Object} params { step_id: 步骤ID（默认为1） }
   * @returns {Promise}
   */
  agreementProcess: (params) => $request({
    url: '/api/card/agreementProcess',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取协议流程详情
   * @param {Object} params { user_id: 用户ID, step_id: 步骤ID, flow_step: 流程步骤 }
   * @returns {Promise}
   */
  agreementDetail: (params) => $request({
    url: '/api/card/agreementDetail',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  // ========== 福卡相关API ==========
  
  /**
   * 获取福卡类型列表
   * @returns {Promise}
   */
  getCardTypes: () => $request({
    url: '/api/fuka/typeList',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: false,
    },
  }),

  /**
   * 获取我的福卡列表
   * @param {Object} params 查询参数
   * @returns {Promise}
   */
  getMyCards: (params) => $request({
    url: '/api/fuka/myCards',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取福卡统计信息
   * @returns {Promise}
   */
  getCardStatistics: () => $request({
    url: '/api/fuka/statistics',
      method: 'GET',
      custom: {
        showLoading: false,
        auth: true,
      },
    }),

  /**
   * 抽取福卡
   * @returns {Promise}
   */
  drawCard: () => $request({
    url: '/api/fuka/draw',
      method: 'POST',
    custom: {
      showLoading: true,
      loadingMsg: '抽取中...',
      auth: true,
    },
  }),

  /**
   * 获取集福机会数量
   * @returns {Promise}
   */
  getChanceCount: () => $request({
    url: '/api/fuka/chanceCount',
    method: 'GET',
      custom: {
      showLoading: false,
        auth: true,
      },
    }),

  /**
   * 兑换福卡
   * @param {Object} data 兑换数据
   * @returns {Promise}
   */
  exchangeCards: (data) => $request({
    url: '/api/fuka/exchange',
      method: 'POST',
      data,
      custom: {
      showLoading: true,
      loadingMsg: '兑换中...',
        showSuccess: true,
      auth: true,
    },
  }),

  /**
   * 获取兑换记录列表
   * @param {Object} params 查询参数
   * @returns {Promise}
   */
  getExchangeRecords: (params) => $request({
    url: '/api/fuka/exchangeList',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取兑换记录详情
   * @param {Number} id 兑换记录ID
   * @returns {Promise}
   */
  getExchangeDetail: (id) => $request({
    url: '/api/fuka/exchangeDetail',
    method: 'GET',
    params: { id },
    custom: {
      showLoading: false,
        auth: true,
      },
    }),

  /**
   * 获取福卡排行榜
   * @param {Object} params 查询参数
   * @returns {Promise}
   */
  getCardRank: (params) => $request({
    url: '/api/fuka/rankList',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: false,
    },
  }),

  /**
   * 获取奖品列表
   * @returns {Promise}
   */
  getPrizeList: () => $request({
    url: '/api/fuka/prizeList',
    method: 'GET',
      custom: {
      showLoading: false,
        auth: true,
      },
    }),

  /**
   * 检查是否可以兑换
   * @returns {Promise}
   */
  checkCanExchange: () => $request({
    url: '/api/fuka/checkCanExchange',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 合成五福卡
   * @returns {Promise}
   */
  combineWufuCard: () => $request({
    url: '/api/fuka/combine',
    method: 'POST',
    custom: {
      showLoading: true,
      loadingMsg: '合成中...',
      showSuccess: true,
      auth: true,
    },
  }),

  /**
   * 获取我的五福卡列表
   * @returns {Promise}
   */
  getMyWufuCards: () => $request({
    url: '/api/fuka/myWufuCards',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 获取取件码（付费获取）
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  getPickupCode: (data) => $request({
    url: '/api/fuka/getPickupCode',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '获取中...',
      auth: true,
    },
  }),

  /**
   * 创建取件码支付订单
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  createPickupCodeOrder: (data) => $request({
    url: '/api/fuka/createPickupCodeOrder',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '创建订单中...',
      auth: true,
    },
  }),

  /**
   * 获取支付参数
   * @param {Object} data { order_id: 订单ID, pay_type: 支付类型 }
   * @returns {Promise}
   */
  getPaymentParams: (data) => $request({
    url: '/api/card/getPaymentParams',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '获取支付参数中...',
      auth: true,
    },
  }),

  /**
   * 查询支付结果
   * @param {Object} data { order_id: 订单ID }
   * @returns {Promise}
   */
  queryPaymentResult: (data) => $request({
    url: '/api/card/paymentResult',
    method: 'GET',
    data,
    custom: {
      auth: true,
    },
  }),

  /**
   * 支付取件码费用（已废弃，请使用createPickupCodeOrder）
   * @deprecated
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  payPickupCode: (data) => $request({
    url: '/api/fuka/payPickupCode',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '支付中...',
      auth: true,
    },
  }),

  /**
   * 获取车辆证书（付费获取）
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  getCertificate: (data) => $request({
    url: '/api/fuka/getCertificate',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '获取中...',
      auth: true,
    },
  }),

  /**
   * 创建车辆证书支付订单
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  createVehicleDocOrder: (data) => $request({
    url: '/api/fuka/createVehicleDocOrder',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '创建订单中...',
      auth: true,
    },
  }),

  /**
   * 支付车辆证书费用（已废弃）
   * @deprecated 请使用createVehicleDocOrder + getPaymentParams
   * @param {Object} data { exchange_id: 兑换记录ID }
   * @returns {Promise}
   */
  payCertificate: (data) => $request({
    url: '/api/fuka/payCertificate',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '支付中...',
      auth: true,
    },
  }),

  // ========== 签到相关API ==========

  /**
   * 获取签到信息
   * @returns {Promise}
   */
  getSigninInfo: () => $request({
    url: '/api/signin/getInfo',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 执行签到
   * @returns {Promise}
   */
  doSignin: () => $request({
    url: '/api/signin/doSignin',
    method: 'POST',
    custom: {
      showLoading: true,
      loadingMsg: '签到中...',
      showSuccess: false,
      auth: true,
    },
  }),

  /**
   * 获取签到奖励列表
   * @returns {Promise}
   */
  getSigninRewardList: () => $request({
    url: '/api/signin/rewardList',
    method: 'GET',
    custom: {
      showLoading: false,
      auth: true,
    },
  }),

  /**
   * 领取签到奖励
   * @param {Object} data { rule_id: 奖励规则ID }
   * @returns {Promise}
   */
  receiveSigninReward: (data) => $request({
    url: '/api/signin/receiveReward',
    method: 'POST',
    data,
    custom: {
      showLoading: true,
      loadingMsg: '领取中...',
      showSuccess: false,
      auth: true,
    },
  }),

  /**
   * 获取签到记录
   * @param {Object} params 查询参数
   * @returns {Promise}
   */
  getSigninRecords: (params) => $request({
    url: '/api/signin/records',
    method: 'GET',
    params,
    custom: {
      showLoading: false,
      auth: true,
    },
  })
}
