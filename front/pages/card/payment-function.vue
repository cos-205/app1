<template>
  <s-layout title="大额收付款功能" :bgStyle="{ color: '#f5f5f5' }">
    <view class="payment-func-page">
      <!-- 功能介绍 -->
      <view class="func-intro">
        <view class="intro-icon">
          <uni-icons type="wallet" size="60" color="#4285F4" />
        </view>
        <view class="intro-content">
          <view class="intro-title">大额收付款功能申请</view>
          <view class="intro-desc">开通此功能后，您可以进行大额交易操作</view>
        </view>
      </view>

      <!-- 功能权限 -->
      <view class="permissions-card">
        <view class="card-title">
          <uni-icons type="list" size="18" color="#4285F4" />
          <text>开通权限</text>
        </view>
        <view class="permission-list">
          <view class="permission-item">
            <view class="permission-content">
              <text class="permission-name">大额转账</text>
              <text class="permission-desc">单笔最高800万元</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-content">
              <text class="permission-name">快速到账</text>
              <text class="permission-desc">实时到账无需等待</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-content">
              <text class="permission-name">收款功能</text>
              <text class="permission-desc">支持扫码收款</text>
            </view>
          </view>
          <view class="permission-item">
            <view class="permission-content">
              <text class="permission-name">交易记录</text>
              <text class="permission-desc">完整的交易明细</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 已完成状态 -->
      <view v-if="state.flowStatus === 3" class="completed-container">
        <view class="completed-icon">
          <uni-icons type="checkmark-circle-filled" size="80" color="#00C853" />
        </view>
        <view class="completed-title">大额支付功能已开通</view>
        <view class="completed-desc">您已成功开通大额收付款功能并完成支付</view>
        <view class="completed-info">
          <view class="info-label">已设置限额</view>
          <view class="info-value">¥{{ formatLimit(state.selectedLimit) }}</view>
        </view>
        <view class="completed-tips">
          <uni-icons type="info" size="16" color="#1890FF" />
          <text>您现在可以使用大额收付款功能进行交易</text>
        </view>
      </view>

      <!-- 额度设置 -->
      <view v-if="false"class="limit-setting">
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
                :color="state.selectedLimit === limit.value ? '#4285F4' : '#CCCCCC'" 
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

      <!-- 底部按钮 -->
      <view class="footer-buttons">
        <!-- 已完成状态 -->
        <view v-if="state.flowStatus === 3" class="completed-status-btn">
          <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
          <text class="completed-text">已完成</text>
        </view>
        <!-- 未完成时显示提交按钮 -->
        <button 
          v-else
          class="submit-button" 
          :disabled="!state.selectedLimit || state.submitting"
          :loading="state.submitting"
          @tap="handleSubmit"
        >
          {{ state.submitting ? '提交中...' : state.feeAmount > 0 ? `确认开通` : '确认开通' }}
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
  step: 4,
  selectedLimit: 8000000,
  limitOptions: [
    { value: 50000, label: '5万元', desc: '日常小额交易' },
    { value: 100000, label: '10万元', desc: '中等金额交易' },
    { value: 300000, label: '30万元', desc: '大宗商品交易' },
    { value: 800000, label: '80万元', desc: '房产车辆交易' },
  ],
  submitting: false,
  flowStatus: 1, // 流程状态：1=未支付, 2=已支付待审核, 3=已完成
  feeAmount: 0, // 费用金额
  dataSubmitted: false, // 是否已提交数据
});

onLoad(async (options) => {
  if (options.step) {
    state.step = parseInt(options.step);
  }
  await loadStepData();
});

// 加载步骤数据（如果有已保存的限额，回显）
async function loadStepData() {
  try {
    const { code, data } = await xxep.$api.card.flowConfig();
    if (code === 1 && data.steps) {
      const stepData = data.steps.find(s => s.step === state.step);
      if (stepData) {
        // 获取流程状态和费用金额
        state.flowStatus = stepData.flow_status || 1;
        state.feeAmount = stepData.fee_amount || 0;
        state.dataSubmitted = stepData.data_submitted || false;
        
        // 如果有已保存的限额，回显
        if (stepData.extra_data && stepData.extra_data.payment_limit) {
          state.selectedLimit = parseFloat(stepData.extra_data.payment_limit);
        }
      }
    }
  } catch (error) {
    console.error('加载步骤数据失败:', error);
  }
}

// 选择限额
function selectLimit(value) {
  state.selectedLimit = value;
}

// 格式化限额显示
function formatLimit(value) {
  if (value >= 10000) {
    return (value / 10000) + '万元';
  }
  return value + '元';
}

// 提交
async function handleSubmit() {
  if (!state.selectedLimit) {
    xxep.$helper.toast('请选择交易限额');
    return;
  }

  state.submitting = true;

  try {
    // 1. 先提交限额数据
    const { code: submitCode, msg: submitMsg } = await xxep.$api.card.submitStepData({
      step: state.step,
      data: {
        payment_limit: state.selectedLimit,
        apply_time: Date.now(),
      },
    });

    if (submitCode !== 1) {
      xxep.$helper.toast(submitMsg || '提交限额失败');
      state.submitting = false;
      return;
    }

    // 2. 数据提交成功后，创建支付订单
    const { code: createCode, data: createData, msg: createMsg } = await xxep.$api.card.createOrder({
      step: state.step,
    });

    if (createCode === 1) {
      // 跳转到支付页面
      uni.redirectTo({
        url: `/pages/card/payment?order_id=${createData.order.id}&step=${state.step}`,
      });
    } else {
      xxep.$helper.toast(createMsg || '创建订单失败');
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

.completed-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80rpx 30rpx;
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

.completed-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10rpx;
  padding: 30rpx;
  background: #F5F5F5;
  border-radius: 12rpx;
  margin-bottom: 30rpx;
  width: 100%;
  
  .info-label {
    font-size: 24rpx;
    color: #999999;
  }
  
  .info-value {
    font-size: 40rpx;
    font-weight: 600;
    color: #4285F4;
  }
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
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24rpx 20rpx;
}

.permission-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32rpx 20rpx;
  background: #F8FEFF;
  border-radius: 12rpx;
  border: 1px solid #E8F4FD;
  transition: all 0.3s;
  
  &:active {
    background: #E8F4FD;
  }
}

.permission-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  text-align: center;
  width: 100%;
}

.permission-name {
  font-size: 28rpx;
  color: #333333;
  font-weight: 600;
}

.permission-desc {
  font-size: 22rpx;
  color: #999999;
  line-height: 1.4;
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
    background: #E8F4FD;
    border-color: #4285F4;
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
  color: #4285F4;
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

