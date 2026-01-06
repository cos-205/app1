<template>
  <scroll-view class="register-page" scroll-y>
    <!-- 顶部Banner -->
    <view class="banner-section">
      <image
        class="banner-logo"
        src="/static/images/banner-logo.png"
        mode="aspectFill"
      />
    </view>

    <!-- 注册表单卡片 -->
    <view class="register-card">
      <view class="card-header">
        <view class="welcome-title">欢迎注册</view>
        <view class="title-underline"></view>
      </view>

      <view class="form-container">
        <!-- 手机号输入框 -->
        <view class="input-group">
          <view class="input-wrapper">
            <uni-icons type="phone" size="20" color="#999999" class="input-icon"></uni-icons>
            <input
              class="input-field"
              v-model="state.mobile"
              type="number"
              maxlength="11"
              placeholder="请输入手机号"
              placeholder-style="color: #CCCCCC"
            />
          </view>
        </view>

        <!-- 密码输入框 -->
        <view class="input-group">
          <view class="input-wrapper">
            <uni-icons type="locked" size="20" color="#999999" class="input-icon"></uni-icons>
            <input
              class="input-field"
              v-model="state.password"
              :type="state.showPassword ? 'text' : 'password'"
              placeholder="请设置登录密码（6-20位）"
              placeholder-style="color: #CCCCCC"
            />
            <uni-icons 
              :type="state.showPassword ? 'eye' : 'eye-slash'" 
              size="20" 
              color="#999999"
              class="password-toggle"
              @click="togglePassword"
            ></uni-icons>
          </view>
        </view>

        <!-- 确认密码输入框 -->
        <view class="input-group">
          <view class="input-wrapper">
            <uni-icons type="locked" size="20" color="#999999" class="input-icon"></uni-icons>
            <input
              class="input-field"
              v-model="state.confirmPassword"
              :type="state.showConfirmPassword ? 'text' : 'password'"
              placeholder="请确认登录密码"
              placeholder-style="color: #CCCCCC"
            />
            <uni-icons 
              :type="state.showConfirmPassword ? 'eye' : 'eye-slash'" 
              size="20" 
              color="#999999"
              class="password-toggle"
              @click="toggleConfirmPassword"
            ></uni-icons>
          </view>
        </view>

        <!-- 邀请码输入框（可选） -->
        <view class="input-group">
          <view class="input-wrapper">
            <uni-icons type="gift" size="20" color="#999999" class="input-icon"></uni-icons>
            <input
              class="input-field"
              v-model="state.inviteCode"
              type="text"
              placeholder="请输入邀请码（选填）"
              placeholder-style="color: #CCCCCC"
            />
          </view>
        </view>

        <!-- 协议勾选 -->
        <view class="agreement-box" @tap="toggleAgreement">
          <view class="agreement-label">
            <view class="custom-checkbox" :class="{ 'checked': state.agreement }">
              <uni-icons v-if="state.agreement" type="checkmarkempty" size="16" color="#FFFFFF"></uni-icons>
            </view>
            <view class="agreement-text">
              我已阅读并同意
              <text class="link-text" @tap.stop="viewProtocol('user')">
                《用户协议》
              </text>
              和
              <text class="link-text" @tap.stop="viewProtocol('privacy')">
                《隐私政策》
              </text>
            </view>
          </view>
        </view>

        <!-- 注册按钮 -->
        <button
          class="register-button"
          :class="{ 'register-button-disabled': !canSubmit }"
          :disabled="!canSubmit"
          @tap="handleRegister"
        >
          立即注册
        </button>

        <!-- 登录链接 -->
        <view class="footer-text">
          已有账号？<text class="link-text" @tap="goLogin">立即登录</text>
        </view>
      </view>
    </view>

    <!-- 安全区域占位 -->
    <view class="safe-area-bottom"></view>
  </scroll-view>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onLoad, onUnload } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  mobile: '',
  code: '',
  password: '',
  confirmPassword: '',
  inviteCode: '',
  showPassword: false,
  showConfirmPassword: false,
  agreement: false,
  countdown: 0,
  timer: null,
});

// 是否可以发送验证码
const canSendCode = computed(() => {
  return /^1[3-9]\d{9}$/.test(state.mobile);
});

// 是否可以提交（不需要验证码）
const canSubmit = computed(() => {
  // 手机号格式验证：11位，1开头
  const isValidMobile = /^1[3-9]\d{9}$/.test(state.mobile);
  // 密码长度验证：6-20位
  const isValidPassword = state.password.length >= 6 && state.password.length <= 20;
  // 两次密码一致
  const isPasswordMatch = state.password === state.confirmPassword && state.password.length > 0;
  // 已勾选协议
  const hasAgreement = state.agreement;
  
  // 调试输出
  console.log('🔍 注册按钮状态检查:', {
    mobile: state.mobile,
    isValidMobile,
    passwordLength: state.password.length,
    isValidPassword,
    isPasswordMatch,
    hasAgreement,
    inviteCode: state.inviteCode,
    canSubmit: isValidMobile && isValidPassword && isPasswordMatch && hasAgreement
  });
  
  return isValidMobile && isValidPassword && isPasswordMatch && hasAgreement;
});

onLoad((options) => {
  // 初始化逻辑
});

onUnload(() => {
  // 清除定时器
  if (state.timer) {
    clearInterval(state.timer);
  }
});

// 切换密码显示/隐藏
const togglePassword = () => {
  state.showPassword = !state.showPassword;
};

// 切换确认密码显示/隐藏
const toggleConfirmPassword = () => {
  state.showConfirmPassword = !state.showConfirmPassword;
};

// 切换协议勾选
const toggleAgreement = () => {
  state.agreement = !state.agreement;
  console.log('✅ 协议勾选状态:', state.agreement);
};

// 查看协议
const viewProtocol = (type) => {
  // 跳转到协议页面
  xxep.$router.go('/pages/public/richtext', {
    type,
  });
};

// 发送验证码
const sendCode = async () => {
  if (!canSendCode.value || state.countdown > 0) return;

  try {
    const res = await xxep.$api.app.sendSms({
      mobile: state.mobile,
      event: 'register',
    });

    if (res.code === 1) {
      xxep.$helper.toast('验证码已发送');
      
      // 开始倒计时
      state.countdown = 60;
      state.timer = setInterval(() => {
        state.countdown--;
        if (state.countdown <= 0) {
          clearInterval(state.timer);
        }
      }, 1000);
    } else {
      xxep.$helper.toast(res.msg || '发送失败');
    }
  } catch (error) {
    xxep.$helper.toast('发送失败，请稍后重试');
    console.error('发送验证码错误:', error);
  }
};

// 处理注册
const handleRegister = async () => {
  if (!canSubmit.value) return;

  // 验证密码格式
  if (state.password.length < 6 || state.password.length > 20) {
    xxep.$helper.toast('密码长度为6-20位');
    return;
  }

  // 验证两次密码是否一致
  if (state.password !== state.confirmPassword) {
    xxep.$helper.toast('两次密码输入不一致');
    return;
  }

  try {
    uni.showLoading({
      title: '注册中...',
      mask: true,
    });

    // 使用固定测试验证码（验证码输入框已隐藏）
    const res = await xxep.$api.user.smsRegister({
      mobile: state.mobile,
      code: '123456',
      password: state.password,
      invite_code: state.inviteCode,
    });

    uni.hideLoading();

    if (res.code === 1) {
      xxep.$helper.toast('注册成功');
      
      // 保存 token
      if (res.data && res.data.token) {
        uni.setStorageSync('token', res.data.token);
      }
      
      // 更新用户信息
      await xxep.$store('user').getInfo();

      // 注册成功后跳转
      setTimeout(() => {
        uni.switchTab({
          url: '/pages/index/index',
        });
      }, 1500);
    } else {
      xxep.$helper.toast(res.msg || '注册失败');
    }
  } catch (error) {
    uni.hideLoading();
    xxep.$helper.toast('注册失败，请稍后重试');
    console.error('注册错误:', error);
  }
};

// 前往登录
const goLogin = () => {
  uni.navigateBack({
    delta: 1,
  });
};
</script>

<style lang="scss" scoped>
@import '@/xxep/scss/_var.scss';

.register-page {
  width: 100%;
  height: 100vh;
  background: linear-gradient(180deg, #F6F6F6 0%, #FFFFFF 100%);
}

/* 顶部Banner */
.banner-section {
  position: relative;
  width: 100%;
  height: 420rpx;
  overflow: hidden;
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
}

.banner-logo {
  width: 100%;
  height: 100%;
  display: block;
}

/* 注册卡片 - 浮动效果 */
.register-card {
  position: relative;
  margin: -80rpx 32rpx 32rpx;
  background: #FFFFFF;
  border-radius: 32rpx;
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15);
  padding: 56rpx 40rpx 48rpx;
  z-index: 10;
}

.card-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 48rpx;
}

.welcome-title {
  font-size: 44rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 12rpx;
}

.title-underline {
  width: 60rpx;
  height: 6rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 3rpx;
}

/* 表单容器 */
.form-container {
  width: 100%;
}

.input-group {
  margin-bottom: 32rpx;
}

.input-wrapper {
  display: flex;
  align-items: center;
  background: #F8F9FA;
  border-radius: 16rpx;
  padding: 0 24rpx;
  height: 96rpx;
  transition: all 0.3s ease;
}

.input-wrapper:focus-within {
  background: #FFFFFF;
  box-shadow: 0 0 0 2rpx #4285F4;
}

.input-icon {
  margin-right: 16rpx;
  flex-shrink: 0;
}

.input-field {
  flex: 1;
  font-size: 28rpx;
  color: #333333;
  height: 96rpx;
  line-height: 96rpx;
}

.password-toggle {
  margin-left: 16rpx;
  padding: 8rpx;
  flex-shrink: 0;
  cursor: pointer;
}

/* 验证码按钮 */
.code-button {
  flex-shrink: 0;
  margin-left: 16rpx;
  padding: 0 28rpx;
  height: 60rpx;
  line-height: 60rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  font-size: 26rpx;
  font-weight: 500;
  border-radius: 30rpx;
  border: none;
  white-space: nowrap;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.25);
  transition: all 0.3s ease;
}

.code-button::after {
  border: none;
}

.code-button:active {
  transform: scale(0.96);
  box-shadow: 0 2rpx 6rpx rgba(66, 133, 244, 0.2);
}

.code-button-disabled {
  background: #E5E7EB;
  color: #9CA3AF;
  box-shadow: none;
}

/* 协议勾选 */
.agreement-box {
  margin-bottom: 32rpx;
  padding: 0 8rpx;
  cursor: pointer;
}

.agreement-label {
  display: flex;
  align-items: flex-start;
}

.custom-checkbox {
  width: 32rpx;
  height: 32rpx;
  border: 2rpx solid #D1D5DB;
  border-radius: 6rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
  background: #FFFFFF;
}

.custom-checkbox.checked {
  background: #4285F4;
  border-color: #4285F4;
}

.agreement-text {
  flex: 1;
  margin-left: 12rpx;
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.6;
}

/* 注册按钮 */
.register-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 1000rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-bottom: 32rpx;
  box-shadow: 0 8rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
}

.register-button::after {
  border: none;
}

.register-button:active {
  transform: scale(0.98);
  box-shadow: 0 4rpx 8rpx rgba(66, 133, 244, 0.2);
}

.register-button-disabled {
  background: #E5E7EB;
  box-shadow: none;
  color: #9CA3AF;
}

/* 底部文字 */
.footer-text {
  text-align: center;
  font-size: 28rpx;
  color: #6B7280;
}

.link-text {
  color: #4285F4;
  font-weight: 500;
}

/* 安全区域 */
.safe-area-bottom {
  height: 60rpx;
  padding-bottom: env(safe-area-inset-bottom);
}
</style>

