import $api from '@/xxep/api';
import $url from '@/xxep/url';
import $router from '@/xxep/router';
import $platform from '@/xxep/platform';
import $helper from '@/xxep/helper';
import zIndex from '@/xxep/config/zIndex.js';
import $store from '@/xxep/store';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import duration from 'dayjs/plugin/duration';
import 'dayjs/locale/zh-cn';

dayjs.locale('zh-cn');
dayjs.extend(relativeTime);
dayjs.extend(duration);

const xxep = {
  $api,
  $store,
  $url,
  $router,
  $platform,
  $helper,
  $zIndex: zIndex,
};

// 加载Cus底层依赖
export async function CusInit() {
  // 应用初始化
  await $store('app').init();

  // 平台初始化加载(各平台provider提供不同的加载流程)
  $platform.load();

  if (process.env.NODE_ENV === 'development') {
    CusDebug();
  }
}

// 开发模式
function CusDebug() {
  // 开发环境引入vconsole调试
  // #ifdef H5
  // import("vconsole").then(vconsole => {
  // 	new vconsole.default();
  // });
  // #endif

  // 同步前端页面到后端
  $api.app.pageSync(ROUTES);
}

export default xxep;
