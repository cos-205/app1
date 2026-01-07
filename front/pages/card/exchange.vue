<template>
  <s-layout
    title="福卡兑换"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="exchange-page">
      <!-- 我的福卡状态 -->
    <view class="my-cards-status">
      <view class="status-title">我的福卡</view>
      <view class="cards-row">
        <view 
          v-for="cardType in myCardTypes" 
          :key="cardType.id"
          class="mini-card"
          :class="{ 'has': cardType.count > 0 }"
        >
          <text class="mini-card-name">{{ cardType.type_name }}</text>
          <text class="mini-card-count">x{{ cardType.count }}</text>
        </view>
      </view>
      
      <view class="status-summary">
        <text class="summary-text">可组成</text>
        <text class="summary-count">{{ setCount }}</text>
        <text class="summary-text">套五福</text>
      </view>
    </view>

    <!-- 奖品列表 -->
    <view class="prizes-section">
      <view class="section-title">选择奖品</view>
      
      <view v-if="prizes.length > 0" class="prizes-list">
        <view 
          v-for="prize in prizes" 
          :key="prize.id"
          class="prize-item"
          :class="{ 
            'active': selectedPrize && selectedPrize.id === prize.id,
            'disabled': !canSelectPrize(prize)
          }"
          @click="selectPrize(prize)"
        >
          <view class="prize-image-wrapper">
            <image 
              v-if="prize.image" 
              :src="prize.image" 
              class="prize-image"
              mode="aspectFill"
            />
            <view v-else class="prize-image-placeholder">
              <text>{{ prize.prize_name }}</text>
            </view>
            
            <!-- 需要套数角标 -->
            <view class="set-count-badge">
              {{ prize.required_set_count }}套
            </view>
            
            <!-- 不可选择遮罩 -->
            <view v-if="!canSelectPrize(prize)" class="prize-mask">
              <text class="mask-text">福卡不足</text>
            </view>
          </view>
          
          <view class="prize-info">
            <text class="prize-name">{{ prize.prize_name }}</text>
            <text class="prize-desc">{{ prize.description || '' }}</text>
            
            <view v-if="prize.prize_type === 1 || prize.prize_type === 2" class="prize-tips">
              <text class="tips-text">
                {{ prize.prize_type === 1 ? '需付费获取取件码' : '需付费获取车辆证书' }}
              </text>
            </view>
          </view>
        </view>
      </view>
      
      <view v-else class="prizes-empty">
        <text class="empty-icon">🎁</text>
        <text class="empty-text">暂无可兑换奖品</text>
      </view>
    </view>

    <!-- 底部操作栏 -->
    <view class="bottom-bar">
      <view class="selected-info">
        <text v-if="selectedPrize" class="selected-text">
          已选择：{{ selectedPrize.prize_name }}
        </text>
        <text v-else class="hint-text">请选择要兑换的奖品</text>
      </view>
      
      <button 
        class="exchange-btn"
        :class="{ 'disabled': !selectedPrize || isExchanging }"
        :disabled="!selectedPrize || isExchanging"
        @click="handleExchange"
      >
        <text v-if="isExchanging">兑换中...</text>
        <text v-else>确认兑换</text>
      </button>
    </view>

    <!-- 兑换确认弹窗 -->
    <view v-if="showConfirmModal" class="confirm-modal" @click="closeConfirmModal">
      <view class="confirm-content" @click.stop>
        <text class="confirm-title">确认兑换</text>
        
        <view class="confirm-prize">
          <image 
            v-if="selectedPrize.image" 
            :src="selectedPrize.image" 
            class="confirm-prize-image"
            mode="aspectFill"
          />
          <text class="confirm-prize-name">{{ selectedPrize.prize_name }}</text>
        </view>
        
        <view class="confirm-info">
          <view class="info-item">
            <text class="info-label">使用福卡套数：</text>
            <text class="info-value">{{ selectedPrize.required_set_count }}套</text>
          </view>
          <view class="info-item">
            <text class="info-label">剩余福卡套数：</text>
            <text class="info-value">{{ setCount - selectedPrize.required_set_count }}套</text>
          </view>
        </view>
        
        <view class="confirm-tips">
          <text class="tips-title">温馨提示：</text>
          <text class="tips-item">• 兑换后福卡将被消耗，不可恢复</text>
          <text class="tips-item" v-if="selectedPrize.prize_type === 1">
            • 手机奖品需付费获取取件码
          </text>
          <text class="tips-item" v-if="selectedPrize.prize_type === 2">
            • 汽车奖品需付费获取车辆证书
          </text>
          <text class="tips-item">• 奖品将在审核通过后发货</text>
        </view>
        
        <view class="confirm-buttons">
          <button class="confirm-btn cancel" @click="closeConfirmModal">
            取消
          </button>
          <button class="confirm-btn primary" @click="confirmExchange">
            确认兑换
          </button>
        </view>
      </view>
    </view>

    <!-- 兑换成功弹窗 -->
    <view v-if="showSuccessModal" class="success-modal" @click="closeSuccessModal">
      <view class="success-content" @click.stop>
        <text class="success-icon">🎉</text>
        <text class="success-title">兑换成功！</text>
        <text class="success-desc">您的兑换申请已提交，请等待审核</text>
        
        <button class="success-btn" @click="viewMyRecords">
          查看我的兑换记录
        </button>
        
        <button class="success-btn secondary" @click="closeSuccessModal">
          继续集福
        </button>
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
const myCardTypes = ref([])
const prizes = ref([])
const selectedPrize = ref(null)
const isExchanging = ref(false)
const showConfirmModal = ref(false)
const showSuccessModal = ref(false)
const setCount = ref(0)

// 页面加载
onLoad(() => {
  console.log('兑换页面加载')
  loadPageData()
})

// 加载页面数据
const loadPageData = async () => {
  try {
    await Promise.all([
      loadMyCards(),
      loadPrizes()
    ])
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  }
}

// 加载我的福卡
const loadMyCards = async () => {
  try {
    const [typesRes, myCardsRes, statsRes] = await Promise.all([
      xxep.$api.card.getCardTypes(),
      xxep.$api.card.getMyCards(),
      xxep.$api.card.getCardStatistics()
    ])
    
    if (typesRes.code === 1 && myCardsRes.code === 1) {
      const types = typesRes.data || []
      const myCards = myCardsRes.data || []
      
      // 合并福卡类型和数量
      myCardTypes.value = types.map(type => {
        const userCards = myCards.filter(
          card => card.type_code === type.type_code && !card.is_used
        )
        return {
          ...type,
          count: userCards.length
        }
      })
      
      // 计算可组成的套数
      if (statsRes.code === 1) {
        setCount.value = statsRes.data.set_count || 0
      } else {
        // 如果没有统计接口，手动计算
        const normalCards = myCardTypes.value.filter(c => !c.is_universal)
        const minCount = Math.min(...normalCards.map(c => c.count))
        setCount.value = minCount >= 0 ? minCount : 0
      }
    }
  } catch (error) {
    console.error('加载福卡失败', error)
  }
}

// 加载奖品列表
const loadPrizes = async () => {
  try {
    const res = await xxep.$api.card.getPrizeList()
    if (res.code === 1) {
      prizes.value = res.data || []
    }
  } catch (error) {
    console.error('加载奖品列表失败', error)
  }
}

// 判断是否可以选择奖品
const canSelectPrize = (prize) => {
  return setCount.value >= prize.required_set_count
}

// 选择奖品
const selectPrize = (prize) => {
  if (!canSelectPrize(prize)) {
    xxep.$helper.toast('福卡套数不足', 'info')
    return
  }
  
  if (selectedPrize.value && selectedPrize.value.id === prize.id) {
    selectedPrize.value = null
  } else {
    selectedPrize.value = prize
  }
}

// 处理兑换
const handleExchange = () => {
  if (!selectedPrize.value || isExchanging.value) {
    return
  }
  
  showConfirmModal.value = true
}

// 确认兑换
const confirmExchange = async () => {
  if (isExchanging.value) return
  
  isExchanging.value = true
  
  try {
    const res = await xxep.$api.card.exchangeCards({
      prize_id: selectedPrize.value.id,
      fuka_set_count: selectedPrize.value.required_set_count
    })
    
    if (res.code === 1) {
      closeConfirmModal()
      showSuccessModal.value = true
      
      // 重新加载福卡数据
      await loadMyCards()
    } else {
      xxep.$helper.toast(res.msg || '兑换失败', 'error')
    }
  } catch (error) {
    console.error('兑换失败', error)
    xxep.$helper.toast('兑换失败，请稍后重试', 'error')
  } finally {
    isExchanging.value = false
  }
}

// 关闭确认弹窗
const closeConfirmModal = () => {
  showConfirmModal.value = false
}

// 关闭成功弹窗
const closeSuccessModal = () => {
  showSuccessModal.value = false
  selectedPrize.value = null
}

// 查看我的兑换记录
const viewMyRecords = () => {
  closeSuccessModal()
  uni.navigateTo({
    url: '/pages/exchange/records'
  })
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 兑换页面样式 - 遵循UI设计规范
// ==========================================================================

.exchange-page {
  padding: 32rpx; // --spacing-md
  padding-bottom: 200rpx; // 底部操作栏高度
}

// ==========================================================================
// 我的福卡状态
// ==========================================================================
.my-cards-status {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.status-title {
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.cards-row {
  display: flex;
  gap: 16rpx; // --spacing-md
  margin-bottom: 24rpx; // --spacing-lg
  flex-wrap: wrap;
}

.mini-card {
  flex: 1;
  min-width: 100rpx;
  padding: 20rpx;
  background: #F9FAFB;
  border-radius: 16rpx; // --radius
  text-align: center;
  opacity: 0.5;
  border: 2rpx solid #E5E7EB; // --bg-gray
  transition: all 0.3s ease; // --transition-base
  
  &.has {
    background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%); // --primary gradient
    opacity: 1;
    border-color: #4285F4; // --primary-color
  }
}

.mini-card-name {
  display: block;
  font-size: 24rpx;
  color: #1F2937;
  margin-bottom: 8rpx;
  font-weight: 500;
}

.mini-card.has .mini-card-name {
  color: #ffffff;
}

.mini-card-count {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: #4285F4;
}

.mini-card.has .mini-card-count {
  color: #ffffff;
}

.status-summary {
  text-align: center;
  padding: 32rpx; // --spacing-md
  background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
  border-radius: 20rpx; // --radius
  border: 2rpx solid #00C853; // --success-color
}

.summary-text {
  font-size: 28rpx; // --font-size-small
  color: #2E7D32;
  font-weight: 500; // --font-weight-medium
}

.summary-count {
  font-size: 64rpx; // --font-size-large
  font-weight: 600; // --font-weight-bold
  color: #00C853; // --success-color
  margin: 0 16rpx; // --spacing-md
  letter-spacing: -1rpx;
}

// ==========================================================================
// 奖品列表
// ==========================================================================
.prizes-section {
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

.prizes-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.prize-item {
  display: flex;
  gap: 24rpx; // --spacing-lg
  padding: 32rpx 24rpx; // --spacing-lg
  background: #F9FAFB;
  border-radius: 20rpx; // --radius
  border: 3rpx solid #E5E7EB; // --bg-gray
  transition: all 0.3s ease; // --transition-base
  cursor: pointer;
  
  &.active {
    border-color: #4285F4; // --primary-color
    background: linear-gradient(135deg, rgba(66, 133, 244, 0.05), rgba(90, 156, 255, 0.05));
    box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.15);
  }
  
  &.disabled {
    opacity: 0.5;
  }
  
  &:active:not(.disabled) {
    transform: scale(0.98); // 点击反馈
  }
}

.prize-image-wrapper {
  position: relative;
  width: 200rpx;
  height: 200rpx;
  flex-shrink: 0;
  border-radius: 16rpx;
  overflow: hidden;
}

.prize-image {
  width: 100%;
  height: 100%;
}

.prize-image-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 16rpx;
  font-size: 28rpx;
  font-weight: bold;
  color: #ffffff;
}

.set-count-badge {
  position: absolute;
  top: 8rpx;
  right: 8rpx;
  background: #00C853;
  color: #ffffff;
  padding: 6rpx 16rpx;
  border-radius: 24rpx;
  font-size: 24rpx;
  font-weight: 600;
  box-shadow: 0 2rpx 8rpx rgba(0, 200, 83, 0.3);
}

.prize-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}

.mask-text {
  font-size: 24rpx;
  color: #ffffff;
  font-weight: bold;
}

.prize-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.prize-name {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
  margin-bottom: 12rpx;
}

.prize-desc {
  font-size: 24rpx;
  color: #666666;
  line-height: 1.6;
  margin-bottom: 12rpx;
}

.prize-tips {
  padding: 8rpx 16rpx;
  background: rgba(255, 165, 0, 0.1);
  border-left: 4rpx solid #ffa500;
  border-radius: 4rpx;
}

.tips-text {
  font-size: 22rpx;
  color: #ffa500;
}

.prizes-empty {
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
  color: #999999;
}

// ==========================================================================
// 底部操作栏
// ==========================================================================
.bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #FFFFFF; // --bg-primary
  padding: 32rpx; // --spacing-md
  box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 24rpx; // --spacing-lg
  z-index: 100;
  border-top: 2rpx solid #E5E7EB; // --bg-gray
}

.selected-info {
  flex: 1;
}

.selected-text {
  font-size: 28rpx; // --font-size-small
  color: #1F2937; // --text-primary
  font-weight: 600; // --font-weight-bold
}

.hint-text {
  font-size: 28rpx; // --font-size-small
  color: #9CA3AF; // --text-tertiary
  font-weight: 400; // --font-weight-normal
}

.exchange-btn {
  width: 240rpx;
  min-height: 88rpx; // --min-touch-size
  background: #4285F4; // --primary-color
  border-radius: 44rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #ffffff;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &:active:not(.disabled) {
    transform: scale(0.98); // 点击反馈
  }
  
  &.disabled {
    opacity: 0.5;
    background: #9CA3AF; // --status-pending
  }
}

// ==========================================================================
// 确认弹窗
// ==========================================================================
.confirm-modal {
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
  padding: 32rpx; // --spacing-md
}

.confirm-content {
  width: 100%;
  max-width: 600rpx;
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx; // --spacing-xl
}

.confirm-title {
  display: block;
  font-size: 40rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  text-align: center;
  margin-bottom: 32rpx; // --spacing-md
}

.confirm-prize {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 32rpx;
  padding: 24rpx;
  background: #f8f8f8;
  border-radius: 16rpx;
}

.confirm-prize-image {
  width: 200rpx;
  height: 200rpx;
  border-radius: 16rpx;
  margin-bottom: 16rpx;
}

.confirm-prize-name {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
}

.confirm-info {
  margin-bottom: 32rpx;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 16rpx 0;
  border-bottom: 2rpx solid #f0f0f0;
}

.info-label {
  font-size: 28rpx;
  color: #666666;
}

.info-value {
  font-size: 28rpx;
  font-weight: 600;
  color: #4285F4;
}

.confirm-tips {
  background: #F9FAFB;
  padding: 32rpx 24rpx; // --spacing-lg
  border-radius: 20rpx; // --radius
  margin-bottom: 32rpx; // --spacing-md
  border: 2rpx solid #E5E7EB; // --bg-gray
}

.tips-title {
  display: block;
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 16rpx; // --spacing-md
}

.tips-item {
  display: block;
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  line-height: 2; // --line-height-loose
  margin-bottom: 8rpx; // --spacing-xs
}

.confirm-buttons {
  display: flex;
  gap: 16rpx; // --spacing-md
}

.confirm-btn {
  flex: 1;
  min-height: 88rpx; // --min-touch-size
  border-radius: 44rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  transition: all 0.3s ease; // --transition-base
  
  &.cancel {
    background: #F3F4F6; // --bg-secondary
    color: #6B7280; // --text-secondary
    border: 2rpx solid #E5E7EB; // --bg-gray
  }
  
  &.primary {
    background: #4285F4; // --primary-color
    color: #ffffff;
    box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  }
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}

// ==========================================================================
// 成功弹窗
// ==========================================================================
.success-modal {
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
  padding: 32rpx; // --spacing-md
}

.success-content {
  width: 100%;
  max-width: 600rpx;
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 64rpx 48rpx; // --spacing-xl
  display: flex;
  flex-direction: column;
  align-items: center;
}

.success-icon {
  font-size: 120rpx;
  margin-bottom: 24rpx; // --spacing-lg
}

.success-title {
  font-size: 48rpx; // --font-size-h2
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 16rpx; // --spacing-md
}

.success-desc {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  text-align: center;
  margin-bottom: 48rpx; // --spacing-xxl
  line-height: 1.6; // --line-height-base
}

.success-btn {
  width: 100%;
  min-height: 88rpx; // --min-touch-size
  background: #4285F4; // --primary-color
  border-radius: 44rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #ffffff;
  margin-bottom: 16rpx; // --spacing-md
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease; // --transition-base
  
  &.secondary {
    background: #F3F4F6; // --bg-secondary
    color: #6B7280; // --text-secondary
    margin-bottom: 0;
    box-shadow: none;
    border: 2rpx solid #E5E7EB; // --bg-gray
  }
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}
</style>

