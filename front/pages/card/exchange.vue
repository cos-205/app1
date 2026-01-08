<template>
  <s-layout
    title="确认兑换"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="exchange-page">
      <!-- 奖品信息 -->
      <view v-if="prizeInfo" class="prize-section">
        <view class="section-title">兑换奖品</view>
        
        <view class="prize-detail">
          <image 
            v-if="prizeInfo.image" 
            :src="prizeInfo.image" 
            class="prize-image"
            mode="aspectFit"
          />
          <view class="prize-info">
            <text class="prize-name">{{ prizeInfo.prize_name }}</text>
            <text class="prize-desc">{{ prizeInfo.description || '' }}</text>
            <view class="prize-requirement">
              <text class="requirement-text">需要：{{ prizeInfo.need_fuka_set }}套五福卡</text>
            </view>
          </view>
        </view>

        <!-- 费用提示 -->
        <view v-if="prizeInfo.need_pickup_code || prizeInfo.need_certificate" class="fee-tips">
          <text class="tips-icon">💡</text>
          <view class="tips-content">
            <text class="tips-title">温馨提示</text>
            <text v-if="prizeInfo.need_pickup_code" class="tips-text">
              此奖品兑换后需支付{{ prizeInfo.pickup_code_fee }}元获取取件码
            </text>
            <text v-if="prizeInfo.need_certificate" class="tips-text">
              此奖品兑换后需支付{{ prizeInfo.certificate_fee }}元获取{{ prizeInfo.certificate_type }}
            </text>
          </view>
        </view>
      </view>

      <!-- 收货地址 -->
      <view class="address-section">
        <view class="section-title">收货地址</view>
        
        <view v-if="selectedAddress" class="address-card" @click="selectAddress">
          <view class="address-info">
            <view class="address-header">
              <text class="consignee-name">{{ selectedAddress.consignee }}</text>
              <text class="consignee-mobile">{{ selectedAddress.mobile }}</text>
            </view>
            <text class="address-detail">
              {{ selectedAddress.province_name }}{{ selectedAddress.city_name }}{{ selectedAddress.district_name }}{{ selectedAddress.address }}
            </text>
          </view>
          <view class="address-action">
            <text class="action-text">修改</text>
            <text class="action-icon">›</text>
          </view>
        </view>
        
        <view v-else class="address-empty" @click="selectAddress">
          <text class="empty-icon">📍</text>
          <text class="empty-text">请选择收货地址</text>
          <text class="empty-action">点击选择 ›</text>
        </view>
      </view>

      <!-- 我的五福卡状态 -->
      <view v-if="prizeInfo" class="wufu-status-section">
        <view class="section-title">我的五福卡</view>
        
        <view class="wufu-status">
          <view class="status-item">
            <text class="status-label">当前拥有</text>
            <text class="status-value">{{ wufuCardCount }}</text>
            <text class="status-unit">套</text>
          </view>
          <text class="status-arrow">→</text>
          <view class="status-item">
            <text class="status-label">兑换后剩余</text>
            <text class="status-value remaining">{{ wufuCardCount - prizeInfo.need_fuka_set }}</text>
            <text class="status-unit">套</text>
          </view>
        </view>
      </view>

      <!-- 底部操作栏 -->
      <view class="bottom-bar">
        <button 
          class="exchange-btn"
          :class="{ 'disabled': !canExchange || isExchanging }"
          :disabled="!canExchange || isExchanging"
          @click="confirmExchange"
        >
          <text v-if="isExchanging">兑换中...</text>
          <text v-else>确认兑换</text>
        </button>
      </view>

      <!-- 兑换成功弹窗 -->
      <view v-if="showSuccessModal" class="success-modal" @click="closeSuccessModal">
        <view class="success-content" @click.stop>
          <text class="success-icon">🎉</text>
          <text class="success-title">兑换成功！</text>
          <text class="success-desc">您的兑换申请已提交，请等待审核</text>
          
          <button class="success-btn" @click="viewMyRecords">
            查看兑换记录
          </button>
          
          <button class="success-btn secondary" @click="goBack">
            返回
          </button>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 响应式数据
const prizeInfo = ref(null)
const prizeIdFromUrl = ref(0)
const selectedAddress = ref(null)
const wufuCardCount = ref(0)
const myWufuCards = ref([])
const isExchanging = ref(false)
const showSuccessModal = ref(false)

// 计算属性：是否可以兑换
const canExchange = computed(() => {
  return prizeInfo.value 
    && selectedAddress.value 
    && wufuCardCount.value >= prizeInfo.value.need_fuka_set
})

// 页面加载
onLoad((options) => {
  console.log('兑换确认页面加载', options)
  if (options && options.prize_id) {
    prizeIdFromUrl.value = parseInt(options.prize_id)
  } else {
    xxep.$helper.toast('缺少奖品信息', 'error')
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  }
})

// 页面显示（用于从地址选择页面返回时刷新）
onShow(() => {
  if (prizeIdFromUrl.value > 0) {
    loadPageData()
  }
})

// 监听地址选择事件
onMounted(() => {
  uni.$on('SELECT_ADDRESS', (data) => {
    if (data && data.addressInfo) {
      selectedAddress.value = data.addressInfo
    }
  })
})

// 加载页面数据
const loadPageData = async () => {
  try {
    await Promise.all([
      loadPrizeInfo(),
      loadDefaultAddress(),
      loadMyWufuCards()
    ])
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  }
}

// 加载奖品信息
const loadPrizeInfo = async () => {
  try {
    const res = await xxep.$api.card.getPrizeList()
    if (res.code === 1) {
      const prizeList = Array.isArray(res.data) ? res.data : []
      const prize = prizeList.find(p => p.id === prizeIdFromUrl.value)
      
      if (prize) {
        prizeInfo.value = {
          ...prize,
          image: prize.image || prize.prize_image || '/static/fuka/default-prize.png'
        }
      } else {
        xxep.$helper.toast('奖品不存在', 'error')
        setTimeout(() => {
          uni.navigateBack()
        }, 1500)
      }
    }
  } catch (error) {
    console.error('加载奖品信息失败', error)
  }
}

// 加载默认地址
const loadDefaultAddress = async () => {
  try {
    const res = await xxep.$api.user.address.list()
    if (res.code === 1 && res.data && res.data.length > 0) {
      // 查找默认地址，如果没有则使用第一个
      const defaultAddr = res.data.find(addr => addr.is_default)
      selectedAddress.value = defaultAddr || res.data[0]
    }
  } catch (error) {
    console.error('加载地址失败', error)
  }
}

// 加载我的五福卡
const loadMyWufuCards = async () => {
  try {
    const res = await xxep.$api.card.getMyWufuCards()
    if (res.code === 1) {
      myWufuCards.value = res.data?.list || []
      wufuCardCount.value = myWufuCards.value.length
    }
  } catch (error) {
    console.error('加载五福卡失败', error)
    myWufuCards.value = []
    wufuCardCount.value = 0
  }
}

// 选择地址
const selectAddress = () => {
  uni.navigateTo({
    url: '/pages/user/address/list'
  })
}

// 确认兑换
const confirmExchange = async () => {
  if (!canExchange.value || isExchanging.value) {
    return
  }
  
  if (!selectedAddress.value) {
    xxep.$helper.toast('请先选择收货地址', 'info')
    return
  }
  
  // 检查五福卡数量
  const needCount = prizeInfo.value.need_fuka_set
  if (myWufuCards.value.length < needCount) {
    xxep.$helper.toast('五福卡数量不足', 'error')
    return
  }
  
  isExchanging.value = true
  
  try {
    // 选择要使用的五福卡ID（按创建时间排序，使用最早的）
    const wufuCardIds = myWufuCards.value
      .slice(0, needCount)
      .map(card => card.id)
    
    // 调用兑换接口
    const res = await xxep.$api.card.exchangeCards({
      prize_id: prizeInfo.value.id,
      wufu_card_ids: wufuCardIds,
      address_id: selectedAddress.value.id,
      consignee: selectedAddress.value.consignee,
      mobile: selectedAddress.value.mobile,
      address: `${selectedAddress.value.province_name}${selectedAddress.value.city_name}${selectedAddress.value.district_name}${selectedAddress.value.address}`
    })
    
    if (res.code === 1) {
      showSuccessModal.value = true
    } else {
      xxep.$helper.toast(res.msg || '兑换失败', 'error')
    }
  } catch (error) {
    console.error('兑换失败', error)
    xxep.$helper.toast(error.msg || '兑换失败，请稍后重试', 'error')
  } finally {
    isExchanging.value = false
  }
}

// 关闭成功弹窗
const closeSuccessModal = () => {
  showSuccessModal.value = false
  // 返回上一页
  uni.navigateBack()
}

// 查看兑换记录
const viewMyRecords = () => {
  closeSuccessModal()
  uni.redirectTo({
    url: '/pages/exchange/records'
  })
}

// 返回
const goBack = () => {
  closeSuccessModal()
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 兑换确认页面样式
// ==========================================================================

.exchange-page {
  padding: 24rpx;
  padding-bottom: 200rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 24rpx;
}

// ==========================================================================
// 奖品信息
// ==========================================================================
.prize-section {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.prize-detail {
  display: flex;
  gap: 24rpx;
  padding: 24rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
}

.prize-image {
  width: 180rpx;
  height: 180rpx;
  flex-shrink: 0;
  border-radius: 12rpx;
}

.prize-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 12rpx;
}

.prize-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
}

.prize-desc {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.6;
}

.prize-requirement {
  display: inline-flex;
  align-self: flex-start;
  padding: 8rpx 16rpx;
  background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
  border-radius: 24rpx;
  border: 2rpx solid #00C853;
}

.requirement-text {
  font-size: 22rpx;
  font-weight: 600;
  color: #2E7D32;
}

.fee-tips {
  display: flex;
  gap: 16rpx;
  margin-top: 24rpx;
  padding: 24rpx;
  background: rgba(255, 165, 0, 0.08);
  border-radius: 16rpx;
  border-left: 6rpx solid #FFA500;
}

.tips-icon {
  font-size: 40rpx;
  flex-shrink: 0;
}

.tips-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.tips-title {
  font-size: 26rpx;
  font-weight: 600;
  color: #D97706;
}

.tips-text {
  font-size: 24rpx;
  color: #92400E;
  line-height: 1.6;
}

// ==========================================================================
// 收货地址
// ==========================================================================
.address-section {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.address-card {
  display: flex;
  align-items: center;
  gap: 24rpx;
  padding: 24rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
  border: 2rpx solid #E5E7EB;
  transition: all 0.3s ease;

  &:active {
    transform: scale(0.98);
    background: #F3F4F6;
  }
}

.address-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.address-header {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.consignee-name {
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
}

.consignee-mobile {
  font-size: 26rpx;
  color: #6B7280;
}

.address-detail {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.6;
}

.address-action {
  display: flex;
  align-items: center;
  gap: 8rpx;
  flex-shrink: 0;
  color: #4285F4;
}

.action-text {
  font-size: 26rpx;
}

.action-icon {
  font-size: 32rpx;
  font-weight: 300;
}

.address-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64rpx 24rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
  border: 2rpx dashed #D1D5DB;
  gap: 16rpx;
  transition: all 0.3s ease;

  &:active {
    transform: scale(0.98);
    background: #F3F4F6;
  }
}

.empty-icon {
  font-size: 72rpx;
}

.empty-text {
  font-size: 26rpx;
  color: #9CA3AF;
}

.empty-action {
  font-size: 24rpx;
  color: #4285F4;
}

// ==========================================================================
// 五福卡状态
// ==========================================================================
.wufu-status-section {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.wufu-status {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 32rpx 24rpx;
  background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
  border-radius: 16rpx;
}

.status-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}

.status-label {
  font-size: 22rpx;
  color: #2E7D32;
}

.status-value {
  font-size: 64rpx;
  font-weight: 600;
  color: #00C853;
  line-height: 1;

  &.remaining {
    color: #1565C0;
  }
}

.status-unit {
  font-size: 24rpx;
  color: #2E7D32;
}

.status-arrow {
  font-size: 48rpx;
  color: #4CAF50;
  font-weight: 300;
}

// ==========================================================================
// 底部操作栏
// ==========================================================================
.bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #FFFFFF;
  padding: 24rpx;
  box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.08);
  z-index: 100;
  border-top: 2rpx solid #E5E7EB;
}

.exchange-btn {
  width: 100%;
  min-height: 88rpx;
  background: linear-gradient(135deg, #4285F4, #5A9CFF);
  border-radius: 44rpx;
  border: none;
  font-size: 32rpx;
  font-weight: 600;
  color: #ffffff;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;

  &:active:not(.disabled) {
    transform: scale(0.98);
  }

  &.disabled {
    opacity: 0.5;
    background: #9CA3AF;
    box-shadow: none;
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
  padding: 32rpx;
}

.success-content {
  width: 100%;
  max-width: 600rpx;
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 64rpx 48rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.success-icon {
  font-size: 120rpx;
  margin-bottom: 24rpx;
}

.success-title {
  font-size: 48rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 16rpx;
}

.success-desc {
  font-size: 28rpx;
  color: #6B7280;
  text-align: center;
  margin-bottom: 48rpx;
  line-height: 1.6;
}

.success-btn {
  width: 100%;
  min-height: 88rpx;
  background: linear-gradient(135deg, #4285F4, #5A9CFF);
  border-radius: 44rpx;
  border: none;
  font-size: 32rpx;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 16rpx;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;

  &.secondary {
    background: #F3F4F6;
    color: #6B7280;
    margin-bottom: 0;
    box-shadow: none;
    border: 2rpx solid #E5E7EB;
  }

  &:active {
    transform: scale(0.98);
  }
}
</style>
