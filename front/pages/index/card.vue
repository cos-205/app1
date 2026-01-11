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
      </view>
    </view>

    <!-- 领取条件 / 卡片信息 -->
    <view class="section-box">
      <!-- 审核中：显示审核中状态 -->
      <template v-if="state.cardData.isAuditing">
        <view class="audit-status-card">
          <view class="audit-header">
            <view class="audit-content">
              <view class="audit-title">审核中</view>
              <view class="audit-desc">您的金卡申请正在审核中，请耐心等待</view>
            </view>
          </view>
          <view class="audit-info">
            <view class="audit-item">
              <view class="audit-label-wrap">
                <uni-icons type="calendar" size="16" color="#FF9800" />
                <text class="audit-label">申请时间</text>
              </view>
              <text class="audit-value">{{ state.cardData.applyTimeText || '--' }}</text>
            </view>
            <view class="audit-tips">
              <view class="audit-tips-header">
                <uni-icons type="info-filled" size="20" color="#1976D2" />
                <text class="audit-tips-title">温馨提示</text>
              </view>
              <view class="audit-tips-content">
                <view class="audit-tips-item">
                  <text class="audit-tips-dot">•</text>
                  <text class="audit-tips-text">审核通常需要1-3个工作日</text>
                </view>
              </view>
            </view>
          </view>
        </view>
      </template>
      
      <!-- 制作中：显示制作中状态 -->
      <template v-else-if="state.cardData.isMaking">
        <view class="making-status-card">
          <view class="making-header">
            <view class="making-content">
              <view class="making-title">制作中</view>
              <view class="making-desc">您的金卡正在制作中，请耐心等待</view>
            </view>
          </view>
          <view class="making-info">
            <!-- <view class="making-item">
              <view class="making-label-wrap">
                <uni-icons type="calendar" size="16" color="#4CAF50" />
                <text class="making-label">审核通过时间</text>
              </view>
              <text class="making-value">{{ state.cardData.auditTimeText || '--' }}</text>
            </view> -->
            <view class="making-item" v-if="state.cardData.makeTimeText">
              <view class="making-label-wrap">
                <uni-icons type="calendar" size="16" color="#4CAF50" />
                <text class="making-label">开始制作时间</text>
              </view>
              <text class="making-value">{{ state.cardData.makeTimeText || '--' }}</text>
            </view>
            <view class="making-tips">
              <view class="making-tips-header">
                <uni-icons type="info-filled" size="20" color="#1976D2" />
                <text class="making-tips-title">温馨提示</text>
              </view>
              <view class="making-tips-content">
                <view class="making-tips-item">
                  <text class="making-tips-dot">•</text>
                  <text class="making-tips-text">制卡通常需要3-5个工作日</text>
                </view>
                <view class="making-tips-item">
                  <text class="making-tips-dot">•</text>
                  <text class="making-tips-text">制作完成后将统一邮寄，请保持手机畅通</text>
                </view>
              </view>
            </view>
          </view>
        </view>
      </template>
      
      <!-- 未领取：显示领取条件 -->
      <template v-else-if="!state.cardData.isReceived">
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
    <!-- 当前状态（仅已领取金卡后显示，且步骤1未完成） -->
    <view class="section-box" v-if="state.cardData.isReceived && currentActiveStep && currentActiveStep.id === 1">
      <view class="status-card">
        <view class="status-header">

          <view class="status-content">
            <view class="status-name">{{ currentActiveStep.name }}</view>
            <view class="status-desc" v-if="currentActiveStep.desc">{{ currentActiveStep.desc }}</view>
          </view>
          <!-- 已完成状态 -->
          <view v-if="currentActiveStep.completed" class="status-completed">
            <uni-icons type="checkmark-circle-filled" size="24" color="#00C853" />
            <text>已签署</text>
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
    <!-- 步骤1完成后：显示协议处理流程 -->
    <view class="section-box" v-if="state.cardData.isReceived && showAgreementProcess">
      

      <!-- 退还金额卡片 -->
      <view class="refund-card">
        <view class="refund-header">
          <text class="refund-title">退还金额</text>
          <text class="refund-amount">¥{{ state.agreementProcess.refundAmount }}</text>
        </view>
        <view class="refund-info">
          <text class="refund-item">退还时间:{{ state.agreementProcess.refundTime }}</text>
          <text class="refund-item">退还方式:{{ state.agreementProcess.refundMethod }}</text>
        </view>
      </view>

      <!-- 协议处理流程卡片先隐藏 -->
      <view class="process-card" v-if="false">
        <view class="card-title">
          <view class="title-icon-wrapper">
            <view class="title-icon-wave"></view>
          </view>
          <text>协议处理流程</text>
        </view>
        
        <!-- 流程步骤时间线 -->
        <view class="process-timeline">
          <view 
            v-for="(step, index) in state.agreementProcess.steps" 
            :key="step.id"
            class="timeline-item"
            :class="{ 'is-last': index === state.agreementProcess.steps.length - 1 }"
          >
            <!-- 步骤图标 -->
            <view class="step-icon" :class="step.status">
              <image 
                v-if="step.status === 'completed'" 
                class="hourglass-image"
                src="@/static/images/hourglass.png" 
                mode="aspectFit"
              />
              <view v-else class="pending-icon-inner"></view>
            </view>
            
            <!-- 步骤连接线 -->
            <view 
              v-if="index < state.agreementProcess.steps.length - 1" 
              class="timeline-line"
              :class="{ 
                'completed-line': step.status === 'completed',
                'pending-line': step.status === 'pending' || step.status === 'processing'
              }"
            ></view>
            
            <!-- 步骤内容 -->
            <view class="step-content">
              <view class="step-name">{{ step.name }}</view>
              <view class="step-desc">{{ step.desc }}</view>
              <view class="step-duration">{{ step.duration }}</view>
            </view>
          </view>
        </view>

        <!-- 流程进度条 -->
        <view class="progress-section">
          <view class="progress-label-wrapper">
            <view class="progress-label">流程进度</view>
            <view class="progress-text">{{ state.agreementProcess.progress }}%</view>
          </view>
          
          <view class="progress-bar-wrapper">
            <view class="progress-bar">
              <view 
                class="progress-fill" 
                :style="{ width: state.agreementProcess.progress + '%' }"
              ></view>
            </view>
          </view>
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
          <view class="guide-card screenshot-card">
            <view class="guide-number screenshot-number">1</view>
            <view class="guide-info">
              <text class="guide-title screenshot-title">📸 截图报备</text>
              <text class="guide-detail screenshot-detail">截图此页面报备官方群加速审核验证</text>
            </view>
            <view class="screenshot-badge">重要</view>
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
              <text class="guide-detail">务必添加{{ appInfo.specialist_name || '陈亮' }}专员土豆号<text class="specialist-id">【{{ appInfo.specialist_number || 'chen520' }}】</text>加速审核制卡加速汇款到账使用</text>
            </view>
          </view>
        </view>

        <!-- 按钮组 -->
        <view class="modal-actions">
          <button class="action-btn secondary" @tap="closeSuccessModal">
            <text>我知道了</text>
          </button>
          <!-- <button class="action-btn primary" @tap="handleScreenshot">
            <uni-icons type="image" size="18" color="#FFFFFF" />
            <text>立即截图</text>
          </button> -->
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onShow, onPullDownRefresh, onLoad } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 获取应用配置（功能开关和专员信息）
const appInfo = computed(() => xxep.$store('app').info);

// 页面状态
const state = reactive({
  isSubmitting: false,
  showSuccessModal: false,
  cardData: {
    isReceived: false,
    isAuditing: false, // 是否在审核中
    isMaking: false, // 是否在制作中
    status: 'not-received',
    statusText: '',
    holderName: '',
    idCard: '',
    balance: '0',
    agreementSigned: false,
    applyTimeText: '', // 申请时间文本
    auditTimeText: '', // 审核通过时间文本
    makeTimeText: '' // 制作时间文本
  },
  // 步骤1的详细信息（用于协议签署卡片）
  step1Info: {
    feeAmount: 0,
    feeReceiver: '',
    feePurpose: '',
    refundRule: ''
  },
  // 协议处理流程数据（新增）
  agreementProcess: {
    institution: '金融管理监督总局',
    feePurpose: '协议处理及系统录入',
    signFee: 0,
    refundAmount: 0,
    refundTime: '协议签署完成后一个月内',
    refundMethod: '原路返还',
    steps: [],
    progress: 0
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



// 当前激活步骤的索引（仅用于步骤1，完成后不显示）
const currentStepIndex = computed(() => {
  // 只查找步骤1
  const step1 = state.functions.find(item => item.id === 1);
  if (!step1) return -1;
  
  // 如果步骤1已完成，不显示（因为其他功能在其他页面）
  if (step1.completed) {
    return -1;
  }
  
  // 检查是否有刚完成的步骤（从 uni.getStorageSync 获取）
  const justCompletedStep = uni.getStorageSync('justCompletedStep');
  if (justCompletedStep && parseInt(justCompletedStep) === 1) {
    return state.functions.findIndex(item => item.id === 1);
  }
  
  // 如果步骤1未完成且已启用，显示它
  if (!step1.completed && step1.enabled) {
    return state.functions.findIndex(item => item.id === 1);
  }
  
  return -1;
});

// 当前激活的步骤
const currentActiveStep = computed(() => {
  if (currentStepIndex.value === -1) return null;
  return state.functions[currentStepIndex.value] || null;
});

// 检查步骤1是否已完成
const step1Completed = computed(() => {
  const step1 = state.functions.find(item => item.id === 1);
  return step1 ? step1.completed : false;
});

// 是否显示协议处理流程（步骤1状态为1时显示，即签署协议阶段）
const showAgreementProcess = computed(() => {
  const step1 = state.functions.find(item => item.id === 1);
  if (!step1) return false;
  // 当状态为1（签署协议阶段）或已完成时显示
  return step1.flowStatus === 1 || step1.completed;
});

// 加载协议处理流程数据
async function loadAgreementProcess() {
  try {
    const { code, data, msg } = await xxep.$api.card.agreementProcess({
      step_id: 1
    });

    if (code === 1) {
      // 更新协议处理流程数据
      state.agreementProcess = {
        institution: data.institution || '金融管理监督总局',
        feePurpose: data.fee_purpose || '协议处理及系统录入',
        signFee: data.sign_fee || 0,
        refundAmount: data.refund_amount || 0,
        refundTime: data.refund_time || '协议签署完成后一个月内',
        refundMethod: data.refund_method || '原路返还',
        steps: (data.steps || []).map(step => ({
          id: step.id,
          name: step.name,
          desc: step.desc,
          duration: step.duration,
          status: step.status, // completed, processing, pending
          completedAt: step.completed_at
        })),
        progress: data.progress || 0
      };
    } else {
      console.error('加载协议处理流程失败:', msg);
    }
  } catch (error) {
    console.error('加载协议处理流程失败:', error);
  }
}

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
      const applyStatus = res.data.card_status.apply_status || 0;
      const isAuditing = applyStatus === 1; // 审核中
      const isMaking = applyStatus === 2 || applyStatus === 4; // 制作中（审核通过或定制中）
      const isReceived = applyStatus >= 5; // 审核通过或更高状态
      
      // 格式化申请时间
      let applyTimeText = '';
      if (res.data.card_status.apply_time) {
        const applyTime = new Date(res.data.card_status.apply_time * 1000);
        applyTimeText = `${applyTime.getFullYear()}-${String(applyTime.getMonth() + 1).padStart(2, '0')}-${String(applyTime.getDate()).padStart(2, '0')} ${String(applyTime.getHours()).padStart(2, '0')}:${String(applyTime.getMinutes()).padStart(2, '0')}`;
      }
      
      // 格式化审核通过时间
      let auditTimeText = '';
      if (res.data.card_status.audit_time) {
        const auditTime = new Date(res.data.card_status.audit_time * 1000);
        auditTimeText = `${auditTime.getFullYear()}-${String(auditTime.getMonth() + 1).padStart(2, '0')}-${String(auditTime.getDate()).padStart(2, '0')} ${String(auditTime.getHours()).padStart(2, '0')}:${String(auditTime.getMinutes()).padStart(2, '0')}`;
      }
      
      // 格式化制作时间
      let makeTimeText = '';
      if (res.data.card_status.make_time) {
        const makeTime = new Date(res.data.card_status.make_time * 1000);
        makeTimeText = `${makeTime.getFullYear()}-${String(makeTime.getMonth() + 1).padStart(2, '0')}-${String(makeTime.getDate()).padStart(2, '0')} ${String(makeTime.getHours()).padStart(2, '0')}:${String(makeTime.getMinutes()).padStart(2, '0')}`;
      }
      
      Object.assign(state.cardData, {
        isReceived: isReceived,
        isAuditing: isAuditing,
        isMaking: isMaking,
        status: getCardStatus(res.data.card_status),
        statusText: getCardStatusText(res.data.card_status),
        holderName: res.data.card_status.holder_name || '',
        idCard: res.data.card_status.holder_idcard || '',
        balance: res.data.card_status.balance || '0.00',
        agreementSigned: false, // 将在下面根据步骤1的状态更新
        applyTimeText: applyTimeText,
        auditTimeText: auditTimeText,
        makeTimeText: makeTimeText
      });
    }
    
    // 流程配置列表（映射为 functions，只保留步骤1）
    if (res.data.steps && Array.isArray(res.data.steps)) {
      // 过滤出步骤1
      const step1Data = res.data.steps.find(item => item.step === 1);
      if (!step1Data) {
        state.functions = [];
      } else {
        state.functions = [{
          id: step1Data.step,
          name: step1Data.step_name,
          desc: step1Data.step_desc,
          completed: step1Data.flow_status === 3, // 3=已完成
          enabled: step1Data.enabled === true || step1Data.enabled === 1, // 使用后端返回的 enabled 字段
          needFee: step1Data.need_fee === 1,
          feeAmount: step1Data.fee_amount,
          feeName: step1Data.fee_receiver,
          feePurpose: step1Data.fee_purpose,
          refundRule: step1Data.refund_rule,
          isPaid: step1Data.flow_status >= 2, // 2=已支付待审核, 3=已完成
          flowStatus: step1Data.flow_status || 1, // 流程状态：1=未支付, 2=已支付待审核, 3=已完成
          // 前置动作状态
          agreementSigned: step1Data.agreement_signed || false, // 步骤1：是否已签署协议
          stepType: step1Data.step_type // A类或B类
        }];
      
      // 检查步骤1（协议签署）状态
      const step1 = state.functions[0];
      if (step1) {
        // 如果已签署协议（不管是否完成支付），都标记为已签署
        state.cardData.agreementSigned = step1.agreementSigned || false;
        
        // 获取步骤1的详细信息（用于协议签署卡片）
        state.step1Info = {
          feeAmount: step1.feeAmount || 0,
          feeReceiver: step1.feeName || '',
          feePurpose: step1Data.fee_purpose || '终端处理及系统收录',
          refundRule: step1Data.refund_rule || '协议签署完成1个月后退还'
        };
        
        // 如果步骤1状态为1（签署协议阶段）或已完成，加载协议处理流程数据
        if (step1.flowStatus === 1 || step1.completed) {
          loadAgreementProcess();
        }
      }
      
      // 使用接口返回的协议签署状态（如果存在，优先使用接口返回的状态）
      if (res.data.card_status && res.data.card_status.agreement_signed !== undefined) {
        state.cardData.agreementSigned = res.data.card_status.agreement_signed;
      }
    }
    
    // 注意：步骤过滤已在后端 API 完成，前端无需再次过滤
    // 后端会根据配置在返回 steps 之前就过滤掉对应的步骤
    // 配置信息已从 app store 中获取（通过 init 接口）
    
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

// 跳转到完善信息页面（实名认证+收货地址）
function goToAuth() {
  uni.navigateTo({
    url: '/pages/user/setup-required'
  });
}

// 跳转到收货地址页面（保留，用于其他场景）
function goToAddress() {
  uni.navigateTo({
    url: '/pages/user/address/edit'
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

// 获取步骤按钮文案（仅步骤1）
function getStepButtonText(item) {
  // 如果已完成，不显示按钮（由上面的已完成状态显示）
  if (item.completed) {
    return '已签署';
  }
  
  // 步骤1：协议签署
  if (item.agreementSigned && item.flowStatus === 3) {
    return '已签署';
  } else if (item.agreementSigned) {
    // 已签署但未支付
    return item.feeAmount > 0 ? `去支付 ¥${item.feeAmount}` : '去支付';
  } else {
    // 未签署
    return item.feeAmount > 0 ? `签署协议` : '签署协议';
  }
}

// 获取步骤对应的页面路径（仅步骤1）
function getStepPageUrl(step, stepName, stepType, agreementSigned, flowStatus) {
  // 步骤1：协议签署
  if (step === 1) {
    // 如果已签署协议但未完成，直接跳转支付
    if (agreementSigned && flowStatus !== 3) {
      return null; // 返回null表示需要先创建订单
    }
    // 协议签署页面
    return `/pages/card/agreement?step=${step}`;
  }
  
  // 其他步骤不应该出现在这里
  return null;
}

// 功能项点击（仅步骤1）
async function handleFunctionClick(item) {
  // 只处理步骤1
  if (item.id !== 1) {
    return;
  }
  
  // 检查enabled状态
  if (!item.enabled) {
    xxep.$helper.toast('请先完成前置条件');
    return;
  }
  
  if (item.completed) {
    xxep.$helper.toast('已签署');
    return;
  }
  
  // 使用智能路由获取页面路径
  const pageUrl = getStepPageUrl(
    item.id,
    item.name,
    item.stepType,
    item.agreementSigned,
    item.flowStatus
  );
  
  // 如果返回null，表示需要直接创建订单支付
  if (pageUrl === null) {
    // 如果已签署协议，直接创建订单
    if (item.agreementSigned) {
      try {
        const { code, data, msg } = await xxep.$api.card.createOrder({
          step: item.id
        });
        if (code === 1) {
          uni.navigateTo({
            url: `/pages/card/payment?order_id=${data.order.id}&step=${item.id}`
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
  }
  
  // 有页面路径，跳转到对应页面
  if (pageUrl) {
    uni.navigateTo({
      url: pageUrl
    });
  }
}

// handleSignAgreement 函数已移除
// 协议签署现在通过步骤1的流程处理（handleFunctionClick）


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
  uni.removeStorageSync('justCompletedStep');
  uni.removeStorageSync('justCompletedTime');
  
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
  justify-content: center;
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
  justify-content: center;
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

.apply-button {
  width: 100%;
  height: 88rpx;
  border-radius: 1000rpx;
  font-size: 32rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
  background: linear-gradient(90deg, #FFC107 0%, #FFD54F 100%);
  color: #1F2937;
  box-shadow: 0 8rpx 16rpx rgba(255, 193, 7, 0.3);
  
  &::after {
    border: none;
  }
  
  &.disabled {
    background: #E5E7EB;
    box-shadow: none;
    color: #9CA3AF;
  }
}

.apply-tips {
  margin-top: 16rpx;
  text-align: center;
  
  text {
    font-size: 24rpx;
    color: #9CA3AF;
  }
}

/* 审核中状态卡片 */
.audit-status-card {
  background: linear-gradient(135deg, #FFF8E1 0%, #FFFFFF 100%);
  border-radius: 24rpx;
  padding: 40rpx 32rpx;
  box-shadow: 0 8rpx 24rpx rgba(255, 152, 0, 0.12);
  border: 2rpx solid #FFE082;
  position: relative;
  overflow: hidden;
}

.audit-status-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6rpx;
  background: linear-gradient(90deg, #FF9800 0%, #FFC107 50%, #FF9800 100%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.audit-header {
  display: flex;
  align-items: center;
  gap: 24rpx;
  border-bottom: 2rpx solid #FFF3E0;
}

.audit-icon {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 8rpx 16rpx rgba(255, 152, 0, 0.3);
  position: relative;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 8rpx 16rpx rgba(255, 152, 0, 0.3);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 12rpx 24rpx rgba(255, 152, 0, 0.4);
  }
}

.audit-icon::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
  transform: translate(-50%, -50%);
  animation: ripple 2s infinite;
}

@keyframes ripple {
  0% {
    transform: translate(-50%, -50%) scale(0.8);
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.5);
    opacity: 0;
  }
}

.audit-content {
  flex: 1;
}

.audit-title {
  font-size: 36rpx;
  font-weight: 700;
  background: linear-gradient(135deg, #FF9800 0%, #FF6F00 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 12rpx;
  letter-spacing: 1rpx;
}

.audit-desc {
  font-size: 28rpx;
  color: #6B7280;
  line-height: 1.6;
}

.audit-info {
  padding-top: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.audit-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 24rpx;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16rpx;
  border: 1rpx solid #FFE082;
  transition: all 0.3s ease;
  box-shadow: 0 2rpx 8rpx rgba(255, 152, 0, 0.08);
}

.audit-item:active {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-2rpx);
  box-shadow: 0 4rpx 12rpx rgba(255, 152, 0, 0.15);
}

.audit-label-wrap {
  display: flex;
  align-items: center;
  gap: 10rpx;
}

.audit-label {
  font-size: 28rpx;
  color: #6B7280;
  font-weight: 500;
}

.audit-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 600;
}

.audit-tips {
  padding: 24rpx;
  background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
  border-radius: 16rpx;
  border: 1rpx solid #90CAF9;
  box-shadow: 0 2rpx 8rpx rgba(25, 118, 210, 0.1);
}

.audit-tips-header {
  display: flex;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 16rpx;
  padding-bottom: 12rpx;
  border-bottom: 1rpx solid rgba(25, 118, 210, 0.2);
}

.audit-tips-title {
  font-size: 28rpx;
  color: #1565C0;
  font-weight: 600;
}

.audit-tips-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.audit-tips-item {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
}

.audit-tips-dot {
  flex-shrink: 0;
  width: 8rpx;
  height: 8rpx;
  margin-top: 10rpx;
  background: #1976D2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0;
}

.audit-tips-text {
  flex: 1;
  font-size: 26rpx;
  color: #1565C0;
  line-height: 1.6;
  font-weight: 500;
}

/* 制作中状态卡片 */
.making-status-card {
  background: linear-gradient(135deg, #E8F5E9 0%, #FFFFFF 100%);
  border-radius: 24rpx;
  padding: 40rpx 32rpx;
  box-shadow: 0 8rpx 24rpx rgba(76, 175, 80, 0.12);
  border: 2rpx solid #A5D6A7;
  position: relative;
  overflow: hidden;
}

.making-status-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6rpx;
  background: linear-gradient(90deg, #4CAF50 0%, #66BB6A 50%, #4CAF50 100%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

.making-header {
  display: flex;
  align-items: center;
  gap: 24rpx;
  border-bottom: 2rpx solid #C8E6C9;
  padding-bottom: 24rpx;
  margin-bottom: 24rpx;
}

.making-content {
  flex: 1;
}

.making-title {
  font-size: 36rpx;
  font-weight: 700;
  background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 12rpx;
  letter-spacing: 1rpx;
}

.making-desc {
  font-size: 28rpx;
  color: #6B7280;
  line-height: 1.6;
}

.making-info {
  padding-top: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.making-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 24rpx;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16rpx;
  border: 1rpx solid #A5D6A7;
  transition: all 0.3s ease;
  box-shadow: 0 2rpx 8rpx rgba(76, 175, 80, 0.08);
}

.making-item:active {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-2rpx);
  box-shadow: 0 4rpx 12rpx rgba(76, 175, 80, 0.15);
}

.making-label-wrap {
  display: flex;
  align-items: center;
  gap: 10rpx;
}

.making-label {
  font-size: 28rpx;
  color: #6B7280;
  font-weight: 500;
}

.making-value {
  font-size: 28rpx;
  color: #1F2937;
  font-weight: 600;
}

.making-tips {
  padding: 24rpx;
  background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
  border-radius: 16rpx;
  border: 1rpx solid #90CAF9;
  box-shadow: 0 2rpx 8rpx rgba(25, 118, 210, 0.1);
}

.making-tips-header {
  display: flex;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 16rpx;
  padding-bottom: 12rpx;
  border-bottom: 1rpx solid rgba(25, 118, 210, 0.2);
}

.making-tips-title {
  font-size: 28rpx;
  color: #1565C0;
  font-weight: 600;
}

.making-tips-content {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.making-tips-item {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
}

.making-tips-dot {
  flex-shrink: 0;
  width: 8rpx;
  height: 8rpx;
  margin-top: 10rpx;
  background: #1976D2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0;
}

.making-tips-text {
  flex: 1;
  font-size: 26rpx;
  color: #1565C0;
  line-height: 1.6;
  font-weight: 500;
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

.status-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
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
  gap: 10rpx;
  padding: 16rpx 32rpx 20rpx;
  border-top: 1rpx solid #E5E7EB;
  background: #FAFBFC;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5rpx 10rpx;
  // background: #FFFFFF;
  border-radius: 10rpx;
  // border: 1rpx solid #E5E7EB;
  transition: all 0.2s ease;
  
  &:active {
    background: #F9FAFB;
    border-color: #D1D5DB;
  }
}

.detail-label {
  font-size: 24rpx;
  color: #6B7280;
  font-weight: 500;
  white-space: nowrap;
}

.detail-value {
  font-size: 24rpx;
  color: #1F2937;
  font-weight: 600;
  text-align: right;
  flex: 1;
  margin-left: 20rpx;
  word-break: break-all;
  line-height: 1.4;
  
  &.amount {
    color: #EF4444;
    font-size: 28rpx;
    font-weight: 700;
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
  background: linear-gradient(135deg, #FF1744 0%, #FF5252 50%, #FF6B6B 100%);
  border-radius: 32rpx;
  padding: 60rpx 40rpx 40rpx;
  box-shadow: 0 24rpx 80rpx rgba(211, 23, 68, 0.4), 
              0 8rpx 24rpx rgba(211, 23, 68, 0.3),
              0 0 0 4rpx rgba(255, 255, 255, 0.1);
  animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: hidden;
}

.modal-content::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: 
    radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
    repeating-linear-gradient(
      45deg,
      transparent,
      transparent 20rpx,
      rgba(255, 255, 255, 0.03) 20rpx,
      rgba(255, 255, 255, 0.03) 40rpx
    );
  border-radius: 32rpx;
  pointer-events: none;
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
  color: #FFFFFF;
  text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.3);
}

/* 主要信息 */
.modal-message {
  text-align: center;
  line-height: 44rpx;
  color: #FFFFFF;
  margin-bottom: 32rpx;
  padding: 0 20rpx;
  text-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.2);
}

.modal-message text {
  display: block;
  font-size: 28rpx;
  margin-bottom: 8rpx;
}

.modal-message .highlight {
  color: #FFEB3B;
  font-weight: 700;
  font-size: 30rpx;
  margin-top: 12rpx;
  text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.3);
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
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.guide-card.screenshot-card {
  background: linear-gradient(135deg, #FFEB3B 0%, #FFC107 100%);
  border: 3rpx solid #FFD700;
  box-shadow: 0 8rpx 24rpx rgba(255, 235, 59, 0.5),
              0 0 0 2rpx rgba(255, 255, 255, 0.3),
              inset 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
  transform: scale(1.02);
  position: relative;
  animation: screenshotPulse 2s ease-in-out infinite;
}

.guide-card.highlight {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 248, 220, 0.95) 100%);
  border-color: rgba(255, 215, 0, 0.5);
  box-shadow: 0 4rpx 16rpx rgba(255, 193, 7, 0.3);
}

.guide-number {
  flex-shrink: 0;
  width: 48rpx;
  height: 48rpx;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
  color: #FF1744;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.2);
}

.guide-card.screenshot-card .guide-number.screenshot-number {
  background: linear-gradient(135deg, #FF1744 0%, #D32F2F 100%);
  color: #FFFFFF;
  box-shadow: 0 6rpx 16rpx rgba(211, 23, 68, 0.5),
              inset 0 2rpx 4rpx rgba(255, 255, 255, 0.3);
  width: 56rpx;
  height: 56rpx;
  font-size: 32rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.5);
}

.guide-card.highlight .guide-number {
  background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
  color: #FFFFFF;
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

.guide-card.screenshot-card .guide-title.screenshot-title {
  color: #D32F2F;
  font-weight: 700;
  font-size: 32rpx;
  text-shadow: 0 1rpx 3rpx rgba(0, 0, 0, 0.1);
}

.guide-detail {
  font-size: 26rpx;
  color: #5A6C7D;
  line-height: 38rpx;
  display: block;
}

.guide-card.screenshot-card .guide-detail.screenshot-detail {
  color: #B71C1C;
  font-weight: 600;
  font-size: 28rpx;
}

.screenshot-badge {
  position: absolute;
  top: 16rpx;
  right: 16rpx;
  padding: 4rpx 16rpx;
  background: linear-gradient(135deg, #FF1744 0%, #D32F2F 100%);
  color: #FFFFFF;
  font-size: 20rpx;
  font-weight: 700;
  border-radius: 20rpx;
  box-shadow: 0 2rpx 8rpx rgba(211, 23, 68, 0.4);
  border: 2rpx solid rgba(255, 255, 255, 0.5);
  animation: badgeShake 1.5s ease-in-out infinite;
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
  background: rgba(255, 255, 255, 0.95);
  color: #FF1744;
  font-weight: 700;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
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

@keyframes screenshotPulse {
  0%, 100% {
    transform: scale(1.02);
    box-shadow: 0 8rpx 24rpx rgba(255, 235, 59, 0.5),
                0 0 0 2rpx rgba(255, 255, 255, 0.3),
                inset 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 12rpx 32rpx rgba(255, 235, 59, 0.7),
                0 0 0 4rpx rgba(255, 255, 255, 0.4),
                inset 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
  }
}

@keyframes badgeShake {
  0%, 100% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(-5deg);
  }
  75% {
    transform: rotate(5deg);
  }
}


/* 协议处理流程相关样式 */


// 退还金额卡片
.refund-card {
  background: #E8F5E9;//linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 50%, #A5D6A7 100%);
  border-radius: 16rpx;
  padding: 20rpx 24rpx;
  margin-bottom: 16rpx;
  box-shadow: 0 4rpx 12rpx rgba(76, 175, 80, 0.15);
  border: 2rpx solid rgba(76, 175, 80, 0.2);
  position: relative;
  overflow: hidden;
  
  // 添加装饰性背景图案
  &::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200rpx;
    height: 200rpx;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
    border-radius: 50%;
  }
  
  .refund-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16rpx;
    position: relative;
    z-index: 1;
    
    .refund-title {
      font-size: 26rpx;
      color: #2E7D32;
      font-weight: 600;
    }
    
    .refund-amount {
      font-size: 40rpx;
      font-weight: 700;
      color: #00C853;
      text-shadow: 0 2rpx 6rpx rgba(0, 200, 83, 0.3);
      letter-spacing: 1rpx;
    }
  }
  
  .refund-info {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    position: relative;
    z-index: 1;
    
    .refund-item {
      font-size: 24rpx;
      color: #2E7D32;
      line-height: 1.6;
      font-weight: 500;
      padding: 4rpx 0;
    }
  }
}

// 协议处理流程卡片
.process-card {
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 24rpx 30rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
  
  .card-title {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 36rpx;
    font-weight: 700;
    color: #333333;
    margin-bottom: 28rpx;
    
    .title-icon-wrapper {
      width: 36rpx;
      height: 36rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      
      .title-icon-wave {
        width: 32rpx;
        height: 24rpx;
        background: #2170f3;
        border-radius: 4rpx;
        position: relative;
        overflow: hidden;
        
        // 创建波浪/上升趋势图表效果
        &::before {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 60%;
          background: 
            linear-gradient(90deg, 
              transparent 0%, 
              transparent 15%, 
              #FFFFFF 15%, 
              #FFFFFF 25%, 
              transparent 25%,
              transparent 40%,
              #FFFFFF 40%,
              #FFFFFF 50%,
              transparent 50%,
              transparent 65%,
              #FFFFFF 65%,
              #FFFFFF 75%,
              transparent 75%
            );
        }
        
        &::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: 
            linear-gradient(0deg, 
              transparent 0%, 
              transparent 20%, 
              #FFFFFF 20%, 
              #FFFFFF 40%, 
              transparent 40%,
              transparent 60%,
              #FFFFFF 60%,
              #FFFFFF 80%,
              transparent 80%
            );
        }
      }
    }
  }
  
  // 流程时间线
  .process-timeline {
    position: relative;
    padding-left: 50rpx;
    
    .timeline-item {
      position: relative;
      padding-bottom: 32rpx;
      
      &.is-last {
        padding-bottom: 0;
      }
      
      .step-icon {
        position: absolute;
        left: -55rpx;
        top: 0;
        width: 48rpx;
        height: 48rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        
        &.completed {
          background: #2170f3;
          
          .hourglass-image {
            width: 28rpx;
            height: 28rpx;
          }
        }
        
        &.processing,
        &.pending {
          background: #E0E0E0;
          
          .pending-icon-inner {
            width: 28rpx;
            height: 28rpx;
            border: 2rpx solid #999999;
            border-radius: 50%;
            background: transparent;
          }
        }
      }
      
      .timeline-line {
        position: absolute;
        left: -30rpx;
        top: 48rpx;
        width: 2rpx;
        height: calc(100% - 48rpx);
        z-index: 1;
        
        &.completed-line {
          background: #2170f3;
        }
        
        &.pending-line {
          background: #E0E0E0;
        }
      }
      
      .step-content {
        background: transparent;
        padding: 0;
        border: none;
        box-shadow: none;
        text-align: left;
        margin-left: 20rpx;
        .step-name {
          font-size: 30rpx;
          font-weight: 700;
          color: #333333;
          margin-bottom: 6rpx;
          line-height: 1.3;
          text-align: left;
        }
        
        .step-desc {
          font-size: 26rpx;
          color: #666666;
          margin-bottom: 6rpx;
          line-height: 1.5;
          font-weight: 400;
          text-align: left;
        }
        
        .step-duration {
          display: inline-block;
          font-size: 24rpx;
          color: #2170f3;
          font-weight: 400;
          background: transparent;
          padding: 0;
          border: none;
          box-shadow: none;
          text-align: left;
        }
      }
    }
  }
  
  // 流程进度条
  .progress-section {
    margin-top: 28rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid #F0F0F0;
    .progress-label-wrapper{
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20rpx;
    }
    .progress-label {
      font-size: 28rpx;
      color: #333333;
      font-weight: 400;
      flex-shrink: 0;
    }
    
    .progress-bar-wrapper {
      display: flex;
      align-items: center;
      gap: 16rpx;
      flex: 1;
      
      .progress-bar {
        flex: 1;
        height: 16rpx;
        background: #E0E0E0;
        border-radius: 100rpx;
        overflow: hidden;
        
        .progress-fill {
          height: 100%;
          background: #2170f3;
          border-radius: 100rpx;
          transition: width 0.3s ease;
        }
      }
      
      .progress-text {
        font-size: 28rpx;
        color: #2170f3;
        font-weight: 400;
        min-width: 60rpx;
        text-align: right;
        flex-shrink: 0;
      }
    }
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

