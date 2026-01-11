<template>
  <s-layout :title="state.stepInfo.step_name" :bgStyle="{ color: '#f5f5f5' }">
    <view class="setup-page">
      <!-- 已完成状态 -->
      <view v-if="state.stepInfo.flow_status === 3" class="completed-container">
        <view class="completed-icon">
          <uni-icons type="checkmark-circle-filled" size="80" color="#00C853" />
        </view>
        <view class="completed-title">{{ state.stepInfo.step_name }}已完成</view>
        <view class="completed-desc">您已成功开通并完成支付</view>
      </view>

      <!-- 未完成时显示开通信息 -->
      <view v-else>
        <!-- 功能说明 -->
        <view class="function-intro">
          <view class="intro-icon">
            <uni-icons :type="state.stepInfo.step === 7 ? 'weixin' : 'wallet'" size="60" color="#4285F4" />
          </view>
          <view class="intro-title">{{ state.stepInfo.step_name }}</view>
          <view class="intro-desc">{{ state.stepInfo.description }}</view>
        </view>

        <!-- 功能说明卡片 -->
        <view class="function-card">
          <view class="card-title">
            <uni-icons type="info-filled" size="18" color="#1890FF" />
            <text>功能说明</text>
          </view>
          <view class="card-content">
            <view class="function-item" v-for="(item, index) in state.functionList" :key="index">
              <view class="item-dot"></view>
              <text class="item-text">{{ item }}</text>
            </view>
          </view>
        </view>

        <!-- 费用说明 -->
        <view class="fee-card" v-if="state.stepInfo.fee_amount > 0">
          <view class="fee-header">
            <text class="fee-label">开通费用</text>
            <text class="fee-amount">¥{{ state.stepInfo.fee_amount }}</text>
          </view>
          <view class="fee-note">
            <uni-icons type="star" size="14" color="#FF9800" />
            <text>费用将在一个月后原路退回</text>
          </view>
        </view>

        <!-- 温馨提示 -->
        <view class="tips-box">
          <view class="tips-title">
            <uni-icons type="notification" size="16" color="#1890FF" />
            <text>温馨提示</text>
          </view>
          <view class="tips-content">
            <text>• 开通后即可使用{{ state.stepInfo.step === 7 ? '微信' : '支付宝' }}支付功能</text>
            <text>• 支付成功后请等待审核（1-3个工作日）</text>
            <text>• 如审核未通过，费用将原路退回</text>
          </view>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="footer-buttons">
        <!-- 已完成状态 -->
        <view v-if="state.stepInfo.flow_status === 3" class="completed-status-btn">
          <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
          <text class="completed-text">已完成</text>
        </view>
        <!-- 未完成时显示提交按钮 -->
        <button 
          v-else
          class="submit-button" 
          :disabled="state.submitting"
          :loading="state.submitting"
          @tap="handleConfirm"
        >
          {{ state.submitting ? '处理中...' : (state.stepInfo.fee_amount > 0 ? `确认开通并支付 ¥${state.stepInfo.fee_amount}` : '确认开通') }}
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
  step: 0,
  stepInfo: {
    step: 0,
    step_name: '',
    description: '',
    fee_amount: 0,
    flow_status: 1, // 流程状态：1=未支付, 2=已支付待审核, 3=已完成
  },
  functionList: [],
  submitting: false,
});

onLoad(async (options) => {
  if (options.step) {
    state.step = parseInt(options.step);
    await loadStepInfo();
  }
});

// 加载步骤信息
async function loadStepInfo() {
  try {
    const { code, data, msg } = await xxep.$api.card.flowConfig();
    
    if (code === 1) {
      const stepConfig = data.steps.find(s => s.step === state.step);
      if (stepConfig) {
        state.stepInfo = {
          step: stepConfig.step,
          step_name: stepConfig.step_name,
          description: stepConfig.step_desc || getStepDescription(stepConfig.step),
          fee_amount: stepConfig.fee_amount,
          flow_status: stepConfig.flow_status || 1,
        };
        
        // 设置功能说明列表
        state.functionList = getFunctionList(stepConfig.step);
      }
    } else {
      xxep.$helper.toast(msg || '加载步骤信息失败');
    }
  } catch (error) {
    console.error('加载步骤信息失败:', error);
    xxep.$helper.toast('加载失败，请重试');
  }
}

// 获取步骤说明
function getStepDescription(step) {
  const descriptions = {
    7: '在金卡上开通微信支付功能，支持微信收付款',
    8: '在金卡上开通支付宝支付功能，支持支付宝收付款',
  };
  return descriptions[step] || '开通支付功能';
}

// 获取功能说明列表
function getFunctionList(step) {
  if (step === 7) {
    return [
      '支持微信扫码支付',
      '支持微信收付款',
      '实时到账，安全便捷',
      '享受微信支付优惠活动',
    ];
  } else if (step === 8) {
    return [
      '支持支付宝扫码支付',
      '支持支付宝收付款',
      '实时到账，安全便捷',
      '享受支付宝支付优惠活动',
    ];
  }
  return [];
}

// 确认处理
async function handleConfirm() {
  state.submitting = true;

  try {
    // 创建支付订单
    const { code, data, msg } = await xxep.$api.card.createOrder({
      step: state.step,
    });

    if (code === 1) {
      // 跳转到支付页面
      uni.redirectTo({
        url: `/pages/card/payment?order_id=${data.order.id}&step=${state.step}`,
      });
    } else {
      xxep.$helper.toast(msg || '创建订单失败');
      state.submitting = false;
    }
  } catch (error) {
    console.error('创建订单失败:', error);
    xxep.$helper.toast('操作失败，请重试');
    state.submitting = false;
  }
}
</script>

<style lang="scss" scoped>
.setup-page {
  padding: 20rpx;
  padding-bottom: 200rpx;
}

.completed-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 30rpx;
  background: #FFFFFF;
  border-radius: 16rpx;
  margin-top: 40rpx;
}

.completed-icon {
  margin-bottom: 30rpx;
}

.completed-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #00C853;
  margin-bottom: 20rpx;
}

.completed-desc {
  font-size: 28rpx;
  color: #666666;
  text-align: center;
}

.function-intro {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 20rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.intro-icon {
  margin-bottom: 24rpx;
}

.intro-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.intro-desc {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.6;
}

.function-card {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 24rpx;
  
  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
  }
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.function-item {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
}

.item-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #4285F4;
  margin-top: 12rpx;
  flex-shrink: 0;
}

.item-text {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.8;
  flex: 1;
}

.fee-card {
  background: linear-gradient(135deg, #FFE5E5 0%, #FFF4E5 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.fee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16rpx;
}

.fee-label {
  font-size: 28rpx;
  color: #666666;
}

.fee-amount {
  font-size: 40rpx;
  font-weight: 700;
  color: #FF3B30;
}

.fee-note {
  display: flex;
  align-items: center;
  gap: 8rpx;

  text {
    font-size: 24rpx;
    color: #FF9800;
  }
}

.tips-box {
  background: #E6F7FF;
  border-radius: 16rpx;
  padding: 30rpx;
}

.tips-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 16rpx;

  text {
    font-size: 28rpx;
    font-weight: 500;
    color: #1890FF;
  }
}

.tips-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  text {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.6;
  }
}

.footer-buttons {
  margin: 40rpx 0;
}

.completed-status-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  width: 100%;
  height: 100rpx;
  background: #F0F0F0;
  border-radius: 50rpx;
  
  .completed-text {
    font-size: 32rpx;
    font-weight: 600;
    color: #00C853;
  }
}

.submit-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;

  &[disabled] {
    opacity: 0.6;
  }
}
</style>
