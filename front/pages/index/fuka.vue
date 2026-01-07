<template>
  <s-layout
    title="我的福卡"
    navbar="custom"
    tabbar="/pages/index/fuka"
    :bgStyle="{ color: 'transparent' }"
    onShareAppMessage
  >
    <view class="fuka-page">
      <!-- 页面标题 -->
      <view class="page-title">集福卡</view>

      <!-- 福卡展示区域 -->
      <view class="cards-section">
        <view class="cards-grid">
          <view 
            v-for="card in sortedCards" 
            :key="card.type_code"
            class="card-item"
            :class="{ 'has-card': card.count > 0 }"
            @click="showCardDetail(card)"
          >
            <view class="card-wrapper">
              <!-- 福卡图片 -->
              <image 
                :src="card.image" 
                class="card-image"
                mode="aspectFit"
              />
              
              <!-- 未拥有的遮罩 -->
              <view v-if="card.count === 0" class="card-mask">
                <text class="mask-text">未获得</text>
              </view>
              
              <!-- 数量角标 -->
              <view v-if="card.count > 0" class="card-count">
                x{{ card.count }}
              </view>
              
              <!-- 万能福标签 -->
              <view v-if="card.is_universal" class="universal-badge">
                万能
              </view>
            </view>
            
            <view class="card-name">{{ card.type_name }}</view>
          </view>
        </view>
      </view>

      <!-- 进度显示区域 -->
      <view class="progress-section">
        <view class="progress-card">
          <!-- 进度标题和数字 -->
          <view class="progress-header">
            <text class="progress-title">集福进度</text>
            <text class="progress-number">{{ collectedTypes }}/5</text>
          </view>
          
          <!-- 进度条 -->
          <view class="progress-bar-container">
            <view class="progress-bar-track">
              <view 
                class="progress-bar-fill" 
                :style="{ width: (collectedTypes / 5 * 100) + '%' }"
              ></view>
            </view>
          </view>
          
          <!-- 福卡收集状态 -->
          <view class="cards-status">
            <view 
              v-for="card in normalCards" 
              :key="card.type_code"
              class="card-status-item"
              :class="{ 'collected': card.count > 0 }"
            >
              <view class="status-icon">
                <text v-if="card.count > 0" class="icon-check">✓</text>
              </view>
              <text class="card-status-name">{{ card.type_name }}</text>
            </view>
          </view>
          
          <!-- 合成五福卡按钮 -->
          <view 
            class="compose-button"
            :class="{ 'disabled': canMakeSets === 0 }"
            @click="goToExchange"
          >
            <text class="compose-btn-text">立即合成五福卡</text>
          </view>
          
          <!-- 进度提示 -->
          <view v-if="progressTip" class="progress-tip">
            <text class="tip-text">{{ progressTip }}</text>
          </view>
        </view>
      </view>

      <!-- 奖品兑换区域 -->
      <view class="prize-exchange-section">
        <view class="section-header">
          <view class="section-title-wrapper">
            <text class="section-title">奖品兑换</text>
            <text class="section-subtitle">PRIZE EXCHANGE</text>
          </view>
        </view>
        
        <view class="prize-list">
          <!-- 商品卡片1：手机 -->
          <view class="prize-card">
            <view class="prize-info">
              <text class="prize-name">iPhone 15 Pro</text>
              <view class="prize-condition-tag">
                <text class="tag-text">1套五福卡</text>
              </view>
            </view>
            <view class="prize-image">
              <image src="/static/fuka/prize-phone.png" mode="aspectFit" class="prize-img" />
            </view>
            <button class="prize-btn" @click="goToExchange">
              <text class="btn-text">兑换</text>
            </button>
          </view>
          
          <!-- 商品卡片2：现金红包 -->
          <view class="prize-card">
            <view class="prize-info">
              <text class="prize-name">现金红包</text>
              <view class="prize-condition-tag">
                <text class="tag-text">2套五福卡</text>
              </view>
            </view>
            <view class="prize-image">
              <image src="/static/fuka/prize-cash.png" mode="aspectFit" class="prize-img" />
            </view>
            <button class="prize-btn" @click="goToExchange">
              <text class="btn-text">兑换</text>
            </button>
          </view>
          
          <!-- 商品卡片3：惊喜礼包 -->
          <view class="prize-card">
            <view class="prize-info">
              <text class="prize-name">惊喜礼包</text>
              <view class="prize-condition-tag">
                <text class="tag-text">3套五福卡</text>
              </view>
            </view>
            <view class="prize-image">
              <image src="/static/fuka/prize-gift.png" mode="aspectFit" class="prize-img" />
            </view>
            <button class="prize-btn" @click="goToExchange">
              <text class="btn-text">兑换</text>
            </button>
          </view>
        </view>
      </view>

      <!-- 活动规则说明区域 -->
      <view class="activity-rules-section">
        <view class="rules-title">活动规则</view>
        
        <view class="rules-group">
          <view class="group-title">集福规则</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">每日签到获得1次集福机会</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">邀请好友完成实名获得1次机会</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">每邀请3位好友额外获得1次机会</text>
          </view>
        </view>

        <view class="rules-group">
          <view class="group-title">兑换说明</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">集齐5张不同福卡合成1套五福卡</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">万能福可替代任意缺少的福卡</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">使用五福卡套数兑换对应奖品</text>
          </view>
        </view>

        <view class="rules-group">
          <view class="group-title">团队奖励</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">团队达20人送手机1部</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">团队达50人每人5万，队长30万</text>
          </view>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 福卡固定顺序配置（2行3列）
const CARD_ORDER = [
  { type_code: 'aiguo', type_name: '爱国福', image: '/static/fuka/爱国.png', is_universal: false },
  { type_code: 'youshan', type_name: '友善福', image: '/static/fuka/友善.png', is_universal: false },
  { type_code: 'jingye', type_name: '敬业福', image: '/static/fuka/敬业.png', is_universal: false },
  { type_code: 'hexie', type_name: '和谐福', image: '/static/fuka/和谐.png', is_universal: false },
  { type_code: 'fuqiang', type_name: '富强福', image: '/static/fuka/富强.png', is_universal: false },
  { type_code: 'wanneng', type_name: '万能福', image: '/static/fuka/万能.png', is_universal: true }
]

// 响应式数据
const cardTypes = ref([])
const loading = ref(false)

// 按固定顺序排列的福卡列表
const sortedCards = computed(() => {
  return CARD_ORDER.map(config => {
    const userCard = cardTypes.value.find(c => c.type_code === config.type_code)
    return {
      ...config,
      count: userCard?.count || 0,
      id: userCard?.id || config.type_code
    }
  })
})

// 普通福卡列表（不含万能福）
const normalCards = computed(() => {
  return sortedCards.value.filter(card => !card.is_universal)
})

// 计算已收集的福卡种类（不含万能福）
const collectedTypes = computed(() => {
  return normalCards.value.filter(card => card.count > 0).length
})

// 计算可合成的五福卡套数
const canMakeSets = computed(() => {
  const normalCards = sortedCards.value.filter(c => !c.is_universal)
  const universalCard = sortedCards.value.find(c => c.is_universal)
  const universalCount = universalCard?.count || 0
  
  // 计算每种普通福卡的数量
  const cardCounts = normalCards.map(c => c.count)
  
  // 如果有任何一种福卡数量为0且没有万能福，则无法合成
  if (cardCounts.some(count => count === 0) && universalCount === 0) {
    return 0
  }
  
  // 计算可合成套数（考虑万能福）
  let sets = 0
  let tempCounts = [...cardCounts]
  let tempUniversal = universalCount
  
  while (true) {
    // 检查是否还能合成一套
    let needUniversal = 0
    for (let i = 0; i < tempCounts.length; i++) {
      if (tempCounts[i] > 0) {
        tempCounts[i]--
      } else {
        needUniversal++
      }
    }
    
    if (needUniversal <= tempUniversal) {
      tempUniversal -= needUniversal
      sets++
    } else {
      break
    }
  }
  
  return sets
})

// 进度提示文案
const progressTip = computed(() => {
  if (canMakeSets.value > 0) {
    return `已集齐福卡，可立即兑换${canMakeSets.value}次奖品！`
  }
  
  const missingTypes = 5 - collectedTypes.value
  if (missingTypes === 0) {
    // 集齐5种但数量不够合成
    const normalCards = sortedCards.value.filter(c => !c.is_universal && c.count === 0)
    if (normalCards.length > 0) {
      return `还差${normalCards.map(c => c.type_name).join('、')}各1张即可合成`
    }
  } else if (missingTypes <= 2) {
    const missing = sortedCards.value.filter(c => !c.is_universal && c.count === 0)
    return `还差${missing.map(c => c.type_name).join('、')}即可合成五福卡`
  }
  
  return '继续集福，集齐5种不同福卡可合成五福卡'
})

// 页面加载
onLoad(() => {
  console.log('福卡页面加载')
  loadPageData()
})

// 加载页面数据
const loadPageData = async () => {
  loading.value = true
  try {
    await loadCardTypes()
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 加载福卡类型和我的福卡
const loadCardTypes = async () => {
  try {
    const [typesRes, myCardsRes] = await Promise.all([
      xxep.$api.card.getCardTypes(),
      xxep.$api.card.getMyCards()
    ])
    
    if (typesRes.code === 1 && myCardsRes.code === 1) {
      const types = typesRes.data || []
      const myCards = myCardsRes.data || []
      
      // 合并福卡类型和数量
      cardTypes.value = types.map(type => {
        const userCards = myCards.filter(card => card.type_code === type.type_code && !card.is_used)
        return {
          ...type,
          count: userCards.length
        }
      })
    }
  } catch (error) {
    console.error('加载福卡类型失败', error)
  }
}

// 查看福卡详情
const showCardDetail = (card) => {
  if (card.count === 0) {
    xxep.$helper.toast('您还没有这张福卡哦', 'info')
    return
  }
  
  const cardInfo = card.is_universal 
    ? `您拥有 ${card.count} 张${card.type_name}\n\n万能福可以替代任意缺少的福卡，帮助您更快合成五福卡！`
    : `您拥有 ${card.count} 张${card.type_name}\n\n收集五福，兑换好礼！`
  
  uni.showModal({
    title: card.type_name,
    content: cardInfo,
    showCancel: false,
    confirmText: '知道了'
  })
}

// 跳转到集福页面
const goToDraw = () => {
  uni.navigateTo({
    url: '/pages/card/draw'
  })
}

// 跳转到兑换页面
const goToExchange = () => {
  if (canMakeSets.value === 0) {
    xxep.$helper.toast('集齐5张不同福卡才能兑换哦', 'info')
    return
  }
  
  uni.navigateTo({
    url: '/pages/card/exchange'
  })
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 福卡页面样式 - 红色渐变玻璃态风格
// ==========================================================================

.fuka-page {
  position: relative;
  min-height: 100vh;
  padding: 32rpx;
  padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
  
  // 背景图片
  background-image: url('/static/images/fuka_bg.jpeg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
}

// ==========================================================================
// 页面标题
// ==========================================================================
.page-title {
  font-size: 80rpx;
  font-weight: 700;
  text-align: center;
  margin-bottom: 48rpx;
  letter-spacing: 12rpx;
  color: #FFEB3B;
  
  // 金色浮雕文字效果
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 107, 74, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 255, 255, 0.9),
    0 0 20rpx rgba(255, 235, 59, 0.6);
}

// ==========================================================================
// 福卡展示区域
// ==========================================================================
.cards-section {
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 48rpx 32rpx;
  margin-bottom: 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24rpx;
}

.card-item {
  text-align: center;
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.95);
  }
}

.card-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 0.75;
  margin-bottom: 12rpx;
  border-radius: 16rpx;
  overflow: hidden;
  
  // 未获得
  background: rgba(255, 255, 255, 0.85);
  border: 2rpx solid rgba(255, 107, 74, 0.2);
}

.card-item.has-card .card-wrapper {
  // 已获得：金色渐变
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border: 2rpx solid rgba(255, 215, 0, 0.5);
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 107, 74, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.mask-text {
  font-size: 26rpx;
  color: #D32F2F;
  font-weight: 600;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.card-count {
  position: absolute;
  top: 12rpx;
  right: 12rpx;
  
  // 红色徽章
  background: #FF5252;
  color: #FFFFFF;
  
  padding: 8rpx 20rpx;
  border-radius: 32rpx;
  font-size: 24rpx;
  font-weight: 700;
  min-width: 60rpx;
  text-align: center;
}

.universal-badge {
  position: absolute;
  top: 12rpx;
  left: 12rpx;
  
  // 金色徽章
  background: #FFD700;
  color: #D32F2F;
  
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  font-weight: 700;
}

.card-name {
  font-size: 26rpx;
  font-weight: 600;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

// ==========================================================================
// 进度显示区域
// ==========================================================================
.progress-section {
  margin-bottom: 32rpx;
}

// 进度卡片
.progress-card {
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 40rpx 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

// 进度头部
.progress-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.progress-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.progress-number {
  font-size: 48rpx;
  font-weight: 700;
  color: #FF5722;
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 255, 255, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 107, 74, 0.5);
}

// 进度条
.progress-bar-container {
  margin-bottom: 32rpx;
}

.progress-bar-track {
  height: 16rpx;
  background: rgba(255, 255, 255, 0.85);
  border-radius: 8rpx;
  overflow: hidden;
  border: 1rpx solid rgba(255, 107, 74, 0.15);
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #FF5722 0%, #FFD700 100%);
  border-radius: 8rpx;
  transition: width 0.6s ease;
  position: relative;
}

// 福卡收集状态
.cards-status {
  display: flex;
  justify-content: space-between;
  gap: 16rpx;
}

.card-status-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
  transition: all 0.3s ease;
}

.status-icon {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  
  // 未收集状态
  background: rgba(255, 255, 255, 0.9);
  border: 2rpx solid rgba(255, 107, 74, 0.2);
}

.card-status-item.collected .status-icon {
  // 已收集状态：金色
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border: 2rpx solid rgba(255, 215, 0, 0.6);
}

.icon-check {
  font-size: 36rpx;
  color: #D32F2F;
  font-weight: 700;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.card-status-name {
  font-size: 22rpx;
  color: #D32F2F;
  text-align: center;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.6),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.3);
  white-space: nowrap;
}

.card-status-item.collected .card-status-name {
  color: #FF5722;
  font-weight: 600;
}

// 合成五福卡按钮
.compose-button {
  width: 100%;
  min-height: 96rpx;
  margin-top: 40rpx;
  border-radius: 24rpx;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  
  // 可用状态：喜庆红色渐变按钮
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  
  &:active:not(.disabled) {
    opacity: 0.9;
    transform: scale(0.98);
  }
  
  // 禁用状态
  &.disabled {
    background: rgba(255, 255, 255, 0.85);
    opacity: 0.8;
  }
}

.compose-btn-text {
  font-size: 32rpx;
  font-weight: 700;
  color: #FFFFFF;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(211, 47, 47, 0.6);
}

.compose-button.disabled .compose-btn-text {
  color: #D32F2F;
  opacity: 0.6;
}

.progress-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 20rpx 24rpx;
  margin-top: 24rpx;
  
  // background: rgba(255, 255, 255, 0.5);
  border-radius: 16rpx;
  // border: 1rpx solid rgba(255, 107, 74, 0.15);
}

.tip-text {
  font-size: 26rpx;
  font-weight: 500;
  color: #FF5722;
  text-align: center;
  opacity: 0.9;
}

// ==========================================================================
// 奖品兑换区域
// ==========================================================================
.prize-exchange-section {
  margin-bottom: 32rpx;
}

.section-header {
  margin-bottom: 24rpx;
}

.section-title-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}

.section-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #FFEB3B;
  
  // 金色浮雕文字效果
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 107, 74, 0.6),
    -2rpx -2rpx 4rpx rgba(255, 255, 255, 0.8),
    0 0 16rpx rgba(255, 235, 59, 0.5);
}

.section-subtitle {
  font-size: 20rpx;
  color: #FF8A65;
  letter-spacing: 4rpx;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.5);
}

.prize-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

.prize-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
  padding: 24rpx 28rpx;
  
  background: rgba(255, 232, 214, 0.95);
  border-radius: 24rpx;
  border: 1rpx solid rgba(255, 107, 74, 0.2);
  
  transition: all 0.3s ease;
  
  &:active {
    opacity: 0.9;
    transform: scale(0.98);
  }
}

// 左侧文字信息
.prize-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  min-width: 0;
}

.prize-name {
  font-size: 28rpx;
  font-weight: 700;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.prize-condition-tag {
  display: inline-flex;
  align-self: flex-start;
  padding: 6rpx 16rpx;
  border-radius: 20rpx;
  
  // 金色徽章
  background: #FFD700;
}

.tag-text {
  font-size: 22rpx;
  font-weight: 700;
  color: #D32F2F;
  white-space: nowrap;
}

// 中间图片
.prize-image {
  width: 100rpx;
  height: 100rpx;
  flex-shrink: 0;
  border-radius: 16rpx;
  overflow: hidden;
  
  background: rgba(255, 255, 255, 0.95);
  border: 1rpx solid rgba(255, 107, 74, 0.2);
  
  display: flex;
  align-items: center;
  justify-content: center;
}

.prize-img {
  width: 100%;
  height: 100%;
}

// 右侧按钮
.prize-btn {
  flex-shrink: 0;
  padding: 20rpx 32rpx;
  border-radius: 48rpx;
  border: none;
  
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  
  transition: all 0.3s ease;
  
  &:active {
    opacity: 0.9;
    transform: scale(0.95);
  }
}

.prize-btn .btn-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #FFFFFF;
  white-space: nowrap;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
}

// ==========================================================================
// 活动规则区域
// ==========================================================================
.activity-rules-section {
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 40rpx 32rpx;
  margin-bottom: 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

.rules-group {
  margin-bottom: 32rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.group-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #FF5722;
  margin-bottom: 20rpx;
  padding-bottom: 12rpx;
  
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  
  border-bottom: 2rpx solid rgba(255, 107, 74, 0.3);
}

.activity-rules-section .rule-item {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  padding: 12rpx 0;
}

.bullet {
  font-size: 28rpx;
  color: #FF8A65;
  line-height: 1.8;
  flex-shrink: 0;
}

.activity-rules-section .rule-text {
  flex: 1;
  font-size: 28rpx;
  color: #D32F2F;
  line-height: 1.8;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.5);
}

</style>

