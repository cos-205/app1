<template>
  <s-layout
    title="我的"
    tabbar="/pages/index/user"
    navbar="custom"
    :bgStyle="template.style?.background"
    :navbarStyle="template.style?.navbar"
    onShareAppMessage
    :showFloatButton="true"
  >
    <s-block v-for="(item, index) in template.data" :key="index" :styles="item.style">
      <s-block-item :type="item.type" :data="item.data" :styles="item.style" />
    </s-block>
  </s-layout>
</template>

<script setup>
  import { computed } from 'vue';
  import { onShow, onPageScroll, onPullDownRefresh } from '@dcloudio/uni-app';
  import xxep from '@/xxep';

  // 隐藏原生tabBar
  uni.hideTabBar({
    fail: () => {},
  });

  const template = computed(() => xxep.$store('app').template.user);
  const isLogin = computed(() => xxep.$store('user').isLogin);

  onShow(() => {
    xxep.$store('user').updateUserData();
  });

  onPullDownRefresh(() => {
    xxep.$store('user').updateUserData();
    setTimeout(function () {
      uni.stopPullDownRefresh();
    }, 800);
  });

  onPageScroll(() => {});
</script>

<style></style>
