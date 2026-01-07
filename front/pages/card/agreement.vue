<template>
  <s-layout title="协议签署" navbar="inner" :bgStyle="{ color: '#F8F9FA' }">
    <view class="agreement-page">
      <!-- 步骤指示器 -->
      <view class="step-indicator">
        <text class="step-num">步骤 1/9</text>
        <text class="step-name">协议签署</text>
      </view>

      <!-- 协议内容 -->
      <view class="agreement-content">
        <view class="agreement-title">财富金卡使用协议</view>
        
        <view class="agreement-text">
          <text class="agreement-section">第一条 协议目的</text>
          <text class="agreement-paragraph">
            本协议旨在明确财富金卡持卡人与发卡方之间的权利和义务，保障双方合法权益。
          </text>

          <text class="agreement-section">第二条 金卡功能</text>
          <text class="agreement-paragraph">
            财富金卡为持卡人提供以下服务：
            \n1. 大额收付款功能
            \n2. 专属理财通道
            \n3. VIP客户服务
            \n4. 积分奖励计划
          </text>

          <text class="agreement-section">第三条 持卡人义务</text>
          <text class="agreement-paragraph">
            持卡人应当：
            \n1. 提供真实有效的身份信息
            \n2. 妥善保管卡片及密码
            \n3. 按规定完成实名认证
            \n4. 遵守相关法律法规
          </text>

          <text class="agreement-section">第四条 费用说明</text>
          <text class="agreement-paragraph">
            1. 金卡开卡需支付工本费300元
            \n2. 所有费用在完成全部流程后退还
            \n3. 如审核未通过，已支付费用原路退回
          </text>

          <text class="agreement-section">第五条 隐私保护</text>
          <text class="agreement-paragraph">
            我们承诺严格保护您的个人信息，不会向第三方泄露或出售。所有信息仅用于金卡申请及后续服务。
          </text>

          <text class="agreement-section">第六条 协议变更</text>
          <text class="agreement-paragraph">
            如协议条款发生变更，我们将通过站内信、短信等方式通知您。继续使用金卡服务即视为同意变更后的协议。
          </text>

          <text class="agreement-section">第七条 其他条款</text>
          <text class="agreement-paragraph">
            本协议未尽事宜，按照国家相关法律法规执行。如有争议，双方应友好协商解决。
          </text>
        </view>

        <!-- 阅读时长提示 -->
        <view class="read-timer" v-if="!state.canAgree">
          <uni-icons type="info" size="14" color="#FF9800" />
          <text>请仔细阅读协议内容 {{ state.remainTime }}s</text>
        </view>
      </view>

      <!-- 用户信息确认 -->
      <view class="user-info-card">
        <view class="card-title">
          <uni-icons type="person" size="18" color="#667EEA" />
          <text>请确认您的信息</text>
        </view>
        
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">真实姓名</text>
            <text class="info-value">{{ state.userInfo.realname || '未设置' }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">身份证号</text>
            <text class="info-value">{{ state.userInfo.idcard || '未设置' }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">手机号码</text>
            <text class="info-value">{{ state.userInfo.mobile }}</text>
          </view>
        </view>

        <view class="info-tips">
          <uni-icons type="info-filled" size="14" color="#1890FF" />
          <text>请确保信息真实有效，后续将用于实名认证</text>
        </view>
      </view>

      <!-- 同意协议 -->
      <view class="agree-section">
        <checkbox-group @change="handleAgreeChange">
          <label class="agree-checkbox">
            <checkbox 
              :checked="state.agreed" 
              :disabled="!state.canAgree"
              color="#667EEA" 
            />
            <text>我已阅读并同意《财富金卡使用协议》</text>
          </label>
        </checkbox-group>
      </view>
    </view>

    <!-- 底部按钮 -->
    <template v-slot:footer>
      <view class="footer-buttons">
        <button 
          class="submit-button" 
          :disabled="!state.agreed || state.submitting"
          :loading="state.submitting"
          @tap="handleSubmit"
        >
          {{ state.submitting ? '提交中...' : '同意并继续' }}
        </button>
        <view class="footer-tips">
          <text>点击继续即表示您已阅读并同意本协议</text>
        </view>
      </view>
    </template>
  </s-layout>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

const state = reactive({
  step: 1,
  userInfo: {},
  agreed: false,
  canAgree: false,
  remainTime: 10, // 阅读时长要求（秒）
  submitting: false,
});

let readTimer = null;

onLoad((options) => {
  if (options.step) {
    state.step = options.step;
  }
  loadUserInfo();
});

onMounted(() => {
  startReadTimer();
});

// 加载用户信息
async function loadUserInfo() {
  try {
    const userInfo = xxep.$store('user').userInfo;
    state.userInfo = {
      realname: userInfo.realname,
      idcard: userInfo.idcard,
      mobile: userInfo.mobile,
    };
  } catch (error) {
    console.error('加载用户信息失败:', error);
  }
}

// 开始阅读计时
function startReadTimer() {
  readTimer = setInterval(() => {
    if (state.remainTime > 0) {
      state.remainTime--;
    } else {
      state.canAgree = true;
      clearInterval(readTimer);
    }
  }, 1000);
}

// 同意协议变更
function handleAgreeChange(e) {
  state.agreed = e.detail.value.length > 0;
}

// 提交
async function handleSubmit() {
  if (!state.agreed) {
    xxep.$helper.toast('请先同意协议');
    return;
  }

  // 检查用户信息是否完整
  if (!state.userInfo.realname || !state.userInfo.idcard) {
    uni.showModal({
      title: '提示',
      content: '请先完善个人信息（真实姓名和身份证号）',
      success: (res) => {
        if (res.confirm) {
          uni.navigateTo({
            url: '/pages/user/info',
          });
        }
      },
    });
    return;
  }

  state.submitting = true;

  try {
    // 先创建支付订单
    const { code, data, msg } = await xxep.$api.card.createOrder({
      step: state.step,
    });

    if (code === 1) {
      // 订单创建成功，跳转到支付页面
      uni.redirectTo({
        url: `/pages/card/payment?order_id=${data.order.id}&step=${state.step}`,
      });
    } else {
      xxep.$helper.toast(msg || '创建订单失败');
      state.submitting = false;
    }
  } catch (error) {
    console.error('提交失败:', error);
    xxep.$helper.toast('提交失败，请重试');
    state.submitting = false;
  }
}

// 页面卸载时清除定时器
onUnmounted(() => {
  if (readTimer) {
    clearInterval(readTimer);
  }
});
</script>

<style lang="scss" scoped>
.agreement-page {
  padding: 20rpx;
  padding-bottom: 300rpx;
}

.step-indicator {
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.step-num {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
}

.step-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
}

.agreement-content {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
  max-height: 800rpx;
  overflow-y: scroll;
}

.agreement-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #333333;
  text-align: center;
  margin-bottom: 40rpx;
}

.agreement-text {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.agreement-section {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
  margin-top: 20rpx;
}

.agreement-paragraph {
  font-size: 26rpx;
  color: #666666;
  line-height: 1.8;
  text-align: justify;
}

.read-timer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  margin-top: 30rpx;
  padding: 20rpx;
  background: #FFF9E6;
  border-radius: 12rpx;

  text {
    font-size: 24rpx;
    color: #FF9800;
  }
}

.user-info-card {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 24rpx;

  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
  }
}

.info-list {
  margin-bottom: 20rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1px solid #F0F0F0;

  &:last-child {
    border-bottom: none;
  }
}

.info-label {
  font-size: 28rpx;
  color: #666666;
}

.info-value {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
}

.info-tips {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 16rpx;
  background: #E6F7FF;
  border-radius: 8rpx;

  text {
    font-size: 24rpx;
    color: #1890FF;
    line-height: 1.6;
  }
}

.agree-section {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 30rpx;
}

.agree-checkbox {
  display: flex;
  align-items: center;
  gap: 12rpx;

  text {
    font-size: 26rpx;
    color: #666666;
    line-height: 1.6;
  }
}

.footer-buttons {
  padding: 20rpx 30rpx;
  background: #FFFFFF;
  border-top: 1px solid #F0F0F0;
}

.submit-button {
  width: 100%;
  height: 88rpx;
  background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-bottom: 16rpx;

  &[disabled] {
    opacity: 0.6;
  }
}

.footer-tips {
  text-align: center;

  text {
    font-size: 22rpx;
    color: #999999;
  }
}
</style>

