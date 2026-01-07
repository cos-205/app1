/**
 * 金卡主页流程更新补丁
 * 将此代码替换到 /pages/index/card.vue 的相应位置
 */

// ==================== 1. 更新 loadCardInfo 函数 ====================
// 替换原来的 loadCardInfo 函数（约第498行）

async function loadCardInfo() {
  try {
    // 获取金卡信息
    const cardInfoRes = await xxep.$api.card.getCardInfo();
    
    if (cardInfoRes.code === 1 && cardInfoRes.data.card) {
      Object.assign(state.cardData, {
        isReceived: cardInfoRes.data.card.apply_status >= 2,
        status: getCardStatus(cardInfoRes.data.card),
        statusText: getCardStatusText(cardInfoRes.data.card),
        holderName: cardInfoRes.data.card.holder_name || '',
        idCard: cardInfoRes.data.card.holder_idcard || '',
        balance: cardInfoRes.data.card.balance || '0',
        agreementSigned: false
      });
    }
    
    // 获取新的流程配置（9步流程）
    const flowRes = await xxep.$api.card.flowConfig();
    
    if (flowRes.code === 1 && flowRes.data.steps) {
      state.functions = flowRes.data.steps.map((item, index) => ({
        id: item.step,
        name: item.step_name,
        desc: item.description || '',
        type: item.step_type, // A类或B类
        completed: item.current_status === 'completed',
        enabled: !item.is_locked,
        needFee: item.need_fee === 1,
        feeAmount: item.fee_amount,
        feeName: item.fee_name || `步骤${item.step}费用`,
        isPaid: item.payment_status === 'paid',
        status: item.current_status // locked, unpaid, pending, completed
      }));
    }
    
    // 检查领取条件
    checkApplyConditions();
  } catch (error) {
    console.error('加载金卡信息失败:', error);
    xxep.$helper.toast('加载失败，请重试');
  }
}

// ==================== 2. 更新 handleFunctionClick 函数 ====================
// 替换原来的 handleFunctionClick 函数（约第626行）

async function handleFunctionClick(item) {
  if (!item.enabled) {
    xxep.$helper.toast('请先完成前置步骤');
    return;
  }
  
  if (item.completed) {
    xxep.$helper.toast('该步骤已完成');
    return;
  }
  
  // 根据步骤类型和状态进行跳转
  const step = item.id;
  const stepType = item.type;
  
  // A类步骤：需要专门的UI界面
  if (stepType === 'A') {
    switch (step) {
      case 1:
        // 步骤1：协议签署
        uni.navigateTo({
          url: `/pages/card/agreement?step=${step}`
        });
        break;
      case 3:
        // 步骤3：设置卡片密码
        uni.navigateTo({
          url: `/pages/card/password?step=${step}`
        });
        break;
      case 4:
        // 步骤4：大额收付款功能
        uni.navigateTo({
          url: `/pages/card/payment-function?step=${step}`
        });
        break;
      default:
        xxep.$helper.toast('该功能暂未开放');
    }
  } 
  // B类步骤：直接支付
  else if (stepType === 'B') {
    uni.navigateTo({
      url: `/pages/card/step-confirm?step=${step}`
    });
  } 
  else {
    xxep.$helper.toast('该功能暂未开放');
  }
}

// ==================== 3. 更新 handleSignAgreement 函数 ====================
// 替换原来的 handleSignAgreement 函数（约第669行）

function handleSignAgreement() {
  if (state.isSubmitting) return;
  
  // 直接跳转到协议签署页面
  uni.navigateTo({
    url: '/pages/card/agreement?step=1'
  });
}

// ==================== 4. 新增流程进度计算 ====================
// 在 computed 部分添加（约第475行之后）

// 流程总进度
const flowProgress = computed(() => {
  if (!state.functions || state.functions.length === 0) return 0;
  const completed = state.functions.filter(f => f.completed).length;
  return Math.floor((completed / state.functions.length) * 100);
});

// 已完成步骤数
const completedStepsCount = computed(() => {
  if (!state.functions) return 0;
  return state.functions.filter(f => f.completed).length;
});

// 总步骤数
const totalStepsCount = computed(() => {
  return state.functions ? state.functions.length : 9;
});

// ==================== 5. 更新状态数据结构 ====================
// 替换 state.functions 初始值（约第381行）

functions: []  // 改为空数组，从API动态加载

// ==================== 6. 新增查看协议进度方法 ====================
// 在方法部分添加

// 查看协议处理进度
function viewAgreementProgress() {
  uni.navigateTo({
    url: '/pages/card/agreement-list?step=1'
  });
}

// 查看订单详情
function viewOrderDetail(orderId) {
  uni.navigateTo({
    url: `/pages/order/detail?id=${orderId}`
  });
}

// 刷新流程状态
async function refreshFlowStatus() {
  uni.showLoading({ title: '刷新中...' });
  await loadData();
  uni.hideLoading();
  xxep.$helper.toast('刷新成功');
}

// ==================== 使用说明 ====================
/**
 * 使用步骤：
 * 
 * 1. 备份原 card.vue 文件
 * 
 * 2. 替换相应函数：
 *    - loadCardInfo (约498行)
 *    - handleFunctionClick (约626行)
 *    - handleSignAgreement (约669行)
 * 
 * 3. 添加新的 computed 属性：
 *    - flowProgress
 *    - completedStepsCount
 *    - totalStepsCount
 * 
 * 4. 添加新的方法：
 *    - viewAgreementProgress
 *    - viewOrderDetail
 *    - refreshFlowStatus
 * 
 * 5. 更新 state.functions 初始值为空数组
 * 
 * 6. 在模板中添加流程进度展示：
 *    <view class="progress-info">
 *      <text>{{ completedStepsCount }}/{{ totalStepsCount }}</text>
 *      <view class="progress-bar">
 *        <view class="progress-fill" :style="{ width: flowProgress + '%' }"></view>
 *      </view>
 *    </view>
 * 
 * 7. 在协议签署区域添加查看进度按钮：
 *    <button @tap="viewAgreementProgress">查看处理进度</button>
 * 
 * 8. 测试所有流程跳转是否正常
 */

// ==================== API 调用示例 ====================
/**
 * 新的 API 接口使用方法：
 * 
 * // 1. 获取流程配置
 * const res = await xxep.$api.card.flowConfig();
 * // 返回: { code: 1, data: { steps: [...], total_amount: 2250 } }
 * 
 * // 2. 创建订单
 * const res = await xxep.$api.card.createOrder({ step: 1 });
 * // 返回: { code: 1, data: { order: {...} } }
 * 
 * // 3. 完成步骤（带数据）
 * const res = await xxep.$api.card.completeStepV2({
 *   step: 3,
 *   extra_data: { card_password: '123456' }
 * });
 * 
 * // 4. 查询协议进度
 * const res = await xxep.$api.card.agreementList({ step_id: 1 });
 * // 返回: { code: 1, data: { list: [...] } }
 */

