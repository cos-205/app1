<template>
  <view class="hotzone-wrap">
    <image :src="xxep.$url.cdn(data.src)" style="width: 100%" mode="widthFix"></image>
    <view
      class="hotzone-box"
      v-for="item in data.list"
      :key="item.width"
      :style="[
        {
          top: item.top + 'rpx',
          left: item.left + 'rpx',
          width: item.width + 'rpx',
          height: item.height + 'rpx',
        },
      ]"
      @tap.stop="xxep.$router.go(item.url)"
    >
    </view>
  </view>
</template>

<script setup>
  import xxep from '@/xxep';

  // 接收参数
  const props = defineProps({
    data: {
      type: Object,
      default: () => ({}),
    },
    styles: {
      type: Object,
      default: () => ({}),
    },
  });
</script>

<style lang="scss" scoped>
  .hotzone-wrap {
    position: relative;
  }
  .hotzone-box {
    position: absolute;
  }
</style>
