/**
 * Cus 第三方平台功能聚合
 * @version 1.0.3
 * @author lidongtony
 * @param {String} name - 厂商+平台名称
 * @param {String} provider - 厂商
 * @param {String} platform - 平台名称
 * @param {String} os - 系统型号
 * @param {Object} device - 设备信息
 */

import { isEmpty } from 'lodash';
// #ifdef H5
import { isWxBrowser } from '@/xxep/helper/utils';
// #endif
import wechat from './provider/wechat/index.js';
import apple from './provider/apple';
import share from './share';
import Pay from './pay';
import request from '@/xxep/request';

const device = uni.getSystemInfoSync();

const os = device.platform;

let name = '';
let provider = '';
let platform = '';
let isWechatInstalled = true;

// #ifdef H5
if (isWxBrowser()) {
  name = 'WechatOfficialAccount';
  provider = 'wechat';
  platform = 'officialAccount';
} else {
  name = 'H5';
  platform = 'h5';
}
// #endif

// #ifdef APP-PLUS
name = 'App';
platform = 'openPlatform';
// 检查微信客户端是否安装，否则AppleStore会因此拒绝上架
if (os === 'ios') {
  isWechatInstalled = plus.ios.import('WXApi').isWXAppInstalled();
}
// #endif

// #ifdef MP-WEIXIN
name = 'WechatMiniProgram';
platform = 'miniProgram';
provider = 'wechat';
// #endif

if (isEmpty(name)) {
  uni.showToast({
    title: '暂不支持该平台',
    icon: 'none',
  });
}

// 加载当前平台前置行为
const load = () => {
  if (provider === 'wechat') {
    wechat.load();
  }
};

// 使用厂商独占sdk name = 'wechat' | 'alipay' | 'apple'
const useProvider = (_provider = '') => {
  if (_provider === '') _provider = provider;
  if (_provider === 'wechat') return wechat;
  if (_provider === 'apple') return apple;
};

// 支付服务转发
const pay = (payment, orderType, orderSN) => {
  return new Pay(payment, orderType, orderSN);
};

/**
 * 检查更新 (只检查小程序和App)
 * @param {Boolean} silence - 静默检查
 */
const checkUpdate = async (silence = false) => {
  // #ifdef MP-WEIXIN
  useProvider().checkUpdate(silence);
  // #endif
	
  // #ifdef APP-PLUS
  try {
    // 1. 获取当前版本号
    const currentVersion = "3.1.0"// plus.runtime.version;
    if (!currentVersion) return;

    // 2. 调用后端接口检查版本（使用已有的init接口）
    const response = await request({
      url: '/api/index/init',
      params: { version: currentVersion },
      custom: { showError: false, showLoading: false },
    });
	
    if (!response || response.code !== 1) return;

    const versionData = response.data;
    if (!versionData) return; // 没有新版本

    // 3. 判断是否需要更新
    if (versionData.version <= currentVersion) return; // 已是最新版本

    // 4. 判断是否为wgt包
    const downloadUrl = versionData.downloadurl || '';
    const isWgt = downloadUrl.toLowerCase().endsWith('.wgt');

    if (!isWgt) {
      // 整包更新：跳转应用商店
      uni.showModal({
        title: '版本更新',
        content: versionData.upgradetext || '发现新版本，是否前往更新？',
        confirmText: '前往更新',
        cancelText: versionData.enforce ? '' : '稍后再说',
        showCancel: !versionData.enforce,
        success: (res) => {
          if (res.confirm) {
            plus.runtime.openURL(downloadUrl);
          }
        },
      });
      return;
    }

    // 5. wgt包：静默下载
    const downloadTask = uni.downloadFile({
      url: downloadUrl,
      success: (res) => {
        if (res.statusCode === 200) {
          // 6. 下载完成，先自动安装（静默）
          uni.showLoading({ title: '正在安装更新...', mask: true });
          plus.runtime.install(
            res.tempFilePath,
            { force: versionData.enforce == true },
            () => {
              // 7. 安装成功，提示用户是否重启
              uni.hideLoading();
              uni.showModal({
                title: '更新完成',
                content: versionData.upgradetext || '更新包已安装完成，是否立即重启应用？',
                confirmText: '立即重启',
                cancelText: versionData.enforce ? '' : '稍后重启',
                showCancel: !versionData.enforce,
                success: (modalRes) => {
                  if (modalRes.confirm) {
                    // 8. 重启应用
                    plus.runtime.restart();
                  }
                },
              });
            },
            (error) => {
              // 安装失败
              uni.hideLoading();
              uni.showToast({
                title: '安装失败，请稍后重试',
                icon: 'none',
              });
            }
          );
        }
      },
      fail: (err) => {
        console.error('下载失败:', err);
      },
    });
  } catch (error) {
    console.error('更新检查失败:', error);
  }
  // #endif
};

/**
 * 检查网络
 * @param {Boolean} silence - 静默检查
 */
async function checkNetwork() {
  const networkStatus = await uni.getNetworkType();
  if (networkStatus.networkType == 'none') {
    return Promise.resolve(false);
  }
  return Promise.resolve(true);
}

// 获取小程序胶囊信息
const getCapsule = () => {
  // #ifdef MP
  let capsule = uni.getMenuButtonBoundingClientRect();
  if (!capsule) {
    capsule = {
      bottom: 56,
      height: 32,
      left: 278,
      right: 365,
      top: 24,
      width: 87,
    };
  }
  return capsule;
  // #endif

  // #ifndef MP
  return {
    bottom: 56,
    height: 32,
    left: 278,
    right: 365,
    top: 24,
    width: 87,
  };
  // #endif
};

const capsule = getCapsule();

// 标题栏高度
const getNavBar = () => {
  return device.statusBarHeight + 44;
};
const navbar = getNavBar();

function getLandingPage() {
  let page = '';
  // #ifdef H5
  page = location.href.split('?')[0];
  // #endif
  return page;
}

// 设置ios+公众号网页落地页 解决微信sdk签名问题
const landingPage = getLandingPage();

const _platform = {
  name,
  device,
  os,
  provider,
  platform,
  useProvider,
  checkUpdate,
  checkNetwork,
  pay,
  share,
  load,
  capsule,
  navbar,
  landingPage,
  isWechatInstalled,
};

export default _platform;
