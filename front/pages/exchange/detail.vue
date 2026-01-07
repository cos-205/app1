<template>
  <s-layout
    title="兑换详情"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="detail-page">
      <!-- 奖品信息卡片 -->
      <view class="prize-card">
        <view class="prize-header">
          <image 
            v-if="detail.prize_image" 
            :src="detail.prize_image" 
            class="prize-image"
            mode="aspectFill"
          />
          <view v-else class="prize-placeholder">
            <text>{{ detail.prize_name }}</text>
          </view>
        </view>

        <view class="prize-info">
          <view class="prize-name">{{ detail.prize_name }}</view>
          <view class="prize-desc">{{ detail.prize_description }}</view>
          
          <view class="status-row">
            <text class="status-label">当前状态：</text>
            <view class="status-badge" :class="['status-' + detail.exchange_status]">
              <text>{{ getStatusText(detail.exchange_status) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 物流进度时间轴（手机奖品） -->
      <view v-if="detail.prize_type === 1" class="timeline-card">
        <view class="timeline-header">
          <text class="timeline-icon">🚚</text>
          <text class="timeline-title">物流进度</text>
        </view>

        <view class="timeline-list">
          <!-- 定制中 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 2 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status > 2,
              'processing': detail.exchange_status === 2,
              'pending': detail.exchange_status < 2
            }">
              <text v-if="detail.exchange_status > 2" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">定制中</view>
              <view class="timeline-desc">奖品定制阶段</view>
              <view v-if="detail.custom_time" class="timeline-time">
                {{ formatTime(detail.custom_time) }}
              </view>
            </view>
          </view>

          <!-- 待发货 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 3 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status > 3,
              'processing': detail.exchange_status === 3,
              'pending': detail.exchange_status < 3
            }">
              <text v-if="detail.exchange_status > 3" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">待发货</view>
              <view class="timeline-desc">定制完成,等待发货</view>
              <view v-if="detail.wait_ship_time" class="timeline-time">
                {{ formatTime(detail.wait_ship_time) }}
              </view>
            </view>
          </view>

          <!-- 已发货 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 4 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status > 4,
              'processing': detail.exchange_status === 4,
              'pending': detail.exchange_status < 4
            }">
              <text v-if="detail.exchange_status > 4" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">已发货</view>
              <view class="timeline-desc">已发出,等待收货</view>
              <view v-if="detail.ship_time" class="timeline-time">
                {{ formatTime(detail.ship_time) }}
              </view>
              <view v-if="detail.logistics_no" class="logistics-info">
                <text class="logistics-label">物流单号：</text>
                <text class="logistics-value">{{ detail.logistics_no }}</text>
              </view>
            </view>
          </view>

          <!-- 取件码 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 5 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status >= 5,
              'pending': detail.exchange_status < 5
            }">
              <text v-if="detail.exchange_status >= 5" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">到达取件点</view>
              <view class="timeline-desc">生成取件码（需付费获取）</view>
              <view v-if="detail.arrive_time" class="timeline-time">
                {{ formatTime(detail.arrive_time) }}
              </view>
              
              <view v-if="detail.exchange_status >= 5" class="pickup-code-section">
                <view v-if="detail.pickup_code_status === 'paid'" class="code-paid">
                  <text class="code-label">取件码：</text>
                  <text class="code-value">{{ detail.pickup_code }}</text>
                </view>
                <button v-else class="pay-code-btn" @click="handlePayCode">
                  <text class="btn-icon">💳</text>
                  <text class="btn-text">付费获取取件码</text>
                </button>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 汽车奖品物流 -->
      <view v-if="detail.prize_type === 2" class="timeline-card">
        <view class="timeline-header">
          <text class="timeline-icon">🚗</text>
          <text class="timeline-title">车辆物流</text>
        </view>

        <view class="timeline-list">
          <!-- 已备货 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 2 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status > 2,
              'processing': detail.exchange_status === 2,
              'pending': detail.exchange_status < 2
            }">
              <text v-if="detail.exchange_status > 2" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">已备货</view>
              <view class="timeline-desc">汽车已准备完成</view>
              <view v-if="detail.prepare_time" class="timeline-time">
                {{ formatTime(detail.prepare_time) }}
              </view>
            </view>
          </view>

          <!-- 托运 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 3 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status > 3,
              'processing': detail.exchange_status === 3,
              'pending': detail.exchange_status < 3
            }">
              <text v-if="detail.exchange_status > 3" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">托运</view>
              <view class="timeline-desc">进入托运流程（需付费）</view>
              <view v-if="detail.transport_time" class="timeline-time">
                {{ formatTime(detail.transport_time) }}
              </view>
              <view v-if="detail.transport_fee" class="fee-info">
                <text class="fee-label">托运费用：</text>
                <text class="fee-value">¥{{ detail.transport_fee }}</text>
              </view>
            </view>
          </view>

          <!-- 绿本 -->
          <view 
            class="timeline-item"
            :class="{ 'active': detail.exchange_status >= 4 }"
          >
            <view class="timeline-node" :class="{
              'completed': detail.exchange_status >= 4,
              'pending': detail.exchange_status < 4
            }">
              <text v-if="detail.exchange_status >= 4" class="node-icon">✓</text>
              <view v-else class="node-dot"></view>
            </view>
            <view class="timeline-content">
              <view class="timeline-name">车辆登记证书</view>
              <view class="timeline-desc">需付费获取车辆登记证书（绿本）</view>
              <view v-if="detail.doc_time" class="timeline-time">
                {{ formatTime(detail.doc_time) }}
              </view>
              
              <view v-if="detail.exchange_status >= 4" class="vehicle-doc-section">
                <view v-if="detail.vehicle_doc_status === 'paid'" class="doc-paid">
                  <text class="doc-label">车辆证书：</text>
                  <text class="doc-value">已获取</text>
                </view>
                <button v-else class="pay-doc-btn" @click="handlePayDoc">
                  <text class="btn-icon">📄</text>
                  <text class="btn-text">付费获取车辆证书</text>
                  <text v-if="detail.doc_fee" class="btn-fee">¥{{ detail.doc_fee }}</text>
                </button>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 兑换信息 -->
      <view class="info-card">
        <view class="info-title">兑换信息</view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">兑换时间</text>
            <text class="info-value">{{ formatTime(detail.exchange_time) }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">使用套数</text>
            <text class="info-value">{{ detail.fuka_set_count }}套五福卡</text>
          </view>
          <view v-if="detail.contact_name" class="info-row">
            <text class="info-label">收货人</text>
            <text class="info-value">{{ detail.contact_name }}</text>
          </view>
          <view v-if="detail.contact_phone" class="info-row">
            <text class="info-label">联系电话</text>
            <text class="info-value">{{ detail.contact_phone }}</text>
          </view>
          <view v-if="detail.shipping_address" class="info-row">
            <text class="info-label">收货地址</text>
            <text class="info-value address">{{ detail.shipping_address }}</text>
          </view>
        </view>
      </view>

      <!-- 温馨提示 -->
      <view class="tips-card">
        <view class="tips-title">温馨提示</view>
        <view class="tips-list">
          <text class="tips-item">• 请保持手机畅通,以便物流联系您</text>
          <text class="tips-item" v-if="detail.prize_type === 1">• 手机奖品到达取件点后需付费获取取件码</text>
          <text class="tips-item" v-if="detail.prize_type === 2">• 汽车奖品需支付托运费和证书费</text>
          <text class="tips-item">• 如有问题请联系客服</text>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 响应式数据
const detail = ref({})
const loading = ref(false)
const recordId = ref(0)

// 页面加载
onLoad((options) => {
  if (options.id) {
    recordId.value = options.id
    loadDetail()
  }
})

// 加载详情
const loadDetail = async () => {
  loading.value = true
  
  try {
    const res = await xxep.$api.card.getExchangeDetail(recordId.value)
    
    if (res.code === 1) {
      detail.value = res.data || {}
    } else {
      xxep.$helper.toast(res.msg || '加载失败', 'error')
    }
  } catch (error) {
    console.error('加载兑换详情失败', error)
    xxep.$helper.toast('加载失败,请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 处理取件码付费
const handlePayCode = () => {
  uni.navigateTo({
    url: `/pages/exchange/pay-pickup-code?id=${recordId.value}`
  })
}

// 处理证书付费
const handlePayDoc = () => {
  uni.navigateTo({
    url: `/pages/exchange/pay-vehicle-doc?id=${recordId.value}`
  })
}

// 格式化时间
const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

// 获取状态文本
const getStatusText = (status) => {
  const statusMap = {
    0: '待审核',
    1: '审核通过',
    2: '定制中',
    3: '待发货',
    4: '已发货',
    5: '已完成',
    6: '已取消'
  }
  return statusMap[status] || '未知'
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 兑换详情页面样式 - 遵循UI设计规范并参考协议处理流程UI
// ==========================================================================

.detail-page {
  padding: 32rpx; // --spacing-md
  padding-bottom: 32rpx;
}

// ==========================================================================
// 奖品信息卡片
// ==========================================================================
.prize-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 32rpx; // --spacing-md
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.prize-header {
  width: 100%;
  height: 400rpx;
  border-radius: 24rpx; // --radius
  overflow: hidden;
  margin-bottom: 32rpx; // --spacing-md
}

.prize-image {
  width: 100%;
  height: 100%;
}

.prize-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%); // --primary gradient
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48rpx; // --font-size-h2
  font-weight: 600; // --font-weight-bold
  color: #FFFFFF;
}

.prize-info {
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.prize-name {
  font-size: 48rpx; // --font-size-h2
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.prize-desc {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  line-height: 1.8; // --line-height-loose
}

.status-row {
  display: flex;
  align-items: center;
  gap: 16rpx; // --spacing-md
  margin-top: 16rpx; // --spacing-md
}

.status-label {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
}

.status-badge {
  padding: 8rpx 24rpx; // --spacing-xs
  border-radius: 24rpx; // --radius
  font-size: 24rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
  
  &.status-0 {
    background: rgba(156, 163, 175, 0.1);
    color: #9CA3AF; // --status-pending
  }
  
  &.status-1,
  &.status-2,
  &.status-3,
  &.status-4 {
    background: rgba(66, 133, 244, 0.1);
    color: #4285F4; // --status-processing
  }
  
  &.status-5 {
    background: rgba(0, 200, 83, 0.1);
    color: #00C853; // --status-completed
  }
  
  &.status-6 {
    background: rgba(244, 67, 54, 0.1);
    color: #F44336; // --status-rejected
  }
}

// ==========================================================================
// 时间轴组件 - 参考UI设计规范
// ==========================================================================
.timeline-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.timeline-header {
  display: flex;
  align-items: center;
  gap: 16rpx; // --spacing-md
  margin-bottom: 48rpx; // --spacing-xxl
}

.timeline-icon {
  font-size: 48rpx;
}

.timeline-title {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.timeline-list {
  position: relative;
  padding-left: 80rpx;
}

.timeline-item {
  position: relative;
  padding-bottom: 64rpx; // --spacing-xl
  
  &:last-child {
    padding-bottom: 0;
  }
  
  // 连接线
  &:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -56rpx;
    top: 96rpx;
    width: 4rpx;
    height: calc(100% - 64rpx);
    background: #E5E7EB; // --bg-gray
  }
  
  // 活跃状态的连接线
  &.active:not(:last-child)::before {
    background: #4285F4; // --primary-color
  }
}

.timeline-node {
  position: absolute;
  left: -80rpx;
  top: 0;
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 6rpx solid #E5E7EB; // --bg-gray
  background: #F3F4F6; // --bg-secondary
  transition: all 0.3s ease; // --transition-base
  
  &.completed {
    background: #4285F4; // --primary-color
    border-color: #4285F4; // --primary-color
    
    .node-icon {
      color: #FFFFFF;
      font-size: 48rpx;
      font-weight: 600; // --font-weight-bold
    }
  }
  
  &.processing {
    background: #4285F4; // --primary-color
    border-color: #4285F4; // --primary-color
    animation: pulse 2s infinite;
    
    .node-dot {
      width: 24rpx;
      height: 24rpx;
      background: #FFFFFF;
      border-radius: 50%;
    }
  }
  
  &.pending {
    .node-dot {
      width: 24rpx;
      height: 24rpx;
      background: #9CA3AF; // --text-tertiary
      border-radius: 50%;
    }
  }
}

// 脉冲动画
@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.4);
  }
  50% {
    box-shadow: 0 0 0 20rpx rgba(66, 133, 244, 0);
  }
}

.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx; // --spacing-sm
}

.timeline-name {
  font-size: 36rpx; // --font-size-h4
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.timeline-item:not(.active) .timeline-name {
  color: #6B7280; // --text-secondary
}

.timeline-desc {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  line-height: 1.6; // --line-height-base
}

.timeline-time {
  font-size: 24rpx; // --font-size-mini
  color: #4285F4; // --primary-color
  font-weight: 500; // --font-weight-medium
}

.timeline-item:not(.active) .timeline-time {
  color: #9CA3AF; // --text-tertiary
}

.logistics-info,
.fee-info {
  margin-top: 16rpx; // --spacing-md
  padding: 16rpx; // --spacing-md
  background: #F9FAFB;
  border-radius: 12rpx;
  display: flex;
  gap: 12rpx; // --spacing-sm
}

.logistics-label,
.fee-label {
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
}

.logistics-value {
  font-size: 24rpx; // --font-size-mini
  font-weight: 500; // --font-weight-medium
  color: #4285F4; // --primary-color
  font-family: monospace;
}

.fee-value {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #FF9800; // --status-warning
}

.pickup-code-section,
.vehicle-doc-section {
  margin-top: 24rpx; // --spacing-lg
}

.code-paid,
.doc-paid {
  display: flex;
  align-items: center;
  gap: 16rpx; // --spacing-md
  padding: 24rpx; // --spacing-lg
  background: linear-gradient(135deg, rgba(0, 200, 83, 0.05), rgba(0, 200, 83, 0.1));
  border-radius: 16rpx; // --radius
  border: 2rpx solid #00C853; // --success-color
}

.code-label,
.doc-label {
  font-size: 28rpx; // --font-size-small
  color: #1F2937; // --text-primary
  font-weight: 500; // --font-weight-medium
}

.code-value {
  font-size: 48rpx; // --font-size-h2
  font-weight: 600; // --font-weight-bold
  color: #00C853; // --success-color
  font-family: monospace;
}

.doc-value {
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #00C853; // --success-color
}

.pay-code-btn,
.pay-doc-btn {
  width: 100%;
  min-height: 88rpx; // --min-touch-size
  background: linear-gradient(135deg, #FF9800 0%, #FB8C00 100%); // --status-warning
  color: #FFFFFF;
  border: none;
  border-radius: 44rpx; // 圆形按钮
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx; // --spacing-md
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  box-shadow: 0 4rpx 16rpx rgba(255, 152, 0, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}

.btn-icon {
  font-size: 40rpx;
}

.btn-text {
  flex: 1;
}

.btn-fee {
  font-size: 36rpx; // --font-size-h4
  font-weight: 600; // --font-weight-bold
}

// ==========================================================================
// 兑换信息卡片
// ==========================================================================
.info-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.info-title {
  font-size: 36rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx; // --spacing-lg
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.info-label {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  flex-shrink: 0;
  width: 180rpx;
}

.info-value {
  flex: 1;
  font-size: 28rpx; // --font-size-small
  color: #1F2937; // --text-primary
  text-align: right;
  
  &.address {
    text-align: right;
    line-height: 1.8; // --line-height-loose
  }
}

// ==========================================================================
// 温馨提示
// ==========================================================================
.tips-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.tips-title {
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 24rpx; // --spacing-lg
}

.tips-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.tips-item {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  line-height: 2; // --line-height-loose
}
</style>

