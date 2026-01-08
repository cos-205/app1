<template>
  <s-layout :title="state.pageTitle" >
    <!-- 头部背景 -->
    <view class="signin-header" :style="{ backgroundImage: `url(${headerBg})` }">
      <view class="header-content">
        <!-- 连续签到天数 -->
        <view class="signin-days">
          <text class="days-number">{{ state.signinDays }}</text>
          <text class="days-text">连续签到天数</text>
        </view>
        
        <!-- 集福机会 -->
        <view class="fuka-chance">
          <text class="chance-label">集福机会</text>
          <text class="chance-number">{{ state.fukaChance }}</text>
        </view>
      </view>
    </view>

    <!-- 签到按钮 -->
    <view class="signin-action">
      <button 
        v-if="state.canSignin" 
        class="signin-btn" 
        @tap="handleSignin"
        :loading="state.signinLoading"
      >
        立即签到
      </button>
      <button v-else class="signin-btn disabled" disabled>
        今日已签到
      </button>
      <view class="signin-tip">每日签到可获得1次集福机会</view>
    </view>

    <!-- 签到日历 -->
    <view class="calendar-section">
      <view class="section-title">
        <text class="title-icon">📅</text>
        <text class="title-text">签到日历</text>
      </view>
      
      <view class="calendar-grid">
        <view 
          v-for="(day, index) in state.calendarDays" 
          :key="index"
          class="calendar-day"
          :class="{
            'is-today': day.isToday,
            'is-signed': day.isSigned,
            'is-future': day.isFuture
          }"
        >
          <view class="day-number">{{ day.day }}</view>
          <view v-if="day.isSigned" class="day-status">✓</view>
        </view>
      </view>
    </view>

    <!-- 签到奖励 -->
    <view class="reward-section">
      <view class="section-title">
        <text class="title-icon">🎁</text>
        <text class="title-text">签到奖励</text>
      </view>
      
      <view class="reward-list">
        <view 
          v-for="reward in state.rewardList" 
          :key="reward.id"
          class="reward-item"
          :class="{
            'can-receive': reward.canReceive,
            'is-received': reward.isReceived
          }"
        >
          <view class="reward-left">
            <view class="reward-title">
              连续签到{{ reward.days }}天
            </view>
            <view class="reward-desc">
              {{ reward.description }}
            </view>
          </view>
          
          <view class="reward-right">
            <view v-if="reward.isReceived" class="reward-status received">
              已领取
            </view>
            <view 
              v-else-if="reward.canReceive" 
              class="reward-btn"
              @tap="handleReceiveReward(reward)"
            >
              领取
            </view>
            <view v-else class="reward-status locked">
              未达成
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 签到记录 -->
    <view class="record-section">
      <view class="section-title">
        <text class="title-icon">📝</text>
        <text class="title-text">最近签到</text>
      </view>
      
      <view class="record-list" v-if="state.recordList.length > 0">
        <view 
          v-for="record in state.recordList" 
          :key="record.id"
          class="record-item"
        >
          <view class="record-date">{{ record.date }}</view>
          <view class="record-reward">+1 集福机会</view>
        </view>
      </view>
      
      <s-empty v-else text="暂无签到记录" icon="/static/data-empty.png"></s-empty>
    </view>

    <!-- 签到成功弹窗 -->
    <su-popup :show="state.showSuccessModal" type="center" round="20" @close="state.showSuccessModal = false">
      <view class="success-modal">
        <view class="success-icon">🎉</view>
        <view class="success-title">签到成功</view>
        <view class="success-content">
          <view class="success-item">
            <text class="item-label">连续签到</text>
            <text class="item-value">{{ state.signinDays }}天</text>
          </view>
          <view class="success-item">
            <text class="item-label">获得集福机会</text>
            <text class="item-value">+1</text>
          </view>
          <view v-if="state.currentReward" class="success-reward">
            <text class="reward-label">🎁 触发奖励</text>
            <text class="reward-value">{{ state.currentReward.description }}</text>
          </view>
        </view>
        <button class="success-btn" @tap="state.showSuccessModal = false">
          确定
        </button>
      </view>
    </su-popup>

    <!-- 领取奖励弹窗 -->
    <su-popup :show="state.showRewardModal" type="center" round="20" @close="state.showRewardModal = false">
      <view class="reward-modal">
        <view class="modal-title">确认领取奖励</view>
        <view class="modal-content" v-if="state.selectedReward">
          <view class="modal-reward">
            <text class="reward-days">连续签到{{ state.selectedReward.days }}天</text>
            <text class="reward-money">{{ state.selectedReward.description }}</text>
          </view>
          <view class="modal-tip">奖励将发放到您的金卡账户</view>
        </view>
        <view class="modal-actions">
          <button class="modal-btn cancel" @tap="state.showRewardModal = false">
            取消
          </button>
          <button 
            class="modal-btn confirm" 
            @tap="confirmReceiveReward"
            :loading="state.receiveLoading"
          >
            确认领取
          </button>
        </view>
      </view>
    </su-popup>
  </s-layout>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import xxep from '@/xxep';
import { onLoad } from '@dcloudio/uni-app';

// 头部背景图
const headerBg = xxep.$url.static('/static/fuka/signin_bg.png');

const state = reactive({
  pageTitle: '每日签到',
  signinDays: 0,           // 连续签到天数
  fukaChance: 0,           // 集福机会
  cardBalance: null,       // 金卡余额
  canSignin: true,         // 是否可以签到
  signinLoading: false,    // 签到加载中
  receiveLoading: false,   // 领取加载中
  lastSigninDate: '',      // 最后签到日期
  calendarDays: [],        // 日历数据
  rewardList: [],          // 奖励列表
  recordList: [],          // 签到记录
  showSuccessModal: false, // 显示成功弹窗
  showRewardModal: false,  // 显示奖励弹窗
  currentReward: null,     // 当前触发的奖励
  selectedReward: null,    // 选中的奖励
});

// 格式化金额
function formatMoney(money) {
  return parseFloat(money).toFixed(2);
}

// 生成日历数据（最近7天）
function generateCalendar() {
  const today = new Date();
  const days = [];
  
  for (let i = 6; i >= 0; i--) {
    const date = new Date(today);
    date.setDate(date.getDate() - i);
    
    const day = date.getDate();
    const dateStr = formatDate(date);
    
    days.push({
      day: day,
      date: dateStr,
      isToday: i === 0,
      isSigned: false, // 后面从接口数据更新
      isFuture: false
    });
  }
  
  state.calendarDays = days;
}

// 格式化日期
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// 获取签到信息
async function getSigninInfo() {
  try {
    const { code, data } = await xxep.$api.card.getSigninInfo();
    
    if (code === 1) {
      state.signinDays = data.signin_days || 0;
      state.fukaChance = data.fuka_chance || 0;
      state.cardBalance = data.card_balance || 0;
      state.lastSigninDate = data.last_signin_date || '';
      
      // 检查今天是否已签到
      const today = formatDate(new Date());
      state.canSignin = state.lastSigninDate !== today;
      
      // 更新日历签到状态
      updateCalendarStatus(data.signin_dates || []);
    }
  } catch (e) {
    console.error('获取签到信息失败', e);
  }
}

// 更新日历签到状态
function updateCalendarStatus(signinDates) {
  state.calendarDays.forEach(day => {
    if (signinDates.includes(day.date)) {
      day.isSigned = true;
    }
  });
}

// 获取奖励列表
async function getRewardList() {
  try {
    const { code, data } = await xxep.$api.card.getSigninRewardList();
    
    if (code === 1) {
      state.rewardList = data.list || [];
    }
  } catch (e) {
    console.error('获取奖励列表失败', e);
  }
}

// 获取签到记录
async function getSigninRecords() {
  try {
    const { code, data } = await xxep.$api.card.getSigninRecords({
      page: 1,
      limit: 10
    });
    
    if (code === 1) {
      state.recordList = data.list || [];
    }
  } catch (e) {
    console.error('获取签到记录失败', e);
  }
}

// 执行签到
async function handleSignin() {
  if (!state.canSignin || state.signinLoading) {
    return;
  }
  
  state.signinLoading = true;
  
  try {
    const { code, data, msg } = await xxep.$api.card.doSignin();
    
    if (code === 1) {
      // 更新签到信息
      state.signinDays = data.signin_days;
      state.fukaChance = data.fuka_chance;
      state.canSignin = false;
      state.currentReward = data.reward;
      
      // 显示成功弹窗
      state.showSuccessModal = true;
      
      // 刷新数据
      setTimeout(() => {
        getData();
      }, 1500);
    } else {
      xxep.$helper.toast(msg || '签到失败');
    }
  } catch (e) {
    console.error('签到失败', e);
    xxep.$helper.toast('签到失败，请稍后重试');
  } finally {
    state.signinLoading = false;
  }
}

// 领取奖励
function handleReceiveReward(reward) {
  if (!reward.canReceive || reward.isReceived) {
    return;
  }
  
  state.selectedReward = reward;
  state.showRewardModal = true;
}

// 确认领取奖励
async function confirmReceiveReward() {
  if (!state.selectedReward || state.receiveLoading) {
    return;
  }
  
  state.receiveLoading = true;
  
  try {
    const { code, msg } = await xxep.$api.card.receiveSigninReward({
      rule_id: state.selectedReward.id
    });
    
    if (code === 1) {
      xxep.$helper.toast('领取成功');
      state.showRewardModal = false;
      
      // 刷新数据
      setTimeout(() => {
        getData();
      }, 1000);
    } else {
      xxep.$helper.toast(msg || '领取失败');
    }
  } catch (e) {
    console.error('领取奖励失败', e);
    xxep.$helper.toast('领取失败，请稍后重试');
  } finally {
    state.receiveLoading = false;
  }
}

// 获取所有数据
function getData() {
  getSigninInfo();
  getRewardList();
  getSigninRecords();
}

onLoad(() => {
  generateCalendar();
  getData();
});
</script>

<style lang="scss" scoped>
page {
  background: linear-gradient(180deg, #FFF5F0 0%, #FFE8D6 100%);
  min-height: 100vh;
}

// 头部区域
.signin-header {
  width: 100%;
  height: 400rpx;
  background: linear-gradient(135deg, #FF5722 0%, #D32F2F 100%);
  background-size: cover;
  background-position: center;
  padding: 60rpx 40rpx;
  box-sizing: border-box;
  position: relative;
  overflow: hidden;
  
  &::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400rpx;
    height: 400rpx;
    background: rgba(255, 215, 0, 0.2);
    border-radius: 50%;
  }
  
  &::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 300rpx;
    height: 300rpx;
    background: rgba(255, 138, 101, 0.2);
    border-radius: 50%;
  }
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    z-index: 1;
  }
  
  .signin-days {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    
    .days-number {
      font-size: 80rpx;
      font-weight: bold;
      color: #FFD700;
      line-height: 1;
      text-shadow: 
        2rpx 2rpx 4rpx rgba(211, 47, 47, 0.5),
        0 0 20rpx rgba(255, 215, 0, 0.6);
    }
    
    .days-text {
      font-size: 28rpx;
      color: #FFFFFF;
      margin-top: 10rpx;
      text-shadow: 1rpx 1rpx 2rpx rgba(0, 0, 0, 0.2);
    }
  }
  
  .fuka-chance {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    background: rgba(255, 255, 255, 0.95);
    padding: 20rpx 30rpx;
    border-radius: 50rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
    border: 2rpx solid rgba(255, 215, 0, 0.3);
    
    .chance-label {
      font-size: 24rpx;
      color: #666;
    }
    
    .chance-number {
      font-size: 48rpx;
      font-weight: bold;
      color: #D32F2F;
      margin-top: 10rpx;
    }
  }
}

// 签到按钮区域
.signin-action {
  margin: -60rpx 40rpx 30rpx;
  text-align: center;
  position: relative;
  z-index: 2;
  
  .signin-btn {
    width: 100%;
    height: 100rpx;
    line-height: 100rpx;
    background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
    border-radius: 50rpx;
    color: #FFFFFF;
    font-size: 32rpx;
    font-weight: bold;
    box-shadow: 0 8rpx 24rpx rgba(211, 47, 47, 0.4);
    border: 2rpx solid rgba(255, 215, 0, 0.3);
    text-shadow: 1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
    transition: all 0.3s ease;
    
    &:active {
      transform: scale(0.98);
      box-shadow: 0 4rpx 12rpx rgba(211, 47, 47, 0.3);
    }
    
    &.disabled {
      background: linear-gradient(135deg, #BDBDBD 0%, #9E9E9E 100%);
      box-shadow: none;
      border-color: transparent;
      opacity: 0.7;
    }
  }
  
  .signin-tip {
    font-size: 24rpx;
    color: #FF5722;
    margin-top: 20rpx;
    font-weight: 500;
  }
}

// 通用区块样式
.calendar-section,
.reward-section,
.record-section {
  background: rgba(255, 255, 255, 0.9);
  margin: 0 30rpx 30rpx;
  border-radius: 24rpx;
  padding: 30rpx;
  box-shadow: 0 4rpx 16rpx rgba(255, 87, 34, 0.08);
  border: 1rpx solid rgba(255, 215, 0, 0.2);
  backdrop-filter: blur(10rpx);
}

// 区块标题
.section-title {
  display: flex;
  align-items: center;
  margin-bottom: 30rpx;
  
  .title-icon {
    font-size: 36rpx;
    margin-right: 10rpx;
  }
  
  .title-text {
    font-size: 32rpx;
    font-weight: bold;
    color: #D32F2F;
    position: relative;
    
    &::after {
      content: '';
      position: absolute;
      bottom: -4rpx;
      left: 0;
      width: 60%;
      height: 4rpx;
      background: linear-gradient(90deg, #FF5722 0%, transparent 100%);
      border-radius: 2rpx;
    }
  }
}

// 日历网格
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 15rpx;
  
  .calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 235, 220, 0.5);
    border-radius: 12rpx;
    position: relative;
    border: 1rpx solid rgba(255, 215, 0, 0.15);
    transition: all 0.3s ease;
    
    &.is-today {
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      border: 2rpx solid #FF5722;
      box-shadow: 0 4rpx 12rpx rgba(255, 87, 34, 0.3);
      
      .day-number {
        color: #D32F2F;
        font-weight: bold;
      }
    }
    
    &.is-signed {
      background: linear-gradient(135deg, #FFE8D6 0%, #FFD7C1 100%);
      
      .day-status {
        position: absolute;
        top: 5rpx;
        right: 5rpx;
        font-size: 20rpx;
        color: #FF5722;
        font-weight: bold;
      }
      
      .day-number {
        color: #D32F2F;
      }
    }
    
    &.is-future {
      opacity: 0.3;
    }
    
    .day-number {
      font-size: 28rpx;
      color: #666;
      font-weight: 500;
    }
  }
}

// 奖励列表
.reward-list {
  .reward-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30rpx 20rpx;
    border-bottom: 1rpx solid rgba(255, 215, 0, 0.15);
    transition: all 0.3s ease;
    
    &:last-child {
      border-bottom: none;
    }
    
    &.can-receive {
      background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 165, 0, 0.1) 100%);
      border-left: 4rpx solid #FF5722;
      padding-left: 16rpx;
      
      .reward-title {
        color: #D32F2F;
      }
    }
    
    &.is-received {
      opacity: 0.6;
      background: rgba(0, 0, 0, 0.02);
    }
    
    .reward-left {
      flex: 1;
      
      .reward-title {
        font-size: 30rpx;
        font-weight: bold;
        color: #333;
        margin-bottom: 10rpx;
      }
      
      .reward-desc {
        font-size: 26rpx;
        color: #FF5722;
        font-weight: 600;
      }
    }
    
    .reward-right {
      margin-left: 20rpx;
      
      .reward-btn {
        padding: 12rpx 40rpx;
        background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
        color: #FFFFFF;
        font-size: 26rpx;
        font-weight: bold;
        border-radius: 30rpx;
        box-shadow: 0 4rpx 12rpx rgba(255, 87, 34, 0.3);
        border: 1rpx solid rgba(255, 215, 0, 0.3);
        
        &:active {
          transform: scale(0.95);
        }
      }
      
      .reward-status {
        font-size: 24rpx;
        padding: 10rpx 30rpx;
        border-radius: 30rpx;
        font-weight: 500;
        
        &.received {
          background: rgba(255, 232, 214, 0.6);
          color: #FF5722;
          border: 1rpx solid rgba(255, 87, 34, 0.2);
        }
        
        &.locked {
          background: rgba(189, 189, 189, 0.2);
          color: #999;
        }
      }
    }
  }
}

// 签到记录
.record-list {
  .record-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25rpx 20rpx;
    border-bottom: 1rpx solid rgba(255, 215, 0, 0.15);
    border-radius: 12rpx;
    margin-bottom: 10rpx;
    background: rgba(255, 245, 240, 0.5);
    transition: all 0.3s ease;
    
    &:last-child {
      border-bottom: 1rpx solid rgba(255, 215, 0, 0.15);
      margin-bottom: 0;
    }
    
    &:active {
      background: rgba(255, 235, 220, 0.8);
    }
    
    .record-date {
      font-size: 28rpx;
      color: #666;
      font-weight: 500;
    }
    
    .record-reward {
      font-size: 26rpx;
      color: #FF5722;
      font-weight: bold;
      background: rgba(255, 87, 34, 0.1);
      padding: 6rpx 16rpx;
      border-radius: 20rpx;
    }
  }
}

// 成功弹窗
.success-modal {
  width: 600rpx;
  padding: 60rpx 40rpx 40rpx;
  background: rgba(255, 232, 214, 0.98);
  backdrop-filter: blur(20rpx);
  border-radius: 32rpx;
  border: 3rpx solid rgba(255, 215, 0, 0.5);
  text-align: center;
  box-shadow: 0 16rpx 48rpx rgba(211, 47, 47, 0.3);
  animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  
  .success-icon {
    font-size: 100rpx;
    margin-bottom: 20rpx;
    animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  
  .success-title {
    font-size: 36rpx;
    font-weight: bold;
    color: #D32F2F;
    margin-bottom: 40rpx;
    text-shadow: 1rpx 1rpx 2rpx rgba(211, 47, 47, 0.1);
  }
  
  .success-content {
    margin-bottom: 40rpx;
    
    .success-item {
      display: flex;
      justify-content: space-between;
      padding: 20rpx 0;
      border-bottom: 1rpx solid rgba(255, 215, 0, 0.3);
      
      .item-label {
        font-size: 28rpx;
        color: #666;
      }
      
      .item-value {
        font-size: 28rpx;
        font-weight: bold;
        color: #FF5722;
      }
    }
    
    .success-reward {
      margin-top: 20rpx;
      padding: 20rpx;
      background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 165, 0, 0.15) 100%);
      border-radius: 16rpx;
      border: 2rpx solid rgba(255, 215, 0, 0.4);
      
      .reward-label {
        display: block;
        font-size: 24rpx;
        color: #666;
        margin-bottom: 10rpx;
      }
      
      .reward-value {
        font-size: 28rpx;
        font-weight: bold;
        color: #D32F2F;
      }
    }
  }
  
  .success-btn {
    width: 100%;
    height: 80rpx;
    line-height: 80rpx;
    background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
    color: #FFFFFF;
    font-size: 30rpx;
    font-weight: bold;
    border-radius: 40rpx;
    box-shadow: 0 8rpx 24rpx rgba(211, 47, 47, 0.4);
    border: 2rpx solid rgba(255, 215, 0, 0.3);
    text-shadow: 1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
    
    &:active {
      transform: scale(0.98);
    }
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(100rpx) scale(0.8);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes scaleIn {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

// 奖励弹窗
.reward-modal {
  width: 600rpx;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20rpx);
  border-radius: 32rpx;
  overflow: hidden;
  border: 3rpx solid rgba(255, 215, 0, 0.4);
  box-shadow: 0 16rpx 48rpx rgba(211, 47, 47, 0.25);
  
  .modal-title {
    padding: 40rpx;
    text-align: center;
    font-size: 32rpx;
    font-weight: bold;
    color: #D32F2F;
    border-bottom: 2rpx solid rgba(255, 215, 0, 0.3);
    background: linear-gradient(180deg, rgba(255, 232, 214, 0.3) 0%, transparent 100%);
  }
  
  .modal-content {
    padding: 40rpx;
    
    .modal-reward {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 30rpx;
      background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 165, 0, 0.1) 100%);
      border-radius: 20rpx;
      margin-bottom: 20rpx;
      border: 2rpx solid rgba(255, 215, 0, 0.4);
      box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.2);
      
      .reward-days {
        font-size: 28rpx;
        color: #666;
        margin-bottom: 10rpx;
        font-weight: 500;
      }
      
      .reward-money {
        font-size: 36rpx;
        font-weight: bold;
        color: #D32F2F;
        text-shadow: 1rpx 1rpx 2rpx rgba(211, 47, 47, 0.1);
      }
    }
    
    .modal-tip {
      text-align: center;
      font-size: 24rpx;
      color: #FF5722;
      font-weight: 500;
    }
  }
  
  .modal-actions {
    display: flex;
    border-top: 2rpx solid rgba(255, 215, 0, 0.3);
    
    .modal-btn {
      flex: 1;
      height: 100rpx;
      line-height: 100rpx;
      text-align: center;
      font-size: 30rpx;
      transition: all 0.3s ease;
      
      &.cancel {
        color: #999;
        background: rgba(245, 245, 245, 0.8);
        font-weight: 500;
        
        &:active {
          background: rgba(224, 224, 224, 0.8);
        }
      }
      
      &.confirm {
        color: #FFFFFF;
        background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
        font-weight: bold;
        text-shadow: 1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
        position: relative;
        
        &::before {
          content: '';
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          width: 0;
          height: 0;
          background: rgba(255, 255, 255, 0.3);
          border-radius: 50%;
          transition: all 0.5s ease;
        }
        
        &:active::before {
          width: 100%;
          height: 100%;
        }
      }
    }
  }
}
</style>

