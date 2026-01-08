<template>
  <view class="bind-page">
    <!-- 顶部导航 -->
    <view class="page-header">
      <view class="header-left" @tap="handleBack">
        <uni-icons type="arrowleft" size="24" color="#1F2937"></uni-icons>
      </view>
      <view class="header-title">绑定支付宝</view>
      <view class="header-right"></view>
    </view>

    <!-- 主要内容 -->
    <view class="page-content">
      
      <!-- 绑定成功状态 -->
      <view v-if="isSuccess" class="success-container">
        <view class="success-card">
          <view class="success-icon-wrapper">
            <uni-icons type="checkmarkempty" size="80" color="#4CAF50"></uni-icons>
          </view>
          <view class="success-title">绑定成功</view>
          <view class="success-desc">您的支付宝账号已成功绑定</view>
          
          <view class="user-info-section">
            <view class="info-item">
              <view class="info-label">支付宝账号</view>
              <view class="info-value">{{ maskedAccount }}</view>
            </view>
          </view>

          <view class="success-tips">
            <uni-icons type="info" size="16" color="#1890FF"></uni-icons>
            <text class="tips-text">您已可以使用支付宝进行分红发放</text>
          </view>

          <button class="back-btn" @tap="handleBack">
            返回
          </button>
        </view>
      </view>

      <!-- 表单卡片 -->
      <view v-else class="card form-card">
        <!-- 支付宝图标 -->
        <view class="platform-icon">
          <image class="icon-img" src="/static/pay/alipay.png" mode="aspectFit"></image>
        </view>

        <view class="form-title">
          <text class="title-text">请输入您的支付宝账号</text>
        </view>

        <!-- 账号输入 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">支付宝账号</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <input
              type="text"
              v-model="formData.account"
              placeholder="请输入支付宝账号（手机号或邮箱）"
              placeholder-class="input-placeholder"
              maxlength="50"
              @input="validateAccount"
            />
          </view>
          <view class="item-error" v-if="errors.account">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ errors.account }}</text>
          </view>
        </view>

        <!-- 确认账号输入 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">确认账号</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <input
              type="text"
              v-model="formData.confirmAccount"
              placeholder="请再次输入支付宝账号"
              placeholder-class="input-placeholder"
              maxlength="50"
              @input="validateConfirmAccount"
            />
          </view>
          <view class="item-error" v-if="errors.confirmAccount">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ errors.confirmAccount }}</text>
          </view>
        </view>

        <!-- 账号信息说明 -->
        <view class="info-box">
          <uni-icons type="info" size="16" color="#1890FF"></uni-icons>
          <view class="info-text">
            <view class="info-item-text">• 请确保账号信息准确无误，用于接收分红</view>
            <view class="info-item-text">• 支持手机号或邮箱格式的支付宝账号</view>
            <view class="info-item-text">• 绑定后如需修改请联系客服</view>
          </view>
        </view>

        <!-- 提交按钮 -->
        <button 
          class="submit-btn" 
          :class="{ disabled: !isFormValid || loading }"
          :disabled="!isFormValid || loading"
          @tap="handleSubmit"
        >
          <uni-icons v-if="loading" type="spinner-cycle" size="20" color="#fff"></uni-icons>
          <text v-else>确认绑定</text>
        </button>
      </view>

    </view>
  </view>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 表单数据
const formData = ref({
  account: '',
  confirmAccount: ''
});

// 错误信息
const errors = ref({
  account: '',
  confirmAccount: ''
});

// 加载状态
const loading = ref(false);

// 绑定成功状态
const isSuccess = ref(false);

// 脱敏后的账号
const maskedAccount = computed(() => {
  if (!formData.value.account) return '';
  const account = formData.value.account;
  
  // 如果是手机号
  if (/^1\d{10}$/.test(account)) {
    return account.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');
  }
  
  // 如果是邮箱
  if (account.includes('@')) {
    const [name, domain] = account.split('@');
    const maskedName = name.length > 2 
      ? name.substring(0, 2) + '****' 
      : name + '****';
    return maskedName + '@' + domain;
  }
  
  return account;
});

// 表单验证
const isFormValid = computed(() => {
  return formData.value.account && 
         formData.value.confirmAccount && 
         !errors.value.account && 
         !errors.value.confirmAccount;
});

// 验证账号
function validateAccount() {
  errors.value.account = '';
  
  if (!formData.value.account) {
    errors.value.account = '请输入支付宝账号';
    return false;
  }
  
  const account = formData.value.account.trim();
  
  // 验证手机号格式
  const mobileReg = /^1[3-9]\d{9}$/;
  // 验证邮箱格式
  const emailReg = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
  
  if (!mobileReg.test(account) && !emailReg.test(account)) {
    errors.value.account = '请输入正确的手机号或邮箱';
    return false;
  }
  
  return true;
}

// 验证确认账号
function validateConfirmAccount() {
  errors.value.confirmAccount = '';
  
  if (!formData.value.confirmAccount) {
    errors.value.confirmAccount = '请再次输入支付宝账号';
    return false;
  }
  
  if (formData.value.account !== formData.value.confirmAccount) {
    errors.value.confirmAccount = '两次输入的账号不一致';
    return false;
  }
  
  return true;
}

// 提交绑定
async function handleSubmit() {
  if (loading.value) return;
  
  // 验证表单
  const isAccountValid = validateAccount();
  const isConfirmValid = validateConfirmAccount();
  
  if (!isAccountValid || !isConfirmValid) {
    return;
  }
  
  loading.value = true;
  
  try {
    // 调用API绑定支付宝
    const res = await xxep.$api.user.bindAlipay({
      account: formData.value.account.trim()
    });
    
    if (res.code === 1) {
      isSuccess.value = true;
      uni.showToast({
        title: '绑定成功',
        icon: 'success'
      });
      
      // 更新用户信息
      xxep.$store('user').updateUserData();
    } else {
      uni.showToast({
        title: res.msg || '绑定失败',
        icon: 'none'
      });
    }
  } catch (error) {
    console.error('绑定支付宝失败:', error);
    uni.showToast({
      title: '网络错误，请重试',
      icon: 'none'
    });
  } finally {
    loading.value = false;
  }
}

// 返回
function handleBack() {
  uni.navigateBack();
}

onLoad(() => {
  // 页面加载时的逻辑
});
</script>

<style lang="scss" scoped>
.bind-page {
  min-height: 100vh;
  background: #F3F4F6;
}

.page-header {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 0 16px;
  background: #FFFFFF;
  border-bottom: 1px solid #E5E7EB;

  .header-left,
  .header-right {
    width: 60px;
    display: flex;
    align-items: center;
  }

  .header-title {
    flex: 1;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #1F2937;
  }
}

.page-content {
  padding: 20px 16px;
}

// 成功状态样式
.success-container {
//   display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 84px);

  .success-card {
    // width: 100%;
    background: #FFFFFF;
    border-radius: 16px;
    padding: 40px 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-align: center;

    .success-icon-wrapper {
      margin-bottom: 24px;
    }

    .success-title {
      font-size: 24px;
      font-weight: 600;
      color: #1F2937;
      margin-bottom: 8px;
    }

    .success-desc {
      font-size: 14px;
      color: #6B7280;
      margin-bottom: 32px;
    }

    .user-info-section {
      background: #F9FAFB;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;

      .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;

        .info-label {
          font-size: 14px;
          color: #6B7280;
        }

        .info-value {
          font-size: 16px;
          font-weight: 500;
          color: #1F2937;
        }
      }
    }

    .success-tips {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 12px;
      background: #EFF6FF;
      border-radius: 8px;
      margin-bottom: 32px;

      .tips-text {
        font-size: 13px;
        color: #1890FF;
      }
    }

    .back-btn {
      width: 100%;
      height: 44px;
      background: linear-gradient(135deg, #1890FF 0%, #096DD9 100%);
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      color: #FFFFFF;
      border: none;
      
      &::after {
        border: none;
      }
    }
  }
}

// 表单卡片样式
.card {
  background: #FFFFFF;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.platform-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;

  .icon-img {
    width: 64px;
    height: 64px;
  }
}

.form-title {
  text-align: center;
  margin-bottom: 32px;

  .title-text {
    font-size: 18px;
    font-weight: 600;
    color: #1F2937;
  }
}

.form-item {
  margin-bottom: 24px;

  .item-label {
    display: flex;
    align-items: center;
    margin-bottom: 12px;

    .label-text {
      font-size: 14px;
      font-weight: 500;
      color: #374151;
    }

    .label-required {
      color: #F44336;
      margin-left: 4px;
    }
  }

  .item-input {
    position: relative;

    input {
    //   width: 100%;
      height: 48px;
      padding: 0 16px;
      background: #F9FAFB;
      border: 1px solid #E5E7EB;
      border-radius: 8px;
      font-size: 15px;
      color: #1F2937;
      transition: all 0.3s;

      &:focus {
        background: #FFFFFF;
        border-color: #1890FF;
      }
    }

    .input-placeholder {
      color: #9CA3AF;
    }
  }

  .item-error {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;

    .error-text {
      font-size: 12px;
      color: #F44336;
    }
  }
}

.info-box {
  display: flex;
  gap: 8px;
  padding: 16px;
  background: #EFF6FF;
  border-radius: 8px;
  margin-bottom: 32px;

  .info-text {
    flex: 1;

    .info-item-text {
      font-size: 12px;
      color: #1890FF;
      line-height: 1.6;
      margin-bottom: 4px;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #1890FF 0%, #096DD9 100%);
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  
  &.disabled {
    background: #D1D5DB;
    opacity: 0.6;
  }

  &::after {
    border: none;
  }
}
</style>

