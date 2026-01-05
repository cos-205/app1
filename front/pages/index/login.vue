<template>
  <!-- 空登陆页 -->
  <view></view>
</template>

<script setup>
  import { isEmpty } from 'lodash';
  import xxep from '@/xxep';
  import { onLoad, onShow } from '@dcloudio/uni-app';

  onLoad(async (options) => {
    // #ifdef H5
    let event = '';
    if (options.login_code) {
      event = 'login';
      const { code } = await xxep.$platform.useProvider().login(options.login_code);
      if (code === 1) {
        xxep.$store('user').getInfo();
      }
    }
    if (options.bind_code) {
      event = 'bind';
      const { code } = await xxep.$platform.useProvider().bind(options.bind_code);
    }

    // 检测H5登录回调
    let returnUrl = uni.getStorageSync('returnUrl');
    if (returnUrl) {
      uni.removeStorage('returnUrl');
      location.replace(returnUrl);
    } else {
      uni.switchTab({
        url: '/',
      });
    }

    // #endif
  });
</script>
