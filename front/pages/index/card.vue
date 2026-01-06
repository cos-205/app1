<template>
  <s-layout
    title="财富金卡"
    navbar="custom"
    tabbar="/pages/index/card"
    :bgStyle="{ color: '#F8F9FA' }"
    onShareAppMessage
  >
    <!-- 金卡卡片 -->
    <view class="card-header">
      <view class="card-banner"></view>
      <view class="card-wrap">
        <view class="golden-card" :class="state.cardData.status">
          <image class="card-bg-image" src="/static/images/card.png" mode="aspectFill" />
          <view class="card-shine"></view>
          <view class="card-content">
            <view class="card-title">财富金卡</view>
          </view>
        </view>
        
        <!-- 状态标签 -->
        <view class="card-status" v-if="state.cardData.statusText">
          <view class="status-tag" :class="state.cardData.status">
            <uni-icons :type="getStatusIcon()" size="16" color="#FFFFFF" />
            <text>{{ state.cardData.statusText }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 领取条件 / 卡片信息 -->
    <view class="section-box">
      <!-- 未领取：显示领取条件 -->
      <template v-if="!state.cardData.isReceived">
        <view class="condition-card ss-m-b-20">
          <view class="condition-title">
            <uni-icons type="gift" size="20" color="#FFC107" />
            <text>领取条件</text>
          </view>
          <view class="condition-list">
            <view 
              class="condition-item" 
              :class="{ completed: state.conditions.memberLevel }"
            >
              <uni-icons 
                :type="state.conditions.memberLevel ? 'checkmark-circle' : 'circle'" 
                size="18" 
                :color="state.conditions.memberLevel ? '#00C853' : '#9CA3AF'" 
              />
              <text>成为白金会员（邀请2位好友实名认证）</text>
            </view>
            <view 
              class="condition-item" 
              :class="{ completed: state.conditions.realName }"
            >
              <uni-icons 
                :type="state.conditions.realName ? 'checkmark-circle' : 'circle'" 
                size="18" 
                :color="state.conditions.realName ? '#00C853' : '#9CA3AF'" 
              />
              <text>完成实名认证</text>
            </view>
            <view 
              class="condition-item" 
              :class="{ completed: state.conditions.address }"
            >
              <uni-icons 
                :type="state.conditions.address ? 'checkmark-circle' : 'circle'" 
                size="18" 
                :color="state.conditions.address ? '#00C853' : '#9CA3AF'" 
              />
              <text>填写收货地址</text>
            </view>
          </view>
        </view>
        
        <view class="apply-btn-wrap">
          <button 
            class="apply-button" 
            :class="{ disabled: !canApply }"
            :disabled="!canApply || state.isSubmitting"
            @tap="handleApply"
          >
            <text v-if="state.isSubmitting">申领中...</text>
            <text v-else-if="canApply">🎉 免费领取财富金卡</text>
            <text v-else>请先完成领取条件</text>
          </button>
          <view class="apply-tips">
            <text>包邮包制卡费，全程免费</text>
          </view>
        </view>
      </template>
      
      <!-- 已领取：显示卡片信息 -->
      <template v-else>
        <view class="card-info-card">
          <view class="card-info-title">
            <uni-icons type="wallet" size="20" color="#4285F4" />
            <text>卡片信息</text>
          </view>
          <view class="card-info-list">
            <view class="card-info-item">
              <view class="info-label">
                <uni-icons type="person" size="16" color="#6B7280" />
                <text>持卡人</text>
              </view>
              <text class="info-value">{{ state.cardData.holderName }}</text>
            </view>
            <view class="card-info-item">
              <view class="info-label">
                <uni-icons type="contact" size="16" color="#6B7280" />
                <text>身份证号</text>
              </view>
              <text class="info-value">{{ state.cardData.idCard }}</text>
            </view>
            <view class="card-info-item highlight">
              <view class="info-label">
                <uni-icons type="wallet" size="16" color="#FF9800" />
                <text>卡内余额</text>
              </view>
              <text class="info-value amount">¥{{ state.cardData.balance }}</text>
            </view>
          </view>
        </view>
      </template>
    </view>

    <!-- 功能清单 -->
    <view class="section-box">
      <view class="section-header">
        <view class="section-title">
          <text class="title-icon">✨</text>
          <text>激活流程</text>
        </view>
        <view class="section-subtitle">完成以下步骤激活金卡全部功能</view>
      </view>
      
      <view class="function-list">
        <view 
          class="function-item" 
          v-for="(item, index) in state.functions" 
          :key="index"
          :class="{ completed: item.completed, disabled: !item.enabled }"
          @tap="handleFunctionClick(item)"
        >
          <view class="function-number">{{ index + 1 }}</view>
          <view class="function-info">
            <text class="function-name">{{ item.name }}</text>
            <text class="function-desc" v-if="item.desc">{{ item.desc }}</text>
          </view>
          <view class="function-status">
            <uni-icons v-if="item.completed" type="checkmark-circle" size="24" color="#00C853" />
            <uni-icons v-else-if="item.enabled" type="forward" size="20" color="#9CA3AF" />
            <uni-icons v-else type="locked" size="20" color="#E5E7EB" />
          </view>
        </view>
      </view>
    </view>

    <!-- 协议签署 -->
    <view class="section-box" v-if="state.cardData.isReceived && !state.cardData.agreementSigned">
      <view class="section-header">
        <view class="section-title">
          <text class="title-icon">📋</text>
          <text>协议签署</text>
        </view>
      </view>
      
      <view class="fee-card">
        <view class="fee-item">
          <text class="fee-label">登记费用</text>
          <text class="fee-value">¥300元</text>
        </view>
        <view class="fee-item">
          <text class="fee-label">收取机构</text>
          <text class="fee-value">金融管理智光局</text>
        </view>
        <view class="fee-item">
          <text class="fee-label">费用用途</text>
          <text class="fee-value">终端处理及系统收录</text>
        </view>
        <view class="fee-item highlight">
          <text class="fee-label">退还规则</text>
          <text class="fee-value refund">协议签署完成1个月后退还</text>
        </view>
      </view>
      
      <view class="apply-btn-wrap">
        <button 
          class="sign-button" 
          :disabled="state.isSubmitting"
          @tap="handleSignAgreement"
        >
          <text v-if="state.isSubmitting">签署中...</text>
          <text v-else>签署协议并支付 ¥300</text>
        </button>
      </view>
    </view>


    <!-- 申领成功弹窗 -->
    <view class="success-modal" v-if="state.showSuccessModal" @tap="closeSuccessModal">
      <view class="modal-mask" @tap="closeSuccessModal"></view>
      <view class="modal-content" @tap.stop>


        <!-- 标题 -->
        <view class="modal-title">
          <text class="title-emoji">🎉</text>
          <text class="title-text">恭喜！申领成功</text>
        </view>

        <!-- 主要提示信息 -->
        <view class="modal-message">
          <text>你申领财富金卡完成，审核验证通过后将开始制作卡片！</text>
          <text>耐心等待完成后将统一邮寄使用。</text>
          <text class="highlight">包邮包制卡费，全程免费</text>
        </view>

        <!-- 操作指引卡片 -->
        <view class="guide-cards">
          <view class="guide-card">
            <view class="guide-number">1</view>
            <view class="guide-info">
              <text class="guide-title">截图报备</text>
              <text class="guide-detail">截图此页面报备官方群加速审核验证</text>
            </view>
          </view>

          <view class="guide-card">
            <view class="guide-number">2</view>
            <view class="guide-info">
              <text class="guide-title">保持活跃</text>
              <text class="guide-detail">APP每天签到保持账号活跃会优先汇款～每天需查看审核与制卡邮寄进度！</text>
            </view>
          </view>

          <view class="guide-card highlight">
            <view class="guide-number">3</view>
            <view class="guide-info">
              <text class="guide-title">添加专员</text>
              <text class="guide-detail">务必添加陈亮专员土豆号<text class="specialist-id">【chen520】</text>加速审核制卡加速汇款到账使用</text>
            </view>
          </view>
        </view>

        <!-- 按钮组 -->
        <view class="modal-actions">
          <button class="action-btn secondary" @tap="closeSuccessModal">
            <text>我知道了</text>
          </button>
          <button class="action-btn primary" @tap="handleScreenshot">
            <uni-icons type="image" size="18" color="#FFFFFF" />
            <text>立即截图</text>
          </button>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 页面状态
const state = reactive({
  isSubmitting: false,
  showSuccessModal: true,
  cardData: {
    isReceived: false,
    status: 'not-received',
    statusText: '',
    holderName: '',
    idCard: '',
    balance: '0',
    agreementSigned: false
  },
  conditions: {
    memberLevel: false,
    realName: false,
    address: false
  },
  functions: [
    {
      id: 1,
      name: '使用协议跟金卡',
      desc: '签署金卡使用协议',
      completed: false,
      enabled: true
    },
    {
      id: 2,
      name: '设置卡片密码',
      desc: '设置金卡支付密码和取款密码',
      completed: false,
      enabled: false
    },
    {
      id: 3,
      name: '卡片大额收付款功能',
      desc: '开通大额收付款功能',
      completed: false,
      enabled: false
    },
    {
      id: 4,
      name: '签署支付宝保密合同',
      desc: '签署支付宝保密协议',
      completed: false,
      enabled: false
    },
    {
      id: 5,
      name: '财富金卡APP提现至卡片',
      desc: '在APP中绑定金卡',
      completed: false,
      enabled: false
    },
    {
      id: 6,
      name: '邮寄支付宝会员入场证',
      desc: '系统自动邮寄入场证',
      completed: false,
      enabled: false
    },
    {
      id: 7,
      name: '开通微信支付功能',
      desc: '在金卡上开通微信支付',
      completed: false,
      enabled: false
    },
    {
      id: 8,
      name: '开通支付宝支付功能',
      desc: '在金卡上开通支付宝支付',
      completed: false,
      enabled: false
    }
  ]
});

// 是否可以申领
const canApply = computed(() => {
  return state.conditions.memberLevel && state.conditions.realName && state.conditions.address;
});

// 获取状态图标
function getStatusIcon() {
  const iconMap = {
    'reviewing': 'eye',
    'customizing': 'gear',
    'shipping': 'paperplane',
    'received': 'checkmark-circle'
  };
  return iconMap[state.cardData.status] || 'info';
}

// 加载数据
async function loadData() {
  uni.showLoading({ title: '加载中...' });
  
  await loadCardInfo();
  
  uni.hideLoading();
}

// 加载金卡信息（包含流程配置）
async function loadCardInfo() {
  const res = await xxep.$api.card.getCardInfo();
  if (res.code === 1) {
    // 金卡信息
    if (res.data.card) {
      Object.assign(state.cardData, {
        isReceived: res.data.card.apply_status >= 2,
        status: getCardStatus(res.data.card),
        statusText: getCardStatusText(res.data.card),
        holderName: res.data.card.holder_name || '',
        idCard: res.data.card.holder_idcard || '',
        balance: res.data.card.balance || '0',
        agreementSigned: false
      });
    }
    
    // 流程配置列表（映射为 functions）
    if (res.data.flow_config && Array.isArray(res.data.flow_config)) {
      state.functions = res.data.flow_config.map((item, index) => ({
        id: item.step,
        name: item.step_name,
        desc: item.step_desc,
        completed: item.is_completed === 1,
        enabled: index === 0 || state.functions[index - 1]?.completed,
        needFee: item.need_fee === 1,
        feeAmount: item.fee_amount,
        feeName: item.fee_name,
        isPaid: item.is_pay_fee === 1
      }));
    }
    
    // 领取条件（根据用户信息判断）
    checkApplyConditions();
  }
}

// 获取卡片状态
function getCardStatus(card) {
  if (!card.apply_status || card.apply_status === 0) return 'not-received';
  if (card.apply_status === 1) return 'reviewing';
  if (card.apply_status === 2) return 'customizing';
  if (card.apply_status === 3) return 'shipping';
  if (card.apply_status === 4) return 'received';
  return 'not-received';
}

// 获取卡片状态文字
function getCardStatusText(card) {
  const statusMap = {
    1: '审核中',
    2: '制作中',
    3: '邮寄中',
    4: '已领取'
  };
  return statusMap[card.apply_status] || '';
}

// 检查领取条件
function checkApplyConditions() {
  const userStore = xxep.$store('user');
  const userInfo = userStore.userInfo;
  
  state.conditions.memberLevel = (userInfo.member_level || 0) >= 1;
  state.conditions.realName = userInfo.is_realname === 1;
  state.conditions.address = !!userInfo.address;
}

// 申领金卡
function handleApply() {
  if (!canApply.value || state.isSubmitting) return;
  
  uni.showModal({
    title: '确认申领',
    content: '确认申领财富金卡？包邮包制卡费，全程免费。',
    success: async (res) => {
      if (res.confirm) {
        state.isSubmitting = true;
        const result = await xxep.$api.card.apply({});
        
        if (result.code === 1) {
          showApplySuccessTip();
          await loadCardInfo();
        }
        
        state.isSubmitting = false;
      }
    }
  });
}

// 显示申领成功提示
function showApplySuccessTip() {
  state.showSuccessModal = true;
}

// 关闭成功弹窗
function closeSuccessModal() {
  state.showSuccessModal = false;
}

// 处理截图
function handleScreenshot() {
  // state.showSuccessModal = false;
  xxep.$helper.toast('请使用手机截图功能截取当前页面');
}

// 功能项点击
async function handleFunctionClick(item) {
  if (!item.enabled) {
    xxep.$helper.toast('请先完成前置步骤');
    return;
  }
  
  if (item.completed) {
    xxep.$helper.toast('已完成');
    return;
  }
  
  // 如果需要支付费用，先支付
  if (item.needFee && !item.isPaid) {
    uni.showModal({
      title: '支付费用',
      content: `该步骤需要支付${item.feeAmount}元（${item.feeName}）`,
      success: async (res) => {
        if (res.confirm) {
          state.isSubmitting = true;
          const payRes = await xxep.$api.card.payFee({ step: item.id });
          
          if (payRes.code === 1) {
            await loadCardInfo();
          }
          
          state.isSubmitting = false;
        }
      }
    });
    return;
  }
  
  state.isSubmitting = true;
  const res = await xxep.$api.card.completeStep({ step: item.id });
  
  if (res.code === 1) {
    await loadCardInfo();
  }
  
  state.isSubmitting = false;
}

// 签署协议（使用 payFee 支付协议费用）
function handleSignAgreement() {
  if (state.isSubmitting) return;
  
  uni.showModal({
    title: '签署协议',
    content: '签署协议需支付登记费用300元，该费用将在协议签署完成1个月后自动退还。',
    success: async (res) => {
      if (res.confirm) {
        state.isSubmitting = true;
        const result = await xxep.$api.card.payFee({
          step: 1  // 假设协议签署是第1步
        });
        
        if (result.code === 1) {
          await loadCardInfo();
        }
        
        state.isSubmitting = false;
      }
    }
  });
}

// 联系专员
function contactSpecialist() {
  uni.showModal({
    title: '联系专员',
    content: '请添加专员土豆号：chen520',
    confirmText: '复制',
    success: (res) => {
      if (res.confirm) {
        uni.setClipboardData({
          data: 'chen520',
          success: () => {
            xxep.$helper.toast('已复制');
          }
        });
      }
    }
  });
}

// 下拉刷新
onPullDownRefresh(() => {
  loadData().finally(() => {
    uni.stopPullDownRefresh();
  });
});

// 页面加载
onLoad(() => {
  loadData();
});
</script>

<style lang="scss" scoped>
@import '@/xxep/scss/_var.scss';

/* 金卡头部 */
.card-header {
  position: relative;
  margin-bottom: 32rpx;
}

.card-banner {
  width: 100%;
  height: 320rpx;
  background: linear-gradient(135deg, #FFC107 0%, #FFD54F 50%, #FFA000 100%);
}

.card-wrap {
  position: relative;
  margin: -200rpx 32rpx 0;
}

.golden-card {
  position: relative;
  width: 100%;
  height: 400rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border-radius: 32rpx;
  box-shadow: 0 16rpx 48rpx rgba(255, 165, 0, 0.4);
  overflow: hidden;
  
  &.not-received {
    background: linear-gradient(135deg, #E5E7EB 0%, #9CA3AF 100%);
    box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.1);
    
    .card-bg-image {
      opacity: 0.7;
    }
  }
}

.card-bg-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.card-shine {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    45deg,
    transparent 0%,
    rgba(255, 255, 255, 0.1) 45%,
    rgba(255, 255, 255, 0.3) 50%,
    rgba(255, 255, 255, 0.1) 55%,
    transparent 100%
  );
  transform: translateX(-100%);
  animation: shine 3s infinite;
  z-index: 3;
}

@keyframes shine {
  0%, 100% {
    transform: translateX(-100%);
  }
  50% {
    transform: translateX(100%);
  }
}

.card-content {
  position: relative;
  padding: 48rpx 40rpx;
  height: 100%;
  display: flex;
  flex-direction: column;
  z-index: 2;
}

.card-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #FFFFFF;
  text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.2);
  margin-bottom: 16rpx;
}

.card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .label {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
  }
  
  .value {
    font-size: 28rpx;
    color: #FFFFFF;
    font-weight: 500;
  }
  
  &.balance {
    margin-top: auto;
    padding-top: 16rpx;
    border-top: 1rpx solid rgba(255, 255, 255, 0.2);
    
    .value.amount {
      font-size: 40rpx;
      font-weight: 600;
    }
  }
}

.card-placeholder {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-text {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.6);
}

.card-status {
  margin-top: 24rpx;
  display: flex;
  justify-content: center;
}

.status-tag {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 24rpx;
  border-radius: 1000rpx;
  background: #4285F4;
  
  text {
    font-size: 24rpx;
    color: #FFFFFF;
    font-weight: 500;
  }
  
  &.reviewing { background: #FF9800; }
  &.customizing { background: #9C27B0; }
  &.shipping { background: #00C853; }
  &.received { background: #4CAF50; }
}

/* 通用section */
.section-box {
  margin: 0 32rpx 32rpx;
}

.section-header {
  margin-bottom: 24rpx;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 8rpx;
  
  .title-icon {
    font-size: 32rpx;
  }
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.section-subtitle {
  font-size: 24rpx;
  color: #6B7280;
  padding-left: 44rpx;
}

/* 领取条件 */
.condition-card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.condition-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.condition-list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.condition-item {
  display: flex;
  align-items: center;
  gap: 16rpx;
  
  text {
    font-size: 28rpx;
    color: #6B7280;
  }
  
  &.completed text {
    color: #1F2937;
  }
}

/* 卡片信息 */
.card-info-card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.card-info-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #F3F4F6;
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.card-info-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.card-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
  
  &.highlight {
    background: linear-gradient(135deg, #FFF9E6 0%, #FFF3D9 100%);
    border: 2rpx solid #FFE4A3;
  }
}

.info-label {
  display: flex;
  align-items: center;
  gap: 8rpx;
  
  text {
    font-size: 28rpx;
    color: #6B7280;
    font-weight: 500;
  }
}

.info-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 600;
  
  &.amount {
    font-size: 40rpx;
    color: #FF9800;
    font-weight: 700;
  }
}

/* 按钮 */
.apply-btn-wrap {
  margin-top: 24rpx;
}

.apply-button,
.sign-button {
  width: 100%;
  height: 88rpx;
  border-radius: 1000rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
  
  &::after {
    border: none;
  }
}

.apply-button {
  background: linear-gradient(90deg, #FFC107 0%, #FFD54F 100%);
  color: #1F2937;
  box-shadow: 0 8rpx 16rpx rgba(255, 193, 7, 0.3);
  
  &.disabled {
    background: #E5E7EB;
    box-shadow: none;
    color: #9CA3AF;
  }
}

.sign-button {
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  box-shadow: 0 8rpx 16rpx rgba(66, 133, 244, 0.3);
}

.apply-tips {
  margin-top: 16rpx;
  text-align: center;
  
  text {
    font-size: 24rpx;
    color: #9CA3AF;
  }
}

/* 功能清单 */
.function-list {
  background: #FFFFFF;
  border-radius: 24rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.function-item {
  display: flex;
  align-items: center;
  gap: 24rpx;
  padding: 32rpx;
  border-bottom: 1rpx solid #F3F4F6;
  
  &:last-child {
    border-bottom: none;
  }
  
  &.disabled {
    opacity: 0.5;
  }
}

.function-number {
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  font-size: 24rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  
  .function-item.completed & {
    background: #00C853;
  }
  
  .function-item.disabled & {
    background: #E5E7EB;
    color: #9CA3AF;
  }
}

.function-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.function-name {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
}

.function-desc {
  font-size: 24rpx;
  color: #6B7280;
}

.function-status {
  flex-shrink: 0;
}

/* 费用说明 */
.fee-card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.fee-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #F3F4F6;
  
  &:last-child {
    border-bottom: none;
  }
  
  &.highlight {
    background: #FFF9E6;
    margin: 16rpx -32rpx -32rpx;
    padding: 24rpx 32rpx;
  }
}

.fee-label {
  font-size: 28rpx;
  color: #6B7280;
}

.fee-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
  
  &.refund {
    color: #FF9800;
    font-weight: 600;
  }
}

/* 操作指引 */
.guide-list {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 32rpx;
}

.guide-item {
  display: flex;
  gap: 24rpx;
}

.guide-icon {
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
  color: #1F2937;
  font-size: 24rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.guide-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.guide-text {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
}

.guide-desc {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.6;
}

.contact-button {
  margin-top: 16rpx;
  width: 200rpx;
  height: 60rpx;
  background: linear-gradient(90deg, #00C853 0%, #34D058 100%);
  border-radius: 30rpx;
  font-size: 24rpx;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 200, 83, 0.3);
  
  &::after {
    border: none;
  }
  
  text {
    font-size: 24rpx;
  }
}

/* ========================================
   申领成功弹窗样式
   ======================================== */
.success-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
}

.modal-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px);
  animation: fadeIn 0.3s ease;
}

.modal-content {
  position: relative;
  width: 100%;
  max-width: 600rpx;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
  border-radius: 32rpx;
  padding: 60rpx 40rpx 40rpx;
  box-shadow: 0 24rpx 80rpx rgba(0, 0, 0, 0.2), 
              0 8rpx 24rpx rgba(0, 0, 0, 0.1);
  animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: hidden;
}

.modal-content::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 200rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  opacity: 0.05;
  border-radius: 32rpx 32rpx 0 0;
}

/* 成功图标 */
.success-icon-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 32rpx;
}

.success-icon {
  position: relative;
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-circle {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(76, 175, 80, 0.4);
  animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.icon-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 160rpx;
  height: 160rpx;
  background: radial-gradient(circle, rgba(76, 175, 80, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  animation: glowPulse 2s ease-in-out infinite;
}

/* 标题 */
.modal-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
}

.title-emoji {
  font-size: 48rpx;
  animation: emojiRotate 1s ease-in-out;
}

.title-text {
  font-size: 40rpx;
  font-weight: 700;
  background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 主要信息 */
.modal-message {
  text-align: center;
  line-height: 44rpx;
  color: #5A6C7D;
  margin-bottom: 32rpx;
  padding: 0 20rpx;
}

.modal-message text {
  display: block;
  font-size: 28rpx;
  margin-bottom: 8rpx;
}

.modal-message .highlight {
  color: #4CAF50;
  font-weight: 600;
  font-size: 30rpx;
  margin-top: 12rpx;
}

/* 操作指引卡片 */
.guide-cards {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  margin-bottom: 32rpx;
}

.guide-card {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
  padding: 24rpx;
  background: #FFFFFF;
  border-radius: 20rpx;
  border: 2rpx solid #E8EAED;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
}

.guide-card.highlight {
  background: linear-gradient(135deg, #FFF9E6 0%, #FFF3CC 100%);
  border-color: #FFD54F;
  box-shadow: 0 4rpx 16rpx rgba(255, 193, 7, 0.2);
}

.guide-number {
  flex-shrink: 0;
  width: 48rpx;
  height: 48rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
}

.guide-card.highlight .guide-number {
  background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
  box-shadow: 0 4rpx 12rpx rgba(255, 152, 0, 0.3);
}

.guide-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.guide-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #2C3E50;
  display: block;
}

.guide-detail {
  font-size: 26rpx;
  color: #5A6C7D;
  line-height: 38rpx;
  display: block;
}

.specialist-id {
  color: #FF9800;
  font-weight: 700;
  padding: 0 8rpx;
  background: rgba(255, 152, 0, 0.1);
  border-radius: 6rpx;
}

/* 按钮组 */
.modal-actions {
  display: flex;
  gap: 16rpx;
}

.action-btn {
  flex: 1;
  height: 88rpx;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
}

.action-btn::after {
  border: none;
}

.action-btn.secondary {
  background: #F5F7FA;
  color: #5A6C7D;
}

.action-btn.secondary:active {
  background: #E8EAED;
  transform: scale(0.96);
}

.action-btn.primary {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.4);
}

.action-btn.primary:active {
  transform: scale(0.96);
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
}

/* 动画 */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(100rpx) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes iconBounce {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes glowPulse {
  0%, 100% {
    opacity: 0.3;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.6;
    transform: translate(-50%, -50%) scale(1.1);
  }
}

@keyframes emojiRotate {
  0% {
    transform: rotate(0deg) scale(0.5);
    opacity: 0;
  }
  50% {
    transform: rotate(180deg) scale(1.2);
  }
  100% {
    transform: rotate(360deg) scale(1);
    opacity: 1;
  }
}
</style>

