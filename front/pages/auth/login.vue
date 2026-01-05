<template>
  <scroll-view class="login-page" scroll-y>
    <!-- 顶部Banner -->
    <view class="banner-section">
      <image
        class="banner-logo"
        src="/static/images/banner-logo.png"
        mode="aspectFill"
      />
    </view>

    <!-- 登录表单卡片 -->
    <view class="login-card">
      <view class="card-header">
        <view class="welcome-title">欢迎登录</view>
        <view class="title-underline"></view>
      </view>

      <view class="form-container">
        <!-- 手机号/账号输入框 -->
        <view class="input-group">
          <view class="input-wrapper">
            <uni-icons type="person" size="20" color="#999999" class="input-icon"></uni-icons>
            <input
              class="input-field"
              v-model="state.account"
              type="text"
              placeholder="请输入手机号/账号"
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
              placeholder="请输入登录密码"
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

        <!-- 登录按钮 -->
        <button
          class="login-button"
          :class="{ 'login-button-disabled': !canSubmit }"
          :disabled="!canSubmit"
          @tap="handleLogin"
        >
          立即登录
        </button>

        <!-- 注册链接 -->
        <view class="footer-text">
          没有账号？<text class="link-text" @tap="goRegister">立即注册</text>
        </view>
      </view>
    </view>

    <!-- 安全区域占位 -->
    <view class="safe-area-bottom"></view>
  </scroll-view>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  account: '',
  password: '',
  showPassword: false,
});

// 是否可以提交
const canSubmit = computed(() => {
  return state.account.trim() !== '' && state.password.trim() !== '';
});

onLoad((options) => {
  // 初始化逻辑
});

// 切换密码显示/隐藏
const togglePassword = () => {
  state.showPassword = !state.showPassword;
};

// 处理登录
const handleLogin = async () => {
  if (!canSubmit.value) return;

  try {
    uni.showLoading({
      title: '登录中...',
      mask: true,
    });

    const res = await xxep.$api.user.accountLogin({
      account: state.account,
      password: state.password,
    });

    uni.hideLoading();

    if (res.code === 1) {
      xxep.$helper.toast('登录成功');
      
      // 保存 token
      if (res.data && res.data.token) {
        uni.setStorageSync('token', res.data.token);
      }
      
      // 更新用户信息
      await xxep.$store('user').getInfo();

      // 登录成功后跳转
      const redirectUrl = uni.getStorageSync('redirectUrl');
      if (redirectUrl) {
        uni.removeStorageSync('redirectUrl');
        uni.redirectTo({
          url: redirectUrl,
        });
      } else {
        uni.switchTab({
          url: '/pages/index/index',
        });
      }
    } else {
      xxep.$helper.toast(res.msg || '登录失败');
    }
  } catch (error) {
    uni.hideLoading();
    xxep.$helper.toast('登录失败，请稍后重试');
    console.error('登录错误:', error);
  }
};

// 前往注册
const goRegister = () => {
  xxep.$router.go('/pages/auth/register');
};
</script>

<style lang="scss" scoped>
@import '@/xxep/scss/_var.scss';

.login-page {
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

/* 登录卡片 - 浮动效果 */
.login-card {
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

/* 登录按钮 */
.login-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 1000rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-top: 16rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 8rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
}

.login-button::after {
  border: none;
}

.login-button:active {
  transform: scale(0.98);
  box-shadow: 0 4rpx 8rpx rgba(66, 133, 244, 0.2);
}

.login-button-disabled {
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
  cursor: pointer;
}

/* 安全区域 */
.safe-area-bottom {
  height: 60rpx;
  padding-bottom: env(safe-area-inset-bottom);
}
</style>

