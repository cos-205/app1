<template>
  <view class="ss-wallet-menu-wrap ss-flex ss-col-top">
    <!-- 账户余额 -->
    <view class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center">
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text ss-line-1">{{ userInfo.money || '0.00' }}</view>
        <view class="unit-text ss-m-l-6">元</view>
      </view>
      <view class="menu-title">余额</view>
    </view>
    <view class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center">
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text ss-line-1">{{ userInfo.score || '0' }}</view>
        <view class="unit-text ss-m-l-6">个</view>
      </view>
      <view class="menu-title">积分</view>
    </view>
    <view class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center">
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text ss-line-1">{{ numData.coupons_num || '0' }}</view>
        <view class="unit-text ss-m-l-6">个</view>
      </view>
      <view class="menu-title">优惠券</view>
    </view>
  </view>
</template>

<script setup>
  /**
   * 装修组件 - 钱包卡片
   */
  import { computed, ref, onMounted } from 'vue';
  import xxep from '@/xxep';

  const userInfo = computed(() => xxep.$store('user').userInfo);
  const numData = computed(() => xxep.$store('user').numData);


</script>

<style lang="scss" scoped>
  .ss-wallet-menu-wrap {
    padding: 0 20rpx;
    
    .menu-wallet {
      width: 144rpx;
    }
    
    .menu-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 10rpx 10rpx;

      .value-box {
        height: 50rpx;
        text-align: center;
        margin-bottom: 12rpx;

        .value-text {
          font-size: 32rpx;
          font-weight: 600;
          color: #000000;
          line-height: 32rpx;
          vertical-align: text-bottom;
          font-family: OPPOSANS;
        }

        .unit-text {
          font-size: 22rpx;
          color: #666666;
          line-height: 22rpx;
        }
      }

      .menu-title {
        font-size: 24rpx;
        line-height: 24rpx;
        color: #666666;
        margin-bottom: 12rpx;
        height: 24rpx;
      }

      .item-icon {
        width: 44rpx;
        height: 44rpx;
      }

      // 提现按钮样式
      .withdraw-btn {
        padding: 6rpx 28rpx;
        background: linear-gradient(135deg, #ff6100 0%, #ff8533 100%);
        border-radius: 18rpx;
        font-size: 22rpx;
        color: #ffffff;
        border: none;
        line-height: 1.5;
        height: 38rpx;
        min-height: 38rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        
        &:active {
          opacity: 0.8;
        }
        
        &::after {
          border: none;
        }
      }

      // 提示文字容器（与提现按钮高度对齐）
      .tips-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 38rpx;
        height: 38rpx;
        padding: 0 5rpx;
        
        .tips-text {
          font-size: 20rpx;
          color: #999999;
          line-height: 1.3;
          text-align: center;
        }
      }
    }
  }
</style>
