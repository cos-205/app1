<template>
  <s-layout
    title="集福排行榜"
    navbar="custom"
    :bgStyle="{ backgroundImage: 'url(/static/images/fuka_bg.jpeg)', backgroundSize: 'cover' }"
    onShareAppMessage
  >
    <view class="rank-page">
      <!-- 返回按钮 -->
      <view class="back-button" @click="goBack">
        <text class="back-icon">‹</text>
      </view>
      
      <!-- 页面标题 -->
      <view class="page-title">集福榜</view>

      <!-- 排行榜列表 -->
      <view class="rank-list-section">
        <view v-if="topTenList.length > 0" class="rank-list">
        <view 
          v-for="(item, index) in topTenList" 
          :key="item.id"
          class="rank-item"
          :class="{ 
            'rank-first': item.rank === 1,
            'rank-second': item.rank === 2,
            'rank-third': item.rank === 3
          }"
        >
          <view class="rank-number">
            <image 
              v-if="item.rank <= 3" 
              :src="`/static/rank/${item.rank}.png`" 
              class="rank-icon"
              mode="aspectFit"
            />
            <view v-else class="rank-normal">
              <image 
                src="/static/rank/normal.png" 
                class="rank-normal-bg"
                mode="aspectFit"
              />
              <text class="rank-normal-text">{{ item.rank }}</text>
            </view>
          </view>
          
          <view class="user-info">
            <text class="user-nickname-text">{{ item.nickname }}</text>
          </view>
          
          <view class="count-info-right">
            <text class="count-text">已获得福卡{{ item.fuka_count }}张</text>
          </view>
        </view>
      </view>
      
      <view v-else-if="!loading" class="rank-empty">
        <text class="empty-icon">📊</text>
        <text class="empty-text">暂无排行数据</text>
      </view>
      </view>

      <!-- 我的排名固定在底部 -->
      <view v-if="myRank" class="my-rank-fixed">
        <view class="my-rank-content">
          <view class="my-rank-left">
            <text class="my-rank-label">我的排名：</text>
            <text class="my-rank-value">{{ myRank.rank > 0 ? `第${myRank.rank}名` : '未上榜' }}</text>
          </view>
          <view class="my-rank-right">
            <text class="my-count-label">我的福卡：</text>
            <text class="my-count-value">{{ myRank.fuka_count }}张</text>
          </view>
        </view>
      </view>

      <!-- 底部操作按钮 -->
      <view class="action-buttons">
        <button class="action-btn draw-btn" @click="goToFuka">
          <text class="btn-text">抽福卡</text>
        </button>
        <button class="action-btn exchange-btn" @click="goToExchange">
          <text class="btn-text">兑换记录</text>
        </button>
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

// 前十名
const topTenList = computed(() => {
  return rankList.value.filter(item => item.rank <= 10).slice(0, 10)
})

// 页面加载
onLoad(() => {
  console.log('排行榜页面加载')
  initPage()
})

// 初始化页面
const initPage = async () => {
  try {
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
      page: 1,
      limit: 10
    })
    
    if (res.code === 1) {
      const data = res.data || {}
      const list = data.list || []
      
      rankList.value = list
      
      // 我的排名
      if (data.my_rank) {
        myRank.value = data.my_rank
      }
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

// 返回上一页
const goBack = () => {
  xxep.$router.back()
}

// 跳转到福卡页面
const goToFuka = () => {
  uni.navigateTo({
    url: '/pages/index/fuka'
  })
}

// 跳转到兑换页面
const goToExchange = () => {
  uni.navigateTo({
    url: '/pages/exchange/records'
  })
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 排行榜页面样式 - 参考福卡界面风格
// ==========================================================================

.rank-page {
  position: relative;
  min-height: 100vh;
  padding: 32rpx;
  padding-bottom: calc(200rpx + env(safe-area-inset-bottom));
  
  // 背景图片
  background-image: url('/static/images/fuka_bg.jpeg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
}

// ==========================================================================
// 返回按钮
// ==========================================================================
.back-button {
  position: fixed;
  top: calc(68rpx + env(safe-area-inset-top));
  left: 32rpx;
  z-index: 999;
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10rpx);
  display: flex;
  justify-content: center;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.92);
    opacity: 0.8;
  }
}

.back-icon {
  font-size: 60rpx;
  font-weight: 700;
  color: #FF6B4A;
  line-height: 1;
  margin-left: -4rpx;
}

// ==========================================================================
// 页面标题
// ==========================================================================
.page-title {
  font-size: 64rpx;
  font-weight: 700;
  text-align: center;
  margin-bottom: 40rpx;
  margin-top: 20rpx;
  letter-spacing: 10rpx;
  color: #FFEB3B;
  
  // 金色浮雕文字效果
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 107, 74, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 255, 255, 0.9),
    0 0 20rpx rgba(255, 235, 59, 0.6);
}

// ==========================================================================
// 排行榜列表
// ==========================================================================
.rank-list-section {
  background: rgba(255, 232, 214, 0.85);
  backdrop-filter: blur(12rpx);
  border-radius: 32rpx;
  padding: 40rpx 24rpx;
  margin-bottom: 24rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  box-shadow: 0 8rpx 24rpx rgba(255, 107, 74, 0.2);
}

.rank-list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.rank-item {
  display: flex;
  align-items: center;
  padding: 24rpx 20rpx;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8rpx);
  border-radius: 20rpx;
  transition: all 0.3s ease;
  border: 2rpx solid rgba(255, 107, 74, 0.2);
  
  // 第1名：金色
  &.rank-first {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-color: rgba(255, 215, 0, 0.8);
    box-shadow: 0 4rpx 16rpx rgba(255, 215, 0, 0.5);
  }
  
  // 第2名：银色
  &.rank-second {
    background: linear-gradient(135deg, #E8E8E8 0%, #B8B8B8 100%);
    border-color: rgba(192, 192, 192, 0.8);
    box-shadow: 0 4rpx 16rpx rgba(192, 192, 192, 0.5);
  }
  
  // 第3名：铜色
  &.rank-third {
    background: linear-gradient(135deg, #CD7F32 0%, #B87333 100%);
    border-color: rgba(205, 127, 50, 0.8);
    box-shadow: 0 4rpx 16rpx rgba(205, 127, 50, 0.5);
  }
}

.rank-number {
  width: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;
}

.rank-icon {
  width: 56rpx;
  height: 56rpx;
}

.rank-normal {
  position: relative;
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rank-normal-bg {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
}

.rank-normal-text {
  position: relative;
  z-index: 1;
  font-size: 32rpx;
  font-weight: 700;
  color: #FFFFFF;
  text-shadow: 1rpx 1rpx 2rpx rgba(0, 0, 0, 0.5);
}

.user-info {
  flex: 1;
  display: flex;
  align-items: center;
}

.user-nickname-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #1F2937;
}

// 前三名文字样式
.rank-first .user-nickname-text,
.rank-second .user-nickname-text,
.rank-third .user-nickname-text {
  color: #FFFFFF;
  font-weight: 700;
  text-shadow: 1rpx 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.count-info-right {
  text-align: right;
}

.count-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #4B5563;
}

// 前三名数量文字样式
.rank-first .count-text,
.rank-second .count-text,
.rank-third .count-text {
  color: #FFFFFF;
  font-weight: 700;
  text-shadow: 1rpx 1rpx 2rpx rgba(0, 0, 0, 0.3);
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
  margin-bottom: 16rpx;
  opacity: 0.5;
}

.empty-text {
  font-size: 28rpx;
  color: #6B7280;
}

// ==========================================================================
// 我的排名固定区域
// ==========================================================================
.my-rank-fixed {
  position: fixed;
  bottom: calc(140rpx + env(safe-area-inset-bottom));
  left: 32rpx;
  right: 32rpx;
  z-index: 100;
}

.my-rank-content {
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  backdrop-filter: blur(12rpx);
  border-radius: 24rpx;
  padding: 24rpx 32rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 3rpx solid rgba(255, 215, 0, 0.8);
  box-shadow: 0 8rpx 32rpx rgba(255, 165, 0, 0.4);
}

.my-rank-left,
.my-rank-right {
  display: flex;
  align-items: baseline;
  gap: 8rpx;
}

.my-rank-label,
.my-count-label {
  font-size: 26rpx;
  color: #FFFFFF;
  font-weight: 600;
}

.my-rank-value,
.my-count-value {
  font-size: 32rpx;
  color: #FFFFFF;
  font-weight: 700;
  text-shadow: 2rpx 2rpx 4rpx rgba(0, 0, 0, 0.3);
}

// ==========================================================================
// 底部操作按钮
// ==========================================================================
.action-buttons {
  position: fixed;
  bottom: env(safe-area-inset-bottom);
  left: 32rpx;
  right: 32rpx;
  display: flex;
  gap: 24rpx;
  padding: 20rpx 0;
  z-index: 100;
}

.action-btn {
  flex: 1;
  height: 96rpx;
  border-radius: 48rpx;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
  backdrop-filter: blur(10rpx);
  
  &:active {
    transform: scale(0.96);
    opacity: 0.9;
  }
}

.draw-btn {
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  border: 3rpx solid rgba(255, 87, 34, 0.6);
  
  .btn-text {
    font-size: 32rpx;
    font-weight: 700;
    color: #FFFFFF;
    text-shadow: 2rpx 2rpx 4rpx rgba(0, 0, 0, 0.3);
  }
}

.exchange-btn {
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border: 3rpx solid rgba(255, 215, 0, 0.8);
  
  .btn-text {
    font-size: 32rpx;
    font-weight: 700;
    color: #FFFFFF;
    text-shadow: 2rpx 2rpx 4rpx rgba(0, 0, 0, 0.3);
  }
}
</style>

