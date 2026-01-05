<template>
  <view :style="{ height: statusBarHeight }" class="uni-status-bar"><slot /></view>
</template>

<script setup>
  import xxep from '@/xxep';
  import { computed } from 'vue';

  const statusBarHeight = xxep.$platform.device.statusBarHeight + 'px';
</script>
<style lang="scss">
  .uni-status-bar {
    // width: 750rpx;
    height: var(--status-bar-height);
  }
</style>
