<template>
  <view class="realname-page">
    <!-- 顶部导航 -->
    <view class="page-header">
      <view class="header-left" @tap="handleBack">
        <uni-icons type="arrowleft" size="24" color="#1F2937"></uni-icons>
      </view>
      <view class="header-title">实名认证</view>
      <view class="header-right"></view>
    </view>

    <!-- 主要内容 -->
    <view class="page-content">

      <!-- 认证成功状态 -->
      <view v-if="isSuccess" class="success-container">
        <view class="success-card">
          <view class="success-icon-wrapper">
            <uni-icons type="checkmarkempty" size="80" color="#4CAF50"></uni-icons>
          </view>
          <view class="success-title">实名认证成功</view>
          <view class="success-desc">恭喜您已完成实名认证</view>
          
          <view class="user-info-section">
            <view class="info-item">
              <view class="info-label">真实姓名</view>
              <view class="info-value">{{ userRealname }}</view>
            </view>
            <view class="info-divider"></view>
            <view class="info-item">
              <view class="info-label">身份证号</view>
              <view class="info-value">{{ maskedIdcard }}</view>
            </view>
          </view>

          <view class="success-tips">
            <uni-icons type="info" size="16" color="#4285F4"></uni-icons>
            <text class="tips-text">您已享受完整的服务权益</text>
          </view>

          <button class="back-btn" @tap="handleBack">
            返回
          </button>
        </view>
      </view>

      <!-- 表单卡片 -->
      <view v-else class="card form-card">
        <view class="form-title">
          <uni-icons type="person" size="20" color="#4285F4"></uni-icons>
          <text class="title-text">请填写您的真实信息</text>
        </view>

        <!-- 姓名输入 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">真实姓名</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <input
              type="text"
              v-model="formData.realname"
              placeholder="请输入真实姓名"
              placeholder-class="input-placeholder"
              maxlength="20"
              @input="validateRealname"
            />
          </view>
          <view class="item-error" v-if="errors.realname">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ errors.realname }}</text>
          </view>
        </view>

        <!-- 身份证号输入 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">身份证号码</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <input
              type="idcard"
              v-model="formData.idcard"
              placeholder="请输入18位身份证号码"
              placeholder-class="input-placeholder"
              maxlength="18"
              @input="validateIdcard"
            />
          </view>
          <view class="item-error" v-if="errors.idcard">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ errors.idcard }}</text>
          </view>
        </view>

        <!-- 身份证信息说明 -->
        <view class="info-box">
          <uni-icons type="locked" size="16" color="#6B7280"></uni-icons>
          <text class="info-text">您的个人信息将被加密保护，仅用于实名认证</text>
        </view>
      </view>

      <!-- 提交按钮 -->
      <view v-if="!isSuccess" class="submit-section">
        <button
          class="submit-btn"
          :class="{ 'submit-btn-disabled': !canSubmit }"
          :disabled="!canSubmit"
          @tap="handleSubmit"
        >
          <text v-if="!submitting">立即认证</text>
          <text v-else>认证中...</text>
        </button>
      </view>

      <!-- 温馨提示 -->
      <view v-if="!isSuccess" class="notice-section">
        <view class="notice-title">温馨提示</view>
        <view class="notice-item">
          <text class="notice-dot">•</text>
          <text class="notice-text">请确保填写的姓名与身份证上的姓名一致</text>
        </view>
        <view class="notice-item">
          <text class="notice-dot">•</text>
          <text class="notice-text">身份证号码必须为18位有效身份证号</text>
        </view>
        <view class="notice-item">
          <text class="notice-dot">•</text>
          <text class="notice-text">实名认证成功后将无法修改，请仔细核对</text>
        </view>
        <view class="notice-item">
          <text class="notice-dot">•</text>
          <text class="notice-text">完成实名认证后可享受完整的服务权益</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 认证成功状态
const isSuccess = ref(false);

// 用户实名信息
const userRealname = ref('');
const userIdcard = ref('');

// 表单数据
const formData = reactive({
  realname: '',
  idcard: '',
});

// 错误信息
const errors = reactive({
  realname: '',
  idcard: '',
});

// 提交状态
const submitting = ref(false);

// 身份证号脱敏显示
const maskedIdcard = computed(() => {
  if (!userIdcard.value || userIdcard.value.length < 18) {
    return '';
  }
  return userIdcard.value.substring(0, 6) + '********' + userIdcard.value.substring(14);
});

// 验证姓名
const validateRealname = () => {
  errors.realname = '';
  
  if (!formData.realname) {
    return;
  }
  
  // 姓名格式验证：2-20个字符，只能包含中文、英文、·
  const nameReg = /^[\u4e00-\u9fa5a-zA-Z·]{2,20}$/;
  if (!nameReg.test(formData.realname)) {
    errors.realname = '请输入正确的姓名格式';
    return false;
  }
  
  return true;
};

// 验证身份证号
const validateIdcard = () => {
  errors.idcard = '';
  
  if (!formData.idcard) {
    return;
  }
  
  // 身份证格式验证：18位，前17位数字，最后一位数字或X
  const idcardReg = /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/;
  if (!idcardReg.test(formData.idcard)) {
    errors.idcard = '请输入正确的18位身份证号码';
    return false;
  }
  
  // 身份证校验码验证
  if (!checkIdcardValid(formData.idcard)) {
    errors.idcard = '身份证号码校验失败，请检查';
    return false;
  }
  
  return true;
};

// 身份证校验码验证
const checkIdcardValid = (idcard) => {
  const Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
  const ValideCode = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
  
  let sum = 0;
  for (let i = 0; i < 17; i++) {
    sum += Wi[i] * parseInt(idcard.charAt(i));
  }
  
  const valCodePosition = sum % 11;
  const lastChar = idcard.charAt(17).toUpperCase();
  
  return lastChar === ValideCode[valCodePosition];
};

// 是否可以提交
const canSubmit = computed(() => {
  return (
    formData.realname.length >= 2 &&
    formData.idcard.length === 18 &&
    !errors.realname &&
    !errors.idcard &&
    !submitting.value
  );
});

// 提交认证
const handleSubmit = async () => {
  if (!canSubmit.value) {
    return;
  }
  
  // 最终验证
  if (!validateRealname()) {
    xxep.$helper.toast('请输入正确的姓名');
    return;
  }
  
  if (!validateIdcard()) {
    xxep.$helper.toast('请输入正确的身份证号码');
    return;
  }
  
  submitting.value = true;
  
  try {
    const res = await xxep.$api.user.submitRealname({
      realname: formData.realname,
      idcard: formData.idcard.toUpperCase(),
    });
    
    if (res.code === 1) {
      // 保存用户实名信息
      userRealname.value = formData.realname;
      userIdcard.value = formData.idcard.toUpperCase();
      
      // 刷新用户信息（获取最新的实名认证状态）
      await xxep.$store('user').getInfo();
      
      // 显示成功状态
      isSuccess.value = true;
      
      // 显示成功提示
      xxep.$helper.toast('实名认证成功！', 'success');
    } else {
      // 显示失败提示
      xxep.$helper.toast(res.msg || '认证失败，请重试');
    }
    
  } catch (error) {
    console.error('实名认证失败:', error);
    xxep.$helper.toast(error.msg || '认证失败，请重试');
  } finally {
    submitting.value = false;
  }
};

// 返回
const handleBack = () => {
  uni.navigateBack({
    delta: 1,
  });
};

// 检查用户实名认证状态
const checkRealnameStatus = () => {
  const userInfo = xxep.$store('user').userInfo;
  
  if (userInfo.is_realname === 1) {
    // 已实名认证
    isSuccess.value = true;
    userRealname.value = userInfo.realname || '';
    userIdcard.value = userInfo.idcard || '';
  } else {
    // 未实名认证，显示表单
    isSuccess.value = false;
  }
};

onLoad(() => {
  console.log('实名认证页面加载');
  checkRealnameStatus();
});
</script>

<style lang="scss" scoped>
.realname-page {
  min-height: 100vh;
  background: #F3F4F6;
}

/* 顶部导航 */
.page-header {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 88rpx;
  padding: 0 32rpx;
  background: #FFFFFF;
  border-bottom: 1rpx solid #E5E7EB;
}

.header-left,
.header-right {
  width: 48rpx;
  height: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
}

/* 内容区域 */
.page-content {
  padding: 32rpx;
}

/* 卡片基础样式 */
.card {
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 48rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

/* 提示卡片 */
.tip-card {
  display: flex;
  align-items: flex-start;
  background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
}

.tip-icon {
  flex-shrink: 0;
  margin-right: 24rpx;
  margin-top: 4rpx;
}

.tip-content {
  flex: 1;
}

.tip-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 12rpx;
}

.tip-desc {
  font-size: 28rpx;
  color: #6B7280;
  line-height: 1.6;
}

/* 表单卡片 */
.form-card {
  padding: 48rpx;
}

.form-title {
  display: flex;
  align-items: center;
  margin-bottom: 48rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #E5E7EB;
}

.title-text {
  font-size: 36rpx;
  font-weight: 600;
  color: #1F2937;
  margin-left: 16rpx;
}

/* 表单项 */
.form-item {
  margin-bottom: 48rpx;
}

.form-item:last-child {
  margin-bottom: 0;
}

.item-label {
  display: flex;
  align-items: center;
  margin-bottom: 16rpx;
}

.label-text {
  font-size: 28rpx;
  font-weight: 500;
  color: #1F2937;
}

.label-required {
  font-size: 28rpx;
  color: #F44336;
  margin-left: 8rpx;
}

.item-input {
  position: relative;
}

.item-input input {
  // width: 100%;
  height: 88rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #1F2937;
  background: #F3F4F6;
  border: 2rpx solid #E5E7EB;
  border-radius: 16rpx;
  transition: all 0.3s ease;
}

.item-input input:focus {
  background: #FFFFFF;
  border-color: #4285F4;
}

.input-placeholder {
  color: #9CA3AF;
  font-size: 28rpx;
}

.item-error {
  display: flex;
  align-items: center;
  margin-top: 12rpx;
}

.error-text {
  font-size: 24rpx;
  color: #F44336;
  margin-left: 8rpx;
}

/* 信息说明框 */
.info-box {
  display: flex;
  align-items: center;
  padding: 24rpx;
  margin-top: 32rpx;
  background: #F9FAFB;
  border-radius: 12rpx;
}

.info-text {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.5;
  margin-left: 12rpx;
}

/* 提交按钮 */
.submit-section {
  padding: 0 32rpx 32rpx;
}

.submit-btn {
  width: 100%;
  height: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 48rpx;
  border: none;
  box-shadow: 0 8rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
}

.submit-btn::after {
  border: none;
}

.submit-btn:active:not(.submit-btn-disabled) {
  transform: scale(0.98);
  box-shadow: 0 4rpx 8rpx rgba(66, 133, 244, 0.2);
}

.submit-btn-disabled {
  background: #E5E7EB;
  color: #9CA3AF;
  box-shadow: none;
}

/* 温馨提示 */
.notice-section {
  padding: 32rpx;
  background: #FFFFFF;
  border-radius: 32rpx;
  margin-bottom: 32rpx;
}

.notice-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 24rpx;
}

.notice-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 20rpx;
}

.notice-item:last-child {
  margin-bottom: 0;
}

.notice-dot {
  font-size: 28rpx;
  color: #4285F4;
  margin-right: 12rpx;
  flex-shrink: 0;
}

.notice-text {
  font-size: 28rpx;
  color: #6B7280;
  line-height: 1.6;
  flex: 1;
}

/* 认证成功状态 */
.success-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-card {
  width: 100%;
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 80rpx 48rpx 64rpx;
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
  text-align: center;
}

.success-icon-wrapper {
  width: 160rpx;
  height: 160rpx;
  margin: 0 auto 40rpx;
  background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: successPulse 1.5s ease-in-out infinite;
}

@keyframes successPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 20rpx rgba(76, 175, 80, 0);
  }
}

.success-title {
  font-size: 44rpx;
  font-weight: 700;
  color: #1F2937;
  margin-bottom: 16rpx;
}

.success-desc {
  font-size: 28rpx;
  color: #6B7280;
  margin-bottom: 48rpx;
}

.user-info-section {
  background: #F9FAFB;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 32rpx;
}

.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16rpx 0;
}

.info-label {
  font-size: 28rpx;
  color: #6B7280;
}

.info-value {
  font-size: 30rpx;
  font-weight: 600;
  color: #1F2937;
}

.info-divider {
  height: 1rpx;
  background: #E5E7EB;
  margin: 8rpx 0;
}

.success-tips {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx;
  background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
  border-radius: 16rpx;
  margin-bottom: 40rpx;
}

.tips-text {
  font-size: 26rpx;
  color: #4285F4;
  margin-left: 12rpx;
}

.back-btn {
  width: 100%;
  height: 96rpx;
  background: linear-gradient(135deg, #4285F4 0%, #3367D6 100%);
  color: #FFFFFF;
  font-size: 32rpx;
  font-weight: 600;
  border-radius: 48rpx;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
}

.back-btn:active {
  transform: translateY(2rpx);
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
}
</style>

