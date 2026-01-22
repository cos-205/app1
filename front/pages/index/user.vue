<template>
  <s-layout
    title="我的"
    tabbar="/pages/index/user"
    navbar="custom"
    :bgStyle="template.style?.background"
    :navbarStyle="template.style?.navbar"
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

  // 退出登录
  function onLogout() {
    uni.showModal({
      title: '提示',
      content: '确认退出账号？',
      success: async function (res) {
        if (res.confirm) {
          const result = await xxep.$store('user').logout();
          if (result) {
            xxep.$store('user').updateUserData();
          }
        }
      },
    });
  }
</script>

<style lang="scss" scoped>
  .logout-btn {
    width: 100%;
    height: 80rpx;
    border-radius: 40rpx;
    font-size: 30rpx;
  }
</style>
