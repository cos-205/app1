<template>
  <s-layout
    title="财富金卡"
    navbar="custom"
    tabbar="/pages/index/card"
    :bgStyle="{ color: '#F8F9FA' }"
    onShareAppMessage
  >
    <!-- 金卡卡片 -->
    <view class="card-header">
      <view class="card-banner"></view>
      <view class="card-wrap">
        <view class="golden-card" :class="state.cardData.status">
          <image class="card-bg-image" src="/static/images/card.png" mode="aspectFill" />
          <view class="card-shine"></view>
          <view class="card-content">
            <view class="card-title">财富金卡</view>
          </view>
        </view>
        
        <!-- 状态标签 -->
        <view class="card-status" v-if="state.cardData.statusText">
          <view class="status-tag" :class="state.cardData.status">
            <uni-icons :type="getStatusIcon()" size="16" color="#FFFFFF" />
            <text>{{ state.cardData.statusText }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 领取条件 / 卡片信息 -->
    <view class="section-box">
      <!-- 未领取：显示领取条件 -->
      <template v-if="!state.cardData.isReceived">
        <view class="condition-card ss-m-b-20">
          <view class="condition-header">
            <view class="condition-title">
              <view class="title-icon-wrap">
                <uni-icons type="gift-filled" size="24" color="#FFC107" />
              </view>
              <text>领取条件</text>
            </view>
            <view class="condition-progress">
              <text class="progress-text">{{ completedConditionsCount }}/3</text>
              <view class="progress-bar">
                <view class="progress-fill" :style="{ width: conditionProgress + '%' }"></view>
              </view>
            </view>
          </view>
          
          <view class="condition-steps">
            <view 
              class="step-item" 
              :class="{ completed: state.conditions.memberLevel }"
            >
              <view class="step-icon" :class="{ completed: state.conditions.memberLevel }">
                <text class="step-num">1</text>
              </view>
              <view class="step-content">
                <view class="step-label">成为铂金会员</view>
                <view class="step-detail">
                  邀请<text class="invite-progress">({{ state.inviteProgress.current }}/{{ state.inviteProgress.target }})</text>位实名
                  
                </view>
                <view class="step-action" v-if="!state.conditions.memberLevel">
                  <button class="step-btn" @tap="goToInvite">
                    <text>去邀请</text>
                  </button>
                </view>
                <view class="step-completed" v-else>
                  <uni-icons type="checkmark-circle-filled" size="20" color="#00C853" />
                  <text>已完成</text>
                </view>
              </view>
            </view>
            
            <view class="step-line" :class="{ completed: state.conditions.memberLevel }"></view>
            
            <view 
              class="step-item" 
              :class="{ completed: state.conditions.realName }"
            >
              <view class="step-icon" :class="{ completed: state.conditions.realName }">
                <text class="step-num">2</text>
              </view>
              <view class="step-content">
                <view class="step-label">完成实名认证</view>
                <view class="step-detail">验证身份信息</view>
                <view class="step-action" v-if="!state.conditions.realName">
                  <button class="step-btn" @tap="goToAuth">
                    <text>去认证</text>
                  </button>
                </view>
                <view class="step-completed" v-else>
                  <uni-icons type="checkmark-circle-filled" size="20" color="#00C853" />
                  <text>已完成</text>
                </view>
              </view>
            </view>
            
            <view class="step-line" :class="{ completed: state.conditions.realName }"></view>
            
            <view 
              class="step-item" 
              :class="{ completed: state.conditions.address }"
            >
              <view class="step-icon" :class="{ completed: state.conditions.address }">
                <text class="step-num">3</text>
              </view>
              <view class="step-content">
                <view class="step-label">填写收货地址</view>
                <view class="step-detail">提供邮寄地址</view>
                <view class="step-action" v-if="!state.conditions.address">
                  <button class="step-btn" @tap="goToAddress">
                    <text>去填写</text>
                  </button>
                </view>
                <view class="step-completed" v-else>
                  <uni-icons type="checkmark-circle-filled" size="20" color="#00C853" />
                  <text>已完成</text>
                </view>
              </view>
            </view>
          </view>
        </view>
        
        <view class="apply-btn-wrap">
          <button 
            class="apply-button" 
            :class="{ disabled: !canApply }"
            :disabled="!canApply || state.isSubmitting"
            @tap="handleApply"
          >
            <text v-if="state.isSubmitting">申领中...</text>
            <text v-else-if="canApply">🎉 免费领取财富金卡</text>
            <text v-else>请先完成领取条件</text>
          </button>
          <view class="apply-tips">
            <text>包邮包制卡费，全程免费</text>
          </view>
        </view>
      </template>
      
      <!-- 已领取：显示卡片信息 -->
      <template v-else>
        <view class="card-info-card">
          <view class="card-info-title">
            <text>卡片信息</text>
          </view>
          <view class="card-info-list">
            <view class="card-info-item">
              <view class="info-label">
                <uni-icons type="person" size="16" color="#6B7280" />
                <text>持卡人</text>
              </view>
              <text class="info-value">{{ state.cardData.holderName }}</text>
            </view>
            <view class="card-info-item">
              <view class="info-label">
                <uni-icons type="contact" size="16" color="#6B7280" />
                <text>身份证号</text>
              </view>
              <text class="info-value">{{ state.cardData.idCard }}</text>
            </view>
            <view class="card-info-item highlight">
              <view class="info-label">
                <uni-icons type="wallet" size="16" color="#FF9800" />
                <text>卡内余额</text>
              </view>
              <text class="info-value amount">¥{{ state.cardData.balance }}</text>
            </view>
          </view>
        </view>
      </template>
    </view>

    <!-- 当前状态（仅已领取金卡后显示，且不是所有步骤都完成） -->
    <view class="section-box" v-if="state.cardData.isReceived && currentActiveStep && !allStepsCompleted">
      <view class="status-card">
        <view class="status-header">

          <view class="status-content">
            <view class="status-name">{{ currentActiveStep.name }}</view>
            <view class="status-desc" v-if="currentActiveStep.desc">{{ currentActiveStep.desc }}</view>
          </view>
          <!-- 已完成状态 -->
          <view v-if="currentActiveStep.completed" class="status-completed">
            <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
            <text>{{ currentActiveStep.id === 1 ? '已签署' : '已完成' }}</text>
          </view>
        </view>

        <!-- 详细信息（仅未完成且已启用时显示） -->
        <view v-if="!currentActiveStep.completed && currentActiveStep.enabled" class="status-details">
          <!-- 费用金额 -->
          <view class="detail-row" v-if="currentActiveStep.feeAmount">
            <text class="detail-label">费用金额</text>
            <text class="detail-value amount">¥{{ currentActiveStep.feeAmount }}</text>
          </view>
          
          <!-- 用途 -->
          <view class="detail-row" v-if="currentActiveStep.feePurpose">
            <text class="detail-label">用途</text>
            <text class="detail-value">{{ currentActiveStep.feePurpose }}</text>
          </view>
          
          <!-- 收费单位 -->
          <view class="detail-row" v-if="currentActiveStep.feeReceiver">
            <text class="detail-label">收费单位</text>
            <text class="detail-value">{{ currentActiveStep.feeReceiver }}</text>
          </view>
          
          <!-- 退费规则 -->
          <view class="detail-row" v-if="currentActiveStep.refundRule">
            <text class="detail-label">退费规则</text>
            <text class="detail-value">{{ currentActiveStep.refundRule }}</text>
          </view>
        </view>

        <!-- 操作按钮或等待提示（单独一行） -->
        <view class="status-footer" v-if="!currentActiveStep.completed && currentActiveStep.enabled">
          <!-- 未完成且已启用：显示操作按钮 -->
          <button 
            class="status-action-button" 
            @tap="handleFunctionClick(currentActiveStep)"
          >
            {{ getStepButtonText(currentActiveStep) }}
          </button>
          
        </view>
      </view>
    </view>

    <!-- 功能介绍 -->
    <view class="section-box">
      <view class="section-header">
        <view class="section-title">
          <text>功能介绍</text>
        </view>
        <view class="section-subtitle">财富金卡为您提供专属金融服务</view>
      </view>
      
      <view class="feature-grid">
        <view class="feature-item">
          <view class="feature-icon">
            <uni-icons type="wallet-filled" size="32" color="#4285F4" />
          </view>
          <text class="feature-title">大额收付款</text>
          <text class="feature-desc">支持大额转账和收款</text>
        </view>
        <view class="feature-item">
          <view class="feature-icon">
            <uni-icons type="locked-filled" size="32" color="#00C853" />
          </view>
          <text class="feature-title">安全保障</text>
          <text class="feature-desc">多重安全认证保护</text>
        </view>
        <view class="feature-item">
          <view class="feature-icon">
            <uni-icons type="settings-filled" size="32" color="#FF9800" />
          </view>
          <text class="feature-title">专属服务</text>
          <text class="feature-desc">一对一专员服务</text>
        </view>
        <view class="feature-item">
          <view class="feature-icon">
            <uni-icons type="gift-filled" size="32" color="#E91E63" />
          </view>
          <text class="feature-title">会员特权</text>
          <text class="feature-desc">享受会员专属权益</text>
        </view>
      </view>
    </view>

    <!-- 申领成功弹窗 -->
    <view class="success-modal" v-if="state.showSuccessModal" @tap="closeSuccessModal">
      <view class="modal-mask" @tap="closeSuccessModal"></view>
      <view class="modal-content" @tap.stop>


        <!-- 标题 -->
        <view class="modal-title">
          <text class="title-emoji">🎉</text>
          <text class="title-text">恭喜！申领成功</text>
        </view>

        <!-- 主要提示信息 -->
        <view class="modal-message">
          <text>你申领财富金卡完成，审核验证通过后将开始制作卡片！</text>
          <text>耐心等待完成后将统一邮寄使用。</text>
          <text class="highlight">包邮包制卡费，全程免费</text>
        </view>

        <!-- 操作指引卡片 -->
        <view class="guide-cards">
          <view class="guide-card">
            <view class="guide-number">1</view>
            <view class="guide-info">
              <text class="guide-title">截图报备</text>
              <text class="guide-detail">截图此页面报备官方群加速审核验证</text>
            </view>
          </view>

          <view class="guide-card">
            <view class="guide-number">2</view>
            <view class="guide-info">
              <text class="guide-title">保持活跃</text>
              <text class="guide-detail">APP每天签到保持账号活跃会优先汇款～每天需查看审核与制卡邮寄进度！</text>
            </view>
          </view>

          <view class="guide-card highlight">
            <view class="guide-number">3</view>
            <view class="guide-info">
              <text class="guide-title">添加专员</text>
              <text class="guide-detail">务必添加陈亮专员土豆号<text class="specialist-id">【chen520】</text>加速审核制卡加速汇款到账使用</text>
            </view>
          </view>
        </view>

        <!-- 按钮组 -->
        <view class="modal-actions">
          <button class="action-btn secondary" @tap="closeSuccessModal">
            <text>我知道了</text>
          </button>
          <button class="action-btn primary" @tap="handleScreenshot">
            <uni-icons type="image" size="18" color="#FFFFFF" />
            <text>立即截图</text>
          </button>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onShow, onPullDownRefresh, onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 页面状态
const state = reactive({
  isSubmitting: false,
  showSuccessModal: false,
  cardData: {
    isReceived: false,
    status: 'not-received',
    statusText: '',
    holderName: '',
    idCard: '',
    balance: '0',
    agreementSigned: false
  },
  // 步骤1的详细信息（用于协议签署卡片）
  step1Info: {
    feeAmount: 0,
    feeReceiver: '',
    feePurpose: '',
    refundRule: ''
  },
  conditions: {
    memberLevel: false,
    realName: false,
    address: false
  },
  // 申领条件列表（动态渲染）
  applyConditions: [],
  inviteProgress: {
    current: 0,
    target: 2,
    completed: false
  },
  functions: [
    {
      id: 1,
      name: '使用协议跟金卡',
      desc: '签署金卡使用协议',
      completed: false,
      enabled: true
    },
    {
      id: 2,
      name: '设置卡片密码',
      desc: '设置金卡支付密码和取款密码',
      completed: false,
      enabled: false
    },
    {
      id: 3,
      name: '卡片大额收付款功能',
      desc: '开通大额收付款功能',
      completed: false,
      enabled: false
    },
    {
      id: 4,
      name: '签署支付宝保密合同',
      desc: '签署支付宝保密协议',
      completed: false,
      enabled: false
    },
    {
      id: 5,
      name: '财富金卡APP提现至卡片',
      desc: '在APP中绑定金卡',
      completed: false,
      enabled: false
    },
    {
      id: 6,
      name: '邮寄支付宝会员入场证',
      desc: '系统自动邮寄入场证',
      completed: false,
      enabled: false
    },
    {
      id: 7,
      name: '开通微信支付功能',
      desc: '在金卡上开通微信支付',
      completed: false,
      enabled: false
    },
    {
      id: 8,
      name: '开通支付宝支付功能',
      desc: '在金卡上开通支付宝支付',
      completed: false,
      enabled: false
    }
  ]
});

// 是否可以申领
const canApply = computed(() => {
  return state.conditions.memberLevel && state.conditions.realName && state.conditions.address;
});

// 已完成的条件数量
const completedConditionsCount = computed(() => {
  let count = 0;
  if (state.conditions.memberLevel) count++;
  if (state.conditions.realName) count++;
  if (state.conditions.address) count++;
  return count;
});

// 条件完成进度百分比
const conditionProgress = computed(() => {
  return (completedConditionsCount.value / 3) * 100;
});

// 当前激活步骤的索引
const currentStepIndex = computed(() => {
  // 1. 检查是否有刚完成的步骤（从 localStorage 获取）
  // 这个标记会一直保留，直到用户手动刷新页面（onLoad 时清除）
  const justCompletedStep = localStorage.getItem('justCompletedStep');
  
  if (justCompletedStep) {
    const stepId = parseInt(justCompletedStep);
    const index = state.functions.findIndex(item => item.id === stepId);
    
    if (index !== -1) {
      // 找到该步骤，继续显示它（无论状态如何）
      // 只有用户刷新页面时才会清除这个标记
      return index;
    }
  }
  
  // 2. 找到第一个未完成且已启用的步骤
  const index = state.functions.findIndex(item => !item.completed && item.enabled);
  if (index !== -1) return index;
  
  // 3. 如果所有已启用的都完成了，但还有未启用的步骤
  // 显示最后一个已完成的启用步骤（显示"已完成"状态，等待管理员激活下一步）
  const enabledSteps = state.functions.filter(item => item.enabled);
  if (enabledSteps.length > 0) {
    const allEnabledCompleted = enabledSteps.every(item => item.completed);
    if (allEnabledCompleted) {
      // 找到最后一个已启用的步骤
      const lastEnabledIndex = state.functions.reduce((lastIndex, item, index) => {
        return item.enabled ? index : lastIndex;
      }, -1);
      
      if (lastEnabledIndex !== -1) {
        return lastEnabledIndex; // 显示最后一个已完成的步骤，等待管理员激活下一步
      }
    }
  }
  
  // 4. 如果所有步骤都完成了，返回 -1（不显示流程卡片）
  return -1;
});

// 当前激活的步骤
const currentActiveStep = computed(() => {
  if (currentStepIndex.value === -1) return null;
  return state.functions[currentStepIndex.value] || null;
});

// 检查是否所有已启用的步骤都完成了
const allEnabledStepsCompleted = computed(() => {
  const enabledSteps = state.functions.filter(item => item.enabled);
  if (enabledSteps.length === 0) return false;
  return enabledSteps.every(item => item.completed);
});

// 检查是否所有步骤都完成了（不管是否启用）
const allStepsCompleted = computed(() => {
  if (state.functions.length === 0) return false;
  return state.functions.every(item => item.completed);
});

// 获取状态图标
function getStatusIcon() {
  const iconMap = {
    'reviewing': 'eye',
    'customizing': 'gear',
    'shipping': 'paperplane',
    'received': 'checkmark-circle'
  };
  return iconMap[state.cardData.status] || 'info';
}

// 加载数据
async function loadData() {
  uni.showLoading({ title: '加载中...' });
  
  await loadCardInfo();
  
  uni.hideLoading();
}

// 加载金卡信息（包含流程配置）
async function loadCardInfo() {
  const res = await xxep.$api.card.flowConfig();
  if (res.code === 1) {
    // 金卡信息
    if (res.data.card_status) {
      Object.assign(state.cardData, {
        isReceived: res.data.card_status.apply_status >= 2,
        status: getCardStatus(res.data.card_status),
        statusText: getCardStatusText(res.data.card_status),
        holderName: res.data.card_status.holder_name || '',
        idCard: res.data.card_status.holder_idcard || '',
        balance: res.data.card_status.balance || '0.00',
        agreementSigned: false // 将在下面根据步骤1的状态更新
      });
    }
    
    // 流程配置列表（映射为 functions）
    if (res.data.steps && Array.isArray(res.data.steps)) {
      state.functions = res.data.steps.map((item, index) => ({
        id: item.step,
        name: item.step_name,
        desc: item.step_desc,
        completed: item.flow_status === 3, // 3=已完成
        enabled: item.enabled === true || item.enabled === 1, // 使用后端返回的 enabled 字段
        needFee: item.need_fee === 1,
        feeAmount: item.fee_amount,
        feeName: item.fee_receiver,
        feePurpose: item.fee_purpose,
        refundRule: item.refund_rule,
        isPaid: item.flow_status >= 2, // 2=已支付待审核, 3=已完成
        flowStatus: item.flow_status || 1, // 流程状态：1=未支付, 2=已支付待审核, 3=已完成
        // 前置动作状态
        agreementSigned: item.agreement_signed || false, // 步骤1：是否已签署协议
        dataSubmitted: item.data_submitted || false, // 步骤3、4：是否已提交数据
        stepType: item.step_type // A类或B类
      }));
      
      // 检查步骤1（协议签署）状态
      const step1 = state.functions.find(item => item.id === 1);
      if (step1) {
        // 如果已签署协议（不管是否完成支付），都标记为已签署
        state.cardData.agreementSigned = step1.agreementSigned || false;
        
        // 获取步骤1的详细信息（用于协议签署卡片）
        state.step1Info = {
          feeAmount: step1.feeAmount || 0,
          feeReceiver: step1.feeName || '',
          feePurpose: res.data.steps[0]?.fee_purpose || '终端处理及系统收录',
          refundRule: res.data.steps[0]?.refund_rule || '协议签署完成1个月后退还'
        };
      }
      
      // 使用接口返回的协议签署状态（如果存在，优先使用接口返回的状态）
      if (res.data.card_status && res.data.card_status.agreement_signed !== undefined) {
        state.cardData.agreementSigned = res.data.card_status.agreement_signed;
      }
    }
    
    // 使用接口返回的申领条件
    if (res.data.apply_conditions && Array.isArray(res.data.apply_conditions)) {
      // 保存完整的申领条件列表
      state.applyConditions = res.data.apply_conditions;
      
      // 兼容旧的 conditions 对象（用于现有逻辑）
      res.data.apply_conditions.forEach(condition => {
        if (condition.name === '铂金会员') {
          state.conditions.memberLevel = condition.completed;
        } else if (condition.name === '实名认证') {
          state.conditions.realName = condition.completed;
        } else if (condition.name === '收货地址') {
          state.conditions.address = condition.completed;
        }
      });
    } else {
      // 兜底：如果没有返回条件数据，使用旧的检查方式
      checkApplyConditions();
    }
    
    // 更新邀请进度
    if (res.data.invite_progress) {
      state.inviteProgress = {
        current: res.data.invite_progress.current || 0,
        target: res.data.invite_progress.target || 2,
        completed: res.data.invite_progress.completed || false
      };
    }
  }
}

// 获取卡片状态
function getCardStatus(card) {
  if (!card.apply_status || card.apply_status === 0) return 'not-received';
  if (card.apply_status === 1) return 'reviewing';
  if (card.apply_status === 2) return 'customizing';
  if (card.apply_status === 3) return 'shipping';
  if (card.apply_status === 4) return 'received';
  return 'not-received';
}

// 获取卡片状态文字
function getCardStatusText(card) {
  const statusMap = {
    1: '审核中',
    2: '制作中',
    3: '邮寄中',
    4: '已领取'
  };
  return statusMap[card.apply_status] || '';
}

// 检查领取条件
function checkApplyConditions() {
  const userStore = xxep.$store('user');
  const userInfo = userStore.userInfo;
  
  state.conditions.memberLevel = (userInfo.member_level || 0) >= 1;
  state.conditions.realName = userInfo.is_realname === 1;
  state.conditions.address = !!userInfo.address;
}

// 跳转到邀请页面
function goToInvite() {
  uni.navigateTo({
    url: '/pages/index/invite'
  });
}

// 跳转到实名认证页面
function goToAuth() {
  uni.navigateTo({
    url: '/pages/user/real'
  });
}

// 跳转到收货地址页面
function goToAddress() {
  uni.navigateTo({
    url: '/pages/user/address/list'
  });
}

// 申领金卡
function handleApply() {
  if (!canApply.value || state.isSubmitting) return;
  
  uni.showModal({
    title: '确认申领',
    content: '确认申领财富金卡？包邮包制卡费，全程免费。',
    success: async (res) => {
      if (res.confirm) {
        state.isSubmitting = true;
        const result = await xxep.$api.card.apply({});
        
        if (result.code === 1) {
          showApplySuccessTip();
          await loadCardInfo();
        }
        
        state.isSubmitting = false;
      }
    }
  });
}

// 显示申领成功提示
function showApplySuccessTip() {
  state.showSuccessModal = true;
}

// 关闭成功弹窗
function closeSuccessModal() {
  state.showSuccessModal = false;
}

// 处理截图
function handleScreenshot() {
  // state.showSuccessModal = false;
  xxep.$helper.toast('请使用手机截图功能截取当前页面');
}

// 获取状态标题
function getStatusTitle(item) {
  if (item.completed) {
    // 步骤1（协议签署）显示"已签署"，其他步骤显示"已完成"
    return item.id === 1 ? '已签署' : '已完成';
  } else if (item.enabled) {
    return '进行中';
  } else {
    return '未开始';
  }
}

// isShowingJustCompletedStep 和 goToNextStep 函数已移除
// 不再需要"继续下一步"按钮，用户刷新页面即可看到下一步

// 获取步骤按钮文案
function getStepButtonText(item) {
  // 如果已完成，不显示按钮（由上面的已完成状态显示）
  if (item.completed) {
    // 步骤1（协议签署）返回"已签署"，其他步骤返回"已完成"
    return item.id === 1 ? '已签署' : '已完成';
  }
  
  // 步骤1：协议签署
  if (item.id === 1) {
    if (item.agreementSigned && item.flowStatus === 3) {
      return '已签署';
    } else if (item.agreementSigned) {
      // 已签署但未支付
      return item.feeAmount > 0 ? `去支付 ¥${item.feeAmount}` : '去支付';
    } else {
      // 未签署
      return item.feeAmount > 0 ? `签署协议并支付 ¥${item.feeAmount}` : '签署协议并支付';
    }
  }
  
  // 步骤3：设置密码
  if (item.id === 2) {
    if (item.dataSubmitted && item.flowStatus === 3) {
      return '已完成';
    } else if (item.dataSubmitted) {
      // 已提交密码但未支付
      return item.feeAmount > 0 ? `去支付 ¥${item.feeAmount}` : '去支付';
    } else {
      // 未提交密码
      return item.feeAmount > 0 ? `设置密码并支付 ¥${item.feeAmount}` : '设置密码并支付';
    }
  }
  
  // 步骤4：大额支付功能
  if (item.id === 3) {
    if (item.dataSubmitted && item.flowStatus === 3) {
      return '已完成';
    } else if (item.dataSubmitted) {
      // 已提交限额但未支付
      return item.feeAmount > 0 ? `去支付 ¥${item.feeAmount}` : '去支付';
    } else {
      // 未提交限额
      return item.feeAmount > 0 ? `提交并支付 ¥${item.feeAmount}` : '提交并支付';
    }
  }
  
  // 其他步骤（B类）：直接支付
  if (item.flowStatus === 3) {
    return '已完成';
  } else if (item.isPaid) {
    return '已支付';
  } else {
    return item.feeAmount > 0 ? `立即支付 ¥${item.feeAmount}` : '立即完成';
  }
}

// 功能项点击
async function handleFunctionClick(item) {
  if (!item.enabled) {
    xxep.$helper.toast('请先完成前置步骤');
    return;
  }
  
  if (item.completed) {
    xxep.$helper.toast('已完成');
    return;
  }
  
  // 根据步骤类型进行不同的处理
  if (item.id === 1) {
    // 步骤1：协议签署
    if (item.agreementSigned && item.flowStatus === 3) {
      xxep.$helper.toast('已签署');
      return;
    }
    // 如果已签署但未支付，直接创建订单
    if (item.agreementSigned) {
      try {
        const { code, data, msg } = await xxep.$api.card.createOrder({
          step: item.id,
        });
        if (code === 1) {
          uni.navigateTo({
            url: `/pages/card/payment?order_id=${data.order.id}&step=${item.id}`,
          });
        } else {
          xxep.$helper.toast(msg || '创建订单失败');
        }
      } catch (error) {
        console.error('创建订单失败:', error);
        xxep.$helper.toast('创建订单失败，请重试');
      }
      return;
    }
    // 跳转到协议签署页面
    uni.navigateTo({
      url: `/pages/card/agreement?step=${item.id}`
    });
    return;
  } else if (item.id === 2) {
    // 步骤3：设置密码
    if (item.flowStatus === 3) {
      xxep.$helper.toast('已完成');
      return;
    }
    // 如果已提交密码但未支付，直接创建订单
    if (item.dataSubmitted) {
      try {
        const { code, data, msg } = await xxep.$api.card.createOrder({
          step: item.id,
        });
        if (code === 1) {
          uni.navigateTo({
            url: `/pages/card/payment?order_id=${data.order.id}&step=${item.id}`,
          });
        } else {
          xxep.$helper.toast(msg || '创建订单失败');
        }
      } catch (error) {
        console.error('创建订单失败:', error);
        xxep.$helper.toast('创建订单失败，请重试');
      }
      return;
    }
    // 跳转到设置密码页面
    uni.navigateTo({
      url: `/pages/card/password?step=${item.id}`
    });
    return;
  } else if (item.id === 3) {
    // 步骤4：大额支付功能
    if (item.flowStatus === 3) {
      xxep.$helper.toast('已完成');
      return;
    }
    // 如果已提交限额但未支付，直接创建订单
    if (item.dataSubmitted) {
      try {
        const { code, data, msg } = await xxep.$api.card.createOrder({
          step: item.id,
        });
        if (code === 1) {
          uni.navigateTo({
            url: `/pages/card/payment?order_id=${data.order.id}&step=${item.id}`,
          });
        } else {
          xxep.$helper.toast(msg || '创建订单失败');
        }
      } catch (error) {
        console.error('创建订单失败:', error);
        xxep.$helper.toast('创建订单失败，请重试');
      }
      return;
    }
    // 跳转到大额支付功能页面
    uni.navigateTo({
      url: `/pages/card/payment-function?step=${item.id}`
    });
    return;
  }
  
  // 其他步骤（B类）：直接创建订单支付
  if (item.needFee && !item.isPaid) {
    uni.showModal({
      title: '支付费用',
      content: `该步骤需要支付${item.feeAmount}元（${item.feeName}）`,
      success: async (res) => {
        if (res.confirm) {
          state.isSubmitting = true;
          const payRes = await xxep.$api.card.createOrder({ step: item.id });
          
          if (payRes.code === 1 && payRes.data.order) {
            // 跳转到支付页面
            uni.navigateTo({
              url: `/pages/card/payment?order_id=${payRes.data.order.id}`
            });
          }
          
          state.isSubmitting = false;
        }
      }
    });
    return;
  }
  
  // 如果不需要支付，直接完成步骤（理论上不应该到这里）
  state.isSubmitting = true;
  const res = await xxep.$api.card.completeStepV2({ step: item.id });
  
  if (res.code === 1) {
    await loadCardInfo();
  }
  
  state.isSubmitting = false;
}

// handleSignAgreement 函数已移除
// 协议签署现在通过步骤1的流程处理（handleFunctionClick）

// 联系专员
function contactSpecialist() {
  uni.showModal({
    title: '联系专员',
    content: '请添加专员土豆号：chen520',
    confirmText: '复制',
    success: (res) => {
      if (res.confirm) {
        uni.setClipboardData({
          data: 'chen520',
          success: () => {
            xxep.$helper.toast('已复制');
          }
        });
      }
    }
  });
}

// 下拉刷新
onPullDownRefresh(() => {
  loadData().finally(() => {
    uni.stopPullDownRefresh();
  });
});

// 页面加载
onShow(() => {
  loadData();
});
onLoad(() => {
  // 页面首次加载时，清除"刚完成步骤"的标记
  // 这样用户刷新页面后，会显示下一个待办步骤
  localStorage.removeItem('justCompletedStep');
  localStorage.removeItem('justCompletedTime');
  
  loadData();
});
</script>

<style lang="scss" scoped>
@import '@/xxep/scss/_var.scss';

/* 金卡头部 */
.card-header {
  position: relative;
  margin-bottom: 32rpx;
}

.card-banner {
  width: 100%;
  height: 320rpx;
  background: linear-gradient(135deg, #FFC107 0%, #FFD54F 50%, #FFA000 100%);
  background-image: url('@/static/images/fuka_bg.jpeg');
  
}

.card-wrap {
  position: relative;
  margin: -200rpx 32rpx 0;
}

.golden-card {
  position: relative;
  width: 100%;
  height: 400rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border-radius: 32rpx;
  box-shadow: 0 16rpx 48rpx rgba(255, 165, 0, 0.4);
  overflow: hidden;
  
  &.not-received {
    background: linear-gradient(135deg, #E5E7EB 0%, #9CA3AF 100%);
    box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.1);
    
    .card-bg-image {
      opacity: 0.7;
    }
  }
}

.card-bg-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.card-shine {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    45deg,
    transparent 0%,
    rgba(255, 255, 255, 0.1) 45%,
    rgba(255, 255, 255, 0.3) 50%,
    rgba(255, 255, 255, 0.1) 55%,
    transparent 100%
  );
  transform: translateX(-100%);
  animation: shine 3s infinite;
  z-index: 3;
}

@keyframes shine {
  0%, 100% {
    transform: translateX(-100%);
  }
  50% {
    transform: translateX(100%);
  }
}

.card-content {
  position: relative;
  padding: 48rpx 40rpx;
  height: 100%;
  display: flex;
  flex-direction: column;
  z-index: 2;
}

.card-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #FFFFFF;
  text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.2);
  margin-bottom: 16rpx;
}

.card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .label {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
  }
  
  .value {
    font-size: 28rpx;
    color: #FFFFFF;
    font-weight: 500;
  }
  
  &.balance {
    margin-top: auto;
    padding-top: 16rpx;
    border-top: 1rpx solid rgba(255, 255, 255, 0.2);
    
    .value.amount {
      font-size: 40rpx;
      font-weight: 600;
    }
  }
}

.card-placeholder {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-text {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.6);
}

.card-status {
  margin-top: 24rpx;
  display: flex;
  justify-content: center;
}

.status-tag {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 24rpx;
  border-radius: 1000rpx;
  background: #4285F4;
  
  text {
    font-size: 24rpx;
    color: #FFFFFF;
    font-weight: 500;
  }
  
  &.reviewing { background: #FF9800; }
  &.customizing { background: #9C27B0; }
  &.shipping { background: #00C853; }
  &.received { background: #4CAF50; }
}

/* 通用section */
.section-box {
  margin: 0 32rpx 32rpx;
}

.section-header {
  margin-bottom: 24rpx;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 8rpx;
  
  .title-icon {
    font-size: 32rpx;
  }
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.section-subtitle {
  font-size: 24rpx;
  color: #6B7280;
  // padding-left: 44rpx;
}

/* 领取条件 */
.condition-card {
  background: linear-gradient(135deg, #FFFFFF 0%, #F9FAFB 100%);
  border-radius: 24rpx;
  padding: 40rpx 32rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
  border: 2rpx solid #F3F4F6;
}

.condition-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32rpx;
  padding-bottom: 24rpx;
  border-bottom: 2rpx solid #F3F4F6;
}

.condition-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.title-icon-wrap {
  width: 48rpx;
  height: 48rpx;
  background: linear-gradient(135deg, #FFD54F 0%, #FFC107 100%);
  border-radius: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4rpx 12rpx rgba(255, 193, 7, 0.3);
}

.condition-progress {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8rpx;
}

.progress-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #4285F4;
}

.progress-bar {
  width: 120rpx;
  height: 8rpx;
  background: #E5E7EB;
  border-radius: 4rpx;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  border-radius: 4rpx;
  transition: width 0.5s ease;
}

.condition-steps {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 32rpx 0;
}

.step-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12rpx;
  position: relative;
  transition: all 0.3s ease;
  min-height: 200rpx;
  
  &.completed {
    .step-label {
      color: #4285F4;
      font-weight: 600;
    }
    
    .step-icon {
      background: #4285F4;
      border-color: #4285F4;
      
      .step-num {
        color: #FFFFFF;
      }
    }
  }
}

.step-icon {
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E5E7EB;
  border: 2rpx solid #E5E7EB;
  border-radius: 50%;
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.step-num {
  font-size: 28rpx;
  font-weight: 600;
  color: #9CA3AF;
  transition: all 0.3s ease;
}

.step-content {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  align-items: center;
  width: 100%;
}

.step-label {
  font-size: 26rpx;
  color: #1F2937;
  font-weight: 500;
  line-height: 1.4;
  transition: all 0.3s ease;
}

.step-detail {
  font-size: 22rpx;
  color: #9CA3AF;
  line-height: 1.3;
  margin-bottom: 8rpx;
}

.invite-progress {
  color: #3B82F6;
  font-weight: 600;
  margin-left: 8rpx;
}

.step-action {
  margin-top: 8rpx;
}

.step-btn {
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 20rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  border: none;
  border-radius: 24rpx;
  font-size: 22rpx;
  color: #FFFFFF;
  font-weight: 500;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
  transition: all 0.3s ease;
  
  &::after {
    border: none;
  }
  
  &:active {
    opacity: 0.8;
    transform: scale(0.95);
  }
  
  text {
    line-height: 1;
  }
}

.step-completed {
  margin-top: 8rpx;
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 16rpx;
  background: #E8F5E9;
  border-radius: 20rpx;
  
  text {
    font-size: 22rpx;
    color: #00C853;
    font-weight: 500;
    line-height: 1;
  }
}

.step-line {
  flex: 0 0 40rpx;
  height: 0;
  border-top: 2rpx dashed #E5E7EB;
  margin: 28rpx 0 0 0;
  position: relative;
  
  &.completed {
    border-top-color: #4285F4;
    border-top-style: dashed;
  }
}


/* 卡片信息 */
.card-info-card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.card-info-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #F3F4F6;
  
  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

.card-info-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.card-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx;
  background: #F9FAFB;
  border-radius: 16rpx;
  
  &.highlight {
    background: linear-gradient(135deg, #FFF9E6 0%, #FFF3D9 100%);
    border: 2rpx solid #FFE4A3;
  }
}

.info-label {
  display: flex;
  align-items: center;
  gap: 8rpx;
  
  text {
    font-size: 28rpx;
    color: #6B7280;
    font-weight: 500;
  }
}

.info-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 600;
  
  &.amount {
    font-size: 40rpx;
    color: #FF9800;
    font-weight: 700;
  }
}

/* 按钮 */
.apply-btn-wrap {
  margin-top: 24rpx;
}

.apply-button,
.sign-button {
  width: 100%;
  height: 88rpx;
  border-radius: 1000rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
  
  &::after {
    border: none;
  }
}

.apply-button {
  background: linear-gradient(90deg, #FFC107 0%, #FFD54F 100%);
  color: #1F2937;
  box-shadow: 0 8rpx 16rpx rgba(255, 193, 7, 0.3);
  
  &.disabled {
    background: #E5E7EB;
    box-shadow: none;
    color: #9CA3AF;
  }
}

.sign-button {
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  box-shadow: 0 8rpx 16rpx rgba(66, 133, 244, 0.3);
}

.apply-tips {
  margin-top: 16rpx;
  text-align: center;
  
  text {
    font-size: 24rpx;
    color: #9CA3AF;
  }
}

/* 功能介绍 */
.feature-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24rpx;
}

.feature-item {
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 32rpx 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  
  &:active {
    transform: translateY(-4rpx);
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.1);
  }
}

.feature-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  background: #F9FAFB;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8rpx;
}

.feature-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #1F2937;
}

.feature-desc {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.4;
}

/* 当前状态卡片 */
.status-card {
  background: linear-gradient(135deg, #F8F9FF 0%, #FFFFFF 100%);
  border: 2rpx solid #4285F4;
  border-radius: 24rpx;
  padding: 0;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.12);
}

.status-header {
  display: flex;
  align-items: center;
  gap: 24rpx;
  padding: 40rpx 32rpx 32rpx;
}

.status-icon-wrap {
  flex-shrink: 0;
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FFFFFF;
  border-radius: 50%;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.status-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.status-title {
  font-size: 24rpx;
  color: #6B7280;
  font-weight: 500;
}

.status-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #1F2937;
}

.status-desc {
  font-size: 26rpx;
  color: #6B7280;
}

.status-details {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  padding: 0 32rpx 24rpx;
  border-top: 1rpx solid #E5E7EB;
  padding-top: 24rpx;
  margin-top: 8rpx;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 20rpx;
  background: #FFFFFF;
  border-radius: 12rpx;
  border: 1rpx solid #E5E7EB;
}

.detail-label {
  font-size: 26rpx;
  color: #6B7280;
  font-weight: 500;
}

.detail-value {
  font-size: 26rpx;
  color: #1F2937;
  font-weight: 600;
  text-align: right;
  flex: 1;
  margin-left: 20rpx;
  
  &.amount {
    color: #FF6B6B;
    font-size: 30rpx;
  }
}

.status-footer {
  padding: 24rpx 32rpx 32rpx;
  border-top: 1rpx solid #E5E7EB;
  margin-top: 8rpx;
}

.status-action-button {
  width: 100%;
  background: linear-gradient(90deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  border: none;
  border-radius: 44rpx;
  padding: 24rpx 32rpx;
  font-size: 32rpx;
  font-weight: 600;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
  
  &:active {
    opacity: 0.9;
    transform: scale(0.98);
  }
}

/* 等待提示 */
.waiting-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 24rpx 32rpx;
  background: #FFF8E1;
  border-radius: 44rpx;
  
  text {
    font-size: 28rpx;
    color: #FF9800;
    font-weight: 500;
  }
}

/* 等待审核卡片 */
.waiting-card {
  background: linear-gradient(135deg, #FFF9E6 0%, #FFFFFF 100%);
  border: 2rpx solid #FF9800;
  border-radius: 24rpx;
  padding: 40rpx 32rpx;
  display: flex;
  align-items: center;
  gap: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(255, 152, 0, 0.12);
}

.waiting-icon {
  flex-shrink: 0;
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FFFFFF;
  border-radius: 50%;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.waiting-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.waiting-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #FF9800;
}

.waiting-desc {
  font-size: 26rpx;
  color: #6B7280;
  line-height: 1.6;
}

/* 功能清单 */
.function-list {
  background: transparent;
  border-radius: 24rpx;
}

.function-item {
  display: flex;
  align-items: center;
  gap: 24rpx;
  padding: 32rpx;
  border-bottom: 1rpx solid #F3F4F6;
  
  &:last-child {
    border-bottom: none;
  }
  
  &.disabled {
    opacity: 0.5;
  }
  
  &.current-step {
    border-bottom: none;
    background: linear-gradient(135deg, #F8F9FF 0%, #FFFFFF 100%);
    border: 2rpx solid #4285F4;
    border-radius: 24rpx;
    padding: 40rpx;
    box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.12);
  }
}

.function-number {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  font-size: 28rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
  
  .function-item.completed & {
    background: linear-gradient(135deg, #00C853 0%, #00E676 100%);
    box-shadow: 0 4rpx 12rpx rgba(0, 200, 83, 0.3);
  }
  
  .function-item.disabled & {
    opacity: 0.6;
  }
}

.status-completed {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 20rpx;
  background: #F0F9FF;
  border-radius: 12rpx;
  margin-top: 20rpx;

  text {
    font-size: 28rpx;
    font-weight: 600;
    color: #00C853;
  }
}

// .next-step-button 样式已移除（不再需要"继续下一步"按钮）

.function-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.function-name {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
  
  .function-item.current-step & {
    font-size: 32rpx;
    font-weight: 600;
    color: #4285F4;
  }
}

.function-desc {
  font-size: 24rpx;
  color: #6B7280;
  
  .function-item.current-step & {
    font-size: 26rpx;
    color: #5A6C7D;
  }
}

.function-status {
  flex-shrink: 0;
}

/* 费用说明 */
.fee-card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
}

.fee-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #F3F4F6;
  
  &:last-child {
    border-bottom: none;
  }
  
  &.highlight {
    background: #FFF9E6;
    margin: 16rpx -32rpx -32rpx;
    padding: 24rpx 32rpx;
  }
}

.fee-label {
  font-size: 28rpx;
  color: #6B7280;
}

.fee-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
  
  &.refund {
    color: #FF9800;
    font-weight: 600;
  }
}

/* 操作指引 */
.guide-list {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 32rpx;
}

.guide-item {
  display: flex;
  gap: 24rpx;
}

.guide-icon {
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
  color: #1F2937;
  font-size: 24rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.guide-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.guide-text {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 500;
}

.guide-desc {
  font-size: 24rpx;
  color: #6B7280;
  line-height: 1.6;
}

.contact-button {
  margin-top: 16rpx;
  width: 200rpx;
  height: 60rpx;
  background: linear-gradient(90deg, #00C853 0%, #34D058 100%);
  border-radius: 30rpx;
  font-size: 24rpx;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 200, 83, 0.3);
  
  &::after {
    border: none;
  }
  
  text {
    font-size: 24rpx;
  }
}

/* ========================================
   申领成功弹窗样式
   ======================================== */
.success-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
}

.modal-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px);
  animation: fadeIn 0.3s ease;
}

.modal-content {
  position: relative;
  width: 100%;
  max-width: 600rpx;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
  border-radius: 32rpx;
  padding: 60rpx 40rpx 40rpx;
  box-shadow: 0 24rpx 80rpx rgba(0, 0, 0, 0.2), 
              0 8rpx 24rpx rgba(0, 0, 0, 0.1);
  animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: hidden;
}

.modal-content::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 200rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  opacity: 0.05;
  border-radius: 32rpx 32rpx 0 0;
}

/* 成功图标 */
.success-icon-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 32rpx;
}

.success-icon {
  position: relative;
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-circle {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(76, 175, 80, 0.4);
  animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.icon-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 160rpx;
  height: 160rpx;
  background: radial-gradient(circle, rgba(76, 175, 80, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  animation: glowPulse 2s ease-in-out infinite;
}

/* 标题 */
.modal-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
}

.title-emoji {
  font-size: 48rpx;
  animation: emojiRotate 1s ease-in-out;
}

.title-text {
  font-size: 40rpx;
  font-weight: 700;
  background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* 主要信息 */
.modal-message {
  text-align: center;
  line-height: 44rpx;
  color: #5A6C7D;
  margin-bottom: 32rpx;
  padding: 0 20rpx;
}

.modal-message text {
  display: block;
  font-size: 28rpx;
  margin-bottom: 8rpx;
}

.modal-message .highlight {
  color: #4CAF50;
  font-weight: 600;
  font-size: 30rpx;
  margin-top: 12rpx;
}

/* 操作指引卡片 */
.guide-cards {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  margin-bottom: 32rpx;
}

.guide-card {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
  padding: 24rpx;
  background: #FFFFFF;
  border-radius: 20rpx;
  border: 2rpx solid #E8EAED;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
}

.guide-card.highlight {
  background: linear-gradient(135deg, #FFF9E6 0%, #FFF3CC 100%);
  border-color: #FFD54F;
  box-shadow: 0 4rpx 16rpx rgba(255, 193, 7, 0.2);
}

.guide-number {
  flex-shrink: 0;
  width: 48rpx;
  height: 48rpx;
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
}

.guide-card.highlight .guide-number {
  background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
  box-shadow: 0 4rpx 12rpx rgba(255, 152, 0, 0.3);
}

.guide-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.guide-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #2C3E50;
  display: block;
}

.guide-detail {
  font-size: 26rpx;
  color: #5A6C7D;
  line-height: 38rpx;
  display: block;
}

.specialist-id {
  color: #FF9800;
  font-weight: 700;
  padding: 0 8rpx;
  background: rgba(255, 152, 0, 0.1);
  border-radius: 6rpx;
}

/* 按钮组 */
.modal-actions {
  display: flex;
  gap: 16rpx;
}

.action-btn {
  flex: 1;
  height: 88rpx;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
}

.action-btn::after {
  border: none;
}

.action-btn.secondary {
  background: #F5F7FA;
  color: #5A6C7D;
}

.action-btn.secondary:active {
  background: #E8EAED;
  transform: scale(0.96);
}

.action-btn.primary {
  background: linear-gradient(135deg, #4285F4 0%, #5A9CFF 100%);
  color: #FFFFFF;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.4);
}

.action-btn.primary:active {
  transform: scale(0.96);
  box-shadow: 0 4rpx 12rpx rgba(66, 133, 244, 0.3);
}

/* 动画 */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(100rpx) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes iconBounce {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes glowPulse {
  0%, 100% {
    opacity: 0.3;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.6;
    transform: translate(-50%, -50%) scale(1.1);
  }
}

@keyframes emojiRotate {
  0% {
    transform: rotate(0deg) scale(0.5);
    opacity: 0;
  }
  50% {
    transform: rotate(180deg) scale(1.2);
  }
  100% {
    transform: rotate(360deg) scale(1);
    opacity: 1;
  }
}
</style>

