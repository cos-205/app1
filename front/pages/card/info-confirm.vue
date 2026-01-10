<template>
  <s-layout :bgStyle="{ color: '#f5f5f5' }">
    <view class="confirm-page">
      <!-- 已完成状态 -->
      <view v-if="state.stepInfo.flow_status === 3" class="completed-container">
        <!-- <view class="completed-icon">
          <uni-icons type="checkmark-circle-filled" size="80" color="#00C853" />
        </view> -->
        <view class="completed-title">{{ state.stepInfo.step_name }}已完成</view>
        <view class="completed-desc">您已成功完成本步骤并支付费用</view>
      </view>

      <!-- 未完成时显示确认信息 -->
      <view v-else>
        <!-- 步骤说明 -->
        <view class="step-description">
          <!-- <view class="desc-icon">
            <uni-icons :type="state.step === 5 ? 'wallet' : 'shop'" size="60" color="#4285F4" />
          </view> -->
          <view class="desc-content">
            <view class="desc-title">{{ state.stepInfo.step_name }}</view>
            <view class="desc-text">{{ state.stepInfo.description }}</view>
          </view>
        </view>

        <!-- 步骤5：金卡绑定信息 -->
        <view v-if="state.step === 5" class="info-card">
          <view class="card-title">
            <uni-icons type="wallet" size="18" color="#FF9800" />
            <text>金卡信息</text>
          </view>
          <view class="info-list">
            <view class="info-row">
              <text class="info-label">卡号</text>
              <text class="info-value">{{ state.cardInfo.card_no || '-' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">持卡人</text>
              <text class="info-value">{{ state.cardInfo.holder_name || '-' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">身份证号</text>
              <text class="info-value">{{ state.cardInfo.holder_idcard || '-' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">卡内余额</text>
              <text class="info-value amount">¥{{ state.cardInfo.balance || '0.00' }}</text>
            </view>
          </view>
          
          <view class="function-desc">
            <view class="desc-title">提现功能说明</view>
            <view class="desc-list">
              <text>• 绑定后可在APP中直接提现至金卡</text>
              <text>• 提现实时到账，无需手续费</text>
              <text>• 支持随时查询余额和交易记录</text>
            </view>
          </view>
        </view>

        <!-- 步骤6：邮寄信息确认 -->
        <view v-if="state.step === 6" class="info-card">
          <view class="card-title">
            <uni-icons type="shop" size="18" color="#FF9800" />
            <text>收货地址</text>
            <text class="edit-btn" @tap="editAddress">编辑</text>
          </view>
          <view class="address-info" v-if="state.addressInfo">
            <view class="address-row">
              <text class="address-label">收货人</text>
              <text class="address-value">{{ state.addressInfo.consignee || '-' }}</text>
            </view>
            <view class="address-row">
              <text class="address-label">联系电话</text>
              <text class="address-value">{{ state.addressInfo.mobile || '-' }}</text>
            </view>
            <view class="address-row">
              <text class="address-label">收货地址</text>
              <text class="address-value">{{ state.addressInfo.fullAddress || '-' }}</text>
            </view>
          </view>
          <view class="no-address" v-else>
            <uni-icons type="info" size="40" color="#CCCCCC" />
            <text>请先添加收货地址</text>
            <button class="add-address-btn" @tap="editAddress">添加地址</button>
          </view>
          
          <view class="shipping-desc">
            <view class="desc-title">邮寄说明</view>
            <view class="desc-list">
              <text>• 我们将为您邮寄支付宝会员入场证</text>
              <text>• 预计3-7个工作日送达</text>
              <text>• 请确保收货地址准确无误</text>
              <text>• 签收后请及时激活使用</text>
            </view>
          </view>
        </view>

        <!-- 费用说明 -->
        <view class="fee-card" v-if="state.stepInfo.fee_amount > 0">
          <view class="fee-header">
            <text class="fee-label">本步骤费用</text>
            <text class="fee-amount">¥{{ state.stepInfo.fee_amount }}</text>
          </view>
          <view class="fee-note">
            <uni-icons type="star" size="14" color="#FF9800" />
            <text>完成全部流程后，所有费用将全额退还</text>
          </view>
        </view>

        <!-- 温馨提示 -->
        <view class="tips-box">
          <view class="tips-title">
            <uni-icons type="notification" size="16" color="#1890FF" />
            <text>温馨提示</text>
          </view>
          <view class="tips-content">
            <text v-if="state.step === 5">• 绑定后即可在APP中使用提现功能</text>
            <text v-if="state.step === 6">• 请确保收货地址准确，避免邮寄错误</text>
            <text>• 支付成功后请等待审核（1-3个工作日）</text>
            <text>• 如审核未通过，费用将原路退回</text>
          </view>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="footer-buttons">
        <!-- 已完成状态 -->
        <view v-if="state.stepInfo.flow_status === 3" class="completed-status-btn">
          <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
          <text class="completed-text">已完成</text>
        </view>
        <!-- 未完成时显示提交按钮 -->
        <button 
          v-else
          class="submit-button" 
          :disabled="state.submitting || (state.step === 6 && !state.addressInfo)"
          :loading="state.submitting"
          @tap="handleConfirm"
        >
          {{ state.submitting ? '处理中...' : (state.stepInfo.fee_amount > 0 ? `确认并支付 ¥${state.stepInfo.fee_amount}` : '确认') }}
        </button>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive } from 'vue';
import { onLoad, onShow } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  step: 0,
  stepInfo: {
    step: 0,
    step_name: '',
    description: '',
    fee_amount: 0,
    flow_status: 1,
  },
  cardInfo: {},
  addressInfo: null,
  submitting: false,
});

onLoad(async (options) => {
  if (options.step) {
    state.step = parseInt(options.step);
    await loadStepInfo();
    if (state.step === 5) {
      await loadCardInfo();
    } else if (state.step === 6) {
      await loadAddressInfo();
    }
  }
});

onShow(() => {
  // 从地址编辑页面返回时，重新加载地址信息
  if (state.step === 6) {
    loadAddressInfo();
  }
});

// 加载步骤信息
async function loadStepInfo() {
  try {
    const { code, data, msg } = await xxep.$api.card.flowConfig();
    
    if (code === 1) {
      const stepConfig = data.steps.find(s => s.step === state.step);
      if (stepConfig) {
        state.stepInfo = {
          step: stepConfig.step,
          step_name: stepConfig.step_name,
          description: stepConfig.step_desc || getStepDescription(stepConfig.step),
          fee_amount: stepConfig.fee_amount,
          flow_status: stepConfig.flow_status || 1,
        };
      }
    } else {
      xxep.$helper.toast(msg || '加载步骤信息失败');
    }
  } catch (error) {
    console.error('加载步骤信息失败:', error);
    xxep.$helper.toast('加载失败，请重试');
  }
}

// 加载金卡信息（步骤5）
async function loadCardInfo() {
  try {
    const { code, data } = await xxep.$api.card.flowConfig();
    if (code === 1 && data.card_status) {
      state.cardInfo = {
        card_no: data.card_status.card_no || '',
        holder_name: data.card_status.holder_name || '',
        holder_idcard: data.card_status.holder_idcard || '',
        balance: data.card_status.balance || '0.00',
      };
    }
  } catch (error) {
    console.error('加载金卡信息失败:', error);
  }
}

// 加载地址信息（步骤6）
async function loadAddressInfo() {
  try {
    // 调用地址列表API，获取默认地址或第一个地址
    const res = await xxep.$api.user.address.list();
    if (res.code === 1 && res.data && res.data.length > 0) {
      // 优先使用默认地址
      let address = res.data.find(addr => addr.is_default === 1);
      if (!address) {
        // 如果没有默认地址，使用第一个地址
        address = res.data[0];
      }
      
      state.addressInfo = {
        id: address.id,
        consignee: address.consignee || '',
        mobile: address.mobile || '',
        fullAddress: `${address.province || ''}${address.city || ''}${address.district || ''}${address.address || ''}`,
      };
    }
  } catch (error) {
    console.error('加载地址信息失败:', error);
  }
}

// 编辑地址
function editAddress() {
  uni.navigateTo({
    url: '/pages/user/address/list?select=true',
  });
}

// 获取步骤说明
function getStepDescription(step) {
  const descriptions = {
    5: '在APP中绑定金卡，开通提现功能',
    6: '确认收货地址，系统将为您邮寄支付宝会员入场证',
  };
  return descriptions[step] || '';
}

// 确认处理
async function handleConfirm() {
  // 步骤6需要检查地址
  if (state.step === 6 && !state.addressInfo) {
    xxep.$helper.toast('请先添加收货地址');
    return;
  }

  state.submitting = true;

  try {
    // 准备提交的数据
    const stepData = {};
    if (state.step === 5) {
      // 步骤5：绑定金卡，提交金卡信息
      stepData.card_no = state.cardInfo.card_no;
      stepData.holder_name = state.cardInfo.holder_name;
    } else if (state.step === 6) {
      // 步骤6：邮寄确认，提交地址信息
      stepData.address_id = state.addressInfo.id;
      stepData.consignee = state.addressInfo.consignee;
      stepData.mobile = state.addressInfo.mobile;
      stepData.address = state.addressInfo.fullAddress;
    }

    // 如果有数据需要提交，先提交数据
    if (Object.keys(stepData).length > 0) {
      const submitRes = await xxep.$api.card.submitStepData({
        step: state.step,
        data: stepData,
      });

      if (submitRes.code !== 1) {
        xxep.$helper.toast(submitRes.msg || '提交数据失败');
        state.submitting = false;
        return;
      }
    }

    // 创建支付订单
    const { code, data, msg } = await xxep.$api.card.createOrder({
      step: state.step,
    });

    if (code === 1) {
      // 跳转到支付页面
      uni.redirectTo({
        url: `/pages/card/payment?order_id=${data.order.id}&step=${state.step}`,
      });
    } else {
      xxep.$helper.toast(msg || '创建订单失败');
      state.submitting = false;
    }
  } catch (error) {
    console.error('操作失败:', error);
    xxep.$helper.toast('操作失败，请重试');
    state.submitting = false;
  }
}
</script>

<style lang="scss" scoped>
.confirm-page {
  padding: 20rpx;
  padding-bottom: 200rpx;
}

.completed-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 30rpx;
  background: #FFFFFF;
  border-radius: 16rpx;
  margin-top: 40rpx;
}

.completed-icon {
  margin-bottom: 30rpx;
}

.completed-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #00C853;
  margin-bottom: 20rpx;
}

.completed-desc {
  font-size: 28rpx;
  color: #666666;
  text-align: center;
}

.step-description {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 40rpx;
  margin-bottom: 20rpx;
  display: flex;
  gap: 24rpx;
}

.desc-icon {
  flex-shrink: 0;
}

.desc-content {
  flex: 1;
}

.desc-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.desc-text {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.8;
}

.info-card {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 24rpx;
  
  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
  }
  
  .edit-btn {
    margin-left: auto;
    font-size: 26rpx;
    color: #4285F4;
  }
}

.info-list {
  margin-bottom: 30rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1px solid #F0F0F0;

  &:last-child {
    border-bottom: none;
  }
}

.info-label {
  font-size: 28rpx;
  color: #666666;
}

.info-value {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
  
  &.amount {
    color: #FF3B30;
    font-weight: 600;
  }
}

.function-desc,
.shipping-desc {
  margin-top: 30rpx;
  padding-top: 30rpx;
  border-top: 1px solid #F0F0F0;
}

.desc-title {
  font-size: 26rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 16rpx;
}

.desc-list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  text {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.6;
  }
}

.address-info {
  margin-bottom: 30rpx;
}

.address-row {
  display: flex;
  margin-bottom: 16rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.address-label {
  font-size: 26rpx;
  color: #666666;
  width: 140rpx;
  flex-shrink: 0;
}

.address-value {
  font-size: 26rpx;
  color: #333333;
  flex: 1;
}

.no-address {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60rpx 0;
  gap: 20rpx;
  
  text {
    font-size: 26rpx;
    color: #999999;
  }
}

.add-address-btn {
  margin-top: 20rpx;
  width: 200rpx;
  height: 60rpx;
  line-height: 60rpx;
  background: #4285F4;
  color: #FFFFFF;
  border-radius: 30rpx;
  font-size: 26rpx;
  border: none;
}

.fee-card {
  background: linear-gradient(135deg, #FFE5E5 0%, #FFF4E5 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.fee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16rpx;
}

.fee-label {
  font-size: 28rpx;
  color: #666666;
}

.fee-amount {
  font-size: 40rpx;
  font-weight: 700;
  color: #FF3B30;
}

.fee-note {
  display: flex;
  align-items: center;
  gap: 8rpx;

  text {
    font-size: 24rpx;
    color: #FF9800;
  }
}

.tips-box {
  background: #E6F7FF;
  border-radius: 16rpx;
  padding: 30rpx;
}

.tips-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 16rpx;

  text {
    font-size: 28rpx;
    font-weight: 500;
    color: #1890FF;
  }
}

.tips-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  text {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.6;
  }
}

.footer-buttons {
  margin: 40rpx 0;
}

.completed-status-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  width: 100%;
  height: 100rpx;
  background: #F0F0F0;
  border-radius: 50rpx;
  
  .completed-text {
    font-size: 32rpx;
    font-weight: 600;
    color: #00C853;
  }
}

.submit-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;

  &[disabled] {
    opacity: 0.6;
  }
}
</style>
