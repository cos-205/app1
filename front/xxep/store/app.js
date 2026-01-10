import appApi from '@/xxep/api/app';
import { defineStore } from 'pinia';
import $platform from '@/xxep/platform';
import $router from '@/xxep/router';
import user from './user';
import sys from './sys';
import { baseUrl } from '@/xxep/config';

const app = defineStore({
  id: 'app',
  state: () => ({
    info: {
      // 应用信息
      name: '', // 商城名称
      logo: '', // logo
      version: '', // 版本号
      cdnurl: '', // 云存储域名
      filesystem: '', // 云存储平台
      user_protocol: {}, // 用户协议
      privacy_protocol: {}, // 隐私协议
      about_us: {}, // 关于我们
      copyright: '', // 版权信息 I
      copytime: '', // 版权信息 II
      // 功能开关配置
      hide_withdraw: 0, // 隐藏提现功能：0=显示，1=隐藏
      hide_entry_ticket: 0, // 隐藏入场券功能：0=显示，1=隐藏
      // 专员信息配置
      specialist_name: '', // 专员名称
      specialist_number: '', // 专员号码
    },
    platform: {
      payment: [], // 支持的支付方式
      share: {
        methods: [], // 支持的分享方式
        forwardInfo: {}, // 默认转发信息
        posterInfo: {}, // 海报信息
        linkAddress: '', // 复制链接地址
      },
      auto_login: 0, // 自动登陆
      bind_mobile: 0, // 登陆后绑定手机号提醒 (弱提醒，可手动关闭)
    },
    chat: {},
    template: {
      // 店铺装修模板
      basic: {}, // 基本信息
      home: {
        // 首页模板
        style: {},
        data: [],
      },
      user: {
        // 个人中心模板
        style: {},
        data: [],
      },
    },
    shareInfo: {}, // 全局分享信息
    has_wechat_trade_managed: 0 // 小程序发货信息管理  0 没有 || 1 有
  }),
  actions: {
    // 获取Cus应用配置和模板
    async init(templateId = null) {
      //检查网络
      const networkStatus = await $platform.checkNetwork();
      if (!networkStatus) {
        $router.error('NetworkError');
      }

      // 检查配置
      if (typeof baseUrl === 'undefined') {
        $router.error('EnvError');
      }

      const res = await appApi.init(templateId);
      if (res.code === 1) {
        // 使用 Object.assign 确保响应式更新，特别是对于持久化存储
        Object.assign(this.info, res.data.app || {});
        this.platform = res.data.platform;
        this.template = res.data.template;
        this.has_wechat_trade_managed = res.data.has_wechat_trade_managed;
        if (!res.data.template) {
          $router.error('TemplateError');
        }
        this.chat = res.data.chat;

        // 加载主题
        const sysStore = sys();
        sysStore.setTheme();

        // 模拟用户登录
        const userStore = user();
        if (userStore.isLogin) {
          userStore.loginAfter();
        }
        return Promise.resolve(true);
      } else {
        $router.error('InitError', res.msg || '加载失败');
      }
    },
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: 'app-store',
      },
    ],
  },
});

export default app;
