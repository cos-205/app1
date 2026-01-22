<!-- 支付凭证审核结果页面 -->
<template>
  <s-layout :title="pageTitle">
    <view class="result-container">
      <!-- 状态图标 -->
      <!-- <view class="status-icon">
        <view v-if="state.status === 0" class="icon-box waiting">
          <text class="iconfont icon-time"></text>
        </view>
        <view v-else-if="state.status === 1" class="icon-box success">
          <text class="iconfont icon-success"></text>
        </view>
        <view v-else class="icon-box failed">
          <text class="iconfont icon-error"></text>
        </view>
      </view> -->

      <!-- 状态文本 -->
      <view class="status-text">
        <text class="title">{{ statusTitle }}</text>
        <text class="desc">{{ statusDesc }}</text>
      </view>

      <!-- 审核信息 -->
      <view class="info-section">
        <view class="info-item">
          <text class="label">订单号</text>
          <text class="value">{{ state.orderSn }}</text>
        </view>
        <view v-if="state.screenshot" class="info-item">
          <text class="label">支付凭证</text>
          <image 
            :src="state.screenshot" 
            class="screenshot-thumb"
            mode="aspectFill"
            @tap="previewScreenshot"
          ></image>
        </view>
        <view v-if="state.auditTime" class="info-item">
          <text class="label">审核时间</text>
          <text class="value">{{ formatTime(state.auditTime) }}</text>
        </view>
        <view v-if="state.auditRemark" class="info-item">
          <text class="label">审核备注</text>
          <text class="value remark">{{ state.auditRemark }}</text>
        </view>
      </view>

      <!-- 操作按钮 -->
      <view class="btn-group">
        <button 
          v-if="state.status === 0" 
          class="btn refresh-btn" 
          @tap="refreshStatus"
        >
          刷新状态
        </button>
        <button 
          v-if="state.status === 2" 
          class="btn reupload-btn" 
          @tap="reupload"
        >
          重新上传
        </button>
        <button 
          v-if="state.status === 1" 
          class="btn order-btn" 
          @tap="goToOrder"
        >
          查看订单
        </button>
        <button class="btn back-btn" @tap="goBack">
          返回首页
        </button>
      </view>

      <!-- 自动刷新提示 -->
      <view v-if="state.status === 0 && state.countdown > 0" class="auto-refresh-tip">
        {{ state.countdown }}秒后自动刷新
      </view>
    </view>
  </s-layout>
</template>

<script setup>
  import { reactive, computed, onUnmounted } from 'vue';
  import { onLoad, onShow } from '@dcloudio/uni-app';
  import xxep from '@/xxep';

  const state = reactive({
    orderSn: '',
    orderType: 'goods',
    status: 0, // 0=待审核, 1=审核通过, 2=审核拒绝
    screenshot: '',
    auditTime: 0,
    auditRemark: '',
    countdown: 10, // 自动刷新倒计时
    timer: null,
  });

  const pageTitle = computed(() => {
    if (state.status === 0) return '等待审核';
    if (state.status === 1) return '审核通过';
    return '审核未通过';
  });

  const statusTitle = computed(() => {
    if (state.status === 0) return '支付凭证审核中';
    if (state.status === 1) return '支付成功';
    return '审核未通过';
  });

  const statusDesc = computed(() => {
    if (state.status === 0) return '您的支付凭证正在审核中，请耐心等待';
    if (state.status === 1) return '您的支付已通过审核，订单处理中';
    return '支付凭证审核未通过，请重新上传';
  });

  // 格式化时间
  function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    const Y = date.getFullYear();
    const M = String(date.getMonth() + 1).padStart(2, '0');
    const D = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    const s = String(date.getSeconds()).padStart(2, '0');
    return `${Y}-${M}-${D} ${h}:${m}:${s}`;
  }

  // 预览截图
  function previewScreenshot() {
    if (state.screenshot) {
      uni.previewImage({
        urls: [state.screenshot],
        current: 0,
      });
    }
  }

  // 刷新状态
  async function refreshStatus() {
    try {
      const { code, data } = await xxep.$api.pay.screenshotStatus({
        order_sn: state.orderSn,
        order_type: state.orderType,
      });

      if (code === 1) {
        state.status = data.screenshot_status || 0;
        state.auditRemark = data.screenshot_audit_remark || '';
        state.auditTime = data.screenshot_audit_time || 0;
        state.screenshot = data.payment_screenshot || '';

        // 重置倒计时
        if (state.status === 0) {
          state.countdown = 10;
        } else {
          // 审核完成，停止定时器
          stopTimer();
        }
      }
    } catch (error) {
      xxep.$helper.toast(error.msg || '刷新失败');
    }
  }

  // 重新上传
  function reupload() {
    xxep.$router.go('/pages/pay/screenshot', {
      orderSN: state.orderSn,
      type: state.orderType,
    });
  }

  // 查看订单
  function goToOrder() {
    if (state.orderType === 'goods') {
      xxep.$router.go('/pages/order/detail', {
        id: state.orderSn,
      });
    } else {
      // 金卡订单
      xxep.$router.go('/pages/card/info-confirm');
    }
  }

  // 返回首页
  function goBack() {
    xxep.$router.go('/pages/index/index');
  }

  // 启动定时器
  function startTimer() {
    if (state.status !== 0) return;

    state.timer = setInterval(() => {
      state.countdown--;
      if (state.countdown <= 0) {
        state.countdown = 10;
        refreshStatus();
      }
    }, 1000);
  }

  // 停止定时器
  function stopTimer() {
    if (state.timer) {
      clearInterval(state.timer);
      state.timer = null;
    }
  }

  // 加载审核状态
  async function loadStatus() {
    await refreshStatus();
    // 如果是待审核状态，启动自动刷新
    if (state.status === 0) {
      startTimer();
    }
  }

  onLoad((options) => {
    state.orderSn = options.orderSn || '';
    state.orderType = options.orderType || 'goods';

    if (!state.orderSn) {
      xxep.$helper.toast('订单号不能为空');
      setTimeout(() => {
        uni.navigateBack();
      }, 1500);
      return;
    }

    loadStatus();
  });

  onShow(() => {
    // 页面显示时刷新状态
    if (state.orderSn) {
      refreshStatus();
    }
  });

  onUnmounted(() => {
    stopTimer();
  });
</script>

<style lang="scss" scoped>
  .result-container {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding: 60rpx 30rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .status-icon {
    margin-bottom: 40rpx;

    .icon-box {
      width: 160rpx;
      height: 160rpx;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 80rpx;

      &.waiting {
        background-color: #fff8e6;
        color: #faad14;
      }

      &.success {
        background-color: #e6f7ff;
        color: #52c41a;
      }

      &.failed {
        background-color: #fff1f0;
        color: #ff4d4f;
      }
    }
  }

  .status-text {
    text-align: center;
    margin-bottom: 60rpx;

    .title {
      display: block;
      font-size: 36rpx;
      font-weight: bold;
      color: #333;
      margin-bottom: 20rpx;
    }

    .desc {
      display: block;
      font-size: 28rpx;
      color: #666;
    }
  }

  .info-section {
    width: 100%;
    background-color: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 40rpx;

    .info-item {
      display: flex;
      align-items: flex-start;
      padding: 20rpx 0;
      border-bottom: 1rpx solid #f0f0f0;

      &:last-child {
        border-bottom: none;
      }

      .label {
        width: 160rpx;
        font-size: 28rpx;
        color: #999;
      }

      .value {
        flex: 1;
        font-size: 28rpx;
        color: #333;
        word-break: break-all;

        &.remark {
          color: #ff4d4f;
        }
      }

      .screenshot-thumb {
        width: 150rpx;
        height: 150rpx;
        border-radius: 8rpx;
      }
    }
  }

  .btn-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20rpx;

    .btn {
      width: 100%;
      height: 88rpx;
      line-height: 88rpx;
      border-radius: 44rpx;
      font-size: 32rpx;
      border: none;
    }

    .refresh-btn,
    .order-btn {
      background: linear-gradient(90deg, var(--ui-BG-Main), var(--ui-BG-Main-gradient));
      color: #fff;
    }

    .reupload-btn {
      background-color: #ff6b00;
      color: #fff;
    }

    .back-btn {
      background-color: #fff;
      color: #666;
      border: 1rpx solid #d9d9d9;
    }
  }

  .auto-refresh-tip {
    margin-top: 30rpx;
    font-size: 24rpx;
    color: #999;
    text-align: center;
  }
</style>
