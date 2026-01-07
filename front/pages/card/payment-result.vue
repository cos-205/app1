<template>
  <s-layout title="支付结果" navbar="inner" :bgStyle="{ color: '#F8F9FA' }">
    <view class="result-page">
      <!-- 成功状态 -->
      <view v-if="state.status === 'success'" class="result-container success">
        <view class="result-icon">
          <uni-icons type="checkmarkempty" size="100" color="#52C41A" />
        </view>
        <view class="result-title">支付成功</view>
        <view class="result-desc">您的支付已成功，请等待审核</view>
        
        <view class="result-info">
          <view class="info-item">
            <text class="info-label">流程步骤</text>
            <text class="info-value">步骤{{ state.step }}</text>
          </view>
          <view class="info-item">
            <text class="info-label">当前状态</text>
            <text class="info-value status-pending">待审核</text>
          </view>
        </view>

        <view class="tips-box">
          <uni-icons type="info" size="16" color="#1890FF" />
          <text>审核通常需要1-3个工作日，请耐心等待</text>
        </view>
      </view>

      <!-- 支付检查中状态 -->
      <view v-if="state.status === 'checking'" class="result-container checking">
        <view class="result-icon">
          <uni-icons type="loop" size="100" color="#1890FF" />
        </view>
        <view class="result-title">支付确认中</view>
        <view class="result-desc">正在确认您的支付状态，请稍候...</view>
        
        <view class="result-info">
          <view class="info-item">
            <text class="info-label">订单号</text>
            <text class="info-value small">{{ state.orderNo }}</text>
          </view>
        </view>

        <view class="tips-box warning">
          <uni-icons type="info" size="16" color="#FF9800" />
          <text>如长时间未到账，请联系客服查询</text>
        </view>

        <button class="action-button secondary" @tap="checkPaymentStatus">
          刷新状态
        </button>
      </view>

      <!-- 失败状态 -->
      <view v-if="state.status === 'failed'" class="result-container failed">
        <view class="result-icon">
          <uni-icons type="closeempty" size="100" color="#F5222D" />
        </view>
        <view class="result-title">支付失败</view>
        <view class="result-desc">{{ state.failReason || '支付过程中出现问题' }}</view>
        
        <button class="action-button primary" @tap="retryPayment">
          重新支付
        </button>
      </view>

      <!-- 操作按钮组 -->
      <view class="action-buttons">
        <button class="action-button outline" @tap="goBack">
          返回金卡
        </button>
        <button v-if="state.status === 'success'" class="action-button primary" @tap="viewProgress">
          查看进度
        </button>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  status: 'success', // success, checking, failed
  step: 0,
  orderNo: '',
  failReason: '',
});

onLoad((options) => {
  if (options.status) {
    state.status = options.status;
  }
  if (options.step) {
    state.step = options.step;
  }
  if (options.order_no) {
    state.orderNo = options.order_no;
    // 如果是checking状态，自动开始轮询
    if (state.status === 'checking') {
      startPolling();
    }
  }
  if (options.reason) {
    state.failReason = decodeURIComponent(options.reason);
  }
});

// 轮询检查支付状态
let pollingTimer = null;
function startPolling() {
  let retryCount = 0;
  const maxRetry = 20;

  pollingTimer = setInterval(async () => {
    try {
      const { code, data } = await xxep.$api.card.paymentResult({
        order_no: state.orderNo,
      });

      if (code === 1 && data.pay_status === 1) {
        clearInterval(pollingTimer);
        state.status = 'success';
        xxep.$helper.toast('支付已确认');
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
  uni.showLoading({ title: '查询中...' });
  
  try {
    const { code, data, msg } = await xxep.$api.card.paymentResult({
      order_no: state.orderNo,
    });

    uni.hideLoading();

    if (code === 1) {
      if (data.pay_status === 1) {
        state.status = 'success';
        xxep.$helper.toast('支付已确认');
      } else {
        xxep.$helper.toast('支付尚未完成');
      }
    } else {
      xxep.$helper.toast(msg || '查询失败');
    }
  } catch (error) {
    uni.hideLoading();
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
  uni.navigateTo({
    url: '/pages/index/card',
  });
}

// 查看进度
function viewProgress() {
  uni.navigateTo({
    url: '/pages/card/agreement-list?step=' + state.step,
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
  padding: 60rpx 30rpx;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.result-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  flex: 1;
}

.result-icon {
  margin-bottom: 40rpx;
  animation: scaleIn 0.5s ease;
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

.result-title {
  font-size: 40rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.result-desc {
  font-size: 28rpx;
  color: #666666;
  margin-bottom: 60rpx;
  line-height: 1.6;
}

.result-info {
  width: 100%;
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1px solid #F0F0F0;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &:first-child {
    padding-top: 0;
  }
}

.info-label {
  font-size: 28rpx;
  color: #666666;
}

.info-value {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;

  &.small {
    font-size: 24rpx;
    max-width: 400rpx;
    word-break: break-all;
  }

  &.status-pending {
    color: #FF9800;
  }
}

.tips-box {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 20rpx;
  background: #E6F7FF;
  border-radius: 12rpx;
  margin-bottom: 40rpx;

  text {
    font-size: 24rpx;
    color: #1890FF;
    line-height: 1.6;
    flex: 1;
  }

  &.warning {
    background: #FFF9E6;

    text {
      color: #FF9800;
    }
  }
}

.action-buttons {
  width: 100%;
  display: flex;
  gap: 20rpx;
  margin-top: auto;
}

.action-button {
  flex: 1;
  height: 88rpx;
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;

  &.primary {
    background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
    color: #FFFFFF;
  }

  &.secondary {
    background: #1890FF;
    color: #FFFFFF;
  }

  &.outline {
    background: #FFFFFF;
    color: #666666;
    border: 1px solid #D9D9D9;
  }
}

.checking {
  .result-icon {
    animation: rotate 2s linear infinite;
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
</style>

