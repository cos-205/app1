<template>
  <view class="ss-wallet-menu-wrap ss-flex ss-col-center">
    <view
      class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center"
      @tap="xxep.$router.go('/pages/user/wallet/money')"
    >
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text ss-line-1">{{ userInfo.money }}</view>
        <view class="unit-text ss-m-l-6">元</view>
      </view>
      <view class="menu-title ss-m-t-28">账户余额</view>
    </view>
    <!-- <view class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center"
			@tap="xxep.$router.go('/pages/user/wallet/commission')">
			<view class="value-box ss-flex ss-col-bottom">
				<view class="value-text">{{ userInfo?.commission || '0.00' }}</view>
				<view class="unit-text ss-m-l-6">元</view>
			</view>
			<view class="menu-title ss-m-t-28">佣金</view>
		</view> -->
    <view
      class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center"
      @tap="xxep.$router.go('/pages/user/wallet/score')"
    >
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text">{{ userInfo.score }}</view>
        <view class="unit-text ss-m-l-6">个</view>
      </view>
      <view class="menu-title ss-m-t-28">积分</view>
    </view>
    <view
      class="menu-item ss-flex-1 ss-flex-col ss-row-center ss-col-center"
      @tap="
        xxep.$router.go('/pages/coupon/list', {
          type: 'geted',
        })
      "
    >
      <view class="value-box ss-flex ss-col-bottom">
        <view class="value-text">{{ numData.coupons_num }}</view>
        <view class="unit-text ss-m-l-6">张</view>
      </view>
      <view class="menu-title ss-m-t-28">优惠券</view>
    </view>
    <view
      class="menu-item ss-flex-col ss-row-center ss-col-center menu-wallet"
      @tap="xxep.$router.go('/pages/user/wallet/money')"
    >
      <image
        class="item-icon"
        :src="xxep.$url.static('/assets/addons/cus/uniapp/user/wallet_icon.png')"
        mode="aspectFit"
      >
      </image>
      <view class="menu-title ss-m-t-30">我的钱包</view>
    </view>
  </view>
</template>

<script setup>
  /**
   * 装修组件 - 订单菜单组
   */
  import { computed, ref } from 'vue';
  import xxep from '@/xxep';

  const userInfo = computed(() => xxep.$store('user').userInfo);
  const numData = computed(() => xxep.$store('user').numData);
</script>

<style lang="scss" scoped>
  .ss-wallet-menu-wrap {
    .menu-wallet {
      width: 144rpx;
    }
    .menu-item {
      height: 160rpx;

      .menu-title {
        font-size: 24rpx;
        line-height: 24rpx;
        color: #333333;
      }

      .item-icon {
        width: 44rpx;
        height: 44rpx;
      }

      .value-box {
        height: 50rpx;
        text-align: center;

        .value-text {
          font-size: 28rpx;
          color: #000000;
          line-height: 28rpx;
          vertical-align: text-bottom;
          font-family: OPPOSANS;
        }

        .unit-text {
          font-size: 24rpx;
          color: #343434;
          line-height: 24rpx;
        }
      }
    }
  }
</style>
