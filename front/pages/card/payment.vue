<template>
  <s-layout title="支付订单"  :bgStyle="{ color: '#F5E8CE' }">
    <view class="payment-page">
      <!-- 订单信息卡片 -->
      <view class="order-card">
        <view class="order-header">
          <view class="step-info">
            <text class="step-label">备注</text>
            <text class="step-name">{{ state.stepInfo.step_name }}</text>
          </view>
          <view class="order-amount">
            <text class="amount-label">应付金额</text>
            <text class="amount-value">¥{{ state.orderInfo.amount }}</text>
          </view>
        </view>

        <view class="order-detail">
          <view class="detail-row">
            <text class="detail-label">订单号</text>
            <text class="detail-value">{{ state.orderInfo.order_no }}</text>
          </view>
          <view class="detail-row">
            <text class="detail-label">创建时间</text>
            <text class="detail-value">{{ state.orderInfo.createtime }}</text>
          </view>
        </view>
      </view>

      <!-- 支付方式选择 -->
      <view class="payment-methods">
        <view class="section-title">选择支付方式</view>
        <view class="method-list">
          <view 
            class="method-item"
            :class="{ active: state.selectedMethod === 'alipay' }"
            @tap="selectMethod('alipay')"
          >
            <view class="method-left">
              <image class="method-icon" src="/static/pay/alipay.png" mode="aspectFit" />
              <text class="method-name">支付宝</text>
            </view>
            <view class="method-right">
              <uni-icons 
                :type="state.selectedMethod === 'alipay' ? 'checkbox-filled' : 'circle'" 
                size="24" 
                :color="state.selectedMethod === 'alipay' ? '#1677FF' : '#CCCCCC'" 
              />
            </view>
          </view>

          <view 
            class="method-item"
            :class="{ active: state.selectedMethod === 'wechat' }"
            @tap="selectMethod('wechat')"
          >
            <view class="method-left">
              <image class="method-icon" src="/static/pay/wechat.png" mode="aspectFit" />
              <text class="method-name">微信支付</text>
            </view>
            <view class="method-right">
              <uni-icons 
                :type="state.selectedMethod === 'wechat' ? 'checkbox-filled' : 'circle'" 
                size="24" 
                :color="state.selectedMethod === 'wechat' ? '#07C160' : '#CCCCCC'" 
              />
            </view>
          </view>

          
          <view 
            class="method-item"
            :class="{ active: state.selectedMethod === 'unionpay' }"
            @tap="selectMethod('unionpay')"
          >
            <view class="method-left">
              <image class="method-icon" src="/static/pay/bank.png" mode="aspectFit" />
              <text class="method-name">云闪付</text>
            </view>
            <view class="method-right">
              <uni-icons 
                :type="state.selectedMethod === 'unionpay' ? 'checkbox-filled' : 'circle'" 
                size="24" 
                :color="state.selectedMethod === 'unionpay' ? '#E21836' : '#CCCCCC'" 
              />
            </view>
          </view>
        </view>
      </view>

      <!-- 注意事项 -->
      <view class="notice-box">
        <view class="notice-title">
          <uni-icons type="info-filled" size="18" color="#FF9800" />
          <text>温馨提示</text>
        </view>
        <view class="notice-content">
          <text>• 支付后将进入审核阶段，请耐心等待</text>
          <text>• 审核通过后方可进行下一步操作</text>
          <text>• 如审核未通过，费用将原路退回</text>
        </view>
      </view>
    </view>

    <!-- 底部支付按钮 -->
      <view class="payment-footer">
        <view class="footer-amount">
          <text class="footer-label">实付金额</text>
          <text class="footer-value">¥{{ state.orderInfo.amount }}</text>
        </view>
        <view 
          class="pay-button" 
          :disabled="state.paying || state.selectedMethod === ''"
          :loading="state.paying"
          @tap="handlePay"
        >
          {{ state.paying ? '支付中...' : '立即支付' }}
        </view>
      </view>
  </s-layout>
</template>

<script setup>
import { reactive } from 'vue';
import { onLoad, onShow } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  orderId: null,
  stepInfo: {
    step: 0,
    step_name: '',
  },
  orderInfo: {
    order_no: '',
    amount: 0,
    createtime: '',
  },
  selectedMethod: 'alipay',
  paying: false,
});

onLoad((options) => {
  if (options.order_id) {
    state.orderId = options.order_id;
    loadOrderInfo();
  }
  if (options.step) {
    state.stepInfo.step = options.step;
  }
});

// 页面显示时检查支付状态（用户从支付页面返回时）
onShow(() => {
  // 如果正在支付中，检查支付结果
  if (state.paying && state.orderInfo.order_no) {
    checkPaymentStatusOnce();
  }
});

// 单次检查支付状态
async function checkPaymentStatusOnce() {
  try {
    const { code, data } = await xxep.$api.card.paymentResult({
      order_no: state.orderInfo.order_no,
    });

    if (code === 1 && data.pay_status === 1) {
      // 支付成功
      state.paying = false;
      uni.redirectTo({
        url: '/pages/card/payment-result?status=success&step=' + state.stepInfo.step + '&order_no=' + state.orderInfo.order_no,
      });
    }
  } catch (error) {
    console.error('检查支付状态失败:', error);
  }
}

// 加载订单信息
async function loadOrderInfo() {
  try {
    const { code, data, msg } = await xxep.$api.card.getOrderInfo({
      order_id: state.orderId,
    });
    
    if (code === 1 && data.order) {
      const order = data.order;
      state.orderInfo = {
        order_no: order.order_no || '',
        amount: order.amount || 0,
        createtime: formatTime(order.createtime),
      };
      state.stepInfo = {
        step: order.step_id || 0,
        step_name: order.step_name || '',
      };
    } else {
      xxep.$helper.toast(msg || '订单信息加载失败');
    }
  } catch (error) {
    console.error('加载订单信息失败:', error);
    xxep.$helper.toast('加载失败，请重试');
  }
}

// 格式化时间
function formatTime(timestamp) {
  if (!timestamp) return '';
  const date = new Date(timestamp * 1000);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// 选择支付方式
function selectMethod(method) {
  state.selectedMethod = method;
}

// 处理支付
async function handlePay() {
  if (!state.selectedMethod) {
    xxep.$helper.toast('请选择支付方式');
    return;
  }

  state.paying = true;

  try {
    // 获取支付参数
    const { code, data, msg } = await xxep.$api.card.getPaymentParams({
      order_id: state.orderId,
      pay_type: state.selectedMethod,
    });

    if (code !== 1) {
      xxep.$helper.toast(msg || '获取支付参数失败');
      state.paying = false;
      return;
    }

    // 调起支付
    await callPay(data);
  } catch (error) {
    console.error('支付失败:', error);
    xxep.$helper.toast('支付失败，请重试');
    state.paying = false;
  }
}

// 调起支付
async function callPay(payParams) {
  const paymentUrl = payParams.payment_url || payParams.pay_url;
  
  if (!paymentUrl) {
    xxep.$helper.toast('支付参数错误');
    state.paying = false;
    return;
  }

  // #ifdef H5
  // H5环境：使用 window.open 在新窗口打开支付页面
  window.open(paymentUrl, '_blank');
  // 跳转后开始轮询支付结果
  checkPaymentResult();
  // #endif

  // #ifdef APP-PLUS
  // APP环境：使用系统默认浏览器打开支付页面
  plus.runtime.openURL(paymentUrl);
  // 跳转后开始轮询支付结果
  checkPaymentResult();
  // #endif

  // #ifdef MP-WEIXIN
  // 小程序环境：使用微信支付
  if (state.selectedMethod === 'wechat') {
    uni.requestPayment({
      ...payParams,
      success: () => {
        handlePaySuccess();
      },
      fail: (err) => {
        console.error('支付失败:', err);
        xxep.$helper.toast('支付已取消');
        state.paying = false;
      },
    });
  } else {
    // 小程序环境不支持其他支付方式
    xxep.$helper.toast('小程序仅支持微信支付');
    state.paying = false;
  }
  // #endif
}

// 支付成功处理
function handlePaySuccess() {
  xxep.$helper.toast('支付成功');
  
  // 轮询查询支付结果
  checkPaymentResult();
}

// 查询支付结果
async function checkPaymentResult() {
  let retryCount = 0;
  const maxRetry = 2;
  
  const timer = setInterval(async () => {
    try {
      const { code, data } = await xxep.$api.card.paymentResult({
        order_no: state.orderInfo.order_no,
      });

      if (code === 1 && data.pay_status === 1) {
        clearInterval(timer);
        state.paying = false;
        
        // 支付成功，跳转到结果页
        uni.redirectTo({
          url: '/pages/card/payment-result?status=success&step=' + state.stepInfo.step + '&order_no=' + state.orderInfo.order_no,
        });
      }

      retryCount++;
      if (retryCount >= maxRetry) {
        clearInterval(timer);
        state.paying = false;
        
        // 超时，跳转到结果页
        uni.redirectTo({
          url: '/pages/card/payment-result?status=checking&order_no=' + state.orderInfo.order_no,
        });
      }
    } catch (error) {
      console.error('查询支付结果失败:', error);
    }
  }, 2000);
}
</script>

<style lang="scss" scoped>
.payment-page {
  padding: 20rpx;
  padding-bottom: 200rpx;
}

.order-card {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 24rpx;
  border-bottom: 1px solid #F0F0F0;
}

.step-info {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.step-label {
  font-size: 24rpx;
  color: #999999;
}

.step-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
}

.order-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8rpx;
}

.amount-label {
  font-size: 24rpx;
  color: #999999;
}

.amount-value {
  font-size: 40rpx;
  font-weight: 700;
  color: #FF3B30;
}

.order-detail {
  margin-top: 24rpx;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.detail-label {
  font-size: 28rpx;
  color: #666666;
}

.detail-value {
  font-size: 28rpx;
  color: #333333;
}

.payment-methods {
  margin-bottom: 20rpx;
}

.section-title {
  font-size: 28rpx;
  color: #666666;
  padding: 20rpx 0;
}

.method-list {
  background: #FFFFFF;
  border-radius: 16rpx;
  overflow: hidden;
}

.method-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1px solid #F0F0F0;
  transition: all 0.3s;

  &:last-child {
    border-bottom: none;
  }

  &.active {
    background: #F8F9FA;
  }
}

.method-left {
  display: flex;
  align-items: center;
  gap: 20rpx;
}

.method-icon {
  width: 48rpx;
  height: 48rpx;
}

.method-name {
  font-size: 30rpx;
  color: #333333;
}

.notice-box {
  background: #FFF9E6;
  border-radius: 16rpx;
  padding: 24rpx;
}

.notice-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 16rpx;

  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #FF9800;
  }
}

.notice-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  text {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.6;
  }
}

.payment-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 30rpx;
  background: #FFFFFF;
  border-top: 1px solid #F0F0F0;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

.footer-amount {
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.footer-label {
  font-size: 24rpx;
  color: #999999;
}

.footer-value {
  font-size: 36rpx;
  font-weight: 700;
  color: #FF3B30;
}

.pay-button {
  width: 400rpx;
  height: 88rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>

