<template>
  <s-layout
    title="奖品兑换"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="prize-page">
      <!-- 提示卡片 -->
      <view class="tips-card">
        <view class="tips-icon">🎁</view>
        <view class="tips-content">
          <text class="tips-title">兑换说明</text>
          <text class="tips-desc">集齐不同数量的五福卡可兑换不同奖品</text>
        </view>
      </view>

      <!-- 我的福卡套数 -->
      <view class="my-sets-card">
        <view class="sets-label">我的五福套数</view>
        <view class="sets-count">{{ mySetCount }}</view>
        <view class="sets-desc">已集齐{{ mySetCount }}套五福卡</view>
      </view>

      <!-- 奖品列表 -->
      <view class="prize-list">
        <view 
          v-for="prize in prizeList" 
          :key="prize.id"
          class="prize-item"
          :class="{ 
            'can-exchange': mySetCount >= prize.required_sets,
            'hot': prize.is_hot 
          }"
          @click="handleExchange(prize)"
        >
          <!-- 热门标签 -->
          <view v-if="prize.is_hot" class="hot-tag">🔥 热门</view>
          
          <!-- 不可兑换遮罩 -->
          <view v-if="mySetCount < prize.required_sets" class="disabled-mask">
            <text class="mask-text">福卡不足</text>
          </view>

          <view class="prize-image-wrapper">
            <image 
              v-if="prize.image" 
              :src="prize.image" 
              class="prize-image"
              mode="aspectFill"
            />
            <view v-else class="prize-placeholder">
              <text>{{ prize.prize_name }}</text>
            </view>
          </view>

          <view class="prize-info">
            <view class="prize-name">{{ prize.prize_name }}</view>
            <view class="prize-desc">{{ prize.description }}</view>
            
            <view class="prize-requirement">
              <view class="requirement-item">
                <text class="requirement-label">需要：</text>
                <text class="requirement-value">{{ prize.required_sets }}套五福卡</text>
              </view>
              
              <view v-if="prize.prize_type === 1" class="requirement-tips">
                <text class="tips-icon">📱</text>
                <text class="tips-text">需获取取件码</text>
              </view>
              
              <view v-if="prize.prize_type === 2" class="requirement-tips">
                <text class="tips-icon">🚗</text>
                <text class="tips-text">需获取车辆证书</text>
              </view>
            </view>

            <view class="prize-status">
              <text v-if="mySetCount >= prize.required_sets" class="status-can">
                可兑换
              </text>
              <text v-else class="status-cannot">
                还差{{ prize.required_sets - mySetCount }}套
              </text>
            </view>
          </view>
        </view>
      </view>

      <!-- 兑换记录入口 -->
      <view class="records-entry" @click="goToRecords">
        <text class="entry-icon">📋</text>
        <text class="entry-text">查看我的兑换记录</text>
        <text class="entry-arrow">→</text>
      </view>

      <!-- 兑换规则说明 -->
      <view class="rules-card">
        <view class="rules-title">兑换规则</view>
        <view class="rules-list">
          <text class="rule-item">• 1套五福卡可兑换手机1部</text>
          <text class="rule-item">• 2套五福卡可兑换现金奖励</text>
          <text class="rule-item">• 3套五福卡可兑换谢谢参与奖</text>
          <text class="rule-item">• 兑换后福卡将被消耗,不可恢复</text>
          <text class="rule-item">• 手机奖品需获取取件码</text>
          <text class="rule-item">• 汽车奖品需获取车辆证书</text>
        </view>
      </view>
    </view>

    <!-- 兑换确认弹窗 -->
    <view v-if="showConfirmModal" class="confirm-modal" @click="closeConfirmModal">
      <view class="confirm-content" @click.stop>
        <text class="confirm-title">确认兑换</text>
        
        <view class="confirm-prize">
          <image 
            v-if="selectedPrize.image" 
            :src="selectedPrize.image" 
            class="confirm-image"
            mode="aspectFill"
          />
          <text class="confirm-name">{{ selectedPrize.prize_name }}</text>
        </view>

        <view class="confirm-info">
          <view class="info-row">
            <text class="info-label">使用套数：</text>
            <text class="info-value">{{ selectedPrize.required_sets }}套</text>
          </view>
          <view class="info-row">
            <text class="info-label">剩余套数：</text>
            <text class="info-value">{{ mySetCount - selectedPrize.required_sets }}套</text>
          </view>
        </view>

        <view class="confirm-tips">
          <text class="tips-title">温馨提示</text>
          <text class="tips-item">• 兑换后福卡将被消耗,不可恢复</text>
          <text class="tips-item" v-if="selectedPrize.prize_type === 1">• 手机奖品需获取取件码</text>
          <text class="tips-item" v-if="selectedPrize.prize_type === 2">• 汽车奖品需获取车辆证书</text>
          <text class="tips-item">• 奖品将在审核通过后发货</text>
        </view>

        <view class="confirm-buttons">
          <button class="btn-cancel" @click="closeConfirmModal">取消</button>
          <button class="btn-confirm" @click="confirmExchange">确认兑换</button>
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
const prizeList = ref([])
const mySetCount = ref(0)
const selectedPrize = ref(null)
const showConfirmModal = ref(false)
const loading = ref(false)

// 页面加载
onLoad(() => {
  loadPageData()
})

// 加载页面数据
const loadPageData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadPrizeList(),
      loadMySetCount()
    ])
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败,请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 加载奖品列表
const loadPrizeList = async () => {
  try {
    const res = await xxep.$api.card.getPrizeList()
    if (res.code === 1) {
      prizeList.value = res.data || []
    }
  } catch (error) {
    console.error('加载奖品列表失败', error)
  }
}

// 加载我的套数
const loadMySetCount = async () => {
  try {
    const res = await xxep.$api.card.getCardStatistics()
    if (res.code === 1) {
      mySetCount.value = res.data.set_count || 0
    }
  } catch (error) {
    console.error('加载套数失败', error)
  }
}

// 处理兑换
const handleExchange = (prize) => {
  if (mySetCount.value < prize.required_sets) {
    xxep.$helper.toast(`还差${prize.required_sets - mySetCount.value}套五福卡`, 'info')
    return
  }
  
  selectedPrize.value = prize
  showConfirmModal.value = true
}

// 确认兑换
const confirmExchange = async () => {
  if (loading.value) return
  
  loading.value = true
  
  try {
    const res = await xxep.$api.card.exchangeCards({
      prize_id: selectedPrize.value.id,
      fuka_set_count: selectedPrize.value.required_sets
    })
    
    if (res.code === 1) {
      xxep.$helper.toast('兑换成功！', 'success')
      closeConfirmModal()
      
      // 跳转到兑换记录
      setTimeout(() => {
        goToRecords()
      }, 1500)
    } else {
      xxep.$helper.toast(res.msg || '兑换失败', 'error')
    }
  } catch (error) {
    console.error('兑换失败', error)
    xxep.$helper.toast('兑换失败,请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 关闭确认弹窗
const closeConfirmModal = () => {
  showConfirmModal.value = false
  selectedPrize.value = null
}

// 跳转到兑换记录
const goToRecords = () => {
  uni.navigateTo({
    url: '/pages/exchange/records'
  })
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 奖品列表页面样式 - 遵循UI设计规范
// ==========================================================================

.prize-page {
  padding: 32rpx; // --spacing-md
  padding-bottom: 32rpx;
}

// ==========================================================================
// 提示卡片
// ==========================================================================
.tips-card {
  background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
  border-radius: 32rpx; // --radius
  padding: 32rpx; // --spacing-md
  margin-bottom: 32rpx; // --spacing-md
  display: flex;
  align-items: center;
  gap: 24rpx; // --spacing-lg
  border: 2rpx solid #FFB74D;
}

.tips-icon {
  font-size: 64rpx;
}

.tips-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx; // --spacing-xs
}

.tips-title {
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #E65100;
}

.tips-desc {
  font-size: 24rpx; // --font-size-mini
  color: #F57C00;
  line-height: 1.6; // --line-height-base
}

// ==========================================================================
// 我的套数卡片
// ==========================================================================
.my-sets-card {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%); // --primary gradient
  border-radius: 32rpx; // --radius
  padding: 48rpx; // --spacing-xl
  margin-bottom: 32rpx; // --spacing-md
  text-align: center;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.3);
}

.sets-label {
  font-size: 28rpx; // --font-size-small
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 16rpx; // --spacing-md
}

.sets-count {
  font-size: 96rpx; // --font-size-large
  font-weight: 600; // --font-weight-bold
  color: #FFFFFF;
  line-height: 1; // --line-height-tight
  margin-bottom: 16rpx; // --spacing-md
  letter-spacing: -2rpx;
}

.sets-desc {
  font-size: 24rpx; // --font-size-mini
  color: rgba(255, 255, 255, 0.8);
}

// ==========================================================================
// 奖品列表
// ==========================================================================
.prize-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx; // --spacing-lg
  margin-bottom: 32rpx; // --spacing-md
}

.prize-item {
  position: relative;
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 24rpx; // --spacing-lg
  display: flex;
  gap: 24rpx; // --spacing-lg
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
  border: 3rpx solid #E5E7EB; // --bg-gray
  transition: all 0.3s ease; // --transition-base
  
  &.can-exchange {
    border-color: #4285F4; // --primary-color
    
    &:active {
      transform: scale(0.98); // 点击反馈
    }
  }
  
  &.hot {
    border-color: #FF9800; // --status-warning
  }
}

.hot-tag {
  position: absolute;
  top: 16rpx;
  right: 16rpx;
  background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
  color: #FFFFFF;
  padding: 8rpx 24rpx; // --spacing-xs
  border-radius: 24rpx; // --radius
  font-size: 22rpx; // --font-size-mini
  font-weight: 600; // --font-weight-bold
  box-shadow: 0 4rpx 12rpx rgba(255, 107, 107, 0.4);
  z-index: 2;
}

.disabled-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 32rpx; // --radius
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.mask-text {
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #FFFFFF;
}

.prize-image-wrapper {
  width: 200rpx;
  height: 200rpx;
  flex-shrink: 0;
  border-radius: 16rpx; // --radius
  overflow: hidden;
  background: #F9FAFB;
}

.prize-image {
  width: 100%;
  height: 100%;
}

.prize-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
  padding: 16rpx; // --spacing-md
  text-align: center;
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #1976D2;
}

.prize-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.prize-name {
  font-size: 36rpx; // --font-size-h4
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 8rpx; // --spacing-xs
}

.prize-desc {
  font-size: 24rpx; // --font-size-mini
  color: #6B7280; // --text-secondary
  line-height: 1.6; // --line-height-base
  margin-bottom: 16rpx; // --spacing-md
}

.prize-requirement {
  display: flex;
  flex-direction: column;
  gap: 8rpx; // --spacing-xs
  margin-bottom: 16rpx; // --spacing-md
}

.requirement-item {
  display: flex;
  align-items: center;
}

.requirement-label {
  font-size: 24rpx; // --font-size-mini
  color: #9CA3AF; // --text-tertiary
}

.requirement-value {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #4285F4; // --primary-color
}

.requirement-tips {
  display: flex;
  align-items: center;
  gap: 8rpx; // --spacing-xs
  padding: 8rpx 16rpx; // --spacing-xs
  background: rgba(255, 152, 0, 0.1);
  border-radius: 8rpx;
  border-left: 4rpx solid #FF9800; // --status-warning
}

.tips-icon {
  font-size: 20rpx;
}

.tips-text {
  font-size: 22rpx; // --font-size-mini
  color: #F57C00;
}

.prize-status {
  text-align: right;
}

.status-can {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #00C853; // --success-color
}

.status-cannot {
  font-size: 24rpx; // --font-size-mini
  color: #9CA3AF; // --text-tertiary
}

// ==========================================================================
// 兑换记录入口
// ==========================================================================
.records-entry {
  background: #FFFFFF; // --bg-primary
  border-radius: 24rpx; // --radius
  padding: 32rpx; // --spacing-md
  margin-bottom: 32rpx; // --spacing-md
  display: flex;
  align-items: center;
  gap: 16rpx; // --spacing-md
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
    background: #F9FAFB;
  }
}

.entry-icon {
  font-size: 48rpx;
}

.entry-text {
  flex: 1;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.entry-arrow {
  font-size: 32rpx; // --font-size-base
  color: #9CA3AF; // --text-tertiary
}

// ==========================================================================
// 兑换规则
// ==========================================================================
.rules-card {
  background: #FFFFFF; // --bg-primary
  border-radius: 32rpx; // --radius
  padding: 48rpx 32rpx; // --spacing-xl
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.rules-title {
  font-size: 36rpx; // --font-size-h3
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
  margin-bottom: 32rpx; // --spacing-md
}

.rules-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx; // --spacing-md
}

.rule-item {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
  line-height: 2; // --line-height-loose
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
  box-shadow: 0 16rpx 64rpx rgba(0, 0, 0, 0.2);
}

.confirm-title {
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
  margin-bottom: 32rpx; // --spacing-md
  padding: 24rpx; // --spacing-lg
  background: #F9FAFB;
  border-radius: 16rpx; // --radius
}

.confirm-image {
  width: 200rpx;
  height: 200rpx;
  border-radius: 16rpx; // --radius
  margin-bottom: 16rpx; // --spacing-md
}

.confirm-name {
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  color: #1F2937; // --text-primary
}

.confirm-info {
  margin-bottom: 32rpx; // --spacing-md
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 16rpx 0; // --spacing-md
  border-bottom: 2rpx solid #F0F0F0;
}

.info-label {
  font-size: 28rpx; // --font-size-small
  color: #6B7280; // --text-secondary
}

.info-value {
  font-size: 28rpx; // --font-size-small
  font-weight: 600; // --font-weight-bold
  color: #4285F4; // --primary-color
}

.confirm-tips {
  background: #F9FAFB;
  padding: 32rpx 24rpx; // --spacing-lg
  border-radius: 20rpx; // --radius
  margin-bottom: 32rpx; // --spacing-md
  border: 2rpx solid #E5E7EB; // --bg-gray
  
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
}

.confirm-buttons {
  display: flex;
  gap: 16rpx; // --spacing-md
}

.btn-cancel,
.btn-confirm {
  flex: 1;
  min-height: 88rpx; // --min-touch-size
  border-radius: 44rpx; // 圆形按钮
  border: none;
  font-size: 32rpx; // --font-size-base
  font-weight: 600; // --font-weight-bold
  transition: all 0.3s ease; // --transition-base
  
  &:active {
    transform: scale(0.98); // 点击反馈
  }
}

.btn-cancel {
  background: #F3F4F6; // --bg-secondary
  color: #6B7280; // --text-secondary
}

.btn-confirm {
  background: #4285F4; // --primary-color
  color: #FFFFFF;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
}
</style>

