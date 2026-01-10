<template>
  <s-layout title="温馨提示" :bgStyle="{ color: '#F8F9FA' }">
    <view class="setup-guide-page">
      <!-- 顶部提示 -->
      <view class="header-tip">
        <view class="tip-icon">
          <uni-icons type="info-filled" size="24" color="#4285F4" />
        </view>
        <view class="tip-content">
          <text class="tip-title">完成以下步骤，继续使用系统功能</text>
          <text class="tip-desc">请按照顺序完成实名认证和收货地址设置</text>
        </view>
      </view>

      <!-- 步骤列表 -->
      <view class="steps-container">
        <!-- 步骤1：实名认证 -->
        <view class="step-card" :class="{ completed: state.steps.realname.completed }">
          <view class="step-header">
            <view class="step-number" :class="{ completed: state.steps.realname.completed }">
              <text v-if="!state.steps.realname.completed">1</text>
              <uni-icons v-else type="checkmarkempty" size="24" color="#FFFFFF" />
            </view>
            <view class="step-info">
              <view class="step-title">实名认证</view>
              <view class="step-desc">验证您的身份信息</view>
            </view>
            <view class="step-status" v-if="state.steps.realname.completed">
              <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
              <text class="status-text">已完成</text>
            </view>
          </view>
          
          <view class="step-content" v-if="!state.steps.realname.completed">
            <view class="step-detail">
              <text class="detail-text">• 需要填写真实姓名和身份证号码</text>
              <text class="detail-text">• 实名认证后无法修改，请仔细核对</text>
              <text class="detail-text">• 完成认证后可享受完整的服务权益</text>
            </view>
            <button class="step-action-btn" @tap="goToRealname">
              去认证
            </button>
          </view>
          
          <view class="step-completed-info" v-else>
            <view class="completed-item">
              <text class="item-label">真实姓名：</text>
              <text class="item-value">{{ state.userInfo.realname || '已认证' }}</text>
            </view>
            <view class="completed-item">
              <text class="item-label">身份证号：</text>
              <text class="item-value">{{ maskedIdcard }}</text>
            </view>
          </view>
        </view>

        <!-- 连接线 -->
        <view class="step-connector" :class="{ completed: state.steps.realname.completed }"></view>

        <!-- 步骤2：创建收货地址 -->
        <view class="step-card" :class="{ completed: state.steps.address.completed }">
          <view class="step-header">
            <view class="step-number" :class="{ completed: state.steps.address.completed }">
              <text v-if="!state.steps.address.completed">2</text>
              <uni-icons v-else type="checkmarkempty" size="24" color="#FFFFFF" />
            </view>
            <view class="step-info">
              <view class="step-title">创建收货地址</view>
              <view class="step-desc">填写您的收货地址信息</view>
            </view>
            <view class="step-status" v-if="state.steps.address.completed">
              <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
              <text class="status-text">已完成</text>
            </view>
          </view>
          
          <view class="step-content" v-if="!state.steps.address.completed">
            <view class="step-detail">
              <text class="detail-text">• 需要填写收货人姓名、手机号和详细地址</text>
              <text class="detail-text">• 地址信息用于商品配送和邮寄服务</text>
              <text class="detail-text">• 可以设置默认地址，方便后续使用</text>
            </view>
            <button class="step-action-btn" @tap="goToAddress" :disabled="!state.steps.realname.completed">
              {{ state.steps.realname.completed ? '去创建' : '请先完成实名认证' }}
            </button>
          </view>
          
          <view class="step-completed-info" v-else>
            <view class="completed-item">
              <text class="item-label">收货人：</text>
              <text class="item-value">{{ state.addressInfo.consignee || '已设置' }}</text>
            </view>
            <view class="completed-item">
              <text class="item-label">手机号：</text>
              <text class="item-value">{{ state.addressInfo.mobile || '已设置' }}</text>
            </view>
            <view class="completed-item">
              <text class="item-label">地址：</text>
              <text class="item-value">{{ state.addressInfo.fullAddress || '已设置' }}</text>
            </view>
            <button class="edit-btn" @tap="goToAddress">
              编辑地址
            </button>
          </view>
        </view>
      </view>

      <!-- 底部提示 -->
      <view class="footer-tip" v-if="allCompleted">
        <view class="tip-icon-success">
          <uni-icons type="checkmark-circle-filled" size="32" color="#00C853" />
        </view>
        <view class="tip-content">
          <text class="tip-title-success">恭喜！您已完成所有步骤</text>
          <text class="tip-desc">现在可以正常使用系统功能了</text>
        </view>
        <button class="continue-btn" @tap="goToHome">
          继续使用
        </button>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onShow, onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  steps: {
    realname: {
      completed: false,
    },
    address: {
      completed: false,
    },
  },
  userInfo: {
    realname: '',
    idcard: '',
    is_realname: 0,
  },
  addressInfo: {
    consignee: '',
    mobile: '',
    fullAddress: '',
  },
});

// 检查步骤完成状态
async function checkStepsStatus() {
  try {
    // 检查实名认证状态
    const userStore = xxep.$store('user');
    if (!userStore) {
      console.error('用户store未初始化');
      return;
    }
    
    await userStore.getInfo();
    const userInfo = userStore.userInfo || {};
    
    state.userInfo = {
      realname: userInfo.realname || '',
      idcard: userInfo.idcard || '',
      is_realname: userInfo.is_realname || 0,
    };
    
    state.steps.realname.completed = userInfo.is_realname === 1;
  } catch (error) {
    console.error('获取用户信息失败:', error);
  }
  
  
  // 检查收货地址
  try {
    const res = await xxep.$api.user.address.list();
    if (res && res.code === 1 && res.data && Array.isArray(res.data) && res.data.length > 0) {
      // 优先使用默认地址
      let address = res.data.find(addr => addr.is_default === 1);
      if (!address) {
        address = res.data[0];
      }
      
      // 兼容不同的地址字段名称
      const province = address.province || address.province_name || '';
      const city = address.city || address.city_name || '';
      const district = address.district || address.district_name || '';
      const addressDetail = address.address || '';
      
      state.addressInfo = {
        consignee: address.consignee || '',
        mobile: address.mobile || '',
        fullAddress: `${province}${city}${district}${addressDetail}`,
      };
      
      state.steps.address.completed = true;
    } else {
      state.steps.address.completed = false;
      state.addressInfo = {
        consignee: '',
        mobile: '',
        fullAddress: '',
      };
    }
  } catch (error) {
    console.error('获取地址列表失败:', error);
    state.steps.address.completed = false;
    state.addressInfo = {
      consignee: '',
      mobile: '',
      fullAddress: '',
    };
  }
}

// 是否全部完成
const allCompleted = computed(() => {
  return state.steps.realname.completed && state.steps.address.completed;
});

// 脱敏身份证号
const maskedIdcard = computed(() => {
  if (!state.userInfo || !state.userInfo.idcard) return '已认证';
  const idcard = String(state.userInfo.idcard);
  if (idcard.length === 18) {
    return idcard.substring(0, 6) + '********' + idcard.substring(14);
  }
  return '已认证';
});

// 跳转到实名认证页面
function goToRealname() {
  uni.navigateTo({
    url: '/pages/user/realname',
  });
}

// 跳转到收货地址页面
function goToAddress() {
  if (!state.steps.realname.completed) {
    xxep.$helper.toast('请先完成实名认证');
    return;
  }
  
  uni.navigateTo({
    url: '/pages/user/address/list',
  });
}

// 返回首页
function goToHome() {
  uni.switchTab({
    url: '/pages/index/index',
  });
}

// 页面加载时检查状态
onLoad(() => {
  checkStepsStatus();
});

// 页面显示时检查状态（从其他页面返回时刷新）
onShow(() => {
  checkStepsStatus();
});
</script>

<style lang="scss" scoped>
.setup-guide-page {
  padding: 20rpx;
  padding-bottom: 100rpx;
  min-height: 100vh;
}

/* 顶部提示 */
.header-tip {
  display: flex;
  align-items: flex-start;
  background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
}

.tip-icon {
  flex-shrink: 0;
  margin-right: 20rpx;
  margin-top: 4rpx;
}

.tip-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.tip-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
}

.tip-desc {
  font-size: 26rpx;
  color: #6B7280;
  line-height: 1.5;
}

/* 步骤容器 */
.steps-container {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* 步骤卡片 */
.step-card {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 0;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  
  &.completed {
    background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
    border: 2rpx solid #00C853;
  }
}

.step-header {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
}

.step-number {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: #E5E7EB;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;
  flex-shrink: 0;
  
  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #6B7280;
  }
  
  &.completed {
    background: #00C853;
  }
}

.step-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.step-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
}

.step-desc {
  font-size: 26rpx;
  color: #6B7280;
}

.step-status {
  display: flex;
  align-items: center;
  gap: 8rpx;
  flex-shrink: 0;
}

.status-text {
  font-size: 26rpx;
  color: #00C853;
  font-weight: 500;
}

/* 步骤内容 */
.step-content {
  margin-top: 20rpx;
}

.step-detail {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding: 20rpx;
  background: #F9FAFB;
  border-radius: 12rpx;
}

.detail-text {
  font-size: 26rpx;
  color: #6B7280;
  line-height: 1.6;
}

.step-action-btn {
  width: 100%;
  height: 80rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 40rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  
  &:disabled {
    background: #E5E7EB;
    color: #9CA3AF;
  }
  
  &:active:not(:disabled) {
    transform: scale(0.98);
  }
}

/* 已完成信息 */
.step-completed-info {
  margin-top: 20rpx;
  padding: 20rpx;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 12rpx;
}

.completed-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.item-label {
  font-size: 26rpx;
  color: #6B7280;
  flex-shrink: 0;
  width: 140rpx;
}

.item-value {
  font-size: 26rpx;
  color: #1F2937;
  flex: 1;
  word-break: break-all;
}

.edit-btn {
  width: 100%;
  height: 64rpx;
  background: #FFFFFF;
  border: 2rpx solid #00C853;
  border-radius: 32rpx;
  font-size: 28rpx;
  font-weight: 500;
  color: #00C853;
  margin-top: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  
  &:active {
    background: #F0FDF4;
  }
}

/* 连接线 */
.step-connector {
  width: 4rpx;
  height: 40rpx;
  background: #E5E7EB;
  margin: 0 auto;
  margin-left: 48rpx;
  transition: all 0.3s ease;
  
  &.completed {
    background: #00C853;
  }
}

/* 底部提示 */
.footer-tip {
  margin-top: 40rpx;
  padding: 40rpx;
  background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
  border-radius: 16rpx;
  border: 2rpx solid #00C853;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20rpx;
}

.tip-icon-success {
  margin-bottom: 10rpx;
}

.tip-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  text-align: center;
}

.tip-title-success {
  font-size: 32rpx;
  font-weight: 600;
  color: #00C853;
}

.tip-desc {
  font-size: 26rpx;
  color: #6B7280;
}

.continue-btn {
  width: 100%;
  height: 80rpx;
  background: linear-gradient(90deg, #00C853 0%, #00E676 100%);
  border-radius: 40rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 10rpx;
  
  &:active {
    transform: scale(0.98);
  }
}
</style>
