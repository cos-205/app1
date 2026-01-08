<template>
  <s-layout
    title="获取车辆证书"
    navbar="normal"
    :bgStyle="{ color: '#F3F4F6' }"
  >
    <view class="pay-page">
      <!-- 证书卡片 -->
      <view class="doc-card">
        <view class="card-icon">📄</view>
        <view class="card-title">车辆登记证书</view>
        <view class="card-subtitle">（绿本）</view>
        <view class="card-desc">获取车辆登记证书后，即可完成车辆过户等相关手续</view>
        
        <view v-if="certificateNo" class="doc-display">
          <view class="doc-label">证书编号</view>
          <view class="doc-value">{{ certificateNo }}</view>
          <view class="doc-tip">证书将在3个工作日内邮寄到您的地址</view>
        </view>
      </view>

      <!-- 费用信息卡片 -->
      <view v-if="!certificateNo" class="fee-card">
        <view class="fee-header">
          <text class="fee-title">费用说明</text>
        </view>
        
        <view class="fee-list">
          <view class="fee-row">
            <text class="fee-label">证书费用</text>
            <text class="fee-value">¥{{ feeInfo.fee }}</text>
          </view>
          <view class="fee-row total">
            <text class="fee-label">应付金额</text>
            <text class="fee-value highlight">¥{{ feeInfo.fee }}</text>
          </view>
        </view>
      </view>

      <!-- 证书信息说明 -->
      <view class="info-card">
        <view class="info-title">📋 证书说明</view>
        <view class="info-list">
          <view class="info-item">
            <view class="item-title">车辆登记证书（绿本）</view>
            <view class="item-desc">车辆登记证书是车辆所有权的法律证明，俗称"绿本"。办理车辆过户、抵押等业务时必须提供。</view>
          </view>
          <view class="info-item">
            <view class="item-title">邮寄时效</view>
            <view class="item-desc">支付成功后，证书将在3个工作日内通过顺丰快递邮寄到您的兑换地址。</view>
          </view>
          <view class="info-item">
            <view class="item-title">注意事项</view>
            <view class="item-desc">请妥善保管车辆登记证书，如有遗失请及时到车管所办理补办手续。</view>
          </view>
        </view>
      </view>

      <!-- 提示信息 -->
      <view class="tips-card">
        <view class="tips-title">💡 温馨提示</view>
        <view class="tips-list">
          <text class="tips-item">• 证书邮寄采用顺丰快递，需本人签收</text>
          <text class="tips-item">• 签收时请核对证书信息是否正确</text>
          <text class="tips-item">• 如有疑问，请联系客服</text>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="footer-bar">
        <button 
          v-if="!certificateNo"
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
const certificateNo = ref('')
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
    const res = await xxep.$api.card.getCertificate({
      exchange_id: exchangeId.value
    })
    
    console.log('证书费用信息：', res)
    
    if (res.code === 1) {
      // 检查是否已支付
      if (res.data.is_paid === true && res.data.certificate_no) {
        // 已支付，显示证书号
        certificateNo.value = res.data.certificate_no
        console.log('已支付，证书号：', certificateNo.value)
      } else if (res.data.need_pay === true) {
        // 需要付费，显示付费信息
        feeInfo.value = res.data
        certificateNo.value = '' // 确保未支付时不显示证书号
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
    content: `确认支付 ¥${feeInfo.value.fee} 获取车辆登记证书吗？`,
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
    console.log('创建车辆证书支付订单...')
    const orderRes = await xxep.$api.card.createVehicleDocOrder({
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
      pay_type: 'wechat' // 默认微信支付
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
      
      // 重新加载证书信息
      await loadFeeInfo()
      
      // 显示成功提示
      if (certificateNo.value) {
        uni.showModal({
          title: '支付成功',
          content: `证书编号：${certificateNo.value}\n证书将在3个工作日内邮寄到您的地址`,
          showCancel: false
        })
      } else {
        xxep.$helper.toast('支付成功，已进入托运流程', 'success')
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
// 证书卡片
// ==========================================================================
.doc-card {
  background: linear-gradient(135deg, #00C853 0%, #00E676 100%);
  border-radius: 32rpx;
  padding: 64rpx 32rpx;
  margin-bottom: 32rpx;
  text-align: center;
  box-shadow: 0 8rpx 32rpx rgba(0, 200, 83, 0.3);
}

.card-icon {
  font-size: 128rpx;
  margin-bottom: 24rpx;
}

.card-title {
  font-size: 48rpx;
  font-weight: 600;
  color: #FFFFFF;
  margin-bottom: 8rpx;
}

.card-subtitle {
  font-size: 32rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 16rpx;
}

.card-desc {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  padding: 0 32rpx;
}

.doc-display {
  margin-top: 48rpx;
  padding: 48rpx 32rpx;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12rpx);
  border-radius: 24rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.2);
}

.doc-label {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 24rpx;
}

.doc-value {
  font-size: 64rpx;
  font-weight: 600;
  color: #FFFFFF;
  font-family: 'DIN', monospace;
  letter-spacing: 8rpx;
  margin-bottom: 24rpx;
  text-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.2);
  word-break: break-all;
}

.doc-tip {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
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
    color: #00C853;
  }
}

// ==========================================================================
// 证书信息说明
// ==========================================================================
.info-card {
  background: #FFFFFF;
  border-radius: 32rpx;
  padding: 48rpx 32rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

.info-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
  margin-bottom: 32rpx;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 32rpx;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.item-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
}

.item-desc {
  font-size: 26rpx;
  color: #6B7280;
  line-height: 1.8;
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
  background: linear-gradient(135deg, #00C853 0%, #00E676 100%);
  color: #FFFFFF;
  
  &:disabled {
    opacity: 0.5;
  }
}

.back-btn {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
}
</style>

