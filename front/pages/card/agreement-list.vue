<template>
  <s-layout title="协议处理进度" navbar="inner" :bgStyle="{ color: '#F8F9FA' }">
    <view class="agreement-list-page">
      <!-- 步骤信息 -->
      <view class="step-header">
        <view class="step-info">
          <text class="step-label">步骤 {{ state.step }}</text>
          <text class="step-name">协议签署</text>
        </view>
        <view class="step-status" :class="'status-' + state.overallStatus">
          <text>{{ getOverallStatusText() }}</text>
        </view>
      </view>

      <!-- 进度概览 -->
      <view class="progress-overview">
        <view class="progress-title">处理进度</view>
        <view class="progress-info">
          <text class="progress-count">{{ state.completedCount }}/{{ state.totalCount }}</text>
          <view class="progress-bar">
            <view class="progress-fill" :style="{ width: state.progressPercent + '%' }"></view>
          </view>
        </view>
        <view class="progress-tips">
          <uni-icons type="info" size="14" color="#1890FF" />
          <text>预计需要 {{ state.estimatedDays }} 个工作日</text>
        </view>
      </view>

      <!-- 协议流程列表 -->
      <view class="flow-list">
        <view 
          class="flow-item"
          :class="'status-' + flow.status"
          v-for="flow in state.flowList"
          :key="flow.id"
        >
          <view class="flow-timeline">
            <view class="timeline-dot" :class="'status-' + flow.status">
              <uni-icons 
                v-if="flow.status === 2" 
                type="checkmarkempty" 
                size="16" 
                color="#FFFFFF" 
              />
              <uni-icons 
                v-else-if="flow.status === 1" 
                type="loop" 
                size="14" 
                color="#FFFFFF" 
              />
              <text v-else class="dot-num">{{ flow.flow_step }}</text>
            </view>
            <view class="timeline-line" v-if="flow.flow_step < state.totalCount"></view>
          </view>

          <view class="flow-content">
            <view class="flow-header">
              <view class="flow-title">
                <text class="flow-name">{{ flow.flow_name }}</text>
                <view class="flow-badge" :class="'badge-' + flow.status">
                  <text>{{ getStatusText(flow.status) }}</text>
                </view>
              </view>
              <view class="flow-time" v-if="flow.status >= 1">
                <text>{{ flow.days_range }}</text>
              </view>
            </view>

            <view class="flow-desc">{{ getFlowDescription(flow.flow_step) }}</view>

            <view class="flow-timeline-info" v-if="flow.start_time || flow.completed_time">
              <view class="time-item" v-if="flow.start_time">
                <uni-icons type="calendar" size="14" color="#999999" />
                <text>开始: {{ flow.start_time }}</text>
              </view>
              <view class="time-item" v-if="flow.completed_time">
                <uni-icons type="checkmarkempty" size="14" color="#52C41A" />
                <text>完成: {{ flow.completed_time }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 温馨提示 -->
      <view class="tips-card">
        <view class="tips-title">
          <uni-icons type="notification" size="16" color="#FF9800" />
          <text>温馨提示</text>
        </view>
        <view class="tips-content">
          <text>• 协议签署后需要邮寄纸质文件</text>
          <text>• 系统录入和审核需要1-3个工作日</text>
          <text>• 审核通过后费用将原路退回</text>
          <text>• 如有疑问请联系客服咨询</text>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="footer-buttons">
        <button class="back-button" @tap="goBack">
          返回金卡
        </button>
        <button class="refresh-button" @tap="loadFlowList">
          <uni-icons type="refreshempty" size="18" color="#FFFFFF" />
          <text>刷新进度</text>
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
  step: 1,
  flowList: [],
  completedCount: 0,
  totalCount: 5,
  progressPercent: 0,
  overallStatus: 0, // 0=未开始, 1=进行中, 2=已完成
  estimatedDays: '7-15',
});

onLoad((options) => {
  if (options.step) {
    state.step = options.step;
  }
  loadFlowList();
});

// 加载协议流程列表
async function loadFlowList() {
  uni.showLoading({ title: '加载中...' });

  try {
    const { code, data, msg } = await xxep.$api.card.agreementList({
      step_id: state.step,
    });

    uni.hideLoading();

    if (code === 1) {
      state.flowList = data.list || [];
      state.totalCount = state.flowList.length;
      
      // 计算完成数量
      state.completedCount = state.flowList.filter(f => f.status === 2).length;
      
      // 计算进度百分比
      state.progressPercent = state.totalCount > 0 
        ? Math.floor((state.completedCount / state.totalCount) * 100) 
        : 0;

      // 判断整体状态
      if (state.completedCount === state.totalCount) {
        state.overallStatus = 2; // 已完成
      } else if (state.completedCount > 0 || state.flowList.some(f => f.status === 1)) {
        state.overallStatus = 1; // 进行中
      } else {
        state.overallStatus = 0; // 未开始
      }

      // 计算预计天数
      const inProgressCount = state.flowList.filter(f => f.status === 1 || f.status === 0).length;
      const avgDays = 3;
      const minDays = inProgressCount * avgDays;
      const maxDays = inProgressCount * (avgDays + 2);
      state.estimatedDays = `${minDays}-${maxDays}`;
    } else {
      xxep.$helper.toast(msg || '加载失败');
    }
  } catch (error) {
    uni.hideLoading();
    console.error('加载协议流程失败:', error);
    xxep.$helper.toast('加载失败，请重试');
  }
}

// 获取状态文本
function getStatusText(status) {
  const statusMap = {
    0: '未开始',
    1: '进行中',
    2: '已完成',
  };
  return statusMap[status] || '未知';
}

// 获取整体状态文本
function getOverallStatusText() {
  const statusMap = {
    0: '待处理',
    1: '处理中',
    2: '已完成',
  };
  return statusMap[state.overallStatus] || '未知';
}

// 获取流程描述
function getFlowDescription(step) {
  const descriptions = {
    1: '用户在线签署电子协议',
    2: '将纸质协议邮寄至指定地址',
    3: '工作人员录入系统',
    4: '管理员审核协议信息',
    5: '审核通过后退还费用',
  };
  return descriptions[step] || '';
}

// 返回金卡页面
function goBack() {
  uni.navigateTo({
    url: '/pages/index/card',
  });
}
</script>

<style lang="scss" scoped>
.agreement-list-page {
  padding: 20rpx;
  padding-bottom: 200rpx;
}

.step-header {
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.step-info {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.step-label {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
}

.step-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
}

.step-status {
  padding: 8rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 255, 255, 0.2);

  text {
    font-size: 24rpx;
    color: #FFFFFF;
    font-weight: 500;
  }

  &.status-2 {
    background: rgba(82, 196, 26, 0.9);
  }

  &.status-1 {
    background: rgba(255, 152, 0, 0.9);
  }
}

.progress-overview {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.progress-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 20rpx;
}

.progress-info {
  display: flex;
  align-items: center;
  gap: 20rpx;
  margin-bottom: 16rpx;
}

.progress-count {
  font-size: 36rpx;
  font-weight: 700;
  color: #667EEA;
  min-width: 120rpx;
}

.progress-bar {
  flex: 1;
  height: 12rpx;
  background: #F0F0F0;
  border-radius: 6rpx;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667EEA 0%, #764BA2 100%);
  border-radius: 6rpx;
  transition: width 0.5s;
}

.progress-tips {
  display: flex;
  align-items: center;
  gap: 8rpx;

  text {
    font-size: 24rpx;
    color: #1890FF;
  }
}

.flow-list {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx 20rpx;
  margin-bottom: 20rpx;
}

.flow-item {
  display: flex;
  gap: 20rpx;
  padding-bottom: 40rpx;

  &:last-child {
    padding-bottom: 0;

    .timeline-line {
      display: none;
    }
  }
}

.flow-timeline {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.timeline-dot {
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  background: #D9D9D9;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;

  .dot-num {
    font-size: 24rpx;
    color: #FFFFFF;
    font-weight: 600;
  }

  &.status-1 {
    background: #FF9800;
    animation: pulse 2s infinite;
  }

  &.status-2 {
    background: #52C41A;
  }
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.7);
  }
  50% {
    box-shadow: 0 0 0 8rpx rgba(255, 152, 0, 0);
  }
}

.timeline-line {
  flex: 1;
  width: 2rpx;
  min-height: 60rpx;
  background: #E8E8E8;
  margin-top: 8rpx;
}

.flow-content {
  flex: 1;
  padding-top: 4rpx;
}

.flow-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12rpx;
}

.flow-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.flow-name {
  font-size: 30rpx;
  font-weight: 600;
  color: #333333;
}

.flow-badge {
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  font-size: 20rpx;

  &.badge-0 {
    background: #F0F0F0;
    color: #999999;
  }

  &.badge-1 {
    background: #FFF9E6;
    color: #FF9800;
  }

  &.badge-2 {
    background: #F0F9FF;
    color: #52C41A;
  }
}

.flow-time {
  font-size: 24rpx;
  color: #999999;
}

.flow-desc {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.6;
  margin-bottom: 12rpx;
}

.flow-timeline-info {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.time-item {
  display: flex;
  align-items: center;
  gap: 8rpx;
  font-size: 24rpx;
  color: #999999;
}

.tips-card {
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
  display: flex;
  gap: 20rpx;
  padding: 20rpx 30rpx;
  background: #FFFFFF;
  border-top: 1px solid #F0F0F0;
}

.back-button,
.refresh-button {
  flex: 1;
  height: 88rpx;
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

.back-button {
  background: #FFFFFF;
  color: #666666;
  border: 1px solid #D9D9D9;
}

.refresh-button {
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
  color: #FFFFFF;
}
</style>

