<template>
  <s-layout
    title="我的福卡"
    navbar="custom"
    tabbar="/pages/index/fuka"
    :bgStyle="{ color: 'transparent' }"
    onShareAppMessage
  >
    <view class="fuka-page">
      <!-- 页面标题 -->
      <view class="page-title">集福卡</view>

      <!-- 福卡展示区域 -->
      <view class="cards-section">
        <view class="cards-grid">
          <view 
            v-for="card in sortedCards" 
            :key="card.type_code"
            class="card-item"
            :class="{ 'has-card': card.count > 0 }"
            @click="showCardDetail(card)"
          >
            <view class="card-wrapper">
              <!-- 福卡图片 -->
              <image 
                :src="card.image || getDefaultImage(card.type_code)" 
                class="card-image"
                mode="aspectFit"
                @error="handleImageError"
              />
              
              <!-- 未拥有的遮罩 -->
              <view v-if="card.count === 0" class="card-mask">
                <text class="mask-text">未获得</text>
              </view>
              
              <!-- 数量角标 -->
              <view v-if="card.count > 0" class="card-count">
                x{{ card.count }}
              </view>
              
              <!-- 万能福标签 -->
              <view v-if="card.is_universal" class="universal-badge">
                万能
              </view>
            </view>
            
            <view class="card-name">
              <text>{{ card.type_name }}</text>
              <text v-if="card.count > 0" class="card-name-count">x{{ card.count }}</text>
            </view>
          </view>
        </view>
      </view>
      <!-- 抽取福卡圆形按钮 -->
      <view class="draw-button-container">
        <view 
          class="draw-button"
          :class="{ 'disabled': chanceCount <= 0 || isDrawing, 'drawing': isDrawing }"
          @click="handleDraw"
        >
          <view class="draw-button-inner">
            <text v-if="isDrawing" class="draw-button-text">抽取中...</text>
            <text v-else-if="chanceCount <= 0" class="draw-button-text">机会已用完</text>
            <text v-else class="draw-button-text">抽取福卡</text>
            <text v-if="chanceCount > 0 && !isDrawing" class="draw-button-chance">剩余{{ chanceCount }}次</text>
          </view>
        </view>
      </view>
      <!-- 进度显示区域 -->
      <view class="progress-section">
        <view class="progress-card">
          <!-- 进度标题和数字 -->
          <view class="progress-header">
            <view class="header-left">
              <text class="progress-title">集福进度</text>
              <text class="progress-number">{{ collectedTypes }}/5</text>
            </view>
            <view class="header-right" @click="goToRank">
              <text class="header-link-text">排行榜</text>
              <text class="header-link-icon">›</text>
            </view>
          </view>
          
          <!-- 进度条 -->
          <view class="progress-bar-container">
            <view class="progress-bar-track">
              <view 
                class="progress-bar-fill" 
                :style="{ width: Math.min((collectedTypes / 5 * 100), 100) + '%' }"
              ></view>
            </view>
          </view>
          
          <!-- 福卡收集状态 -->
          <view class="cards-status">
            <view 
              v-for="card in normalCards" 
              :key="card.type_code"
              class="card-status-item"
              :class="{ 'collected': card.count > 0 }"
            >
              <view class="status-icon">
                <text v-if="card.count > 0" class="icon-check">✓</text>
              </view>
              <text class="card-status-name">{{ card.type_name }}</text>
            </view>
          </view>
          
          <!-- 合成五福卡按钮 -->
          <view 
            class="compose-button"
            :class="{ 'disabled': canMakeSets === 0 || isCombining }"
            @click="handleCombine"
          >
            <text v-if="isCombining" class="compose-btn-text">合成中...</text>
            <text v-else class="compose-btn-text">立即合成五福卡</text>
          </view>
          
          <!-- 进度提示 -->
          <view v-if="progressTip" class="progress-tip">
            <text class="tip-text">{{ progressTip }}</text>
          </view>
        </view>
      </view>

      <!-- 奖品兑换区域 -->
      <view class="prize-exchange-section">
        <view class="section-header">
          <view class="section-title-wrapper">
            <text class="section-title">奖品兑换</text>
            <text class="section-subtitle">PRIZE EXCHANGE</text>
          </view>
        </view>
        
        <view class="prize-list">
          <!-- 五福卡数量展示与兑换记录入口 -->
          <view class="wufu-card-count prize-wufu-card-count">
            <view class="wufu-count-info">
              <text class="wufu-count-label">拥有</text>
              <text class="wufu-count-number">{{ wufuCardCount }}</text>
              <text class="wufu-count-unit">个五福卡</text>
            </view>
            <view class="wufu-record-action" @click="goToExchangeRecords">
              <text class="record-link-text">兑换记录</text>
              <text class="record-link-icon">›</text>
            </view>
          </view>

          <!-- 动态渲染奖品列表 -->
          <view 
            v-for="prize in prizeList" 
            :key="prize.id"
            class="prize-card"
            :class="{ 'disabled': wufuCardCount < prize.need_fuka_set }"
          >
            <view class="prize-info">
              <text class="prize-name">{{ prize.prize_name }}</text>
              <view class="prize-condition-tag">
                <text class="tag-text">{{ prize.need_fuka_set }}套五福卡</text>
              </view>
            </view>
            <view class="prize-image">
              <image 
                :src="prize.image" 
                mode="aspectFill" 
                class="prize-img"
                @error="(e) => prize.image = '/static/fuka/default-prize.png'" 
              />
            </view>
            <button 
              :class="['prize-btn', { 'prize-btn-disabled': prize.stock === 0 || wufuCardCount < prize.need_fuka_set }]"
              :disabled="prize.stock === 0 || wufuCardCount < prize.need_fuka_set"
              @click="goToExchange(prize)"
            >
              <text class="btn-text">
                {{ prize.stock === 0 ? '未开放' : (wufuCardCount < prize.need_fuka_set ? '福卡不足' : '兑换') }}
              </text>
            </button>
          </view>
          
          <!-- 无奖品提示 -->
          <view v-if="prizeList.length === 0" class="prize-empty">
            <text class="empty-icon">🎁</text>
            <text class="empty-text">暂无可兑换奖品</text>
          </view>
        </view>
      </view>

      

      <!-- 抽中福卡动画弹窗 -->
      <view v-if="showDrawResult" class="draw-result-modal" @click="closeDrawResult">
        <view class="draw-result-content" @click.stop>
          <view class="drawn-card-animation" :class="{ 'show': showCardAnimation }">
            <image 
              v-if="drawnCard"
              :src="drawnCard.image || getDefaultImage(drawnCard.type_code)" 
              class="drawn-card-image"
              mode="aspectFit"
              @error="handleImageError"
            />
            <view class="drawn-card-info">
              <text class="drawn-card-title">恭喜获得</text>
              <text class="drawn-card-name">{{ drawnCard?.type_name }}</text>
              <view v-if="drawnCard?.is_universal" class="drawn-card-universal">
                <text>✨ 万能福卡 ✨</text>
              </view>
            </view>
          </view>
          <button class="draw-result-btn" @click="closeDrawResult">知道了</button>
        </view>
      </view>

      <!-- 活动规则说明区域 -->
      <view class="activity-rules-section">
        <view class="rules-title">活动规则</view>
        
        <view class="rules-group">
          <view class="group-title">集福规则</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">每日签到获得1次集福机会</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">邀请好友完成实名获得1次机会</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">每邀请3位好友额外获得1次机会</text>
          </view>
        </view>

        <view class="rules-group">
          <view class="group-title">兑换说明</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">集齐5张不同福卡合成1套五福卡</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">万能福可替代任意缺少的福卡</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">使用五福卡套数兑换对应奖品</text>
          </view>
        </view>

        <view class="rules-group">
          <view class="group-title">团队奖励</view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">团队达20人送手机1部</text>
          </view>
          <view class="rule-item">
            <text class="bullet">•</text>
            <text class="rule-text">团队达50人每人5万，队长30万</text>
          </view>
        </view>
      </view>
    </view>

      <!-- 合成成功弹窗 -->
      <view v-if="showCombineSuccess" class="combine-result-modal" @click="closeCombineSuccess">
        <view class="combine-result-content success" @click.stop>
          <view class="combine-success-icon">
            <text class="success-icon">✓</text>
          </view>
          <view class="combine-result-title">合成成功！</view>
          <view class="combine-result-message">恭喜您成功合成五福卡</view>
          <view class="combine-result-tip">现在可以使用五福卡兑换奖品了</view>
          <button class="combine-result-btn" @click="closeCombineSuccess">知道了</button>
        </view>
      </view>

      <!-- 合成失败弹窗 -->
      <view v-if="showCombineError" class="combine-result-modal" @click="closeCombineError">
        <view class="combine-result-content error" @click.stop>
          <view class="combine-error-icon">
            <text class="error-icon">✗</text>
          </view>
          <view class="combine-result-title">合成失败</view>
          <view class="combine-result-message">{{ combineErrorMsg }}</view>
          <button class="combine-result-btn" @click="closeCombineError">知道了</button>
        </view>
      </view>
  </s-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import xxep from '@/xxep'

// 福卡固定顺序配置（2行3列）
const CARD_ORDER = [
  { type_code: 'aiguo', type_name: '爱国福', image: '/static/fuka/爱国.png', is_universal: false },
  { type_code: 'youshan', type_name: '友善福', image: '/static/fuka/友善.png', is_universal: false },
  { type_code: 'jingye', type_name: '敬业福', image: '/static/fuka/敬业.png', is_universal: false },
  { type_code: 'hexie', type_name: '和谐福', image: '/static/fuka/和谐.png', is_universal: false },
  { type_code: 'fuqiang', type_name: '富强福', image: '/static/fuka/富强.png', is_universal: false },
  { type_code: 'wanneng', type_name: '万能福', image: '/static/fuka/万能.png', is_universal: true }
]

// 响应式数据
const cardTypes = ref([])
const loading = ref(false)
const chanceCount = ref(0)
const isDrawing = ref(false)
const showDrawResult = ref(false)
const showCardAnimation = ref(false)
const drawnCard = ref(null)
const wufuCardCount = ref(0) // 实际拥有的五福卡数量
const isCombining = ref(false) // 合成中状态
const showCombineSuccess = ref(false) // 显示合成成功弹窗
const showCombineError = ref(false) // 显示合成失败弹窗
const combineErrorMsg = ref('') // 合成失败错误信息
const prizeList = ref([]) // 奖品列表

// 按固定顺序排列的福卡列表
const sortedCards = computed(() => {
  return CARD_ORDER.map(config => {
    const userCard = cardTypes.value.find(c => c.type_code === config.type_code)
    return {
      ...config,
      // 优先使用后端返回的图片，如果没有则使用默认配置
      image: userCard?.image || userCard?.image_url || config.image,
      count: userCard?.count || 0,
      id: userCard?.id || config.type_code
    }
  })
})

// 普通福卡列表（不含万能福）
const normalCards = computed(() => {
  return sortedCards.value.filter(card => !card.is_universal)
})

// 计算已收集的福卡种类（不含万能福）
const collectedTypes = computed(() => {
  return normalCards.value.filter(card => card.count > 0).length
})

// 计算可合成的五福卡套数
const canMakeSets = computed(() => {
  const normalCards = sortedCards.value.filter(c => !c.is_universal)
  const universalCard = sortedCards.value.find(c => c.is_universal)
  const universalCount = universalCard?.count || 0
  
  // 计算每种普通福卡的数量
  const cardCounts = normalCards.map(c => c.count)
  
  // 如果有任何一种福卡数量为0且没有万能福，则无法合成
  if (cardCounts.some(count => count === 0) && universalCount === 0) {
    return 0
  }
  
  // 计算可合成套数（考虑万能福）
  let sets = 0
  let tempCounts = [...cardCounts]
  let tempUniversal = universalCount
  
  while (true) {
    // 检查是否还能合成一套
    let needUniversal = 0
    for (let i = 0; i < tempCounts.length; i++) {
      if (tempCounts[i] > 0) {
        tempCounts[i]--
      } else {
        needUniversal++
      }
    }
    
    if (needUniversal <= tempUniversal) {
      tempUniversal -= needUniversal
      sets++
    } else {
      break
    }
  }
  
  return sets
})

// 进度提示文案
const progressTip = computed(() => {
  if (canMakeSets.value > 0) {
    return `已集齐福卡，可立即兑换${canMakeSets.value}次奖品！`
  }
  
  const missingTypes = 5 - collectedTypes.value
  if (missingTypes === 0) {
    // 集齐5种但数量不够合成
    const normalCards = sortedCards.value.filter(c => !c.is_universal && c.count === 0)
    if (normalCards.length > 0) {
      return `还差${normalCards.map(c => c.type_name).join('、')}各1张即可合成`
    }
  } else if (missingTypes <= 2) {
    const missing = sortedCards.value.filter(c => !c.is_universal && c.count === 0)
    return `还差${missing.map(c => c.type_name).join('、')}即可合成五福卡`
  }
  
  return '继续集福，集齐5种不同福卡可合成五福卡'
})

// 页面加载
onLoad(() => {
  console.log('福卡页面加载')
  loadPageData()
})

// 加载页面数据
const loadPageData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadCardTypes(),
      loadChanceCount(),
      loadStatistics(),
      loadPrizeList()
    ])
  } catch (error) {
    console.error('加载页面数据失败', error)
    xxep.$helper.toast('加载失败，请稍后重试', 'error')
  } finally {
    loading.value = false
  }
}

// 加载奖品列表
const loadPrizeList = async () => {
  try {
    const res = await xxep.$api.card.getPrizeList()
    if (res.code === 1) {
      prizeList.value = (res.data || []).map(prize => ({
        ...prize,
        // 确保图片路径正确
        image: prize.prize_image || prize.image || '/static/fuka/default-prize.png'
      }))
    }
  } catch (error) {
    console.error('加载奖品列表失败', error)
    // 不显示错误提示，避免影响用户体验
  }
}

// 加载统计信息（包含五福卡数量）
const loadStatistics = async () => {
  try {
    const res = await xxep.$api.card.getCardStatistics()
    if (res.code === 1) {
      wufuCardCount.value = res.data.wufu_card_count || 0
      // 如果API返回了可合成数量，可以在这里使用
      // 但前端计算逻辑已经足够，这里仅作为备用
    }
  } catch (error) {
    console.error('加载统计信息失败', error)
    xxep.$helper.toast('加载统计信息失败', 'error')
  }
}

// 加载集福机会数量
const loadChanceCount = async () => {
  try {
    const res = await xxep.$api.card.getChanceCount()
    if (res.code === 1) {
      chanceCount.value = res.data.chance_count || 0
    }
  } catch (error) {
    console.error('加载集福机会失败', error)
    // 不显示错误提示，避免影响用户体验
  }
}

// 加载福卡类型和我的福卡
const loadCardTypes = async () => {
  try {
    const [typesRes, myCardsRes] = await Promise.all([
      xxep.$api.card.getCardTypes(),
      xxep.$api.card.getMyCards()
    ])
    
    if (typesRes.code === 1 && myCardsRes.code === 1) {
      // 处理API返回的数据结构：typeList返回 {list: [...]}
      const types = typesRes.data?.list || (Array.isArray(typesRes.data) ? typesRes.data : [])
      
      // 处理API返回的数据结构：myCards返回 {list: [], statistics: []}
      const myCardsList = myCardsRes.data?.list || (Array.isArray(myCardsRes.data) ? myCardsRes.data : [])
      const myCardsStatistics = myCardsRes.data?.statistics || []
      
      // 合并福卡类型和数量
      cardTypes.value = types.map(type => {
        // 优先使用statistics中的数据（已统计好的数量）
        const statItem = myCardsStatistics.find(s => s.type_code === type.type_code)
        let count = 0
        
        if (statItem) {
          // 使用统计数据
          count = statItem.count || 0
        } else {
          // 如果没有统计数据，从列表中计算
          const userCards = myCardsList.filter(card => 
            card.type_code === type.type_code && !card.is_used
          )
          count = userCards.length
        }
        
        // 优先使用后端返回的图片，如果没有则使用默认路径
        const image = type.image || type.image_url || statItem?.image || CARD_ORDER.find(c => c.type_code === type.type_code)?.image || ''
        
        return {
          ...type,
          image: image,
          count: count
        }
      })
    }
  } catch (error) {
    console.error('加载福卡类型失败', error)
    xxep.$helper.toast('加载福卡信息失败，请稍后重试', 'error')
  }
}

// 获取默认图片
const getDefaultImage = (typeCode) => {
  const defaultImages = {
    'aiguo': '/static/fuka/爱国.png',
    'youshan': '/static/fuka/友善.png',
    'jingye': '/static/fuka/敬业.png',
    'hexie': '/static/fuka/和谐.png',
    'fuqiang': '/static/fuka/富强.png',
    'wanneng': '/static/fuka/万能.png'
  }
  return defaultImages[typeCode] || '/static/fuka/default.png'
}

// 处理图片加载错误
const handleImageError = (e) => {
  console.warn('福卡图片加载失败', e)
  // 可以在这里设置默认图片
}

// 查看福卡详情
const showCardDetail = (card) => {
  if (card.count === 0) {
    xxep.$helper.toast('您还没有这张福卡哦', 'info')
    return
  }
  
  const cardInfo = card.is_universal 
    ? `您拥有 ${card.count} 张${card.type_name}\n\n万能福可以替代任意缺少的福卡，帮助您更快合成五福卡！`
    : `您拥有 ${card.count} 张${card.type_name}\n\n收集五福，兑换好礼！`
  
  uni.showModal({
    title: card.type_name,
    content: cardInfo,
    showCancel: false,
    confirmText: '知道了'
  })
}

// 跳转到集福页面
const goToDraw = () => {
  uni.navigateTo({
    url: '/pages/card/draw'
  })
}

// 跳转到排行榜
const goToRank = () => {
  uni.navigateTo({
    url: '/pages/card/rank'
  })
}

// 跳转到兑换记录
const goToExchangeRecords = () => {
  uni.navigateTo({
    url: '/pages/exchange/records'
  })
}

// 处理合成五福卡
const handleCombine = async () => {
  if (isCombining.value || canMakeSets.value === 0) {
    if (canMakeSets.value === 0) {
      xxep.$helper.toast('集齐5张不同福卡才能合成哦', 'info')
    }
    return
  }
  
  isCombining.value = true
  
  try {
    const res = await xxep.$api.card.combineWufuCard()
    
    if (res.code === 1) {
      // 显示成功弹窗
      showCombineSuccess.value = true
      // 刷新数据（确保更新五福卡数量）
      await Promise.all([
        loadCardTypes(),
        loadStatistics(),
        loadChanceCount() // 确保机会数量同步
      ])
    } else {
      // 显示失败弹窗
      combineErrorMsg.value = res.msg || '合成失败，请稍后重试'
      showCombineError.value = true
    }
  } catch (error) {
    console.error('合成五福卡失败', error)
    // 显示失败弹窗
    combineErrorMsg.value = error.message || '合成失败，请稍后重试'
    showCombineError.value = true
  } finally {
    isCombining.value = false
  }
}

// 关闭合成成功弹窗
const closeCombineSuccess = () => {
  showCombineSuccess.value = false
}

// 关闭合成失败弹窗
const closeCombineError = () => {
  showCombineError.value = false
  combineErrorMsg.value = ''
}

// 处理兑换奖品
const goToExchange = async (prize = null) => {
  if (wufuCardCount.value === 0) {
    xxep.$helper.toast('请先合成五福卡', 'info')
    return
  }
  
  // 检查库存
  if (prize && prize.stock === 0) {
    xxep.$helper.toast('该奖品暂未开放兑换', 'info')
    return
  }
  
  // 检查五福卡数量是否满足
  if (prize && wufuCardCount.value < prize.need_fuka_set) {
    xxep.$helper.toast(`需要${prize.need_fuka_set}套五福卡才能兑换此奖品`, 'info')
    return
  }
  
  // 判断奖品类型
  if (!prize) {
    // 没有指定奖品，跳转到兑换页面选择
    uni.navigateTo({
      url: '/pages/card/exchange'
    })
    return
  }
  
  // prize_type: 0-现金, 1-手机, 2-汽车, 3-现金红包
  if (prize.prize_type === 0 || prize.prize_type === 3) {
    // 现金奖品：直接兑换，发放至金卡账户
    await handleDirectExchange(prize)
  } else {
    // 实物奖品（手机、汽车）：跳转到兑换页面选择收货地址
    uni.navigateTo({
      url: `/pages/card/exchange?prize_id=${prize.id}`
    })
  }
}

// 直接兑换（现金红包）
const handleDirectExchange = async (prize) => {
  try {
    // 显示确认弹窗
    const confirmed = await new Promise((resolve) => {
      uni.showModal({
        title: '确认兑换',
        content: `确认使用${prize.need_fuka_set}套五福卡兑换${prize.prize_name}吗？`,
        confirmText: '确认兑换',
        cancelText: '取消',
        success: (res) => {
          resolve(res.confirm)
        }
      })
    })
    
    if (!confirmed) {
      return
    }
    
    // 显示加载中
    uni.showLoading({
      title: '兑换中...',
      mask: true
    })
    
    // 获取我的五福卡列表
    const wufuRes = await xxep.$api.card.getMyWufuCards()
    if (wufuRes.code !== 1 || !wufuRes.data?.list || wufuRes.data.list.length < prize.need_fuka_set) {
      uni.hideLoading()
      xxep.$helper.toast('五福卡数量不足', 'error')
      return
    }
    
    // 选择要使用的五福卡ID（按创建时间排序，使用最早的）
    const wufuCardIds = wufuRes.data.list
      .slice(0, prize.need_fuka_set)
      .map(card => card.id)
    
    // 调用兑换接口
    const res = await xxep.$api.card.exchangeCards({
      prize_id: prize.id,
      wufu_card_ids: wufuCardIds
    })
    
    uni.hideLoading()
    
    if (res.code === 1) {
      // 兑换成功
      const prizeValueText = prize.prize_value ? `¥${prize.prize_value}` : ''
      uni.showModal({
        title: '兑换成功',
        content: `恭喜您成功兑换${prize.prize_name}！${prizeValueText ? prizeValueText + '已' : '现金已'}发放到您的金卡余额中。`,
        showCancel: false,
        confirmText: '我知道了',
        success: (modalRes) => {
          // 可选：跳转到金卡页面查看余额
        }
      })
      
      // 刷新数据
      await Promise.all([
        loadStatistics(),
        loadPrizeList()
      ])
    } else {
      xxep.$helper.toast(res.msg || '兑换失败', 'error')
    }
  } catch (error) {
    uni.hideLoading()
    console.error('兑换失败', error)
    xxep.$helper.toast(error.msg || '兑换失败，请稍后重试', 'error')
  }
}

// 处理抽取福卡
const handleDraw = async () => {
  if (isDrawing.value || chanceCount.value <= 0) {
    return
  }
  
  isDrawing.value = true
  
  try {
    const res = await xxep.$api.card.drawCard()
    
    if (res.code === 1) {
      // 更新机会数量（如果API返回了则使用，否则重新获取）
      if (res.data.chance_count !== undefined) {
        chanceCount.value = res.data.chance_count
      } else {
        await loadChanceCount()
      }
      
      // 获取抽中的福卡信息
      const cardType = cardTypes.value.find(c => c.type_code === res.data.card?.type_code)
      if (cardType) {
        drawnCard.value = {
          ...cardType,
          ...res.data.card,
          // 确保图片URL存在
          image: res.data.card?.image || cardType.image || CARD_ORDER.find(c => c.type_code === res.data.card?.type_code)?.image || ''
        }
      } else {
        // 如果没有找到类型，使用默认配置
        const defaultCard = CARD_ORDER.find(c => c.type_code === res.data.card?.type_code)
        drawnCard.value = {
          ...res.data.card,
          image: res.data.card?.image || defaultCard?.image || '',
          type_name: res.data.card?.type_name || defaultCard?.type_name || ''
        }
      }
      
      // 显示动画弹窗
      showDrawResult.value = true
      
      // 延迟显示动画效果
      setTimeout(() => {
        showCardAnimation.value = true
      }, 100)
      
      // 刷新福卡列表
      await loadCardTypes()
    } else {
      xxep.$helper.toast(res.msg || '抽取失败', 'error')
    }
  } catch (error) {
    console.error('抽取福卡失败', error)
    xxep.$helper.toast('抽取失败，请稍后重试', 'error')
  } finally {
    isDrawing.value = false
  }
}

// 关闭抽中结果弹窗
const closeDrawResult = () => {
  showDrawResult.value = false
  showCardAnimation.value = false
  drawnCard.value = null
}
</script>

<style lang="scss" scoped>
// ==========================================================================
// 福卡页面样式 - 红色渐变玻璃态风格
// ==========================================================================

.fuka-page {
  position: relative;
  min-height: 100vh;
  padding: 32rpx;
  padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
  
  // 背景图片
  background-image: url('/static/images/fuka_bg.jpeg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
}

// ==========================================================================
// 页面标题
// ==========================================================================
.page-title {
  font-size: 64rpx;
  font-weight: 700;
  text-align: center;
  margin-bottom: 40rpx;
  margin-top: 20rpx;
  letter-spacing: 10rpx;
  color: #FFEB3B;
  
  // 金色浮雕文字效果
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 107, 74, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 255, 255, 0.9),
    0 0 20rpx rgba(255, 235, 59, 0.6);
}

// ==========================================================================
// 福卡展示区域
// ==========================================================================
.cards-section {
  background: rgba(255, 232, 214, 0.85);
  backdrop-filter: blur(12rpx);
  border-radius: 32rpx;
  padding: 40rpx 24rpx;
  margin-bottom: 24rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  box-shadow: 0 8rpx 32rpx rgba(255, 107, 74, 0.15);
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20rpx;
}

.card-item {
  text-align: center;
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.95);
  }
}

.card-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 0.75;
  margin-bottom: 12rpx;
  border-radius: 20rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  
  // 未获得
  background: rgba(255, 255, 255, 0.9);
  border: 3rpx solid rgba(255, 107, 74, 0.3);
}

.card-item.has-card .card-wrapper {
  // 已获得：金色渐变
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border: 3rpx solid rgba(255, 215, 0, 0.8);
  box-shadow: 0 6rpx 20rpx rgba(255, 215, 0, 0.3);
  
  &:active {
    box-shadow: 0 4rpx 16rpx rgba(255, 215, 0, 0.4);
  }
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 107, 74, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.mask-text {
  font-size: 26rpx;
  color: #D32F2F;
  font-weight: 600;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.card-count {
  position: absolute;
  top: 12rpx;
  right: 12rpx;
  
  // 红色徽章
  background: #FF5252;
  color: #FFFFFF;
  
  padding: 8rpx 20rpx;
  border-radius: 32rpx;
  font-size: 24rpx;
  font-weight: 700;
  min-width: 60rpx;
  text-align: center;
}

.universal-badge {
  position: absolute;
  top: 12rpx;
  left: 12rpx;
  
  // 金色徽章
  background: #FFD700;
  color: #D32F2F;
  
  padding: 6rpx 16rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  font-weight: 700;
}

.card-name {
  font-size: 26rpx;
  font-weight: 600;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

.card-name-count {
  font-size: 24rpx;
  color: #FF5722;
  font-weight: 700;
}

// ==========================================================================
// 进度显示区域
// ==========================================================================
.progress-section {
  margin-bottom: 24rpx;
}

// 进度卡片
.progress-card {
  background: rgba(255, 232, 214, 0.85);
  backdrop-filter: blur(12rpx);
  border-radius: 32rpx;
  padding: 40rpx 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  box-shadow: 0 8rpx 32rpx rgba(255, 107, 74, 0.15);
}

// 进度头部
.progress-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.progress-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.progress-number {
  font-size: 36rpx;
  font-weight: 700;
  color: #FF5722;
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 255, 255, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 107, 74, 0.5);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 16rpx;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 24rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.95);
    background: rgba(255, 255, 255, 0.6);
  }
}

.header-link-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #D32F2F;
}

.header-link-icon {
  font-size: 28rpx;
  font-weight: 300;
  color: #FF5722;
}

// 进度条
.progress-bar-container {
  margin-bottom: 32rpx;
}

.progress-bar-track {
  height: 16rpx;
  background: rgba(255, 255, 255, 0.85);
  border-radius: 8rpx;
  overflow: hidden;
  border: 1rpx solid rgba(255, 107, 74, 0.15);
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #FF5722 0%, #FFD700 100%);
  border-radius: 8rpx;
  transition: width 0.6s ease;
  position: relative;
}

// 福卡收集状态
.cards-status {
  display: flex;
  justify-content: space-between;
  gap: 16rpx;
}

.card-status-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
  transition: all 0.3s ease;
}

.status-icon {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  
  // 未收集状态
  background: rgba(255, 255, 255, 0.9);
  border: 2rpx solid rgba(255, 107, 74, 0.2);
}

.card-status-item.collected .status-icon {
  // 已收集状态：金色
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border: 2rpx solid rgba(255, 215, 0, 0.6);
}

.icon-check {
  font-size: 36rpx;
  color: #D32F2F;
  font-weight: 700;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
}

.card-status-name {
  font-size: 22rpx;
  color: #D32F2F;
  text-align: center;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.6),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.3);
  white-space: nowrap;
}

.card-status-item.collected .card-status-name {
  color: #FF5722;
  font-weight: 600;
}

// 合成五福卡按钮
.compose-button {
  width: 100%;
  min-height: 96rpx;
  margin-top: 40rpx;
  border-radius: 24rpx;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  
  // 可用状态：喜庆红色渐变按钮
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  
  &:active:not(.disabled) {
    opacity: 0.9;
    transform: scale(0.98);
  }
  
  // 禁用状态
  &.disabled {
    background: rgba(255, 255, 255, 0.85);
    opacity: 0.8;
  }
}

.compose-btn-text {
  font-size: 32rpx;
  font-weight: 700;
  color: #FFFFFF;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(211, 47, 47, 0.6);
}

.compose-button.disabled .compose-btn-text {
  color: #D32F2F;
  opacity: 0.6;
}

.progress-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  // padding: 20rpx 24rpx;
  margin-top: 24rpx;
  
  // background: rgba(255, 255, 255, 0.5);
  border-radius: 16rpx;
  // border: 1rpx solid rgba(255, 107, 74, 0.15);
}

.tip-text {
  font-size: 26rpx;
  font-weight: 500;
  color: #FF5722;
  text-align: center;
  opacity: 0.9;
}

// ==========================================================================
// 奖品兑换区域
// ==========================================================================
.prize-exchange-section {
  margin-bottom: 24rpx;
}

.section-header {
  margin-bottom: 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
}

// 五福卡数量展示
.wufu-card-count {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 8rpx;
  padding: 8rpx 0;
  border-radius: 32rpx;
}

.prize-wufu-card-count {
  width: 100%;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16rpx;
  padding: 20rpx 28rpx;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(12rpx);
  border-radius: 28rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.35);
  box-shadow: 0 4rpx 16rpx rgba(255, 107, 74, 0.1);
}

.wufu-count-info {
  display: flex;
  align-items: baseline;
  gap: 8rpx;
}

.wufu-count-label {
  font-size: 28rpx;
  color: #D32F2F;
  font-weight: 600;
}

.wufu-count-number {
  font-size: 48rpx;
  color: #FF5722;
  font-weight: 700;
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 255, 255, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 107, 74, 0.5);
}

.wufu-record-action {
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 16rpx;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 20rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.3);
  flex-shrink: 0;
  transition: all 0.3s ease;
  
  &:active {
    transform: scale(0.95);
    background: rgba(255, 255, 255, 0.5);
  }
}

.record-link-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #D32F2F;
}

.record-link-icon {
  font-size: 28rpx;
  font-weight: 300;
  color: #FF5722;
}

.wufu-count-unit {
  font-size: 28rpx;
  color: #D32F2F;
  font-weight: 600;
}

.section-title-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}

.section-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #FFEB3B;
  
  // 金色浮雕文字效果
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 107, 74, 0.6),
    -2rpx -2rpx 4rpx rgba(255, 255, 255, 0.8),
    0 0 16rpx rgba(255, 235, 59, 0.5);
}

.section-subtitle {
  font-size: 20rpx;
  color: #FF8A65;
  letter-spacing: 4rpx;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.5);
}

.prize-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

.prize-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24rpx;
  padding: 28rpx 32rpx;
  min-height: 160rpx;
  
  background: rgba(255, 232, 214, 0.95);
  border-radius: 24rpx;
  border: 1rpx solid rgba(255, 107, 74, 0.2);
  box-shadow: 0 2rpx 8rpx rgba(255, 107, 74, 0.1);
  
  transition: all 0.3s ease;
  
  &:active:not(.disabled) {
    opacity: 0.9;
    transform: scale(0.98);
  }
  
  &.disabled {
    opacity: 0.6;
    background: rgba(200, 200, 200, 0.3);
    box-shadow: none;
  }
}

.prize-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80rpx 0;
  gap: 24rpx;
}

.prize-empty .empty-icon {
  font-size: 96rpx;
  opacity: 0.5;
}

.prize-empty .empty-text {
  font-size: 28rpx;
  color: #D32F2F;
  opacity: 0.7;
}

// 左侧文字信息
.prize-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  min-width: 0;
}

.prize-name {
  font-size: 30rpx;
  font-weight: 700;
  color: #D32F2F;
  line-height: 1.4;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.prize-condition-tag {
  display: inline-flex;
  align-self: flex-start;
  padding: 8rpx 18rpx;
  border-radius: 24rpx;
  
  // 金色徽章
  background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
  box-shadow: 0 2rpx 8rpx rgba(255, 215, 0, 0.3);
}

.tag-text {
  font-size: 22rpx;
  font-weight: 700;
  color: #D32F2F;
  white-space: nowrap;
}

// 中间图片
.prize-image {
  width: 140rpx;
  height: 140rpx;
  flex-shrink: 0;
  border-radius: 20rpx;
  overflow: hidden;
  
  background: rgba(255, 255, 255, 0.8);
  border: 2rpx solid rgba(255, 107, 74, 0.25);
  box-shadow: 0 4rpx 12rpx rgba(255, 107, 74, 0.15);
  
  display: flex;
  align-items: center;
  justify-content: center;
}

.prize-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

// 右侧按钮
.prize-btn {
  flex-shrink: 0;
  padding: 16rpx 24rpx;
  border-radius: 40rpx;
  border: none;
  min-width: 120rpx;
  
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  box-shadow: 0 4rpx 12rpx rgba(255, 87, 34, 0.3);
  
  transition: all 0.3s ease;
  
  &:active:not(.prize-btn-disabled) {
    opacity: 0.9;
    transform: scale(0.95);
  }
}

.prize-btn-disabled {
  background: linear-gradient(135deg, #BDBDBD 0%, #bebbbb 100%) !important;
  opacity: 1 !important;
  box-shadow: none !important;
  
  .btn-text {
    color: #FFFFFF !important;
    text-shadow: none !important;
    opacity: 0.9;
  }
}

.prize-btn .btn-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #FFFFFF;
  white-space: nowrap;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
}

// ==========================================================================
// 活动规则区域
// ==========================================================================
.activity-rules-section {
  background: rgba(255, 232, 214, 0.75);
  backdrop-filter: blur(8rpx);
  border-radius: 32rpx;
  padding: 40rpx 32rpx;
  margin-bottom: 32rpx;
  border: 2rpx solid rgba(255, 107, 74, 0.25);
}

.rules-group {
  margin-bottom: 32rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.group-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #FF5722;
  margin-bottom: 20rpx;
  padding-bottom: 12rpx;
  
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  
  border-bottom: 2rpx solid rgba(255, 107, 74, 0.3);
}

.activity-rules-section .rule-item {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  padding: 12rpx 0;
}

.bullet {
  font-size: 28rpx;
  color: #FF8A65;
  line-height: 1.8;
  flex-shrink: 0;
}

.activity-rules-section .rule-text {
  flex: 1;
  font-size: 28rpx;
  color: #D32F2F;
  line-height: 1.8;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.5);
}

// ==========================================================================
// 抽取福卡按钮
// ==========================================================================
.draw-button-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 28rpx 0;
  position: relative;
  z-index: 10;
}

.draw-button {
  width: 200rpx;
  height: 200rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.3s ease;
  
  // 可用状态：金色渐变圆形按钮
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  box-shadow: 
    0 8rpx 24rpx rgba(255, 215, 0, 0.4),
    0 0 0 8rpx rgba(255, 215, 0, 0.1),
    0 0 0 16rpx rgba(255, 215, 0, 0.05);
  
  &:active:not(.disabled) {
    transform: scale(0.95);
  }
  
  // 抽取中动画
  &.drawing {
    animation: drawPulse 1s ease-in-out infinite;
  }
  
  // 禁用状态
  &.disabled {
    background: rgba(255, 255, 255, 0.6);
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
    opacity: 0.7;
  }
}

@keyframes drawPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 
      0 8rpx 24rpx rgba(255, 215, 0, 0.4),
      0 0 0 8rpx rgba(255, 215, 0, 0.1),
      0 0 0 16rpx rgba(255, 215, 0, 0.05);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 
      0 12rpx 32rpx rgba(255, 215, 0, 0.6),
      0 0 0 12rpx rgba(255, 215, 0, 0.2),
      0 0 0 24rpx rgba(255, 215, 0, 0.1);
  }
}

.draw-button-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4rpx;
}

.draw-button-text {
  font-size: 28rpx;
  font-weight: 700;
  color: #D32F2F;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.8),
    -1rpx -1rpx 2rpx rgba(255, 107, 74, 0.4);
  white-space: nowrap;
}

.draw-button-chance {
  font-size: 22rpx;
  color: #FF5722;
  font-weight: 600;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(255, 255, 255, 0.6);
}

.draw-button.disabled .draw-button-text {
  color: #9CA3AF;
  text-shadow: none;
}

// ==========================================================================
// 抽中福卡动画弹窗
// ==========================================================================
.draw-result-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.draw-result-content {
  width: 600rpx;
  padding: 60rpx 40rpx;
  background: rgba(255, 232, 214, 0.95);
  backdrop-filter: blur(12rpx);
  border-radius: 32rpx;
  border: 3rpx solid rgba(255, 215, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40rpx;
  box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.3);
}

.drawn-card-animation {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 32rpx;
  opacity: 0;
  transform: scale(0.5) translateY(100rpx);
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  
  &.show {
    opacity: 1;
    transform: scale(1) translateY(0);
    animation: cardBounce 0.8s ease 0.3s;
  }
}

@keyframes cardBounce {
  0%, 100% {
    transform: scale(1) translateY(0);
  }
  25% {
    transform: scale(1.1) translateY(-20rpx);
  }
  50% {
    transform: scale(1) translateY(0);
  }
  75% {
    transform: scale(1.05) translateY(-10rpx);
  }
}

.drawn-card-image {
  width: 300rpx;
  height: 400rpx;
  border-radius: 24rpx;
  box-shadow: 0 12rpx 32rpx rgba(0, 0, 0, 0.2);
}

.drawn-card-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
}

.drawn-card-title {
  font-size: 32rpx;
  color: #FF5722;
  font-weight: 600;
}

.drawn-card-name {
  font-size: 48rpx;
  color: #D32F2F;
  font-weight: 700;
  text-shadow: 
    2rpx 2rpx 4rpx rgba(255, 255, 255, 0.8),
    -2rpx -2rpx 4rpx rgba(255, 107, 74, 0.5);
}

.drawn-card-universal {
  padding: 12rpx 24rpx;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  border-radius: 24rpx;
  border: 2rpx solid rgba(255, 215, 0, 0.6);
  
  text {
    font-size: 28rpx;
    color: #D32F2F;
    font-weight: 700;
  }
}

.draw-result-btn {
  width: 100%;
  padding: 24rpx;
  border-radius: 24rpx;
  border: none;
  background: linear-gradient(135deg, #FF5722 0%, #FF8A65 100%);
  color: #FFFFFF;
  font-size: 32rpx;
  font-weight: 700;
  box-shadow: 0 8rpx 24rpx rgba(211, 47, 47, 0.4);
}

// ==========================================================================
// 合成结果弹窗
// ==========================================================================
.combine-result-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

.combine-result-content {
  width: 600rpx;
  padding: 60rpx 40rpx;
  background: rgba(255, 232, 214, 0.95);
  backdrop-filter: blur(12rpx);
  border-radius: 32rpx;
  border: 3rpx solid rgba(255, 215, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 32rpx;
  box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.3);
  animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  
  &.success {
    border-color: rgba(76, 175, 80, 0.6);
    background: rgba(232, 245, 233, 0.95);
  }
  
  &.error {
    border-color: rgba(244, 67, 54, 0.6);
    background: rgba(255, 235, 238, 0.95);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(100rpx) scale(0.8);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.combine-success-icon {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(76, 175, 80, 0.4);
  animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.combine-error-icon {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #F44336 0%, #C62828 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(244, 67, 54, 0.4);
  animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes scaleIn {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

.success-icon {
  font-size: 72rpx;
  color: #FFFFFF;
  font-weight: 700;
  line-height: 1;
}

.error-icon {
  font-size: 72rpx;
  color: #FFFFFF;
  font-weight: 700;
  line-height: 1;
}

.combine-result-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #D32F2F;
  text-align: center;
}

.combine-result-content.success .combine-result-title {
  color: #2E7D32;
}

.combine-result-content.error .combine-result-title {
  color: #C62828;
}

.combine-result-message {
  font-size: 28rpx;
  color: #666666;
  text-align: center;
  line-height: 1.6;
  word-break: break-all;
}

.combine-result-tip {
  font-size: 24rpx;
  color: #999999;
  text-align: center;
  margin-top: -16rpx;
}

.combine-result-btn {
  width: 100%;
  padding: 24rpx;
  background: linear-gradient(135deg, #FF5722 0%, #D32F2F 100%);
  color: #FFFFFF;
  font-size: 32rpx;
  font-weight: 700;
  text-shadow: 
    1rpx 1rpx 2rpx rgba(211, 47, 47, 0.5);
  transition: all 0.3s ease;
  
  &:active {
    opacity: 0.9;
    transform: scale(0.98);
  }
}

</style>

