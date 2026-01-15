<template>
  <s-layout
    title="邀请好友"
    navbar="custom"
    tabbar="/pages/index/invite"
    :bgStyle="{ color: '#F3F4F6' }"
    onShareAppMessage
  >
    <!-- 背景图片 -->
    <image class="bg-image" src="/static/images/invite_bg.png" mode="aspectFill" />
    
    <view class="page-content">
      <!-- 顶部卡片：邀请信息 + 操作按钮 -->
      <view class="card invite-header-card">
        <view class="invite-info">
          <view class="info-title">我的邀请链接</view>
          <view class="invite-code-row">
            <text class="code-label">邀请码：</text>
            <text class="code-value">{{ state.inviteCode || '--'}}</text>
            <button class="btn-copy" @tap="handleCopyCode">复制</button>
          </view>
        </view>
        
        <view class="action-buttons">
          <button class="btn-action qrcode-btn" @tap="handleShowQRCode">
            <uni-icons type="scan" size="20" color="#4285F4" />
            <text>邀请链接</text>
          </button>
          <button class="btn-action team-btn" @tap="handleGoTeam">
            <uni-icons type="person-filled" size="20" color="#00C853" />
            <text>我的团队</text>
          </button>
        </view>
      </view>

      <!-- 会员等级列表 -->
      <view class="level-cards">
        <view
          v-for="(level, index) in state.memberLevels"
          :key="index"
          class="level-card"
          :class="{ 'is-current': state.userLevel === level.level }"
        >
          <view class="level-header">
            <image 
              v-if="level.image" 
              class="level-image" 
              :src="level.image" 
              mode="aspectFit"
            />
            <view v-else class="level-badge" :style="{ background: level.color }">
              <uni-icons :type="'medal-filled'" size="24" color="#FFFFFF" />
            </view>
            <view class="level-name">{{ level.name }}</view>
            <view class="level-status" v-if="state.userLevel >= level.level">
              <uni-icons type="checkmarkempty" size="20" color="#00C853" />
            </view>
          </view>
          <view class="level-body">
            <text>{{ level.desc }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 二维码弹窗 -->
    <view class="qrcode-modal" v-if="state.showQRCode" @tap="handleCloseQRCode">
      <view class="modal-mask"></view>
      <view class="modal-content" @tap.stop>
        <view class="modal-header">
          <view class="modal-title">邀请二维码</view>
          <view class="modal-close" @tap="handleCloseQRCode">
            <uni-icons type="closeempty" size="24" color="#6B7280" />
          </view>
        </view>

        <view class="qrcode-box">
          <s-qrcode 
            ref="qrcodeRef"
            :text="state.inviteUrl || '11111'"
            :size="500"
            canvasId="inviteQrcode"
            @success="handleQRCodeSuccess"
            @fail="handleQRCodeFail"
          />
          <view class="qrcode-label">{{ state.inviteCode }}</view>
        </view>

        <view class="btn-group">
          <!-- #ifndef H5 -->
          <button class="btn-secondary" @tap="handleSaveQRCode">
            <uni-icons type="image" size="18" color="#1F2937" />
            <text>保存到相册</text>
          </button>
          <!-- #endif -->
          <!-- #ifdef H5 -->
          <button class="btn-secondary">
            <uni-icons type="image" size="18" color="#1F2937" />
            <text>截图保存</text>
          </button>
          <!-- #endif -->
          <button class="btn-primary" @tap="handleCopyUrl">
            <uni-icons type="link" size="18" color="#FFFFFF" />
            <text>复制链接</text>
          </button>
        </view>
      </view>
    </view>
  </s-layout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app';
import xxep from '@/xxep';
import sQrcode from '@/components/s-qrcode/s-qrcode.vue';

// 二维码组件引用
const qrcodeRef = ref(null);

// 页面状态
const state = reactive({
  loading: false,
  showQRCode: false,
  inviteCode: '',
  inviteUrl: '',
  userLevel: 0,
  memberLevels: [],
  stats: {
    level1: 0,
    level2: 0,
    level3: 0,
    total: 0
  }
});

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

  const res = await xxep.$api.invite.getInviteInfo();
  if (res.code === 1) {
    state.inviteCode = res.data.inviteCode;
    state.inviteUrl = res.data.inviteUrl;
    state.userLevel = res.data.userLevel;
    state.stats = res.data.stats;
    state.memberLevels = res.data.memberLevels || [];
  }
  
  state.loading = false;
}

// 复制邀请码
function handleCopyCode() {
  uni.setClipboardData({
    data: state.inviteCode,
    success: () => {
      xxep.$helper.toast('邀请码已复制');
    }
  });
}

// 显示二维码弹窗
function handleShowQRCode() {
  if (!state.inviteUrl) {
    xxep.$helper.toast('邀请链接未加载');
    return;
  }
  state.showQRCode = true;
}

// 关闭二维码弹窗
function handleCloseQRCode() {
  state.showQRCode = false;
}

// 二维码生成成功
function handleQRCodeSuccess() {
  console.log('二维码生成成功');
}

// 二维码生成失败
function handleQRCodeFail(error) {
  console.error('二维码生成失败:', error);
  xxep.$helper.toast('二维码生成失败');
}

// 保存二维码到相册
async function handleSaveQRCode() {
  if (!qrcodeRef.value) {
    xxep.$helper.toast('二维码组件未初始化');
    return;
  }
  
  try {
    await qrcodeRef.value.saveToAlbum();
    xxep.$helper.toast('已保存到相册');
  } catch (error) {
    if (error.errMsg && error.errMsg.includes('auth')) {
      uni.showModal({
        title: '提示',
        content: '需要相册权限，请在设置中开启',
        confirmText: '去设置',
        success: (modalRes) => {
          if (modalRes.confirm) {
            uni.openSetting();
          }
        }
      });
    } else {
      xxep.$helper.toast('保存失败');
    }
  }
}

// 复制链接
function handleCopyUrl() {
  uni.setClipboardData({
    data: state.inviteUrl,
    success: () => {
      xxep.$helper.toast('链接已复制');
    }
  });
}
// 分享二维码
async function handleShareQRCode() {
  if (!qrcodeRef.value) {
    xxep.$helper.toast('二维码组件未初始化');
    return;
  }
  
  try {
    const filePath = await qrcodeRef.value.getTempFilePath();
    
    // #ifdef H5
    xxep.$helper.toast('请点击右上角分享');
    // #endif
    
    // #ifndef H5
    uni.shareWithSystem({
      type: 'image',
      imageUrl: filePath,
      success: () => {
        xxep.$helper.toast('分享成功');
      },
      fail: () => {
        xxep.$helper.toast('分享失败');
      }
    });
    // #endif
  } catch (error) {
    xxep.$helper.toast('获取二维码失败');
  }
}

// 跳转我的团队
function handleGoTeam() {
  uni.navigateTo({
    url: '/pages/user/team'
  });
}
</script>

<style lang="scss" scoped>
// 背景图片
.bg-image {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
//   opacity: 0.3;
}

.page-content {
  position: relative;
  z-index: 1;
  padding: 32rpx;
  display: flex;
  flex-direction: column;
  gap: 32rpx;
}

// 卡片基础样式
.card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10rpx);
  border-radius: 32rpx;
  padding: 40rpx;
  box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
}

// 邀请信息卡片
.invite-header-card {
  .invite-info {
    margin-bottom: 32rpx;
    
    .info-title {
      font-size: 32rpx;
      font-weight: 600;
      color: #1F2937;
      margin-bottom: 24rpx;
    }
    
    .invite-code-row {
      display: flex;
      align-items: center;
      gap: 16rpx;
      margin-bottom: 16rpx;
      
      .code-label {
        font-size: 28rpx;
        color: #6B7280;
      }
      
      .code-value {
        font-size: 36rpx;
        font-weight: 700;
        color: #4285F4;
        letter-spacing: 4rpx;
        font-family: 'Courier New', monospace;
      }
      
      .btn-copy {
        padding: 8rpx 24rpx;
        height: auto;
        line-height: 1.5;
        background: linear-gradient(90deg, #4285F4, #5A9CFF);
        color: #FFFFFF;
        font-size: 24rpx;
        font-weight: 500;
        border-radius: 20rpx;
        border: none;
        
        &::after {
          border: none;
        }
        
        &:active {
          opacity: 0.8;
        }
      }
    }
    
    .invite-url-row {
      .url-text {
        font-size: 24rpx;
        color: #9CA3AF;
        word-break: break-all;
      }
    }
  }
  
  .action-buttons {
    display: flex;
    gap: 24rpx;
    
    .btn-action {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12rpx;
      height: 80rpx;
      border-radius: 40rpx;
      font-size: 28rpx;
      font-weight: 500;
      border: none;
      transition: all 0.3s ease;
      
      &::after {
        border: none;
      }
      
      &:active {
        transform: scale(0.96);
        opacity: 0.8;
      }
      
      &.qrcode-btn {
        background: linear-gradient(135deg, #EBF4FF, #D6E8FF);
        color: #4285F4;
      }
      
      &.team-btn {
        background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
        color: #00C853;
      }
    }
  }
}

// 会员等级卡片列表
.level-cards {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
  
  .level-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10rpx);
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
    border: 2rpx solid transparent;
    transition: all 0.3s ease;
    
    &.is-current {
      background: rgba(255, 255, 255, 0.8);
      border-color: #4285F4;
      box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.2);
    }
    
    .level-header {
      display: flex;
      align-items: center;
      gap: 20rpx;
      margin-bottom: 20rpx;
      
      .level-image {
        width: 80rpx;
        height: 80rpx;
        flex-shrink: 0;
      }
      
      .level-badge {
        width: 60rpx;
        height: 60rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
      }
      
      .level-name {
        flex: 1;
        font-size: 32rpx;
        font-weight: 600;
        color: #1F2937;
      }
      
      .level-status {
        flex-shrink: 0;
      }
    }
    
    .level-body {
      padding-left: 80rpx;
      font-size: 26rpx;
      color: #6B7280;
      line-height: 1.6;
      white-space: pre-line;
    }
  }
}

// 按钮样式
.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  height: 88rpx;
  border-radius: 44rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
  
  &::after {
    border: none;
  }
  
  &:active {
    transform: scale(0.98);
    opacity: 0.8;
  }
}

.btn-primary {
  background: linear-gradient(90deg, #4285F4, #5A9CFF);
  color: #FFFFFF;
  box-shadow: 0 8rpx 24rpx rgba(66, 133, 244, 0.3);
}

.btn-secondary {
  background: #F3F4F6;
  color: #1F2937;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06);
}

.btn-group {
  display: flex;
  gap: 24rpx;
  
  button {
    flex: 1;
  }
}

// 二维码弹窗
.qrcode-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  
  .modal-mask {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(10rpx);
  }
  
  .modal-content {
    position: relative;
    width: 90%;
    max-width: 600rpx;
    background: #FFFFFF;
    border-radius: 32rpx;
    padding: 48rpx;
    box-shadow: 0 24rpx 48rpx rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s ease-out;
  }
  
  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 40rpx;
    
    .modal-title {
      font-size: 36rpx;
      font-weight: 600;
      color: #1F2937;
    }
    
    .modal-close {
      width: 48rpx;
      height: 48rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: #F3F4F6;
      transition: all 0.3s ease;
      
      &:active {
        background: #E5E7EB;
        transform: scale(0.9);
      }
    }
  }
}

// 二维码
.qrcode-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 32rpx;
  margin-bottom: 40rpx;
  
  ::v-deep .s-qrcode {
    background: #FFFFFF;
    border-radius: 16rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
    padding: 20rpx;
  }
  
  .qrcode-label {
    font-size: 36rpx;
    font-weight: 700;
    color: #1F2937;
    letter-spacing: 6rpx;
    font-family: 'Courier New', monospace;
  }
  
  .qrcode-desc {
    font-size: 28rpx;
    color: #6B7280;
  }
}

@keyframes modalSlideUp {
  from {
    transform: translateY(100rpx);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
