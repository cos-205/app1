<!-- 订单详情 -->
<template>
  <su-video
    class="xxs"
    :uid="guid()"
    :src="xxep.$url.cdn(data.videoUrl)"
    :poster="xxep.$url.cdn(data.src)"
    :height="styles.height * 2"
  ></su-video>
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
  .xxs {
    z-index: -100;
  }
</style>
