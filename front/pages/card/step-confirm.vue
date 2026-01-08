<template>
  <s-layout :title="state.stepInfo.step_name" :bgStyle="{ color: '#f5f5f5' }">
    <view class="confirm-page">
      <!-- 已完成状态 -->
      <view v-if="state.stepInfo.flow_status === 3" class="completed-container">
        <view class="completed-icon">
          <uni-icons type="checkmark-circle-filled" size="80" color="#00C853" />
        </view>
        <view class="completed-title">{{ state.stepInfo.step_name }}已完成</view>
        <view class="completed-desc">您已成功完成本步骤并支付费用</view>
        <view class="completed-tips">
          <uni-icons type="info" size="16" color="#1890FF" />
          <text>请继续完成后续步骤</text>
        </view>
      </view>

      <!-- 未完成时显示步骤信息 -->
      <view v-else>
      <!-- 步骤说明 -->
      <view class="step-description">
        <view class="desc-icon">
          <uni-icons type="info-filled" size="60" color="#4285F4" />
        </view>
        <view class="desc-content">
          <view class="desc-title">{{ state.stepInfo.step_name }}</view>
          <view class="desc-text">{{ state.stepInfo.description }}</view>
        </view>
      </view>

      <!-- 费用说明 -->
      <view class="fee-card">
        <view class="fee-header">
          <text class="fee-label">本步骤费用</text>
          <text class="fee-amount">¥{{ state.stepInfo.fee_amount }}</text>
        </view>
        <view class="fee-note">
          <uni-icons type="star" size="14" color="#FF9800" />
          <text>完成全部流程后，所有费用将全额退还</text>
        </view>
      </view>

      <!-- 操作说明 -->
      <view class="operation-guide">
        <view class="guide-title">操作说明</view>
        <view class="guide-steps">
          <view class="guide-step">
            <view class="step-dot">1</view>
            <text>点击下方"确认并支付"按钮</text>
          </view>
          <view class="guide-step">
            <view class="step-dot">2</view>
            <text>选择支付方式完成支付</text>
          </view>
          <view class="guide-step">
            <view class="step-dot">3</view>
            <text>等待管理员审核（1-3个工作日）</text>
          </view>
          <view class="guide-step">
            <view class="step-dot">4</view>
            <text>审核通过后继续下一步骤</text>
          </view>
        </view>
      </view>

      <!-- 温馨提示 -->
      <view class="tips-box">
        <view class="tips-title">
          <uni-icons type="notification" size="16" color="#1890FF" />
          <text>温馨提示</text>
        </view>
        <view class="tips-content">
          <text>• 支付成功后请等待审核</text>
          <text>• 如审核未通过，费用将原路退回</text>
          <text>• 完成全部9个步骤后，所有费用全额退还</text>
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
          {{ state.submitting ? '处理中...' : '确认并支付 ¥' + state.stepInfo.fee_amount }}
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
          flow_status: stepConfig.flow_status || 1, // 获取流程状态
        };
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
    2: '签署财富金卡使用协议，明确双方权利义务',
    5: '签署支付宝保密合同，确保账户安全',
    6: '开通APP提现功能，方便资金管理',
    7: '邮寄支付宝会员入场证，享受专属权益',
    8: '开通微信支付功能，支持微信收付款',
    9: '开通支付宝支付功能，支持支付宝收付款',
  };
  return descriptions[step] || '完成本步骤以继续金卡申请流程';
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
.confirm-page {
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
  margin-bottom: 30rpx;
}

.completed-tips {
  display: flex;
  align-items: center;
  gap: 10rpx;
  padding: 20rpx 30rpx;
  background: #E3F2FD;
  border-radius: 12rpx;
  
  text {
    font-size: 24rpx;
    color: #1890FF;
  }
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

// 步骤指示器已移除

.step-description {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 20rpx;
  display: flex;
  gap: 24rpx;
}

.desc-icon {
  flex-shrink: 0;
}

.desc-content {
  flex: 1;
}

.desc-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.desc-text {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.8;
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

.operation-guide {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.guide-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 24rpx;
}

.guide-steps {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.guide-step {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
}

.step-dot {
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  font-size: 24rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.guide-step text {
  font-size: 26rpx;
  color: #666666;
  line-height: 44rpx;
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
  // background: #FFFFFF;
  border-top: 1px solid #F0F0F0;
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

