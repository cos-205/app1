<template>
  <view class="ss-flex ss-col-center">
    <view
      v-if="data.type === 'text'"
      class="nav-title inline"
      :style="[{ color: data.textColor, width: width }]"
    >
      {{ data.text }}
    </view>
    <view
      v-if="data.type === 'image'"
      :style="[{ width: width }]"
      class="menu-icon-wrap ss-flex ss-row-center ss-col-center"
      @tap="xxep.$router.go(data.url)"
    >
      <image class="nav-image" :src="xxep.$url.cdn(data.src)" mode="aspectFit"></image>
    </view>
    <view class="ss-flex-1" v-if="data.type == 'search'" :style="[{ width: width }]">
      <s-search-block
        :placeholder="data.placeholder || '搜索关键字'"
        :radius="data.borderRadius"
        elBackground="#fff"
        :height="height"
        :width="width"
        @click="xxep.$router.go('/pages/index/search')"
      ></s-search-block>
    </view>
  </view>
</template>

<script setup>
  import xxep from '@/xxep';
  import { computed } from 'vue';

  // 接收参数
  const props = defineProps({
    data: {
      type: Object,
      default: () => ({}),
    },
    width: {
      type: String,
      default: '1px',
    },
  });

  const height = computed(() => xxep.$platform.capsule.height);
</script>

<style lang="scss" scoped>
  .nav-title {
    font-size: 36rpx;
    color: #333;
    text-align: center;
  }

  .menu-icon-wrap {
    .nav-image {
      height: 24px;
    }
  }
</style>
