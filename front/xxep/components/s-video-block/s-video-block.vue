<!-- 订单详情 -->
<template>
  <view class="video-block-wrapper">
    <su-video
      class="xxs"
      :uid="guid()"
      :src="xxep.$url.cdn(data.videoUrl)"
      :poster="xxep.$url.cdn(data.src)"
      :height="styles.height * 2"
    ></su-video>
  </view>
</template>

<script setup>
  import xxep from '@/xxep';
  import { guid } from '@/xxep/helper';
  const props = defineProps({
    data: {
      type: Object,
      default() {},
    },
    styles: {
      type: Object,
      default() {},
    },
  });
</script>

<style lang="scss" scoped>
  .video-block-wrapper {
    // background: linear-gradient(135deg, #FF1744 0%, #FF5252 50%, #FF6B6B 100%);
    border-radius: 16rpx;
    box-shadow: 0 8rpx 24rpx rgba(211, 23, 68, 0.3);
    overflow: hidden;
  }
  
  .xxs {
    z-index: -100;
    border-radius: 12rpx;
    overflow: hidden;

  }
</style>
