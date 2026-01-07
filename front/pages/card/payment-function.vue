<template>
  <s-layout title="大额收付款功能" navbar="inner" :bgStyle="{ color: '#F8F9FA' }">
    <view class="payment-func-page">
      <!-- 步骤指示器 -->
      <view class="step-indicator">
        <text class="step-num">步骤 4/9</text>
        <text class="step-name">大额收付款功能</text>
      </view>

      <!-- 功能介绍 -->
      <view class="func-intro">
        <view class="intro-icon">
          <uni-icons type="wallet" size="60" color="#667EEA" />
        </view>
        <view class="intro-content">
          <view class="intro-title">大额收付款功能申请</view>
          <view class="intro-desc">开通此功能后，您可以进行大额交易操作</view>
        </view>
      </view>

      <!-- 功能权限 -->
      <view class="permissions-card">
        <view class="card-title">
          <uni-icons type="list" size="18" color="#667EEA" />
          <text>开通权限</text>
        </view>
        <view class="permission-list">
          <view class="permission-item">
            <view class="permission-icon">
              <uni-icons type="checkmarkempty" size="20" color="#52C41A" />
            </view>
            <view class="permission-content">
              <text class="permission-name">大额转账</text>
              <text class="permission-desc">单笔最高50万元</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-icon">
              <uni-icons type="checkmarkempty" size="20" color="#52C41A" />
            </view>
            <view class="permission-content">
              <text class="permission-name">快速到账</text>
              <text class="permission-desc">实时到账，无需等待</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-icon">
              <uni-icons type="checkmarkempty" size="20" color="#52C41A" />
            </view>
            <view class="permission-content">
              <text class="permission-name">收款功能</text>
              <text class="permission-desc">支持扫码收款</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-icon">
              <uni-icons type="checkmarkempty" size="20" color="#52C41A" />
            </view>
            <view class="permission-content">
              <text class="permission-name">交易记录</text>
              <text class="permission-desc">完整的交易明细查询</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 额度设置 -->
      <view class="limit-setting">
        <view class="setting-title">设置单笔交易限额</view>
        <view class="limit-options">
          <view 
            class="limit-option"
            :class="{ active: state.selectedLimit === limit.value }"
            v-for="limit in state.limitOptions"
            :key="limit.value"
            @tap="selectLimit(limit.value)"
          >
            <view class="option-content">
              <text class="option-label">{{ limit.label }}</text>
              <text class="option-desc">建议用于{{ limit.desc }}</text>
            </view>
            <view class="option-radio">
              <uni-icons 
                :type="state.selectedLimit === limit.value ? 'checkbox-filled' : 'circle'" 
                size="24" 
                :color="state.selectedLimit === limit.value ? '#667EEA' : '#CCCCCC'" 
              />
            </view>
          </view>
        </view>
      </view>

      <!-- 使用场景 -->
      <view class="scenario-card">
        <view class="card-title">
          <uni-icons type="help" size="18" color="#1890FF" />
          <text>常见使用场景</text>
        </view>
        <view class="scenario-list">
          <view class="scenario-item">
            <text class="scenario-dot">•</text>
            <text>企业采购货款支付</text>
          </view>
          <view class="scenario-item">
            <text class="scenario-dot">•</text>
            <text>房产交易定金/尾款</text>
          </view>
          <view class="scenario-item">
            <text class="scenario-dot">•</text>
            <text>车辆购买款项支付</text>
          </view>
          <view class="scenario-item">
            <text class="scenario-dot">•</text>
            <text>大额投资理财转账</text>
          </view>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="security-tips">
        <view class="tips-title">
          <uni-icons type="locked" size="16" color="#FF9800" />
          <text>安全提示</text>
        </view>
        <view class="tips-content">
          <text>• 大额交易前请确认收款方身份</text>
          <text>• 交易过程需验证金卡密码</text>
          <text>• 所有交易均有完整记录可查</text>
          <text>• 如有异常交易请立即联系客服</text>
        </view>
      </view>
    </view>

    <!-- 底部按钮 -->
    <template v-slot:footer>
      <view class="footer-buttons">
        <button 
          class="submit-button" 
          :disabled="!state.selectedLimit || state.submitting"
          :loading="state.submitting"
          @tap="handleSubmit"
        >
          {{ state.submitting ? '提交中...' : '确认并支付' }}
        </button>
      </view>
    </template>
  </s-layout>
</template>

<script setup>
import { reactive } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  step: 4,
  selectedLimit: 100000,
  limitOptions: [
    { value: 50000, label: '5万元', desc: '日常小额交易' },
    { value: 100000, label: '10万元', desc: '中等金额交易' },
    { value: 300000, label: '30万元', desc: '大宗商品交易' },
    { value: 500000, label: '50万元', desc: '房产车辆交易' },
  ],
  submitting: false,
});

onLoad((options) => {
  if (options.step) {
    state.step = options.step;
  }
});

// 选择限额
function selectLimit(value) {
  state.selectedLimit = value;
}

// 提交
async function handleSubmit() {
  if (!state.selectedLimit) {
    xxep.$helper.toast('请选择交易限额');
    return;
  }

  state.submitting = true;

  try {
    // 1. 先创建订单
    const { code: createCode, data: createData, msg: createMsg } = await xxep.$api.card.createOrder({
      step: state.step,
    });

    if (createCode !== 1) {
      xxep.$helper.toast(createMsg || '创建订单失败');
      state.submitting = false;
      return;
    }

    // 2. 提交额度数据
    const { code: completeCode, msg: completeMsg } = await xxep.$api.card.completeStepV2({
      step: state.step,
      extra_data: {
        payment_limit: state.selectedLimit,
        apply_time: Date.now(),
      },
    });

    if (completeCode === 1) {
      // 跳转到支付页面
      uni.redirectTo({
        url: `/pages/card/payment?order_id=${createData.order.id}&step=${state.step}`,
      });
    } else {
      xxep.$helper.toast(completeMsg || '提交失败');
      state.submitting = false;
    }
  } catch (error) {
    console.error('提交失败:', error);
    xxep.$helper.toast('提交失败，请重试');
    state.submitting = false;
  }
}
</script>

<style lang="scss" scoped>
.payment-func-page {
  padding: 20rpx;
  padding-bottom: 200rpx;
}

.step-indicator {
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.step-num {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
}

.step-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
}

.func-intro {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 20rpx;
  display: flex;
  gap: 24rpx;
}

.intro-icon {
  flex-shrink: 0;
}

.intro-content {
  flex: 1;
}

.intro-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 12rpx;
}

.intro-desc {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.6;
}

.permissions-card,
.scenario-card {
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

.permission-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.permission-item {
  display: flex;
  gap: 16rpx;
}

.permission-icon {
  flex-shrink: 0;
  width: 40rpx;
  height: 40rpx;
  background: #F0F9FF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.permission-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.permission-name {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
}

.permission-desc {
  font-size: 24rpx;
  color: #999999;
}

.limit-setting {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.setting-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 24rpx;
}

.limit-options {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.limit-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx;
  background: #F8F9FA;
  border-radius: 12rpx;
  border: 2px solid transparent;
  transition: all 0.3s;

  &.active {
    background: #F0F5FF;
    border-color: #667EEA;
  }
}

.option-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.option-label {
  font-size: 30rpx;
  color: #333333;
  font-weight: 600;
}

.option-desc {
  font-size: 24rpx;
  color: #999999;
}

.scenario-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.scenario-item {
  display: flex;
  align-items: center;
  gap: 12rpx;
  font-size: 26rpx;
  color: #666666;
  line-height: 1.6;
}

.scenario-dot {
  color: #667EEA;
  font-weight: 700;
}

.security-tips {
  background: #FFF9E6;
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
    color: #FF9800;
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
  padding: 20rpx 30rpx;
  background: #FFFFFF;
  border-top: 1px solid #F0F0F0;
}

.submit-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
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

