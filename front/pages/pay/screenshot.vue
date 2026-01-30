<!-- 支付凭证上传页面 -->
<template>
  <s-layout title="支付收银台">
    <view class="payment-container">
      <!-- 订单信息卡片 - 紧凑型 -->
      <view class="order-card">
        <view class="order-amount">
          <text class="amount-label">支付金额</text>
          <text class="amount-value">¥{{ state.orderInfo.pay_fee || state.orderInfo.amount }}</text>
        </view>
        <view class="order-sn">{{ state.orderSn }}</view>
      </view>

      <!-- 支付渠道选择 - 紧凑型可点击 -->
      <view class="channel-selector" @tap="showChannelModal">
        <view class="selector-label">支付渠道</view>
        <view class="selector-value">
          <text v-if="state.selectedChannel" class="selected-text">{{ state.selectedChannel.channel_name }}</text>
          <text v-else class="placeholder-text">请选择支付渠道</text>
          <text class="iconfont icon-arrow-right">切换</text>
        </view>
      </view>

      <!-- 收款信息 - 紧凑型 -->
      <view v-if="state.selectedChannel" class="payment-info">
        <view class="qrcode-wrapper" @tap="previewQrcode">
          <image 
            v-if="state.selectedChannel.qrcode_image"
            :src="state.selectedChannel.qrcode_image" 
            mode="aspectFit"
            class="qrcode-img"
          ></image>
          <view v-else class="qrcode-placeholder">暂无收款码</view>
          <view class="qrcode-tip">点击查看大图</view>
        </view>
        <view class="account-details">
          <view class="detail-item">
            <text class="detail-label">收款账户</text>
            <text class="detail-value">{{ state.selectedChannel.account_name }}</text>
          </view>
          <view class="detail-item" v-if="state.selectedChannel.account_number">
            <text class="detail-label">收款账号</text>
            <text class="detail-value">{{ state.selectedChannel.account_number }}</text>
          </view>
          <view class="detail-tip" v-if="state.selectedChannel.remark">
            {{ state.selectedChannel.remark }}
          </view>
        </view>
      </view>

      <!-- 上传截图 - 紧凑型 -->
      <view class="upload-card">
        <view class="card-title">支付凭证</view>
        <view class="upload-wrapper">
          <view v-if="state.screenshot" class="screenshot-box" @tap="previewScreenshot">
            <image :src="xxep.$url.cdn(state.screenshot)" mode="aspectFit" class="screenshot-img"></image>
            <view class="remove-icon" @tap.stop="removeScreenshot">×</view>
          </view>
          <view v-else class="upload-trigger" @tap="chooseImage">
            <text class="upload-icon">📷</text>
            <text class="upload-text">上传截图</text>
          </view>
        </view>
        <view class="upload-hint">请确保截图清晰，包含支付金额和时间</view>
      </view>

      <!-- 提交按钮 -->
      <view class="submit-wrapper">
        <button 
          class="submit-button"
          :disabled="!canSubmit"
          :class="{ disabled: !canSubmit }"
          @tap="submitScreenshot"
        >
          提交审核
        </button>
      </view>
    </view>

    <!-- 支付渠道选择弹窗 -->
    <view v-if="state.showChannelModal" class="modal-overlay" @tap="hideChannelModal">
      <view class="modal-content" @tap.stop>
        <view class="modal-header">
          <text class="modal-title">选择支付渠道</text>
          <text class="modal-close" @tap="hideChannelModal">×</text>
        </view>
        <view class="modal-body">
          <view 
            v-for="channel in state.channels" 
            :key="channel.id"
            :class="['modal-channel-item', { active: state.selectedChannelId === channel.id }]"
            @tap="selectChannelFromModal(channel)"
          >
            <view class="channel-content">
              <text class="channel-name">{{ channel.channel_name }}</text>
              <text class="channel-desc">{{ channel.account_name }}</text>
            </view>
            <view class="channel-check" v-if="state.selectedChannelId === channel.id">✓</view>
          </view>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
  import { reactive, computed } from 'vue';
  import { onLoad } from '@dcloudio/uni-app';
  import xxep from '@/xxep';

  const state = reactive({
    orderSn: '',
    orderType: 'goods', // goods 或 card
    orderInfo: {},
    channels: [],
    selectedChannelId: 0,
    selectedChannel: null,
    screenshot: '',
    showChannelModal: false, // 控制弹窗显示
  });

  const canSubmit = computed(() => {
    return state.selectedChannelId > 0 && state.screenshot !== '';
  });

  // 显示渠道选择弹窗
  function showChannelModal() {
    state.showChannelModal = true;
  }

  // 隐藏渠道选择弹窗
  function hideChannelModal() {
    state.showChannelModal = false;
  }

  // 从弹窗中选择渠道
  function selectChannelFromModal(channel) {
    state.selectedChannelId = channel.id;
    state.selectedChannel = channel;
    hideChannelModal();
  }

  // 选择渠道（保留用于默认选择）
  function selectChannel(channel) {
    state.selectedChannelId = channel.id;
    state.selectedChannel = channel;
  }

  // 预览收款码
  function previewQrcode() {
    if (state.selectedChannel && state.selectedChannel.qrcode_image) {
      uni.previewImage({
        urls: [state.selectedChannel.qrcode_image],
        current: 0,
      });
    }
  }

  // 选择图片
  function chooseImage() {
    uni.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['camera', 'album'],
      success: (res) => {
        const tempFilePath = res.tempFilePaths[0];
        uploadImage(tempFilePath);
      },
    });
  }

  // 上传图片
  async function uploadImage(filePath) {
    try {
      // 使用现成的上传函数
      const uploadResult = await xxep.$api.app.upload(filePath, 'payment');
      state.screenshot = uploadResult.url || uploadResult.fullurl;
    } catch (error) {
      xxep.$helper.toast(error.message || '上传失败');
    }
  }

  // 预览截图
  function previewScreenshot() {
    if (state.screenshot) {
      uni.previewImage({
        urls: [ xxep.$url.cdn(state.screenshot)],
        current: 0,
      });
    }
  }

  // 删除截图
  function removeScreenshot() {
    uni.showModal({
      title: '提示',
      content: '确定要删除该截图吗？',
      success: (res) => {
        if (res.confirm) {
          state.screenshot = '';
        }
      },
    });
  }

  // 提交截图
  async function submitScreenshot() {
    if (!canSubmit.value) {
      xxep.$helper.toast('请选择支付渠道并上传截图');
      return;
    }

    // 增强参数验证
    if (!state.orderSn) {
      xxep.$helper.toast('订单号不能为空');
      console.error('订单号为空');
      return;
    }

    if (!state.selectedChannelId) {
      xxep.$helper.toast('请选择支付渠道');
      console.error('未选择支付渠道');
      return;
    }

    if (!state.screenshot) {
      xxep.$helper.toast('请先上传支付凭证');
      console.error('截图为空');
      return;
    }

    try {
      // 选择对应的API方法
      let apiMethod;
      let params = {
        channel_id: state.selectedChannelId,
        screenshot: state.screenshot,
      };

      if (state.orderType === 'card') {
        // 金卡订单
        apiMethod = xxep.$api.pay.uploadCardScreenshot;
        params.order_no = state.orderSn;
      } else {
        // 商品订单和充值订单都使用同一个接口
        apiMethod = xxep.$api.pay.uploadScreenshot;
        params.order_sn = state.orderSn;
      }

      // 调试日志
      console.log('=== 上传截图参数 ===');
      console.log('订单类型:', state.orderType);
      console.log('订单号:', state.orderSn);
      console.log('渠道ID:', state.selectedChannelId);
      console.log('截图URL:', state.screenshot);
      console.log('完整参数:', JSON.stringify(params));
      console.log('==================');

      const result = await apiMethod(params);
      
      // 调试日志
      console.log('=== 上传截图响应 ===');
      console.log('完整响应:', JSON.stringify(result));
      console.log('==================');
      
      if (result && result.code === 1) {
        xxep.$helper.toast(result.msg || '提交成功');
        // 跳转到审核状态页面
        setTimeout(() => {
          xxep.$router.go('/pages/pay/screenshot-result', {
            orderSn: state.orderSn,
            orderType: state.orderType,
          });
        }, 1500);
      } else {
        const errorMsg = result?.msg || '提交失败，请重试';
        console.error('提交失败:', errorMsg);
        xxep.$helper.toast(errorMsg);
      }
    } catch (error) {
      console.error('=== 上传截图异常 ===');
      console.error('错误对象:', error);
      console.error('错误消息:', error?.msg || error?.message);
      console.error('==================');
      
      const errorMsg = error?.msg || error?.message || '网络错误，请检查网络连接';
      xxep.$helper.toast(errorMsg);
    }
  }

  // 获取收款渠道列表
  async function loadChannels() {
    try {
      const { code, data } = await xxep.$api.pay.channelList();
      if (code === 1 && data.channels) {
        state.channels = data.channels;
        // 默认选中第一个
        if (state.channels.length > 0) {
          selectChannel(state.channels[0]);
        }
      }
    } catch (error) {
      console.error('获取收款渠道失败', error);
    }
  }

  // 获取订单信息
  async function loadOrderInfo() {
    try {
      if (state.orderType === 'recharge') {
        // 充值订单
        const { data, code } = await xxep.$api.trade.order(state.orderSn);
        if (code === 1) {
          state.orderInfo = data;
        }
      } else if (state.orderType === 'card') {
        // 金卡订单
        const { data, code } = await xxep.$api.card.getOrderInfo({
          order_id: state.orderSn
        });
        if (code === 1) {
          state.orderInfo = data.order || data;
        }
      } else {
        // 商品订单
        const { data, code } = await xxep.$api.order.detail(state.orderSn);
        if (code === 1) {
          state.orderInfo = data;
        }
      }
    } catch (error) {
      console.error('获取订单信息失败', error);
      xxep.$helper.toast('获取订单信息失败');
    }
  }

  onLoad(async (options) => {
    state.orderSn = options.orderSN || options.id || '';
    state.orderType = options.type || 'goods';

    if (!state.orderSn) {
      xxep.$helper.toast('订单号不能为空');
      setTimeout(() => {
        uni.navigateBack();
      }, 1500);
      return;
    }

    // 加载数据
    await loadChannels();
    await loadOrderInfo();
  });
</script>

<style lang="scss" scoped>
  .payment-container {
    min-height: 100vh;
    background-color: #f7f8fa;
    padding: 20rpx;
    padding-bottom: 120rpx;
  }

  // 订单信息卡片 - 紧凑型
  .order-card {
    background: linear-gradient(135deg, var(--ui-BG-Main) 0%, var(--ui-BG-Main-gradient) 100%);
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);

    .order-amount {
      display: flex;
      align-items: baseline;
      justify-content: center;
      margin-bottom: 12rpx;

      .amount-label {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.9);
        margin-right: 10rpx;
      }

      .amount-value {
        font-size: 48rpx;
        font-weight: bold;
        color: #fff;
      }
    }

    .order-sn {
      text-align: center;
      font-size: 22rpx;
      color: rgba(255, 255, 255, 0.85);
    }
  }

  // 支付渠道选择器 - 紧凑型
  .channel-selector {
    background-color: #fff;
    border-radius: 12rpx;
    padding: 24rpx 30rpx;
    margin-bottom: 20rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

    .selector-label {
      font-size: 28rpx;
      color: #333;
      font-weight: 500;
    }

    .selector-value {
      display: flex;
      align-items: center;
      
      .selected-text {
        font-size: 28rpx;
        color: var(--ui-BG-Main);
        margin-right: 8rpx;
      }

      .placeholder-text {
        font-size: 28rpx;
        color: #999;
        margin-right: 8rpx;
      }

      .iconfont {
        font-size: 24rpx;
        color: #999;
      }
    }
  }

  // 收款信息 - 紧凑型
  .payment-info {
    background-color: #fff;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

    .qrcode-wrapper {
      text-align: center;
      margin-bottom: 20rpx;

      .qrcode-img {
        width: 320rpx;
        height: 320rpx;
        border-radius: 12rpx;
        border: 1rpx solid #eee;
      }

      .qrcode-placeholder {
        width: 320rpx;
        height: 320rpx;
        line-height: 320rpx;
        margin: 0 auto;
        border: 2rpx dashed #ddd;
        border-radius: 12rpx;
        color: #999;
        font-size: 24rpx;
      }

      .qrcode-tip {
        margin-top: 12rpx;
        font-size: 22rpx;
        color: #999;
      }
    }

    .account-details {
      background-color: #f9fafb;
      border-radius: 8rpx;
      padding: 20rpx;

      .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12rpx;
        font-size: 26rpx;

        &:last-of-type {
          margin-bottom: 0;
        }

        .detail-label {
          color: #666;
        }

        .detail-value {
          color: #333;
          font-weight: 500;
        }
      }

      .detail-tip {
        margin-top: 12rpx;
        padding-top: 12rpx;
        border-top: 1rpx dashed #e5e5e5;
        font-size: 22rpx;
        color: #ff6b00;
        line-height: 1.6;
      }
    }
  }

  // 上传卡片 - 紧凑型
  .upload-card {
    background-color: #fff;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

    .card-title {
      font-size: 28rpx;
      color: #333;
      font-weight: 500;
      margin-bottom: 16rpx;
    }

    .upload-wrapper {
      .screenshot-box {
        position: relative;
        width: 100%;
        height: 320rpx;
        border-radius: 12rpx;
        overflow: hidden;

        .screenshot-img {
          width: 100%;
          height: 100%;
        }

        .remove-icon {
          position: absolute;
          top: 10rpx;
          right: 10rpx;
          width: 48rpx;
          height: 48rpx;
          background-color: rgba(0, 0, 0, 0.6);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fff;
          font-size: 36rpx;
          line-height: 48rpx;
          text-align: center;
        }
      }

      .upload-trigger {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 320rpx;
        border: 2rpx dashed #ddd;
        border-radius: 12rpx;
        background-color: #fafafa;

        .upload-icon {
          font-size: 64rpx;
          margin-bottom: 12rpx;
        }

        .upload-text {
          font-size: 26rpx;
          color: #666;
        }
      }
    }

    .upload-hint {
      margin-top: 16rpx;
      font-size: 22rpx;
      color: #ff6b00;
      text-align: center;
      line-height: 1.5;
    }
  }

  // 提交按钮
  .submit-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16rpx 20rpx;
    background-color: #fff;
    box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.06);

    .submit-button {
      width: 100%;
      height: 80rpx;
      line-height: 80rpx;
      background: linear-gradient(90deg, var(--ui-BG-Main), var(--ui-BG-Main-gradient));
      color: #fff;
      border-radius: 40rpx;
      font-size: 30rpx;
      font-weight: 500;
      border: none;
      box-shadow: 0 4rpx 12rpx rgba(var(--ui-BG-Main-rgb), 0.3);

      &.disabled {
        background: #e5e5e5;
        color: #999;
        box-shadow: none;
      }
    }
  }

  // 弹窗样式
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: flex-end;
    z-index: 999;

    .modal-content {
      width: 100%;
      max-height: 70vh;
      background-color: #fff;
      border-radius: 24rpx 24rpx 0 0;
      overflow: hidden;
      animation: slideUp 0.3s ease-out;

      .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 30rpx;
        border-bottom: 1rpx solid #f0f0f0;

        .modal-title {
          font-size: 32rpx;
          font-weight: 500;
          color: #333;
        }

        .modal-close {
          font-size: 48rpx;
          color: #999;
          line-height: 1;
        }
      }

      .modal-body {
        max-height: 60vh;
        overflow-y: auto;
        padding: 20rpx 0;

        .modal-channel-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 28rpx 30rpx;
          border-bottom: 1rpx solid #f5f5f5;
          transition: background-color 0.2s;

          &:active {
            background-color: #f9f9f9;
          }

          &.active {
            background-color: rgba(var(--ui-BG-Main-rgb), 0.05);

            .channel-name {
              color: var(--ui-BG-Main);
            }
          }

          .channel-content {
            flex: 1;

            .channel-name {
              font-size: 30rpx;
              color: #333;
              font-weight: 500;
              margin-bottom: 8rpx;
            }

            .channel-desc {
              font-size: 24rpx;
              color: #999;
            }
          }

          .channel-check {
            font-size: 36rpx;
            color: var(--ui-BG-Main);
            font-weight: bold;
          }
        }
      }
    }
  }

  @keyframes slideUp {
    from {
      transform: translateY(100%);
    }
    to {
      transform: translateY(0);
    }
  }
</style>
