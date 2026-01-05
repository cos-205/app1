<template>
  <view class="ss-flex ss-col-center notice-wrap">
    <image class="icon-img" :src="xxep.$url.cdn(data.src)" mode="heightFix"></image>
    <su-notice-bar
      style="flex: 1"
      :showIcon="false"
      scrollable
      single
      :text="data.title.text"
      :speed="50"
      :color="data.title.color"
      @tap="xxep.$router.go(data.url)"
    ></su-notice-bar>
  </view>
</template>

<script setup>
  /**
   * 装修组件  - 通知栏
   *
   */
  import xxep from '@/xxep';
  const props = defineProps({
    data: {
      type: Object,
      default() {},
    },
  });
</script>

<style lang="scss" scoped>
  .notice-wrap {
    .icon-img {
      height: 60rpx;
    }
  }
</style>
