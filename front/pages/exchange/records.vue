<template>
  <s-layout
    title="兑换记录"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="records-page">
      <!-- 筛选标签 -->
      <view class="filter-tabs">
        <view 
          v-for="tab in filterTabs" 
          :key="tab.value"
          class="filter-tab"
          :class="{ 'active': currentFilter === tab.value }"
          @click="changeFilter(tab.value)"
        >
          <text class="tab-text">{{ tab.label }}</text>
          <view v-if="currentFilter === tab.value" class="tab-indicator"></view>
        </view>
      </view>

      <!-- 记录列表 -->
      <view v-if="recordList.length > 0" class="records-list">
        <view 
          v-for="record in recordList" 
          :key="record.id"
          class="record-item"
          @click="viewDetail(record)"
        >
          <view class="record-header">
            <view class="record-title">
              <text class="prize-name">{{ record.prize_name }}</text>
              <view class="status-badge" :class="['status-' + record.exchange_status]">
                <text>{{ getStatusText(record.exchange_status) }}</text>
              </view>
            </view>
            <view class="record-time">{{ formatTime(record.exchange_time) }}</view>
          </view>

          <view class="record-body">
            <view class="record-image-wrapper">
              <image 
                v-if="record.prize_image" 
                :src="record.prize_image" 
                class="record-image"
                mode="aspectFill"
              />
              <view v-else class="image-placeholder">
                <text>{{ record.prize_name }}</text>
              </view>
            </view>

            <view class="record-info">
              <view class="info-item">
                <text class="info-label">兑换套数：</text>
                <text class="info-value">{{ record.fuka_set_count }}套</text>
              </view>
              
              <view v-if="record.logistics_no" class="info-item">
                <text class="info-label">物流单号：</text>
                <text class="info-value logistics">{{ record.logistics_no }}</text>
              </view>
              
              <view v-if="record.prize_type === 1 && record.pickup_code_status" class="info-item">
                <text class="info-label">取件码：</text>
                <text v-if="record.pickup_code_status === 'paid'" class="info-value code">
                  {{ record.pickup_code }}
                </text>
                <button v-else class="pay-btn" @click.stop="handlePayCode(record)">
                  付费获取
                </button>
              </view>
              
              <view v-if="record.prize_type === 2 && record.vehicle_doc_status" class="info-item">
                <text class="info-label">车辆证书：</text>
                <button 
                  v-if="record.vehicle_doc_status !== 'paid'" 
                  class="pay-btn" 
                  @click.stop="handlePayDoc(record)"
                >
                  付费获取
                </button>
                <text v-else class="info-value success">已获取</text>
              </view>
            </view>
          </view>

          <view class="record-footer">
            <button class="detail-btn" @click.stop="viewDetail(record)">
              查看详情
            </button>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-else-if="!loading" class="empty-state">
        <text class="empty-icon">📦</text>
        <text class="empty-text">暂无兑换记录</text>
        <button class="empty-btn" @click="goToExchange">
          去兑换
        </button>
      </view>

      <!-- 加载更多 -->
      <view v-if="loading" class="loading-more">
        <text>加载中...</text>
      </view>
      
      <view v-else-if="hasMore && recordList.length > 0" class="load-more" @click="loadMore">
        <text>加载更多</text>
      </view>
      
      <view v-else-if="recordList.length > 0" class="no-more">
        <text>已加载全部</text>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 响应式数据
const filterTabs = ref([
  { label: '全部', value: '' },
  { label: '待审核', value: '0' },
  { label: '定制中', value: '1,2' },
  { label: '已发货', value: '3,4' },
  { label: '已完成', value: '5' }
])
const currentFilter = ref('')
const recordList = ref([])
const loading = ref(false)
const hasMore = ref(true)
const currentPage = ref(1)
const pageSize = ref(10)

// 页面加载
onLoad(() => {
  loadRecordList()
})

// 切换筛选
const changeFilter = (value) => {
  if (currentFilter.value === value) return
  
  currentFilter.value = value
  currentPage.value = 1
  recordList.value = []
  hasMore.value = true
  loadRecordList()
}

// 加载兑换记录
const loadRecordList = async () => {
  if (loading.value) return
  
  loading.value = true
  
  try {
    const res = await xxep.$api.card.getExchangeRecords({
      page: currentPage.value,
      limit: pageSize.value,
      status: currentFilter.value
    })
    
    if (res.code === 1) {
      const data = res.data || {}
      const list = data.list || []
      
      if (currentPage.value === 1) {
        recordList.value = list
      } else {
        recordList.value = [...recordList.value, ...list]
      }
      
      hasMore.value = list.length >= pageSize.value
    } else {
      xxep.$helper.toast(res.msg || '加载失败', 'error')
    }
  } catch (error) {
    console.error('加载兑换记录失败', error)
    xxep.$helper.toast('加载失败,请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 加载更多
const loadMore = () => {
  if (!hasMore.value || loading.value) return
  
  currentPage.value++
  loadRecordList()
}

// 查看详情
const viewDetail = (record) => {
  uni.navigateTo({
    url: `/pages/exchange/detail?id=${record.id}`
  })
}

// 处理取件码付费
const handlePayCode = (record) => {
  uni.navigateTo({
    url: `/pages/exchange/pay-pickup-code?id=${record.id}`
  })
}

// 处理证书付费
const handlePayDoc = (record) => {
  uni.navigateTo({
    url: `/pages/exchange/pay-vehicle-doc?id=${record.id}`
  })
}

// 跳转到兑换页面
const goToExchange = () => {
  uni.navigateTo({
    url: '/pages/exchange/index'
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
// 兑换记录页面样式 - 遵循UI设计规范
// ==========================================================================

.records-page {
  padding-bottom: 32rpx; // --spacing-md
}

// ==========================================================================
// 筛选标签
// ==========================================================================
.filter-tabs {
  background: #FFFFFF; // --bg-primary
  padding: 16rpx 32rpx; // --spacing-md
  display: flex;
  gap: 32rpx; // --spacing-md
  overflow-x: auto;
  border-bottom: 2rpx solid #E5E7EB; // --bg-gray
  position: sticky;
  top: 0;
  z-index: 10;
}

.filter-tab {
  position: relative;
  flex-shrink: 0;
  padding: 16rpx 8rpx; // --spacing-md
  cursor: pointer;
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    opacity: 0.7;
  }
}

.tab-text {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  font-weight: 500; // --font-weight-medium
  transition: all 0.3s ease; // --transition-base
}

.filter-tab.active {
  .tab-text {
    font-size: 32rpx; // --font-size-base
    font-weight: 600; // --font-weight-bold
    color: #4285F4; // --primary-color
  }
}

.tab-indicator {
  position: absolute;
  bottom: -2rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 4rpx;
  background: #4285F4; // --primary-color
  border-radius: 2rpx;
}

// ==========================================================================
// 记录列表
// ==========================================================================
.records-list {
  padding: 32rpx; // --spacing-md
  display: flex;
  flex-direction: column;
  gap: 24rpx; // --spacing-lg
}

.record-item {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: translateY(2rpx); // 点击反馈
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
  }
}

.record-header {
  margin-bottom: 24rpx; // --spacing-lg
}

.record-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12rpx; // --spacing-sm
}

.prize-name {
  font-size: 36rpx; // --font-size-h4
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.status-badge {
  padding: 6rpx 20rpx; // --spacing-xs
  border-radius: 24rpx; // --radius
  font-size: 22rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
  
  &.status-0 {
    background: rgba(156, 163, 175, 0.1);
    color: #9CA3AF; // --status-pending
  }
  
  &.status-1,
  &.status-2,
  &.status-3 {
    background: rgba(66, 133, 244, 0.1);
    color: #4285F4; // --status-processing
  }
  
  &.status-4,
  &.status-5 {
    background: rgba(0, 200, 83, 0.1);
    color: #00C853; // --status-completed
  }
  
  &.status-6 {
    background: rgba(244, 67, 54, 0.1);
    color: #F44336; // --status-rejected
  }
}

.record-time {
  font-size: 24rpx; // --font-size-mini
  color: #9CA3AF; // --text-tertiary
}

.record-body {
  display: flex;
  gap: 24rpx; // --spacing-lg
  margin-bottom: 24rpx; // --spacing-lg
}

.record-image-wrapper {
  width: 160rpx;
  height: 160rpx;
  flex-shrink: 0;
  border-radius: 16rpx; // --radius
  overflow: hidden;
  background: #F9FAFB;
}

.record-image {
  width: 100%;
  height: 100%;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
  padding: 16rpx; // --spacing-md
  text-align: center;
  font-size: 24rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
  color: #1976D2;
}

.record-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.info-label {
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
}

.info-value {
  font-size: 28rpx; // --font-size-small
  font-weight: 500; // --font-weight-medium
  color: #1F2937; // --text-primary
  
  &.logistics {
    color: #4285F4; // --primary-color
  }
  
  &.code {
    font-family: monospace;
    background: #F3F4F6; // --bg-secondary
    padding: 4rpx 12rpx; // --spacing-xs
    border-radius: 8rpx;
    color: #00C853; // --success-color
  }
  
  &.success {
    color: #00C853; // --success-color
  }
}

.pay-btn {
  padding: 8rpx 24rpx; // --spacing-xs
  background: linear-gradient(135deg, #FF9800 0%, #FB8C00 100%); // --status-warning
  color: #FFFFFF;
  border: none;
  border-radius: 24rpx; // --radius
  font-size: 22rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.95); // 点击反馈
  }
}

.record-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 24rpx; // --spacing-lg
  border-top: 2rpx solid #E5E7EB; // --bg-gray
}

.detail-btn {
  padding: 16rpx 48rpx; // --spacing-md
  background: #4285F4; // --primary-color
  color: #FFFFFF;
  border: none;
  border-radius: 24rpx; // --radius
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}

// ==========================================================================
// 空状态
// ==========================================================================
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 160rpx 32rpx; // --spacing-xl
}

.empty-icon {
  font-size: 160rpx;
  margin-bottom: 32rpx; // --spacing-md
  opacity: 0.4;
}

.empty-text {
  font-size: 32rpx; // --font-size-base
  color: #9CA3AF; // --text-tertiary
  margin-bottom: 48rpx; // --spacing-xxl
}

.empty-btn {
  padding: 24rpx 64rpx; // --spacing-lg
  background: #4285F4; // --primary-color
  color: #FFFFFF;
  border: none;
  border-radius: 48rpx; // 圆形按钮
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}

// ==========================================================================
// 加载状态
// ==========================================================================
.loading-more,
.load-more,
.no-more {
  text-align: center;
  padding: 32rpx 0; // --spacing-md
  font-size: 28rpx; // --font-size-small
  color: #9CA3AF; // --text-tertiary
}

.load-more {
  color: #4285F4; // --primary-color
  font-weight: 500; // --font-weight-medium
  cursor: pointer;
  transition: opacity 0.3s ease; // --transition-base
  
  &:active {
    opacity: 0.7;
  }
}
</style>

