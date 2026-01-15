<template>
  <view class="setup-required-page">
    <!-- 顶部导航 -->
    <view class="page-header">
      <view class="header-left" @tap="handleBack">
        <uni-icons type="arrowleft" size="24" color="#1F2937"></uni-icons>
      </view>
      <view class="header-title">完善信息</view>
      <view class="header-right"></view>
    </view>

    <!-- 主要内容 -->
    <view class="page-content">
      <!-- 页面说明 -->
      <view class="tip-card card">
        <view class="tip-icon">
          <uni-icons type="info" size="20" color="#4285F4"></uni-icons>
        </view>
        <view class="tip-content">
          <view class="tip-title">请完善以下信息</view>
          <view class="tip-desc">为了给您提供更好的服务，请填写实名认证和收货地址信息</view>
        </view>
      </view>

      <!-- 实名认证表单卡片 -->
      <view class="card form-card">
        <view class="form-title">
          <uni-icons type="person" size="20" color="#4285F4"></uni-icons>
          <text class="title-text">实名认证</text>
          <text class="required-mark">*</text>
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
              v-model="state.realnameData.realname"
              placeholder="请输入真实姓名"
              placeholder-class="input-placeholder"
              maxlength="20"
              @input="validateRealname"
            />
          </view>
          <view class="item-error" v-if="state.realnameErrors.realname">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.realnameErrors.realname }}</text>
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
              v-model="state.realnameData.idcard"
              placeholder="请输入18位身份证号码"
              placeholder-class="input-placeholder"
              maxlength="18"
              @input="validateIdcard"
            />
          </view>
          <view class="item-error" v-if="state.realnameErrors.idcard">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.realnameErrors.idcard }}</text>
          </view>
        </view>

        <!-- 身份证信息说明 -->
        <view class="info-box">
          <uni-icons type="locked" size="16" color="#6B7280"></uni-icons>
          <text class="info-text">您的个人信息将被加密保护，仅用于实名认证</text>
        </view>
      </view>

      <!-- 收货地址表单卡片 -->
      <view class="card form-card">
        <view class="form-title">
          <uni-icons type="location" size="20" color="#4285F4"></uni-icons>
          <text class="title-text">收货地址</text>
          <text class="required-mark">*</text>
        </view>

        <!-- 收货人 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">收货人</text>
            <text class="label-required">*</text>
            <text class="auto-fill-tip" v-if="state.realnameData.realname && !state.addressData.consignee">
              （已自动填充，可修改）
            </text>
          </view>
          <view class="item-input">
            <input
              type="text"
              v-model="state.addressData.consignee"
              placeholder="请输入收货人姓名"
              placeholder-class="input-placeholder"
              maxlength="20"
              @input="validateAddress"
            />
          </view>
          <view class="item-error" v-if="state.addressErrors.consignee">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.addressErrors.consignee }}</text>
          </view>
        </view>

        <!-- 手机号 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">手机号</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <input
              type="number"
              v-model="state.addressData.mobile"
              placeholder="请输入手机号"
              placeholder-class="input-placeholder"
              maxlength="11"
              @input="validateAddress"
            />
          </view>
          <view class="item-error" v-if="state.addressErrors.mobile">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.addressErrors.mobile }}</text>
          </view>
        </view>

        <!-- 省市区 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">省市区</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input region-picker" @tap="handleOpenRegionPicker">
            <text v-if="state.addressData.region" class="region-text">{{ state.addressData.region }}</text>
            <text v-else class="input-placeholder">请选择省市区</text>
            <uni-icons type="right" size="16" color="#9CA3AF" class="region-arrow"></uni-icons>
          </view>
          <view class="item-error" v-if="state.addressErrors.region">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.addressErrors.region }}</text>
          </view>
        </view>

        <!-- 详细地址 -->
        <view class="form-item">
          <view class="item-label">
            <text class="label-text">详细地址</text>
            <text class="label-required">*</text>
          </view>
          <view class="item-input">
            <textarea
              v-model="state.addressData.address"
              placeholder="请输入详细地址"
              placeholder-class="input-placeholder"
              maxlength="200"
              @input="validateAddress"
              class="address-textarea"
            />
          </view>
          <view class="item-error" v-if="state.addressErrors.address">
            <uni-icons type="info" size="14" color="#F44336"></uni-icons>
            <text class="error-text">{{ state.addressErrors.address }}</text>
          </view>
        </view>
      </view>

      <!-- 提交按钮 -->
      <view class="submit-section">
        <button
          class="submit-btn"
          :class="{ 'submit-btn-disabled': !canSubmit }"
          :disabled="!canSubmit || state.submitting"
          @tap="handleSubmit"
        >
          <text v-if="!state.submitting">提交并完成</text>
          <text v-else>提交中...</text>
        </button>
      </view>

      <!-- 温馨提示 -->
      <view class="notice-section">
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
          <text class="notice-text">收货人姓名已自动填充，您可以根据需要修改</text>
        </view>
        <view class="notice-item">
          <text class="notice-dot">•</text>
          <text class="notice-text">请确保收货地址信息准确，以便正常收货</text>
        </view>
      </view>
    </view>

    <!-- 省市区选择器 -->
    <su-region-picker
      :show="state.showRegion"
      @cancel="state.showRegion = false"
      @confirm="onRegionConfirm"
    />
  </view>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';
import _ from 'lodash';

// 表单数据
const state = reactive({
  // 实名认证表单数据
  realnameData: {
    realname: '',
    idcard: ''
  },
  
  // 收货地址表单数据
  addressData: {
    consignee: '', // 自动填充实名姓名（可修改）
    mobile: '',
    province_name: '',
    city_name: '',
    district_name: '',
    address: '',
    region: '', // 显示用的省市区字符串
    is_default: true
  },
  
  // 表单验证状态
  realnameErrors: {
    realname: '',
    idcard: ''
  },
  addressErrors: {
    consignee: '',
    mobile: '',
    region: '',
    address: ''
  },
  
  // 提交状态
  submitting: false,
  
  // 省市区选择器
  showRegion: false
});

// 监听实名姓名变化，自动填充收货人
watch(() => state.realnameData.realname, (newVal) => {
  if (newVal && !state.addressData.consignee) {
    // 如果收货人姓名为空，自动填充
    state.addressData.consignee = newVal;
  }
});

// 监听省市区变化，更新region字符串（作为备用，主要更新在 onRegionConfirm 中）
watch(
  () => [state.addressData.province_name, state.addressData.city_name, state.addressData.district_name],
  ([province, city, district]) => {
    if (province && city && district && !state.addressData.region) {
      // 只有当 region 为空时才更新，避免覆盖 onRegionConfirm 中的更新
      state.addressData.region = `${province}-${city}-${district}`;
      // 清除错误
      state.addressErrors.region = '';
    }
  },
  { deep: true }
);

// 验证姓名
const validateRealname = () => {
  state.realnameErrors.realname = '';
  
  if (!state.realnameData.realname) {
    return;
  }
  
  // 姓名格式验证：2-20个字符，只能包含中文、英文、·
  const nameReg = /^[\u4e00-\u9fa5a-zA-Z·]{2,20}$/;
  if (!nameReg.test(state.realnameData.realname)) {
    state.realnameErrors.realname = '请输入正确的姓名格式';
    return false;
  }
  
  return true;
};

// 验证身份证号
const validateIdcard = () => {
  state.realnameErrors.idcard = '';
  
  if (!state.realnameData.idcard) {
    return;
  }
  
  // 身份证格式验证：18位，前17位数字，最后一位数字或X
  const idcardReg = /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/;
  if (!idcardReg.test(state.realnameData.idcard)) {
    state.realnameErrors.idcard = '请输入正确的18位身份证号码';
    return false;
  }
  
  // 身份证校验码验证
  if (!checkIdcardValid(state.realnameData.idcard)) {
    state.realnameErrors.idcard = '身份证号码校验失败，请检查';
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

// 验证收货地址
const validateAddress = () => {
  // 收货人
  if (!state.addressData.consignee) {
    state.addressErrors.consignee = '';
    return;
  }
  if (state.addressData.consignee.length < 2) {
    state.addressErrors.consignee = '收货人姓名至少2个字符';
    return false;
  }
  state.addressErrors.consignee = '';
  
  // 手机号
  if (!state.addressData.mobile) {
    state.addressErrors.mobile = '';
    return;
  }
  const mobileReg = /^1[3-9]\d{9}$/;
  if (!mobileReg.test(state.addressData.mobile)) {
    state.addressErrors.mobile = '请输入正确的手机号';
    return false;
  }
  state.addressErrors.mobile = '';
  
  // 省市区
  if (!state.addressData.region) {
    state.addressErrors.region = '';
    return;
  }
  state.addressErrors.region = '';
  
  // 详细地址
  if (!state.addressData.address) {
    state.addressErrors.address = '';
    return;
  }
  if (state.addressData.address.length < 5) {
    state.addressErrors.address = '详细地址至少5个字符';
    return false;
  }
  state.addressErrors.address = '';
  
  return true;
};

// 是否可以提交
const canSubmit = computed(() => {
  const realnameValid = 
    state.realnameData.realname.length >= 2 &&
    state.realnameData.idcard.length === 18 &&
    !state.realnameErrors.realname &&
    !state.realnameErrors.idcard;
  
  const addressValid = 
    state.addressData.consignee.length >= 2 &&
    state.addressData.mobile.length === 11 &&
    state.addressData.region &&
    state.addressData.address.length >= 5 &&
    !state.addressErrors.consignee &&
    !state.addressErrors.mobile &&
    !state.addressErrors.region &&
    !state.addressErrors.address;
  
  return realnameValid && addressValid && !state.submitting;
});

// 打开省市区选择器（确保数据已加载）
const handleOpenRegionPicker = async () => {
  // 检查地区数据是否已加载
  const areaData = uni.getStorageSync('areaData');
  if (_.isEmpty(areaData)) {
    // 如果数据未加载，先加载数据
    uni.showLoading({ title: '加载中...' });
    try {
      await getAreaData();
      uni.hideLoading();
    } catch (error) {
      uni.hideLoading();
      xxep.$helper.toast('地区数据加载失败，请重试');
      return;
    }
  }
  // 确保数据已加载后再打开选择器
  state.showRegion = true;
};

// 省市区选择确认
const onRegionConfirm = (e) => {
  if (!e || !e.province_name || !e.city_name || !e.district_name) {
    console.error('地区选择数据不完整:', e);
    return;
  }
  
  // 逐个更新属性，保持响应式
  state.addressData.province_name = e.province_name;
  state.addressData.city_name = e.city_name;
  state.addressData.district_name = e.district_name;
  
  // 立即更新 region 字符串（不依赖 watch）
  state.addressData.region = `${e.province_name}-${e.city_name}-${e.district_name}`;
  
  // 清除错误
  state.addressErrors.region = '';
  
  state.showRegion = false;
};

// 提交处理
const handleSubmit = async () => {
  if (!canSubmit.value) {
    return;
  }
  
  // 最终验证
  if (!validateRealname()) {
    xxep.$helper.toast('请正确填写实名认证信息');
    return;
  }
  
  if (!validateIdcard()) {
    xxep.$helper.toast('请正确填写身份证号码');
    return;
  }
  
  if (!validateAddress()) {
    xxep.$helper.toast('请正确填写收货地址信息');
    return;
  }
  
  state.submitting = true;
  
  try {
    // 使用整合接口一次性提交
    const res = await xxep.$api.user.setupRequired({
      realname: state.realnameData.realname,
      idcard: state.realnameData.idcard.toUpperCase(),
      address: {
        consignee: state.addressData.consignee,
        mobile: state.addressData.mobile,
        province_name: state.addressData.province_name,
        city_name: state.addressData.city_name,
        district_name: state.addressData.district_name,
        address: state.addressData.address,
        is_default: state.addressData.is_default ? 1 : 0
      }
    });
    
    if (res.code === 1) {
       // 显示成功提示
       xxep.$helper.toast('信息完善成功！');
      // 刷新用户信息（确保获取最新的实名和地址状态）
      await xxep.$store('user').getInfo();
      // 稍微延迟一下，确保 store 持久化完成
      setTimeout(() => {
        uni.switchTab({
          url: '/pages/index/index',
        });
      }, 1500);
    }
    
  } catch (error) {
    console.error('提交失败:', error);
    xxep.$helper.toast(error.msg || '提交失败，请重试');
  } finally {
    state.submitting = false;
  }
};

// 返回
const handleBack = () => {
  uni.navigateBack({
    delta: 1,
  });
};

// 初始化地区数据
const getAreaData = async () => {
  const areaData = uni.getStorageSync('areaData');
  if (_.isEmpty(areaData)) {
    try {
      const res = await xxep.$api.data.area();
      if (res.code === 1 && res.data) {
        uni.setStorageSync('areaData', res.data);
        return res.data;
      }
    } catch (error) {
      console.error('加载地区数据失败:', error);
    }
  }
  return areaData;
};

onLoad(async () => {
  console.log('完善信息页面加载');
  
  // 确保地区数据已加载（异步等待）
  await getAreaData();
  
  // 检查用户是否已完善信息
  const userInfo = xxep.$store('user').userInfo;
  if (userInfo.is_realname === 1) {
    // 已实名认证，填充实名信息
    state.realnameData.realname = userInfo.realname || '';
    state.realnameData.idcard = userInfo.idcard || '';
  }
});
</script>

<style lang="scss" scoped>
.setup-required-page {
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
  padding: 24rpx;
}

/* 卡片基础样式 */
.card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
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
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 8rpx;
}

.tip-desc {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.5;
}

/* 表单卡片 */
.form-card {
  padding: 32rpx;
}

.form-title {
  display: flex;
  align-items: center;
  margin-bottom: 32rpx;
  padding-bottom: 16rpx;
  border-bottom: 1rpx solid #E5E7EB;
}

.title-text {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-left: 12rpx;
}

.required-mark {
  font-size: 32rpx;
  color: #F44336;
  margin-left: 6rpx;
}

/* 表单项 */
.form-item {
  margin-bottom: 32rpx;
}

.form-item:last-child {
  margin-bottom: 0;
}

.item-label {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
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

.auto-fill-tip {
  font-size: 24rpx;
  color: #4285F4;
  margin-left: 12rpx;
  font-weight: 400;
}

.item-input {
  position: relative;
}

.item-input input {
  width: 100%;
  height: 80rpx;
  padding: 0 24rpx;
  font-size: 28rpx;
  color: #1F2937;
  background: #F3F4F6;
  border: 2rpx solid #E5E7EB;
  border-radius: 12rpx;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.item-input input:focus {
  background: #FFFFFF;
  border-color: #4285F4;
}

.address-textarea {
  width: 100%;
  min-height: 120rpx;
  padding: 16rpx 24rpx;
  font-size: 28rpx;
  color: #1F2937;
  background: #F3F4F6;
  border: 2rpx solid #E5E7EB;
  border-radius: 12rpx;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.address-textarea:focus {
  background: #FFFFFF;
  border-color: #4285F4;
}

.region-picker {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}

.region-text {
  font-size: 32rpx;
  color: #1F2937;
  flex: 1;
}

.region-arrow {
  margin-left: 16rpx;
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
  padding: 16rpx 20rpx;
  margin-top: 24rpx;
  background: #F9FAFB;
  border-radius: 8rpx;
}

.info-text {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.5;
  margin-left: 12rpx;
}

/* 提交按钮 */
.submit-section {
  padding: 0 24rpx 24rpx;
}

.submit-btn {
  width: 100%;
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 44rpx;
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
  padding: 24rpx;
  background: #FFFFFF;
  border-radius: 24rpx;
  margin-bottom: 24rpx;
}

.notice-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 16rpx;
}

.notice-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12rpx;
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
</style>
