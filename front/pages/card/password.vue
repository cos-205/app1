<template>
  <s-layout title="设置卡片密码" navbar="inner" :bgStyle="{ color: '#F8F9FA' }">
    <view class="password-page">
      <!-- 步骤指示器 -->
      <view class="step-indicator">
        <text class="step-num">步骤 3/9</text>
        <text class="step-name">设置卡片密码</text>
      </view>

      <!-- 密码说明 -->
      <view class="password-intro">
        <view class="intro-icon">
          <uni-icons type="locked" size="60" color="#667EEA" />
        </view>
        <view class="intro-title">设置您的金卡密码</view>
        <view class="intro-desc">密码用于大额交易验证，请妥善保管</view>
      </view>

      <!-- 密码输入表单 -->
      <view class="password-form">
        <view class="form-item">
          <view class="form-label">
            <text>支付密码</text>
            <text class="required">*</text>
          </view>
          <view class="password-input-wrap">
            <input
              class="password-input"
              type="text"
              :password="!state.showPassword"
              v-model="state.password"
              placeholder="请输入6位数字密码"
              maxlength="6"
              @input="handlePasswordInput"
            />
            <view class="show-password-btn" @tap="togglePassword">
              <uni-icons 
                :type="state.showPassword ? 'eye-filled' : 'eye-slash-filled'" 
                size="20" 
                color="#999999" 
              />
            </view>
          </view>
          <view class="password-strength" v-if="state.password.length > 0">
            <view class="strength-bar">
              <view 
                class="strength-fill" 
                :class="'strength-' + state.passwordStrength"
                :style="{ width: getStrengthWidth() }"
              ></view>
            </view>
            <text class="strength-text">{{ getStrengthText() }}</text>
          </view>
        </view>

        <view class="form-item">
          <view class="form-label">
            <text>确认密码</text>
            <text class="required">*</text>
          </view>
          <view class="password-input-wrap">
            <input
              class="password-input"
              type="text"
              :password="!state.showConfirmPassword"
              v-model="state.confirmPassword"
              placeholder="请再次输入密码"
              maxlength="6"
            />
            <view class="show-password-btn" @tap="toggleConfirmPassword">
              <uni-icons 
                :type="state.showConfirmPassword ? 'eye-filled' : 'eye-slash-filled'" 
                size="20" 
                color="#999999" 
              />
            </view>
          </view>
        </view>
      </view>

      <!-- 密码要求 -->
      <view class="password-requirements">
        <view class="requirements-title">
          <uni-icons type="info" size="16" color="#1890FF" />
          <text>密码要求</text>
        </view>
        <view class="requirements-list">
          <view class="requirement-item" :class="{ met: state.password.length === 6 }">
            <uni-icons 
              :type="state.password.length === 6 ? 'checkmarkempty' : 'closeempty'" 
              size="16" 
              :color="state.password.length === 6 ? '#52C41A' : '#CCCCCC'" 
            />
            <text>密码长度为6位数字</text>
          </view>
          <view class="requirement-item" :class="{ met: /^\d+$/.test(state.password) && state.password.length > 0 }">
            <uni-icons 
              :type="/^\d+$/.test(state.password) && state.password.length > 0 ? 'checkmarkempty' : 'closeempty'" 
              size="16" 
              :color="/^\d+$/.test(state.password) && state.password.length > 0 ? '#52C41A' : '#CCCCCC'" 
            />
            <text>仅包含数字</text>
          </view>
          <view class="requirement-item" :class="{ met: state.password === state.confirmPassword && state.confirmPassword.length > 0 }">
            <uni-icons 
              :type="state.password === state.confirmPassword && state.confirmPassword.length > 0 ? 'checkmarkempty' : 'closeempty'" 
              size="16" 
              :color="state.password === state.confirmPassword && state.confirmPassword.length > 0 ? '#52C41A' : '#CCCCCC'" 
            />
            <text>两次密码一致</text>
          </view>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="security-tips">
        <view class="tips-title">
          <uni-icons type="notification" size="16" color="#FF9800" />
          <text>安全提示</text>
        </view>
        <view class="tips-list">
          <text>• 密码设置后不可修改，请谨慎设置</text>
          <text>• 请勿使用生日、手机号等简单密码</text>
          <text>• 密码将用于大额交易验证</text>
          <text>• 如忘记密码，需联系客服重置</text>
        </view>
      </view>
    </view>

    <!-- 底部按钮 -->
    <template v-slot:footer>
      <view class="footer-buttons">
        <button 
          class="submit-button" 
          :disabled="!canSubmit() || state.submitting"
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
  step: 3,
  password: '',
  confirmPassword: '',
  showPassword: false,
  showConfirmPassword: false,
  passwordStrength: 'weak', // weak, medium, strong
  submitting: false,
});

onLoad((options) => {
  if (options.step) {
    state.step = options.step;
  }
});

// 密码输入处理
function handlePasswordInput(e) {
  const value = e.detail.value;
  // 只允许数字
  state.password = value.replace(/\D/g, '');
  // 计算密码强度
  calculatePasswordStrength();
}

// 计算密码强度
function calculatePasswordStrength() {
  const password = state.password;
  if (password.length < 6) {
    state.passwordStrength = 'weak';
  } else {
    // 检查是否有连续数字或重复数字
    const hasSequential = /012|123|234|345|456|567|678|789/.test(password);
    const hasRepeating = /(\d)\1{2,}/.test(password);
    
    if (hasSequential || hasRepeating) {
      state.passwordStrength = 'weak';
    } else {
      const uniqueDigits = new Set(password.split('')).size;
      if (uniqueDigits >= 5) {
        state.passwordStrength = 'strong';
      } else if (uniqueDigits >= 3) {
        state.passwordStrength = 'medium';
      } else {
        state.passwordStrength = 'weak';
      }
    }
  }
}

// 获取强度宽度
function getStrengthWidth() {
  switch (state.passwordStrength) {
    case 'weak':
      return '33%';
    case 'medium':
      return '66%';
    case 'strong':
      return '100%';
    default:
      return '0%';
  }
}

// 获取强度文本
function getStrengthText() {
  switch (state.passwordStrength) {
    case 'weak':
      return '弱';
    case 'medium':
      return '中';
    case 'strong':
      return '强';
    default:
      return '';
  }
}

// 切换密码显示
function togglePassword() {
  state.showPassword = !state.showPassword;
}

// 切换确认密码显示
function toggleConfirmPassword() {
  state.showConfirmPassword = !state.showConfirmPassword;
}

// 检查是否可以提交
function canSubmit() {
  return (
    state.password.length === 6 &&
    /^\d+$/.test(state.password) &&
    state.password === state.confirmPassword
  );
}

// 提交
async function handleSubmit() {
  if (!canSubmit()) {
    xxep.$helper.toast('请检查密码输入');
    return;
  }

  // 弱密码警告
  if (state.passwordStrength === 'weak') {
    uni.showModal({
      title: '密码强度较弱',
      content: '您设置的密码强度较弱，建议使用更复杂的组合。确定继续吗？',
      success: (res) => {
        if (res.confirm) {
          submitPassword();
        }
      },
    });
  } else {
    submitPassword();
  }
}

// 提交密码
async function submitPassword() {
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

    // 2. 提交密码数据
    const { code: completeCode, msg: completeMsg } = await xxep.$api.card.completeStepV2({
      step: state.step,
      extra_data: {
        card_password: state.password,
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
.password-page {
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

.password-intro {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 50rpx 30rpx;
  margin-bottom: 20rpx;
  text-align: center;
}

.intro-icon {
  margin-bottom: 20rpx;
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
}

.password-form {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.form-item {
  margin-bottom: 40rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 28rpx;
  color: #333333;
  margin-bottom: 16rpx;
  font-weight: 500;

  .required {
    color: #FF3B30;
    margin-left: 4rpx;
  }
}

.password-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input {
  flex: 1;
  height: 88rpx;
  padding: 0 100rpx 0 30rpx;
  background: #F8F9FA;
  border-radius: 12rpx;
  font-size: 32rpx;
  letter-spacing: 8rpx;
}

.show-password-btn {
  position: absolute;
  right: 30rpx;
  padding: 10rpx;
}

.password-strength {
  margin-top: 16rpx;
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.strength-bar {
  flex: 1;
  height: 6rpx;
  background: #F0F0F0;
  border-radius: 3rpx;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s;
  border-radius: 3rpx;

  &.strength-weak {
    background: #FF3B30;
  }

  &.strength-medium {
    background: #FF9800;
  }

  &.strength-strong {
    background: #52C41A;
  }
}

.strength-text {
  font-size: 24rpx;
  color: #999999;
  min-width: 40rpx;
}

.password-requirements {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.requirements-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 20rpx;

  text {
    font-size: 28rpx;
    font-weight: 500;
    color: #333333;
  }
}

.requirements-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.requirement-item {
  display: flex;
  align-items: center;
  gap: 12rpx;

  text {
    font-size: 26rpx;
    color: #999999;
  }

  &.met text {
    color: #52C41A;
  }
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

.tips-list {
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

