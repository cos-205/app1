<template>
  <su-swiper
    :list="imgList"
    :dotStyle="dotMap[data.indicator]"
    imageMode="widthFix"
    dotCur="bg-mask-40"
    :seizeHeight="300"
    :autoplay="data.autoplay"
    :interval="Number(data.interval)"
  />
</template>

<script setup>
  import { computed } from 'vue';
  import xxep from '@/xxep';

  const dotMap = {
    1: 'long',
    2: 'tag',
  };
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

  const imgList = computed(() =>
    props.data.list.map((item) => ({
      ...item,
      src: xxep.$url.cdn(item.src),
      poster: xxep.$url.cdn(item.poster),
    })),
  );
</script>

<style></style>
