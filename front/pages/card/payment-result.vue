<template>
  <s-layout title="支付结果" :bgStyle="{ color: '#f5f5f5' }">
    <view class="result-page">
      <!-- 成功状态 -->
      <view v-if="state.status === 'success'" class="result-container success">
        <view class="result-icon-wrapper">
          <view class="result-icon success-icon">
            <uni-icons type="checkmarkempty" size="80" color="#52C41A" />
          </view>
        </view>
        <view class="result-title">支付成功</view>
        <view class="result-desc">您的支付已成功，请等待审核</view>
        
        <view class="result-info-card">
          <view class="card-header">
            <text class="card-title">订单信息</text>
          </view>
          <view class="info-list">
            <view class="info-item">
              <text class="info-label">订单号</text>
              <text class="info-value">{{ state.orderInfo.order_no || state.orderNo }}</text>
            </view>
            <view class="info-item" v-if="state.orderInfo.amount">
              <text class="info-label">支付金额</text>
              <text class="info-value amount">¥{{ formatAmount(state.orderInfo.amount) }}</text>
            </view>
            <view class="info-item" v-if="state.orderInfo.step_name">
              <text class="info-label">备注</text>
              <text class="info-value">{{ state.orderInfo.step_name }}</text>
            </view>
            <view class="info-item" v-if="state.orderInfo.createtime">
              <text class="info-label">支付时间</text>
              <text class="info-value">{{ state.orderInfo.createtime }}</text>
            </view>
            <view class="info-item">
              <text class="info-label">当前状态</text>
              <text class="info-value status-pending">
                <text class="status-dot"></text>
                待审核
              </text>
            </view>
          </view>
        </view>

        <view class="tips-box info">
          <uni-icons type="info" size="16" color="#1890FF" />
          <text class="tips-text">审核通常需要1-3个工作日，请耐心等待</text>
        </view>
      </view>

      <!-- 支付检查中状态 -->
      <view v-if="state.status === 'checking'" class="result-container checking">
        <view class="result-icon-wrapper">
          <view class="result-icon checking-icon">
            <uni-icons type="loop" size="80" color="#1890FF" />
          </view>
        </view>
        <view class="result-title">支付确认中</view>
        <view class="result-desc">正在确认您的支付状态，请稍候...</view>
        
        <view class="result-info-card">
          <view class="card-header">
            <text class="card-title">订单信息</text>
          </view>
          <view class="info-list">
            <view class="info-item">
              <text class="info-label">订单号</text>
              <text class="info-value small">{{ state.orderInfo.order_no || state.orderNo }}</text>
            </view>
            <view class="info-item" v-if="state.orderInfo.amount">
              <text class="info-label">支付金额</text>
              <text class="info-value amount">¥{{ formatAmount(state.orderInfo.amount) }}</text>
            </view>
            <view class="info-item" v-if="state.orderInfo.step_name">
              <text class="info-label">备注</text>
              <text class="info-value">{{ state.orderInfo.step_name }}</text>
            </view>
          </view>
        </view>

        <view class="tips-box warning">
          <uni-icons type="info" size="16" color="#FF9800" />
          <text class="tips-text warning">如长时间未到账，请联系客服查询</text>
        </view>

        <view class="action-button secondary" @tap="checkPaymentStatus" :disabled="state.checking">
          <text v-if="state.checking">查询中...</text>
          <text v-else>刷新状态</text>
        </view>
      </view>

      <!-- 失败状态 -->
      <view v-if="state.status === 'failed'" class="result-container failed">
        <view class="result-icon-wrapper">
          <view class="result-icon failed-icon">
            <uni-icons type="closeempty" size="80" color="#F5222D" />
          </view>
        </view>
        <view class="result-title">支付失败</view>
        <view class="result-desc">{{ state.failReason || '支付过程中出现问题，请重试' }}</view>
        
        <view class="result-info-card" v-if="state.orderNo">
          <view class="card-header">
            <text class="card-title">订单信息</text>
          </view>
          <view class="info-list">
            <view class="info-item">
              <text class="info-label">订单号</text>
              <text class="info-value small">{{ state.orderNo }}</text>
            </view>
          </view>
        </view>

        <button class="action-button primary" @tap="retryPayment">
          重新支付
        </button>
      </view>

      <!-- 操作按钮组 -->
      <view class="action-buttons">
        <button class="action-button back-button" @tap="goBack">
          返回金卡
        </button>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, onUnmounted } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  status: 'success', // success, checking, failed
  step: 0,
  orderNo: '',
  failReason: '',
  orderInfo: {},
  checking: false,
});

onLoad(async (options) => {
  if (options.status) {
    state.status = options.status;
  }
  if (options.step) {
    state.step = parseInt(options.step);
  }
  if (options.order_no) {
    state.orderNo = options.order_no;
    // 加载订单详情
    await loadOrderInfo();
    // 如果是checking状态，自动开始轮询
    if (state.status === 'checking') {
      startPolling();
    }
  }
  if (options.reason) {
    state.failReason = decodeURIComponent(options.reason);
  }
});

// 加载订单信息
async function loadOrderInfo() {
  if (!state.orderNo) return;
  
  try {
    const { code, data } = await xxep.$api.card.paymentResult({
      order_no: state.orderNo,
    });
    
    if (code === 1 && data.order) {
      const order = data.order;
      state.orderInfo = {
        order_no: order.order_no || state.orderNo,
        amount: order.amount || 0,
        createtime: order.createtime ? formatTime(order.createtime) : '',
        step_id: order.step_id || state.step,
        step_name: order.step_name || '',
      };
      // 如果订单已支付，更新状态
      if (data.pay_status === 1 && state.status === 'checking') {
        state.status = 'success';
      }
    }
  } catch (error) {
    console.error('加载订单信息失败:', error);
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

// 格式化金额
function formatAmount(amount) {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
}

// 轮询检查支付状态
let pollingTimer = null;
function startPolling() {
  let retryCount = 0;
  const maxRetry = 20;

  pollingTimer = setInterval(async () => {
    try {
      // 1. 查询订单支付状态
      const { code, data } = await xxep.$api.card.paymentResult({
        order_no: state.orderNo,
      });

      if (code === 1 && data.pay_status === 1) {
        // 订单已支付，继续查询流程状态
        try {
          // 2. 查询流程状态，确认 flow_status 已更新为 3（已完成）
          const flowRes = await xxep.$api.card.flowConfig();
          if (flowRes.code === 1 && flowRes.data.steps) {
            const currentStep = flowRes.data.steps.find(s => s.step === state.step);
            
            if (currentStep && currentStep.flow_status === 3) {
              // 流程状态已更新为"已完成"，可以安全返回
              clearInterval(pollingTimer);
              state.status = 'success';
              
              // 更新订单信息
              if (data.order) {
                const order = data.order;
                state.orderInfo = {
                  ...state.orderInfo,
                  amount: order.amount || state.orderInfo.amount,
                  createtime: order.createtime ? formatTime(order.createtime) : state.orderInfo.createtime,
                  step_name: order.step_name || state.orderInfo.step_name,
                };
              }
              
              xxep.$helper.toast('支付已确认，流程已完成');
            } else {
              // 订单已支付，但流程状态还未更新，继续轮询
              console.log('订单已支付，等待流程状态更新...', currentStep?.flow_status);
            }
          }
        } catch (flowError) {
          console.error('查询流程状态失败:', flowError);
          // 如果查询流程状态失败，仍然按照原逻辑处理
          clearInterval(pollingTimer);
          state.status = 'success';
          xxep.$helper.toast('支付已确认');
        }
      }

      retryCount++;
      if (retryCount >= maxRetry) {
        clearInterval(pollingTimer);
      }
    } catch (error) {
      console.error('查询支付状态失败:', error);
    }
  }, 3000);
}

// 手动检查支付状态
async function checkPaymentStatus() {
  if (state.checking) return;
  
  state.checking = true;
  uni.showLoading({ title: '查询中...' });
  
  try {
    const { code, data, msg } = await xxep.$api.card.paymentResult({
      order_no: state.orderNo,
    });

    uni.hideLoading();
    state.checking = false;

    if (code === 1) {
      if (data.pay_status === 1) {
        state.status = 'success';
        // 更新订单信息
        if (data.order) {
          const order = data.order;
          state.orderInfo = {
            ...state.orderInfo,
            amount: order.amount || state.orderInfo.amount,
            createtime: order.createtime ? formatTime(order.createtime) : state.orderInfo.createtime,
            step_name: order.step_name || state.orderInfo.step_name,
          };
        }
        xxep.$helper.toast('支付已确认');
      } else {
        xxep.$helper.toast('支付尚未完成');
      }
    } else {
      xxep.$helper.toast(msg || '查询失败');
    }
  } catch (error) {
    uni.hideLoading();
    state.checking = false;
    console.error('查询失败:', error);
    xxep.$helper.toast('查询失败，请重试');
  }
}

// 重新支付
function retryPayment() {
  uni.navigateBack();
}

// 返回金卡页面
function goBack() {
  // 记录刚完成的步骤到 localStorage，用于在金卡页面显示完成状态
  if (state.step && state.status === 'success') {
    localStorage.setItem('justCompletedStep', state.step.toString());
    localStorage.setItem('justCompletedTime', Date.now().toString());
  }
  
  // 使用 reLaunch 清空页面栈，确保完全重新加载
  // 延迟2秒，等待支付回调完成
  uni.reLaunch({
      url: '/pages/index/card',
    });
  }
// 页面卸载时清除定时器
onUnmounted(() => {
  if (pollingTimer) {
    clearInterval(pollingTimer);
  }
});
</script>

<style lang="scss" scoped>
.result-page {
  padding: 40rpx 30rpx 30rpx;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.result-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;

}

.result-icon-wrapper {
  margin-bottom: 40rpx;
}

.result-icon {
  width: 160rpx;
  height: 160rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: scaleIn 0.5s ease;
  
  &.success-icon {
    background: linear-gradient(135deg, #E6F7FF 0%, #BAE7FF 100%);
  }
  
  &.checking-icon {
    background: linear-gradient(135deg, #E6F7FF 0%, #BAE7FF 100%);
    animation: scaleIn 0.5s ease, rotate 2s linear infinite;
  }
  
  &.failed-icon {
    background: linear-gradient(135deg, #FFF1F0 0%, #FFCCC7 100%);
  }
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.result-title {
  font-size: 44rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.result-desc {
  font-size: 28rpx;
  color: #666666;
  margin-bottom: 40rpx;
  line-height: 1.6;
  padding: 0 20rpx;
}

.result-info-card {
  width: 100%;
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 0;
  margin-bottom: 30rpx;
  box-shadow: 0 2rpx 16rpx rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.card-header {
  padding: 30rpx 30rpx 20rpx;
  border-bottom: 1px solid #F0F0F0;
}

.card-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
}

.info-list {
  padding: 0 30rpx 20rpx;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1px solid #F5F5F5;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &:first-child {
    padding-top: 20rpx;
  }
}

.info-label {
  font-size: 28rpx;
  color: #666666;
  flex-shrink: 0;
}

.info-value {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
  text-align: right;
  flex: 1;
  margin-left: 20rpx;

  &.small {
    font-size: 24rpx;
    max-width: 400rpx;
    word-break: break-all;
  }

  &.amount {
    font-size: 32rpx;
    font-weight: 600;
    color: #FF4D4F;
  }

  &.status-pending {
    color: #FF9800;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8rpx;
  }
}

.status-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #FF9800;
  display: inline-block;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.tips-box {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12rpx;
  padding: 0;
  margin-bottom: 40rpx;
}

.tips-text {
  font-size: 26rpx;
  color: #1890FF;
  line-height: 1.6;
  
  &.warning {
    color: #FF9800;
  }
}

.action-buttons {
  // width: 100%;
  display: flex;
  gap: 20rpx;
  margin-top: 40rpx;
  padding: 0 30rpx 40rpx;
}

.action-button {
  width: 100%;
  height: 88rpx;
  border-radius: 44rpx;
  background: #1890FF;
  color: #FFFFFF;
  font-size: 32rpx;
  font-weight: 600;
  text-align: center;
  line-height: 88rpx;
  transition: all 0.3s ease;

  &.primary {
    background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
    color: #FFFFFF;
    box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
    border: none;
    
    &:active {
      transform: scale(0.98);
      box-shadow: 0 2rpx 8rpx rgba(66, 133, 244, 0.2);
    }
  }

  &.secondary {
    background: #FFFFFF;
    color: #4285F4;
    border: 2rpx solid #4285F4;
    box-shadow: none;
    
    &:active {
      transform: scale(0.98);
      background: #F0F7FF;
    }
    
    &:disabled {
      opacity: 0.6;
      background: #F5F5F5;
      color: #999999;
      border-color: #D9D9D9;
    }
  }

  &.outline {
    background: #FFFFFF;
    color: #666666;
    border: 2rpx solid #D9D9D9;
    
    &:active {
      transform: scale(0.98);
      background: #F5F5F5;
    }
  }

  &.back-button {
    background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
    color: #FFFFFF;
    box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
    border: none;
    
    &:active {
      transform: scale(0.98);
      box-shadow: 0 2rpx 8rpx rgba(66, 133, 244, 0.2);
    }
  }
}
</style>

