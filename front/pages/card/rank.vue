<template>
  <s-layout
    title="集福排行榜"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="rank-page">
      <!-- 前三名展台 -->
    <view class="top-three-section">
      <!-- 第二名 -->
      <view v-if="topThree[1]" class="top-item rank-2">
        <view class="rank-badge">
          <text class="rank-text">2</text>
        </view>
        <view class="user-avatar-wrapper">
          <image 
            :src="topThree[1].avatar || defaultAvatar" 
            class="user-avatar"
            mode="aspectFill"
          />
        </view>
        <text class="user-nickname">{{ topThree[1].nickname }}</text>
        <text class="user-count">{{ topThree[1].fuka_count }}张</text>
      </view>

      <!-- 第一名 -->
      <view v-if="topThree[0]" class="top-item rank-1">
        <view class="crown-icon">👑</view>
        <view class="rank-badge champion">
          <text class="rank-text">1</text>
        </view>
        <view class="user-avatar-wrapper">
          <image 
            :src="topThree[0].avatar || defaultAvatar" 
            class="user-avatar"
            mode="aspectFill"
          />
        </view>
        <text class="user-nickname">{{ topThree[0].nickname }}</text>
        <text class="user-count">{{ topThree[0].fuka_count }}张</text>
      </view>

      <!-- 第三名 -->
      <view v-if="topThree[2]" class="top-item rank-3">
        <view class="rank-badge">
          <text class="rank-text">3</text>
        </view>
        <view class="user-avatar-wrapper">
          <image 
            :src="topThree[2].avatar || defaultAvatar" 
            class="user-avatar"
            mode="aspectFill"
          />
        </view>
        <text class="user-nickname">{{ topThree[2].nickname }}</text>
        <text class="user-count">{{ topThree[2].fuka_count }}张</text>
      </view>
    </view>

    <!-- 我的排名 -->
    <view v-if="myRank" class="my-rank-section">
      <view class="my-rank-card">
        <view class="rank-info">
          <text class="rank-label">我的排名</text>
          <text class="rank-value">{{ myRank.rank > 0 ? `第${myRank.rank}名` : '未上榜' }}</text>
        </view>
        <view class="count-info">
          <text class="count-label">福卡数量</text>
          <text class="count-value">{{ myRank.fuka_count }}张</text>
        </view>
      </view>
    </view>

    <!-- 排行榜列表 -->
    <view class="rank-list-section">
      <view class="section-title">完整排行榜</view>
      
      <view v-if="rankList.length > 0" class="rank-list">
        <view 
          v-for="(item, index) in rankList" 
          :key="item.id"
          class="rank-item"
          :class="{ 
            'is-me': item.user_id === currentUserId,
            'top-rank': item.rank <= 3
          }"
        >
          <view class="rank-number">
            <text v-if="item.rank <= 3" class="medal">{{ getMedalEmoji(item.rank) }}</text>
            <text v-else class="number">{{ item.rank }}</text>
          </view>
          
          <view class="user-info">
            <image 
              :src="item.avatar || defaultAvatar" 
              class="user-avatar-small"
              mode="aspectFill"
            />
            <view class="user-details">
              <text class="user-nickname-text">
                {{ item.nickname }}
                <text v-if="item.user_id === currentUserId" class="me-tag">（我）</text>
              </text>
              <text class="user-update-time">{{ formatTime(item.update_time) }}</text>
            </view>
          </view>
          
          <view class="count-info-right">
            <text class="count-number">{{ item.fuka_count }}</text>
            <text class="count-unit">张</text>
          </view>
        </view>
      </view>
      
      <view v-else-if="!loading" class="rank-empty">
        <text class="empty-icon">📊</text>
        <text class="empty-text">暂无排行数据</text>
      </view>
      
      <view v-if="loading" class="loading-more">
        <text>加载中...</text>
      </view>
      
      <view v-else-if="hasMore" class="load-more" @click="loadMore">
        <text>加载更多</text>
      </view>
      
      <view v-else-if="rankList.length > 0" class="no-more">
        <text>已加载全部</text>
      </view>
    </view>

    <!-- 排行榜说明 -->
    <view class="rank-tips">
      <text class="tips-title">📋 排行榜说明</text>
      <text class="tips-item">• 排行榜每小时更新一次</text>
      <text class="tips-item">• 显示所有用户已收集的福卡总数</text>
      <text class="tips-item">• 已使用的福卡不计入排名</text>
      <text class="tips-item">• 数量相同时，先达到者排名靠前</text>
    </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 响应式数据
const rankList = ref([])
const myRank = ref(null)
const loading = ref(false)
const hasMore = ref(true)
const currentPage = ref(1)
const pageSize = ref(20)
const currentUserId = ref(0)
const defaultAvatar = '/static/images/default-avatar.png'

// 前三名
const topThree = computed(() => {
  const top = rankList.value.filter(item => item.rank <= 3)
  return [
    top.find(item => item.rank === 1),
    top.find(item => item.rank === 2),
    top.find(item => item.rank === 3)
  ]
})

// 页面加载
onLoad(() => {
  console.log('排行榜页面加载')
  initPage()
})

// 初始化页面
const initPage = async () => {
  try {
    // 获取当前用户ID
    const userInfo = xxep.$store('user').userInfo
    if (userInfo && userInfo.id) {
      currentUserId.value = userInfo.id
    }
    
    await loadRankList()
  } catch (error) {
    console.error('初始化页面失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  }
}

// 加载排行榜
const loadRankList = async (loadingMore = false) => {
  if (loading.value) return
  
  loading.value = true
  
  try {
    const res = await xxep.$api.card.getCardRank({
      page: currentPage.value,
      limit: pageSize.value
    })
    
    if (res.code === 1) {
      const data = res.data || {}
      const list = data.list || []
      
      if (loadingMore) {
        rankList.value = [...rankList.value, ...list]
      } else {
        rankList.value = list
      }
      
      // 我的排名
      if (data.my_rank) {
        myRank.value = data.my_rank
      }
      
      // 判断是否还有更多数据
      hasMore.value = list.length >= pageSize.value
    } else {
      xxep.$helper.toast(res.msg || '加载失败', 'error')
    }
  } catch (error) {
    console.error('加载排行榜失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 加载更多
const loadMore = () => {
  if (!hasMore.value || loading.value) return
  
  currentPage.value++
  loadRankList(true)
}

// 获取奖牌emoji
const getMedalEmoji = (rank) => {
  const medals = {
    1: '🥇',
    2: '🥈',
    3: '🥉'
  }
  return medals[rank] || rank
}

// 格式化时间
const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const now = new Date()
  const diff = now - date
  
  // 一天内显示相对时间
  if (diff < 24 * 60 * 60 * 1000) {
    const hours = Math.floor(diff / (60 * 60 * 1000))
    if (hours < 1) {
      const minutes = Math.floor(diff / (60 * 1000))
      return minutes < 1 ? '刚刚更新' : `${minutes}分钟前更新`
    }
    return `${hours}小时前更新`
  }
  
  // 超过一天显示日期
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  
  if (year === now.getFullYear()) {
    return `${month}-${day} 更新`
  }
  return `${year}-${month}-${day} 更新`
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 排行榜页面样式 - 遵循UI设计规范
// ==========================================================================

.rank-page {
  padding: 32rpx; // --spacing-md
  padding-bottom: 32rpx;
}

// ==========================================================================
// 前三名展台
// ==========================================================================
.top-three-section {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 16rpx; // --spacing-md
  margin-bottom: 48rpx; // --spacing-xxl
  padding: 0 16rpx; // --spacing-md
}

.top-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32rpx 16rpx; // --spacing-md
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx 32rpx 0 0; // --radius
  position: relative;
  box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.08);
  border-top: 4rpx solid #E5E7EB; // --bg-gray
  
  &.rank-1 {
    height: 400rpx;
    background: linear-gradient(135deg, #ffffff 0%, #E8F5E9 100%);
    z-index: 3;
    border-top-color: #00C853; // --success-color
  }
  
  &.rank-2 {
    height: 350rpx;
    background: linear-gradient(135deg, #ffffff 0%, #E3F2FD 100%);
    z-index: 2;
    border-top-color: #4285F4; // --primary-color
  }
  
  &.rank-3 {
    height: 320rpx;
    background: linear-gradient(135deg, #ffffff 0%, #FFF3E0 100%);
    z-index: 1;
    border-top-color: #FF9800; // --status-warning
  }
}

.crown-icon {
  position: absolute;
  top: -32rpx;
  font-size: 48rpx;
  animation: float 2s ease-in-out infinite;
}

// 皇冠浮动动画
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8rpx);
  }
}

.rank-badge {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: #F3F4F6; // --bg-secondary
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16rpx; // --spacing-md
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
  border: 3rpx solid #E5E7EB; // --bg-gray
  
  &.champion {
    width: 72rpx;
    height: 72rpx;
    background: #00C853; // --success-color
    border-color: #00C853; // --success-color
  }
}

.rank-badge .rank-text {
  font-size: 32rpx;
  font-weight: 600;
  color: #4285F4;
}

.rank-badge.champion .rank-text {
  font-size: 40rpx;
  color: #ffffff;
}

.user-avatar-wrapper {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  overflow: hidden;
  border: 4rpx solid #E5E7EB;
  margin-bottom: 16rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.rank-1 .user-avatar-wrapper {
  width: 140rpx;
  height: 140rpx;
  border-width: 6rpx;
  border-color: #00C853;
}

.user-avatar {
  width: 100%;
  height: 100%;
}

.user-nickname {
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 8rpx;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rank-1 .user-nickname {
  font-size: 32rpx;
  color: #00C853;
}

.user-count {
  font-size: 24rpx;
  color: #6B7280;
  font-weight: 500;
}

.rank-1 .user-count {
  font-size: 28rpx;
  font-weight: 600;
  color: #00C853;
}

// ==========================================================================
// 我的排名
// ==========================================================================
.my-rank-section {
  margin-bottom: 32rpx; // --spacing-md
}

.my-rank-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  display: flex;
  justify-content: space-around;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
  border: 2rpx solid #E5E7EB; // --bg-gray
}

.rank-info,
.count-info {
  text-align: center;
}

.rank-label,
.count-label {
  display: block;
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  margin-bottom: 12rpx; // --spacing-sm
  font-weight: 400; // --font-weight-normal
}

.rank-value,
.count-value {
  display: block;
  font-size: 56rpx; // --font-size-h1
  font-weight: 600; // --font-weight-bold
  color: #4285F4; // --primary-color
  letter-spacing: -1rpx;
}

// ==========================================================================
// 排行榜列表
// ==========================================================================
.rank-list-section {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.section-title {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.rank-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.rank-item {
  display: flex;
  align-items: center;
  padding: 32rpx 24rpx; // --spacing-lg
  background: #F9FAFB;
  border-radius: 20rpx; // --radius
  transition: all 0.3s ease; // --transition-base
  border: 2rpx solid #E5E7EB; // --bg-gray
  
  &.is-me {
    background: linear-gradient(135deg, rgba(66, 133, 244, 0.05), rgba(90, 156, 255, 0.05));
    border-color: #4285F4; // --primary-color
    box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.15);
  }
  
  &.top-rank {
    background: linear-gradient(135deg, rgba(0, 200, 83, 0.05), rgba(232, 245, 233, 0.1));
    border-color: #00C853; // --success-color
  }
}

.rank-number {
  width: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}

.medal {
  font-size: 56rpx;
}

.number {
  font-size: 36rpx;
  font-weight: 600;
  color: #9CA3AF;
}

.user-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.user-avatar-small {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  border: 3rpx solid #E5E7EB;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.user-nickname-text {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.me-tag {
  color: #4285F4; // --primary-color
  font-size: 24rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
}

.user-update-time {
  font-size: 22rpx; // --font-size-mini
  color: #9CA3AF; // --text-tertiary
}

.count-info-right {
  text-align: right;
}

.count-number {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #4285F4; // --primary-color
  margin-right: 4rpx;
}

.count-unit {
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  font-weight: 500; // --font-weight-medium
}

.rank-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80rpx 0;
}

.empty-icon {
  font-size: 96rpx;
  margin-bottom: 16rpx; // --spacing-md
  opacity: 0.5;
}

.empty-text {
  font-size: 28rpx; // --font-size-small
  color: #9CA3AF; // --text-tertiary
}

.loading-more,
.load-more,
.no-more {
  text-align: center;
  padding: 32rpx 0; // --spacing-md
  font-size: 28rpx; // --font-size-small
  color: #9CA3AF; // --text-tertiary
}

.load-more {
  cursor: pointer;
  color: #4285F4; // --primary-color
  font-weight: 500; // --font-weight-medium
  transition: opacity 0.3s ease; // --transition-base
  
  &:active {
    opacity: 0.7;
  }
}

// ==========================================================================
// 排行榜说明
// ==========================================================================
.rank-tips {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
  border: 2rpx solid #E5E7EB; // --bg-gray
}

.tips-title {
  display: block;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 24rpx; // --spacing-lg
}

.tips-item {
  display: block;
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  line-height: 2; // --line-height-loose
  margin-bottom: 12rpx; // --spacing-sm
}
</style>

