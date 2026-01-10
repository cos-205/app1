<template>
  <s-layout
    title="我的团队"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <view class="page-content">
      <!-- 团队统计 -->
      <view class="card">
        <view class="section-header">
          <view class="section-title">
            <uni-icons type="person-filled" size="20" color="#4285F4" />
            <text>团队统计</text>
          </view>
        </view>

        <view class="stats-grid">
          <view class="stat-item">
            <view class="stat-value">{{ state.stats.level1 || 0 }}</view>
            <view class="stat-label">一级会员</view>
          </view>
          <view class="stat-item">
            <view class="stat-value">{{ state.stats.level2 || 0 }}</view>
            <view class="stat-label">二级会员</view>
          </view>
          <view class="stat-item">
            <view class="stat-value">{{ state.stats.level3 || 0 }}</view>
            <view class="stat-label">三级会员</view>
          </view>
          <view class="stat-item highlight">
            <view class="stat-value">{{ state.stats.total || 0 }}</view>
            <view class="stat-label">总人数</view>
          </view>
        </view>
      </view>

      <!-- 当前等级 -->
      <view class="card">
        <view class="section-header">
          <view class="section-title">
            <uni-icons type="vip-filled" size="20" color="#4285F4" />
            <text>当前等级</text>
          </view>
        </view>

        <view class="level-current">
          <image 
            v-if="currentLevelInfo.image" 
            class="level-image" 
            :src="currentLevelInfo.image" 
            mode="aspectFit"
          />
          <view v-else class="level-badge" :style="{ background: currentLevelInfo.color }">
            <uni-icons :type="'person-filled'" size="32" color="#FFFFFF" />
          </view>
          <view class="level-info">
            <view class="level-name">{{ currentLevelInfo.name }}</view>
            <view class="level-desc">{{ currentLevelInfo.desc }}</view>
          </view>
        </view>

        <view class="progress-box" v-if="nextLevelInfo">
          <view class="progress-header">
            <view class="progress-label">升级进度</view>
            <view class="progress-value">
              {{ state.stats.total }}/{{ nextLevelInfo.inviteCount }}
            </view>
          </view>
          <view class="progress-track">
            <view
              class="progress-fill"
              :style="{ width: upgradeProgress + '%' }"
            ></view>
          </view>
          <view class="progress-tip">
            还需邀请{{ nextLevelInfo.inviteCount - state.stats.total }}人升级至{{ nextLevelInfo.name }}
          </view>
        </view>
      </view>

      <!-- 团队成员 -->
      <view class="card">
        <view class="section-header">
          <view class="section-title">
            <uni-icons type="list" size="20" color="#4285F4" />
            <text>团队成员</text>
          </view>
          <view class="filter-tabs">
            <view
              v-for="(tab, index) in filterTabs"
              :key="index"
              class="filter-tab"
              :class="{ active: state.activeFilter === index }"
              @tap="handleFilterChange(index)"
            >
              {{ tab.label }}
            </view>
          </view>
        </view>

        <view class="member-list" v-if="filteredMembers.length > 0">
          <view
            v-for="(member, index) in filteredMembers"
            :key="index"
            class="member-item"
          >
            <image
              class="member-avatar"
              :src="member.avatar || '/static/images/default-avatar.png'"
              mode="aspectFill"
            />
            <view class="member-info">
              <view class="member-name">
                <text>{{ member.name }}</text>
                <text class="level-tag" :class="'level-' + member.level">{{ member.level }}级</text>
              </view>
              <view class="member-status">
                <uni-icons
                  :type="member.isRealname ? 'checkmarkempty' : 'info'"
                  size="14"
                  :color="member.isRealname ? '#00C853' : '#FFC107'"
                />
                <text :class="{ verified: member.isRealname }">
                  {{ member.isRealname ? '已实名' : '未实名' }}
                </text>
                <text class="member-time">{{ member.createTime }}</text>
              </view>
            </view>
          </view>
        </view>

        <view class="empty-box" v-else>
          <uni-icons type="info-circle" size="40" color="#CCCCCC" />
          <text>暂无团队成员</text>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app';
import xxep from '@/xxep';

// 筛选标签
const filterTabs = [
  { label: '全部', level: 0 },
  { label: '一级', level: 1 },
  { label: '二级', level: 2 },
  { label: '三级', level: 3 }
];

// 页面状态
const state = reactive({
  loading: false,
  activeFilter: 0,
  userLevel: 0,
  memberLevels: [],
  currentLevelConfig: null,
  stats: {
    level1: 0,
    level2: 0,
    level3: 0,
    total: 0
  },
  members: []
});

// 当前等级信息
const currentLevelInfo = computed(() => {
  if (state.userLevel === 0 || !state.currentLevelConfig) {
    return {
      name: '普通用户',
      desc: '邀请好友升级会员等级',
      icon: 'person-filled',
      color: 'linear-gradient(135deg, #E5E7EB, #D1D5DB)'
    };
  }
  
  const config = state.currentLevelConfig;
  const desc = config.dividendMoney > 0 
    ? `每月支付宝分红 ${config.dividendMoney} 万元`
    : '邀请更多好友升级会员等级';
  
  return {
    name: config.name,
    desc: desc,
    image: config.image || '',
    color: getLevelColor(config.level)
  };
});

// 下一等级信息
const nextLevelInfo = computed(() => {
  return state.memberLevels.find(l => l.level === state.userLevel + 1);
});

// 升级进度
const upgradeProgress = computed(() => {
  if (!nextLevelInfo.value) return 100;
  return Math.min(100, (state.stats.total / nextLevelInfo.value.inviteCount) * 100);
});

// 筛选后的成员列表
const filteredMembers = computed(() => {
  if (state.activeFilter === 0) {
    return state.members;
  }
  return state.members.filter(m => m.level === state.activeFilter);
});

// 获取等级图标
function getLevelIcon(level) {
  const icons = {
    0: 'person',
    1: 'medal-filled',
    2: 'star-filled',
    3: 'fire-filled',
    4: 'flag-filled',
    5: 'gift-filled'
  };
  return icons[level] || 'medal-filled';
}

// 获取等级颜色
function getLevelColor(level) {
  const colors = {
    0: 'linear-gradient(135deg, #D1D5DB, #9CA3AF)',
    1: 'linear-gradient(135deg, #9CA3AF, #6B7280)',
    2: 'linear-gradient(135deg, #FBBF24, #F59E0B)',
    3: 'linear-gradient(135deg, #60A5FA, #3B82F6)',
    4: 'linear-gradient(135deg, #4B5563, #1F2937)',
    5: 'linear-gradient(135deg, #A855F7, #9333EA)'
  };
  return colors[level] || 'linear-gradient(135deg, #9CA3AF, #6B7280)';
}

// 生命周期
onLoad(() => {
  loadData();
});

onPullDownRefresh(async () => {
  await loadData();
  uni.stopPullDownRefresh();
});

// 加载数据
async function loadData() {
  if (state.loading) return;
  state.loading = true;

  // 获取团队信息
  const teamRes = await xxep.$api.invite.getTeamInfo();
  if (teamRes.code === 1) {
    state.userLevel = teamRes.data.userLevel;
    state.stats = teamRes.data.stats;
    state.memberLevels = teamRes.data.memberLevels || [];
    state.currentLevelConfig = teamRes.data.currentLevelConfig;
  }

  // 获取团队成员列表
  const membersRes = await xxep.$api.invite.getTeamMembers({
    level: state.activeFilter,
    page: 1,
    limit: 50
  });
  if (membersRes.code === 1) {
    state.members = membersRes.data.members;
  }

  state.loading = false;
}

// 切换筛选
async function handleFilterChange(index) {
  state.activeFilter = index;
  
  // 重新加载成员列表
  const membersRes = await xxep.$api.invite.getTeamMembers({
    level: state.activeFilter,
    page: 1,
    limit: 50
  });
  if (membersRes.code === 1) {
    state.members = membersRes.data.members;
  }
}
</script>

<style lang="scss" scoped>
.page-content {
  padding: 20rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

// 卡片
.card {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 24rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

// 区块标题
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16rpx;
  
  .section-title {
    display: flex;
    align-items: center;
    gap: 6rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #1F2937;
  }
}

// 统计网格
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12rpx;
  
  .stat-item {
    background: #F3F4F6;
    border-radius: 12rpx;
    padding: 16rpx 12rpx;
    text-align: center;
    transition: all 0.3s ease;
    
    &.highlight {
      background: linear-gradient(135deg, #4285F4, #5A9CFF);
      
      .stat-value,
      .stat-label,
      .stat-desc {
        color: #FFFFFF;
      }
    }
    
    .stat-value {
      font-size: 40rpx;
      font-weight: 700;
      color: #1F2937;
      margin-bottom: 4rpx;
      line-height: 1;
    }
    
    .stat-label {
      font-size: 24rpx;
      color: #1F2937;
      font-weight: 500;
      margin-bottom: 2rpx;
    }
    
    .stat-desc {
      font-size: 20rpx;
      color: #6B7280;
    }
  }
}

// 当前等级
.level-current {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 16rpx;
  background: linear-gradient(135deg, #F3F4F6, #E5E7EB);
  border-radius: 12rpx;
  margin-bottom: 16rpx;
  
  .level-image {
    width: 100rpx;
    height: 100rpx;
    flex-shrink: 0;
  }
  
  .level-badge {
    width: 64rpx;
    height: 64rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
  }
  
  .level-info {
    flex: 1;
    
    .level-name {
      font-size: 28rpx;
      font-weight: 600;
      color: #1F2937;
      margin-bottom: 2rpx;
    }
    
    .level-desc {
      font-size: 22rpx;
      color: #6B7280;
      line-height: 1.3;
    }
  }
}

// 进度条
.progress-box {
  padding: 16rpx;
  background: #F3F4F6;
  border-radius: 12rpx;
  
  .progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10rpx;
    
    .progress-label {
      font-size: 24rpx;
      color: #6B7280;
    }
    
    .progress-value {
      font-size: 26rpx;
      font-weight: 600;
      color: #4285F4;
    }
  }
  
  .progress-track {
    height: 10rpx;
    background: #E5E7EB;
    border-radius: 5rpx;
    overflow: hidden;
    margin-bottom: 10rpx;
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #4285F4, #5A9CFF);
      border-radius: 5rpx;
      transition: width 0.6s ease;
    }
  }
  
  .progress-tip {
    font-size: 20rpx;
    color: #9CA3AF;
    text-align: center;
  }
}

// 筛选标签
.filter-tabs {
  display: flex;
  gap: 12rpx;
  
  .filter-tab {
    padding: 8rpx 20rpx;
    background: #F3F4F6;
    border-radius: 16rpx;
    font-size: 24rpx;
    color: #6B7280;
    transition: all 0.3s ease;
    
    &.active {
      background: linear-gradient(90deg, #4285F4, #5A9CFF);
      color: #FFFFFF;
    }
    
    &:active {
      opacity: 0.7;
    }
  }
}

// 成员列表
.member-list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  
  .member-item {
    display: flex;
    align-items: center;
    gap: 16rpx;
    background: #F8F9FA;
    border-radius: 12rpx;
    padding: 16rpx;
    
    .member-avatar {
      width: 64rpx;
      height: 64rpx;
      border-radius: 50%;
      background: #E8EAED;
      flex-shrink: 0;
    }
    
    .member-info {
      flex: 1;
      
      .member-name {
        display: flex;
        align-items: center;
        gap: 8rpx;
        margin-bottom: 6rpx;
        
        > text:first-child {
          font-size: 28rpx;
          font-weight: bold;
          color: #2C3E50;
        }
        
        .level-tag {
          font-size: 20rpx;
          padding: 2rpx 10rpx;
          border-radius: 12rpx;
          color: #FFFFFF;
          
          &.level-1 {
            background: linear-gradient(135deg, #FBBF24, #F59E0B);
          }
          
          &.level-2 {
            background: linear-gradient(135deg, #60A5FA, #3B82F6);
          }
          
          &.level-3 {
            background: linear-gradient(135deg, #A855F7, #9333EA);
          }
        }
      }
      
      .member-status {
        display: flex;
        align-items: center;
        gap: 6rpx;
        font-size: 22rpx;
        color: #9CA3AF;
        
        .verified {
          color: #00C853;
        }
        
        .member-time {
          margin-left: auto;
          color: #CCCCCC;
        }
      }
    }
  }
}

.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60rpx 0;
  gap: 16rpx;
  
  text {
    font-size: 26rpx;
    color: #999999;
  }
}
</style>

