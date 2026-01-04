import third from '@/sheep/api/third';
import $wxsdk from '@/sheep/libs/sdk-h5-weixin';
import $store from '@/sheep/store';
import $router from '@/sheep/router';
import { getRootUrl } from '@/sheep/helper';
import $helper from '@/sheep/helper';

// 加载微信公众号JSSDK
async function load() {
  if (
    $store('app').platform.auto_login &&
    !$store('user').isLogin &&
    location.href.search('pages/index/login') === -1
  ) {
    // 发起自动登陆
    login();
  }
  $wxsdk.init();
}

// 微信公众号登陆
async function login(code = '') {
  // 获取登陆地址
  if (!code) {
    const loginResult = await getLoginUrl();
    if (loginResult.code === 1 && loginResult.data.login_url) {
      uni.setStorageSync('returnUrl', location.href);
      window.location = loginResult.data.login_url;
    }
  } else {
    // 解密code发起登陆
    const loginResult = await loginByCode(code);
    if (loginResult.code === 1) {
      return loginResult;
    }
  }
  return false;
}

// 微信公众号绑定
async function bind(code = '') {
  // 获取绑定地址
  if (code === '') {
    const loginResult = await getLoginUrl('bind');
    if (loginResult.code === 1 && loginResult.data.login_url) {
      uni.setStorageSync('returnUrl', location.href);
      window.location = loginResult.data.login_url;
    }
  } else {
    // 解密code发起登陆
    const loginResult = await bindByCode(code);
    if (loginResult.code === 1) {
      return loginResult;
    }
  }
  return false;
}

// 微信公众号解除绑定
async function unbind() {
  const { code } = await third.wechat.unbind({
    platform: 'officialAccount',
  });
  return !error;
}

async function transfer(tansferData) {
  $wxsdk.requestMerchantTransfer(tansferData, {
    success: function (res) {
      console.log('提现成功', res);
      uni.showModal({
        title: '操作成功',
        content: '您的提现申请已成功提交,请在提现记录中查看进度',
        cancelText: '继续提现',
        confirmText: '查看记录',
        success: function (res) {
          res.confirm && $router.go('/pages/pay/withdraw-log');
        },
      });
    },
    fail: function (err) {
      // TODO: 优化错误显示
      $helper.toast(err.err_msg);
    },
  });
}

// 获取公众号登陆地址
function getLoginUrl(event = 'login') {
  let page = getRootUrl() + 'pages/index/login';

  return third.wechat.oauthLogin({
    platform: 'officialAccount',
    payload: encodeURIComponent(
      JSON.stringify({
        page,
        event,
      }),
    ),
  });
}

// 此处使用前端发送code在后端解密，防止用户在后端过长时间停留
function loginByCode(code) {
  return third.wechat.login(
    { code },
    {
      platform: 'officialAccount',
      shareInfo: uni.getStorageSync('shareLog') || {},
    },
  );
}

// 此处使用前端发送code在后端解密，防止用户在后端过长时间停留
function bindByCode(code) {
  return third.wechat.bind(
    { code },
    {
      platform: 'officialAccount',
    },
  );
}

export default {
  load,
  login,
  bind,
  unbind,
  transfer,
  jssdk: $wxsdk,
};
