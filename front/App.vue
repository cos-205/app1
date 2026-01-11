<script setup>
  import { onLaunch, onShow, onError } from '@dcloudio/uni-app';
  import { CusInit } from './xxep';
  import $platform from './xxep/platform';

  onLaunch(() => {
    // 隐藏原生导航栏 使用自定义底部导航
    uni.hideTabBar({
      fail: () => {},
    });

    // 加载Cus底层依赖
    CusInit();
	setTimeout(() => {
	  $platform.checkUpdate();
    console.log('checkUpdate');
	}, 2000);
    // 检查APP更新（静默下载）
    // #ifdef APP-PLUS
    setTimeout(() => {
      $platform.checkUpdate();
    }, 2000); // 延迟2秒，确保基础功能已加载
    // #endif
  });

  onError((err) => {
    console.log('AppOnError:', err);
  });

  onShow(() => {
    // #ifdef APP-PLUS
    // 获取urlSchemes参数
    const args = plus.runtime.arguments;
    if (args) {
    }

    // 获取剪贴板
    uni.getClipboardData({
      success: (res) => {},
    });
    // #endif
  });
</script>

<style lang="scss">
  @import '@/xxep/scss/index.scss';
</style>
