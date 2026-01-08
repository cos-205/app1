<template>
  <s-layout
    title="获取取件码"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
  >
    <view class="pay-page">
      <!-- 取件码卡片 -->
      <view class="code-card">
        <view class="card-icon">📦</view>
        <view class="card-title">取件码</view>
        <view class="card-desc">获取取件码后，可凭此码到指定地点领取您的奖品</view>
        
        <view v-if="pickupCode" class="code-display">
          <view class="code-label">您的取件码</view>
          <view class="code-value">{{ pickupCode }}</view>
          <view class="code-tip">请妥善保管，到取件点出示此码即可</view>
        </view>
      </view>

      <!-- 费用信息卡片 -->
      <view v-if="!pickupCode" class="fee-card">
        <view class="fee-header">
          <text class="fee-title">费用说明</text>
        </view>
        
        <view class="fee-list">
          <view class="fee-row">
            <text class="fee-label">取件码费用</text>
            <text class="fee-value">¥{{ feeInfo.fee }}</text>
          </view>
          <view class="fee-row total">
            <text class="fee-label">应付金额</text>
            <text class="fee-value highlight">¥{{ feeInfo.fee }}</text>
          </view>
        </view>
      </view>

      <!-- 提示信息 -->
      <view class="tips-card">
        <view class="tips-title">💡 温馨提示</view>
        <view class="tips-list">
          <text class="tips-item">• 取件码有效期为7天，请及时领取</text>
          <text class="tips-item">• 取件时请携带本人身份证</text>
          <text class="tips-item">• 如有疑问，请联系客服</text>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="footer-bar">
        <button 
          v-if="!pickupCode"
          class="pay-btn" 
          :disabled="paying"
          @click="handlePay"
        >
          <text v-if="paying">支付中...</text>
          <text v-else>确认支付 ¥{{ feeInfo.fee }}</text>
        </button>
        <button 
          v-else
          class="back-btn" 
          @click="handleBack"
        >
          返回详情
        </button>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 响应式数据
const exchangeId = ref(0)
const feeInfo = ref({
  fee: 0,
  need_pay: false
})
const pickupCode = ref('')
const paying = ref(false)

// 页面加载
onLoad((options) => {
  if (options.id) {
    exchangeId.value = options.id
    loadFeeInfo()
  }
})

// 加载费用信息
const loadFeeInfo = async () => {
  try {
    const res = await xxep.$api.card.getPickupCode({
      exchange_id: exchangeId.value
    })
    
    console.log('取件码费用信息：', res)
    
    if (res.code === 1) {
      // 检查是否已支付
      if (res.data.is_paid === true && res.data.pickup_code) {
        // 已支付，显示取件码
        pickupCode.value = res.data.pickup_code
        console.log('已支付，取件码：', pickupCode.value)
      } else if (res.data.need_pay === true) {
        // 需要付费，显示付费信息
        feeInfo.value = res.data
        pickupCode.value = '' // 确保未支付时不显示取件码
        console.log('需要付费，费用：', feeInfo.value.fee)
      } else {
        // 其他情况（不应该出现）
        xxep.$helper.toast('数据异常', 'error')
        setTimeout(() => {
          uni.navigateBack()
        }, 1500)
      }
    } else {
      xxep.$helper.toast(res.msg || '加载失败', 'error')
      setTimeout(() => {
        uni.navigateBack()
      }, 1500)
    }
  } catch (error) {
    console.error('加载费用信息失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  }
}

// 处理支付
const handlePay = async () => {
  if (paying.value) return
  
  uni.showModal({
    title: '确认支付',
    content: `确认支付 ¥${feeInfo.value.fee} 获取取件码吗？`,
    success: async (res) => {
      if (res.confirm) {
        await processPay()
      }
    }
  })
}

// 处理支付流程
const processPay = async () => {
  paying.value = true
  
  try {
    // 步骤1: 创建支付订单
    console.log('创建取件码支付订单...')
    const orderRes = await xxep.$api.card.createPickupCodeOrder({
      exchange_id: exchangeId.value
    })
    
    console.log('订单创建结果：', orderRes)
    
    if (orderRes.code !== 1) {
      xxep.$helper.toast(orderRes.msg || '创建订单失败', 'error')
      paying.value = false
      return
    }
    
    const orderId = orderRes.data.order_id
    const orderNo = orderRes.data.order_no
    
    // 步骤2: 获取支付参数（默认使用微信支付）
    console.log('获取支付参数...', { orderId, orderNo })
    const payRes = await xxep.$api.card.getPaymentParams({
      order_id: orderId,
      pay_type: 'wechat' // 默认微信支付，可以根据用户选择修改
    })
    
    console.log('支付参数：', payRes)
    
    if (payRes.code !== 1) {
      xxep.$helper.toast(payRes.msg || '获取支付参数失败', 'error')
      paying.value = false
      return
    }
    
    const paymentUrl = payRes.data.payment_url || payRes.data.pay_url
    
    if (!paymentUrl) {
      xxep.$helper.toast('支付参数错误', 'error')
      paying.value = false
      return
    }
    
    // 步骤3: 跳转到支付页面（与金卡支付一致）
    console.log('跳转到支付页面：', paymentUrl)
    await callPay(paymentUrl, orderId)
    
  } catch (error) {
    console.error('支付失败', error)
    xxep.$helper.toast(error.msg || '支付失败，请稍后重试', 'error')
    paying.value = false
  }
}

// 调起支付（与金卡支付逻辑一致）
const callPay = async (paymentUrl, orderId) => {
  // #ifdef H5
  // H5环境：使用 window.open 在新窗口打开支付页面
  window.open(paymentUrl, '_blank')
  // 跳转后开始轮询支付结果
  checkPaymentResult(orderId)
  // #endif

  // #ifdef APP-PLUS
  // APP环境：使用系统默认浏览器打开支付页面
  plus.runtime.openURL(paymentUrl)
  // 跳转后开始轮询支付结果
  checkPaymentResult(orderId)
  // #endif

  // #ifdef MP-WEIXIN
  // 小程序环境：暂不支持，提示用户
  xxep.$helper.toast('请在H5或APP环境完成支付')
  paying.value = false
  // #endif
}

// 查询支付结果（轮询）
const checkPaymentResult = async (orderId, retryCount = 0) => {
  const maxRetries = 10 // 最多查询10次
  const retryInterval = 2000 // 每次间隔2秒
  
  if (retryCount === 0) {
    uni.showLoading({
      title: '等待支付结果...',
      mask: true
    })
  }
  
  try {
    const res = await xxep.$api.card.queryPaymentResult({
      order_id: orderId
    })
    
    console.log(`第${retryCount + 1}次查询支付结果：`, res)
    
    if (res.code === 1 && res.data.pay_status === 1) {
      // 支付成功
      uni.hideLoading()
      paying.value = false
      
      // 重新加载取件码信息
      await loadFeeInfo()
      
      // 显示成功提示
      if (pickupCode.value) {
        uni.showModal({
          title: '支付成功',
          content: `您的取件码是：${pickupCode.value}`,
          showCancel: false
        })
      } else {
        xxep.$helper.toast('支付成功', 'success')
      }
      
    } else if (retryCount < maxRetries) {
      // 继续查询
      setTimeout(() => {
        checkPaymentResult(orderId, retryCount + 1)
      }, retryInterval)
    } else {
      // 查询超时
      uni.hideLoading()
      paying.value = false
      xxep.$helper.toast('支付结果查询超时，请稍后在详情页查看', 'info')
      setTimeout(() => {
        uni.navigateBack()
      }, 2000)
    }
  } catch (error) {
    console.error('查询支付结果失败', error)
    if (retryCount < maxRetries) {
      setTimeout(() => {
        checkPaymentResult(orderId, retryCount + 1)
      }, retryInterval)
    } else {
      uni.hideLoading()
      paying.value = false
      xxep.$helper.toast('支付结果查询失败', 'error')
    }
  }
}

// 返回详情页
const handleBack = () => {
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.pay-page {
  min-height: 100vh;
  padding: 32rpx;
  padding-bottom: 200rpx;
}

// ==========================================================================
// 取件码卡片
// ==========================================================================
.code-card {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 32rpx;
  padding: 64rpx 32rpx;
  margin-bottom: 32rpx;
  text-align: center;
  box-shadow: 0 8rpx 32rpx rgba(66, 133, 244, 0.3);
}

.card-icon {
  font-size: 128rpx;
  margin-bottom: 24rpx;
}

.card-title {
  font-size: 48rpx;
  font-weight: 600;
  color: #FFFFFF;
  margin-bottom: 16rpx;
}

.card-desc {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  padding: 0 32rpx;
}

.code-display {
  margin-top: 48rpx;
  padding: 48rpx 32rpx;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12rpx);
  border-radius: 24rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.2);
}

.code-label {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 24rpx;
}

.code-value {
  font-size: 96rpx;
  font-weight: 600;
  color: #FFFFFF;
  font-family: 'DIN', monospace;
  letter-spacing: 16rpx;
  margin-bottom: 24rpx;
  text-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.2);
}

.code-tip {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
}

// ==========================================================================
// 费用信息卡片
// ==========================================================================
.fee-card {
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 48rpx 32rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.fee-header {
  margin-bottom: 32rpx;
}

.fee-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #1F2937;
}

.fee-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.fee-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 0;
  
  &:not(:last-child) {
    border-bottom: 1rpx solid #F3F4F6;
  }
  
  &.total {
    padding-top: 32rpx;
    border-top: 2rpx solid #E5E7EB;
  }
}

.fee-label {
  font-size: 28rpx;
  color: #6B7280;
}

.fee-value {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  
  &.highlight {
    font-size: 48rpx;
    color: #FF9800;
  }
}

// ==========================================================================
// 提示信息
// ==========================================================================
.tips-card {
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 48rpx 32rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.tips-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 24rpx;
}

.tips-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.tips-item {
  font-size: 28rpx;
  color: #6B7280;
  line-height: 2;
}

// ==========================================================================
// 底部按钮
// ==========================================================================
.footer-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 32rpx;
  background: #FFFFFF;
  box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.08);
  z-index: 100;
}

.pay-btn,
.back-btn {
  width: 100%;
  height: 96rpx;
  border: none;
  border-radius: 48rpx;
  font-size: 32rpx;
  font-weight: 600;
  box-shadow: 0 4rpx 16rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.98);
  }
}

.pay-btn {
  background: linear-gradient(135deg, #FF9800 0%, #FB8C00 100%);
  color: #FFFFFF;
  
  &:disabled {
    opacity: 0.5;
  }
}

.back-btn {
  background: linear-gradient(135deg, #00C853 0%, #00E676 100%);
  color: #FFFFFF;
}
</style>

