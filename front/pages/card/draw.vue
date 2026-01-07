<template>
  <s-layout
    title="集福卡"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="draw-page">
      <!-- 集福机会卡片 -->
    <view class="chance-card">
      <view class="chance-info">
        <text class="chance-label">剩余集福机会</text>
        <text class="chance-count">{{ chanceCount }}</text>
        <text class="chance-unit">次</text>
      </view>
      
      <view class="get-chance-tips">
        <text class="tips-title">如何获得集福机会：</text>
        <text class="tips-item">• 每日签到获得1次</text>
        <text class="tips-item">• 邀请好友实名认证获得1次</text>
        <text class="tips-item">• 每邀请3位好友额外获得1次</text>
      </view>
    </view>

    <!-- 抽卡动画区域 -->
    <view class="draw-area">
      <view 
        class="draw-card-wrapper"
        :class="{ 'drawing': isDrawing, 'flip': shouldFlip }"
      >
        <!-- 卡片背面 -->
        <view class="card-back">
          <view class="card-back-pattern">
            <text class="pattern-text">福</text>
          </view>
          <text class="card-back-text">点击抽取</text>
        </view>
        
        <!-- 卡片正面（抽中的福卡） -->
        <view class="card-front" v-if="drawnCard">
          <view class="card-content">
            <image 
              v-if="drawnCard.icon" 
              :src="drawnCard.icon" 
              class="card-icon"
              mode="aspectFit"
            />
            <text v-else class="card-name-large">{{ drawnCard.type_name }}</text>
            
            <view v-if="drawnCard.is_universal" class="universal-badge">
              <text>万能福</text>
            </view>
          </view>
          <text class="card-type-name">{{ drawnCard.type_name }}</text>
        </view>
      </view>
      
      <!-- 抽卡按钮 -->
      <button 
        class="draw-btn"
        :class="{ 'disabled': isDrawing || chanceCount <= 0 }"
        :disabled="isDrawing || chanceCount <= 0"
        @click="handleDraw"
      >
        <text v-if="isDrawing">抽取中...</text>
        <text v-else-if="chanceCount <= 0">机会已用完</text>
        <text v-else>立即抽取</text>
      </button>
    </view>

    <!-- 福卡预览 -->
    <view class="cards-preview">
      <view class="preview-title">福卡图鉴</view>
      <view class="preview-grid">
        <view 
          v-for="cardType in cardTypes" 
          :key="cardType.id"
          class="preview-item"
        >
          <view class="preview-card">
            <image 
              v-if="cardType.icon" 
              :src="cardType.icon" 
              class="preview-icon"
              mode="aspectFit"
            />
            <text v-else class="preview-name">{{ cardType.type_name }}</text>
            
            <view v-if="cardType.is_universal" class="preview-universal">
              万能
            </view>
          </view>
          <text class="preview-label">{{ cardType.type_name }}</text>
          <text class="preview-rate">{{ cardType.drop_rate / 100 }}%</text>
        </view>
      </view>
    </view>

    <!-- 抽卡历史 -->
    <view class="draw-history">
      <view class="history-title">最近抽取</view>
      <view v-if="drawHistory.length > 0" class="history-list">
        <view 
          v-for="(item, index) in drawHistory" 
          :key="index"
          class="history-item"
        >
          <text class="history-card-name">{{ item.type_name }}</text>
          <text class="history-time">{{ formatTime(item.createtime) }}</text>
        </view>
      </view>
      <view v-else class="history-empty">
        <text>暂无抽卡记录</text>
      </view>
    </view>

    <!-- 抽中福卡弹窗 -->
    <view v-if="showResultModal" class="result-modal" @click="closeResultModal">
      <view class="result-content" @click.stop>
        <view class="result-card">
          <view class="result-icon-wrapper">
            <image 
              v-if="drawnCard.icon" 
              :src="drawnCard.icon" 
              class="result-icon"
              mode="aspectFit"
            />
            <text v-else class="result-name">{{ drawnCard.type_name }}</text>
          </view>
          
          <text class="result-title">恭喜获得</text>
          <text class="result-card-name">{{ drawnCard.type_name }}</text>
          
          <view v-if="drawnCard.is_universal" class="result-universal">
            <text>✨ 万能福卡 ✨</text>
            <text class="universal-desc">可替代任意福卡</text>
          </view>
          
          <button class="result-btn" @click="closeResultModal">
            知道了
          </button>
        </view>
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
const chanceCount = ref(0)
const cardTypes = ref([])
const isDrawing = ref(false)
const shouldFlip = ref(false)
const drawnCard = ref(null)
const showResultModal = ref(false)
const drawHistory = ref([])

// 页面加载
onLoad(() => {
  console.log('集福页面加载')
  loadPageData()
})

// 加载页面数据
const loadPageData = async () => {
  try {
    await Promise.all([
      loadChanceCount(),
      loadCardTypes(),
      loadDrawHistory()
    ])
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  }
}

// 加载集福机会数量
const loadChanceCount = async () => {
  try {
    const res = await xxep.$api.card.getChanceCount()
    if (res.code === 1) {
      chanceCount.value = res.data.count || 0
    }
  } catch (error) {
    console.error('加载集福机会失败', error)
  }
}

// 加载福卡类型
const loadCardTypes = async () => {
  try {
    const res = await xxep.$api.card.getCardTypes()
    if (res.code === 1) {
      cardTypes.value = res.data || []
    }
  } catch (error) {
    console.error('加载福卡类型失败', error)
  }
}

// 加载抽卡历史
const loadDrawHistory = async () => {
  try {
    const res = await xxep.$api.card.getMyCards({ limit: 5 })
    if (res.code === 1) {
      drawHistory.value = (res.data || []).slice(0, 5)
    }
  } catch (error) {
    console.error('加载抽卡历史失败', error)
  }
}

// 处理抽卡
const handleDraw = async () => {
  if (isDrawing.value || chanceCount.value <= 0) {
    return
  }
  
  isDrawing.value = true
  shouldFlip.value = false
  drawnCard.value = null
  
  try {
    // 播放抽卡动画
    setTimeout(() => {
      shouldFlip.value = true
    }, 100)
    
    // 调用抽卡接口
    const res = await xxep.$api.card.drawCard()
    
    if (res.code === 1) {
      drawnCard.value = res.data.card
      chanceCount.value = res.data.chance_count
      
      // 更新历史记录
      drawHistory.value.unshift(res.data.card)
      if (drawHistory.value.length > 5) {
        drawHistory.value.pop()
      }
      
      // 延迟显示结果弹窗
      setTimeout(() => {
        showResultModal.value = true
        isDrawing.value = false
      }, 1000)
    } else {
      xxep.$helper.toast(res.msg || '抽取失败', 'error')
      isDrawing.value = false
      shouldFlip.value = false
    }
  } catch (error) {
    console.error('抽卡失败', error)
    xxep.$helper.toast('抽取失败，请稍后重试', 'error')
    isDrawing.value = false
    shouldFlip.value = false
  }
}

// 关闭结果弹窗
const closeResultModal = () => {
  showResultModal.value = false
  shouldFlip.value = false
  
  // 重置抽卡状态
  setTimeout(() => {
    drawnCard.value = null
  }, 300)
}

// 格式化时间
const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const now = new Date()
  const diff = now - date
  
  if (diff < 60 * 1000) {
    return '刚刚'
  }
  if (diff < 60 * 60 * 1000) {
    return `${Math.floor(diff / (60 * 1000))}分钟前`
  }
  if (diff < 24 * 60 * 60 * 1000) {
    return `${Math.floor(diff / (60 * 60 * 1000))}小时前`
  }
  
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  return `${month}-${day} ${hours}:${minutes}`
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 集福页面样式 - 遵循UI设计规范
// ==========================================================================

.draw-page {
  padding: 32rpx; // --spacing-md
  padding-bottom: 32rpx;
}

// ==========================================================================
// 集福机会卡片
// ==========================================================================
.chance-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 48rpx; // --spacing-xxl
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.chance-info {
  text-align: center;
  margin-bottom: 32rpx; // --spacing-md
  padding-bottom: 32rpx; // --spacing-md
  border-bottom: 2rpx solid #E5E7EB; // --bg-gray
}

.chance-label {
  display: block;
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  margin-bottom: 16rpx; // --spacing-md
  font-weight: 400; // --font-weight-normal
}

.chance-count {
  display: inline-block;
  font-size: 96rpx; // --font-size-large (超大)
  font-weight: 600; // --font-weight-bold
  color: #4285F4; // --primary-color
  margin: 0 8rpx; // --spacing-xs
  letter-spacing: -2rpx;
}

.chance-unit {
  font-size: 32rpx; // --font-size-base
  color: #9CA3AF; // --text-tertiary
}

.get-chance-tips {
  display: flex;
  flex-direction: column;
  gap: 12rpx; // --spacing-sm
  background: #F9FAFB;
  padding: 24rpx; // --spacing-lg
  border-radius: 16rpx; // --radius
}

.tips-title {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 12rpx; // --spacing-sm
}

.tips-item {
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  line-height: 1.8; // --line-height-loose
}

// ==========================================================================
// 抽卡区域
// ==========================================================================
.draw-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 48rpx; // --spacing-xxl
}

.draw-card-wrapper {
  width: 500rpx;
  height: 700rpx;
  perspective: 1000rpx;
  margin-bottom: 48rpx; // --spacing-xxl
  
  &.drawing {
    animation: shake 0.5s ease-in-out;
  }
  
  &.flip {
    .card-back {
      transform: rotateY(180deg);
    }
    
    .card-front {
      transform: rotateY(0deg);
    }
  }
}

// 抽卡抖动动画
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10rpx) rotate(-2deg); }
  75% { transform: translateX(10rpx) rotate(2deg); }
}

.card-back,
.card-front {
  width: 100%;
  height: 100%;
  border-radius: 24rpx; // --radius
  position: absolute;
  backface-visibility: hidden;
  transition: transform 0.6s; // 翻转动画
  box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.3);
}

.card-back {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%); // --primary gradient
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transform: rotateY(0deg);
}

.card-back-pattern {
  width: 300rpx;
  height: 300rpx;
  border: 8rpx solid rgba(255, 255, 255, 0.4);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;
}

.pattern-text {
  font-size: 180rpx;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-shadow: 2rpx 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.card-back-text {
  font-size: 32rpx;
  color: #ffffff;
  font-weight: 600;
}

.card-front {
  background: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transform: rotateY(-180deg);
  border: 4rpx solid #E5E7EB;
}

.card-content {
  position: relative;
  width: 350rpx;
  height: 350rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;
}

.card-icon {
  width: 100%;
  height: 100%;
}

.card-name-large {
  font-size: 120rpx;
  font-weight: bold;
  color: #ffffff;
  text-shadow: 4rpx 4rpx 8rpx rgba(0, 0, 0, 0.2);
}

.universal-badge {
  position: absolute;
  top: 0;
  right: 0;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: #ffffff;
  padding: 8rpx 24rpx;
  border-radius: 24rpx;
  font-size: 24rpx;
  font-weight: bold;
  box-shadow: 0 4rpx 16rpx rgba(245, 87, 108, 0.5);
}

.card-type-name {
  font-size: 48rpx;
  font-weight: bold;
  color: #333333;
}

// ==========================================================================
// 抽卡按钮
// ==========================================================================
.draw-btn {
  width: 500rpx;
  min-height: 96rpx; // --min-touch-size (48px = 96rpx)
  background: #4285F4; // --primary-color
  border-radius: 48rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #ffffff;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active:not(.disabled) {
    transform: scale(0.98); // 点击反馈
    box-shadow: 0 2rpx 8rpx rgba(66, 133, 244, 0.3);
  }
  
  &.disabled {
    opacity: 0.5;
    background: #9CA3AF; // --status-pending
  }
}

// ==========================================================================
// 福卡预览
// ==========================================================================
.cards-preview {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.preview-title {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24rpx;
}

.preview-item {
  text-align: center;
}

.preview-card {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  border-radius: 20rpx;
  background: linear-gradient(135deg, #E5E7EB 0%, #F3F4F6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
  border: 2rpx solid #E5E7EB;
}

.preview-icon {
  width: 80%;
  height: 80%;
}

.preview-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #4285F4;
}

.preview-universal {
  position: absolute;
  top: 4rpx;
  right: 4rpx;
  background: #FF9800;
  color: #ffffff;
  padding: 4rpx 12rpx;
  border-radius: 12rpx;
  font-size: 18rpx;
  font-weight: 600;
}

.preview-label {
  display: block;
  font-size: 24rpx;
  color: #1F2937;
  margin-bottom: 8rpx;
  font-weight: 500;
}

.preview-rate {
  display: block;
  font-size: 20rpx;
  color: #4285F4;
  font-weight: 500;
}

// ==========================================================================
// 抽卡历史
// ==========================================================================
.draw-history {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.history-title {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.history-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 24rpx; // --spacing-lg
  background: #F9FAFB;
  border-radius: 16rpx; // --radius
  border: 2rpx solid #E5E7EB; // --bg-gray
  transition: all 0.3s ease; // --transition-base
}

.history-card-name {
  font-size: 28rpx; // --font-size-small
  color: #1F2937; // --text-primary
  font-weight: 600; // --font-weight-bold
}

.history-time {
  font-size: 24rpx; // --font-size-mini
  color: #9CA3AF; // --text-tertiary
}

.history-empty {
  text-align: center;
  padding: 80rpx 0;
  font-size: 28rpx; // --font-size-small
  color: #9CA3AF; // --text-tertiary
}

// ==========================================================================
// 结果弹窗
// ==========================================================================
.result-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s; // --transition-base
}

// 淡入动画
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.result-content {
  width: 600rpx;
  animation: slideUp 0.3s; // --transition-base
}

// 上滑动画
@keyframes slideUp {
  from {
    transform: translateY(100rpx);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.result-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 64rpx 48rpx; // --spacing-xl
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 16rpx 64rpx rgba(0, 0, 0, 0.2);
}

.result-icon-wrapper {
  width: 300rpx;
  height: 300rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 24rpx;
  padding: 32rpx;
}

.result-icon {
  width: 100%;
  height: 100%;
}

.result-name {
  font-size: 120rpx;
  font-weight: 600;
  color: #ffffff;
}

.result-title {
  font-size: 32rpx; // --font-size-base
  color: #6B7280; // --text-secondary
  margin-bottom: 16rpx; // --spacing-md
  font-weight: 400; // --font-weight-normal
}

.result-card-name {
  font-size: 64rpx; // --font-size-large
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.result-universal {
  background: #FF9800; // --status-warning
  padding: 24rpx 48rpx; // --spacing-lg
  border-radius: 24rpx; // --radius
  margin-bottom: 48rpx; // --spacing-xxl
  text-align: center;
  box-shadow: 0 4rpx 16rpx rgba(255, 152, 0, 0.3);
  
  text {
    display: block;
    color: #ffffff;
    
    &:first-child {
      font-size: 32rpx; // --font-size-base
      font-weight: 600; // --font-weight-bold
      margin-bottom: 8rpx; // --spacing-xs
    }
  }
}

.universal-desc {
  font-size: 24rpx !important; // --font-size-mini
  opacity: 0.95;
}

.result-btn {
  width: 100%;
  min-height: 88rpx; // --min-touch-size
  background: #4285F4; // --primary-color
  border-radius: 44rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #ffffff;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}
</style>

