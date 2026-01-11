<!-- 页面 -->
<template>
  <view class="ss-user-info-wrap ss-p-t-50">
    <view class="ss-flex ss-col-top ss-row-between ss-m-b-20">
      <view class="left-box ss-flex ss-col-center ss-m-l-36">
        <!-- 头像 -->
        <view class="avatar-box ss-m-r-24">
          <image
            class="avatar-img"
            :src="
              isLogin
                ? xxep.$url.cdn(userInfo.avatar)
                : xxep.$url.static('/assets/addons/cus/uniapp/default_avatar.png')
            "
            mode="aspectFill"
            @tap="xxep.$router.go('/pages/user/info')"
          ></image>
        </view>
        
        <!-- 用户信息 -->
        <view class="info-content">
          <!-- 第一行：昵称 -->
          <view class="info-row nickname-row">
            <view class="nick-name">{{ userInfo?.realname || userInfo?.nickname || nickname }}</view>
          </view>
          
          <!-- 第二行：手机号 -->
          <view class="info-row" v-if="isLogin">
            <text class="label">手机：</text>
            <text class="value">{{ maskedMobile }}</text>
          </view>
          
          <!-- 第三行：邀请码 -->
          <view class="info-row" v-if="isLogin">
            <text class="label">邀请码：</text>
            <text class="value">{{ displayInviteCode }}</text>
            <button 
              class="copy-btn" 
              @tap.stop="copyInviteCode"
              v-if="displayInviteCode !== '未设置'"
            >
              复制
            </button>
          </view>
        </view>
      </view>
      
      <!-- 右侧：会员等级 + 入场券 + 二维码 -->
      <view class="right-box ss-m-r-52">
        <!-- 会员等级 -->
        <view 
          class="member-level-wrapper" 
          @tap="showMemberDetail"
          v-if="isLogin"
        >
          <view class="member-level-content">
            <image 
              v-if="memberLevelImage" 
              class="member-level-image" 
              :src="memberLevelImage" 
              mode="aspectFit"
            />
            <text class="level-name">{{ displayMemberLevel }}</text>
          </view>
        </view>
        
        <!-- 入场券 -->
        <view 
          class="ticket-count" 
          @tap="showTickets"
          v-if="isLogin && appInfo.hide_entry_ticket === 0"
        >
          入场券：<text class="count">{{ displayTicketCount }}</text>张
        </view>
        
        <!-- 二维码按钮 -->
        <!-- <button class="ss-reset-button qrcode-btn" @tap="showShareModal">
          <text class="sicon-qrcode"></text>
        </button> -->
      </view>
    </view>
  </view>
</template>

<script setup>
  /**
   * 用户卡片
   *
   * @property {Number} leftSpace 									- 容器左间距
   * @property {Number} rightSpace 									- 容器右间距
   *
   * @property {String} avatar 					- 头像
   * @property {String} nickname 					- 昵称
   * @property {String} mobile 					- 手机号
   * @property {String} inviteCode 				- 邀请码
   * @property {String} memberLevel 				- 会员等级
   * @property {Number} ticketCount 				- 入场券数量
   * @property {String} vip		  				- 等级
   * @property {String} collectNum 				- 收藏数
   * @property {String} likeNum 					- 点赞数
   *
   *
   */
  import { computed, reactive } from 'vue';
  import xxep from '@/xxep';
  import { showShareModal, showAuthModal } from '@/xxep/hooks/useModal';

  // 用户信息
  const userInfo = computed(() => xxep.$store('user').userInfo);

  // 是否登录
  const isLogin = computed(() => xxep.$store('user').isLogin);
  
  // 获取应用配置（功能开关）
  const appInfo = computed(() => xxep.$store('app').info);
  
  // 接收参数
  const props = defineProps({
    background: {
      type: String,
      default: '',
    },
    // 头像
    avatar: {
      type: String,
      default: '',
    },
    nickname: {
      type: String,
      default: '请先登录',
    },
    // 手机号
    mobile: {
      type: String,
      default: '',
    },
    // 邀请码
    inviteCode: {
      type: String,
      default: '',
    },
    // 会员等级
    memberLevel: {
      type: String,
      default: '',
    },
    // 入场券数量
    ticketCount: {
      type: [String, Number],
      default: 0,
    },
    vip: {
      type: [String, Number],
      default: '1',
    },
    collectNum: {
      type: [String, Number],
      default: '1',
    },
    likeNum: {
      type: [String, Number],
      default: '1',
    },
  });

  // 手机号脱敏处理
  const maskedMobile = computed(() => {
    if (!userInfo.value?.mobile && !props.mobile) return '未绑定';
    const mobile = userInfo.value?.mobile || props.mobile;
    return mobile.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');
  });

  // 邀请码显示
  const displayInviteCode = computed(() => {
    return userInfo.value?.invite_code || props.inviteCode || '未设置';
  });

  // 会员等级ID到名称的映射（与数据库 fa_fuka_member_level 表对应）
  const memberLevelMap = {
    0: { name: '普通会员', type: 'normal' },
    1: { name: '铂金会员', type: 'platinum' },
    2: { name: '黄金会员', type: 'gold' },
    3: { name: '钻石会员', type: 'diamond' },
    4: { name: '黑金会员', type: 'black' },
    5: { name: '至尊会员', type: 'supreme' },
  };

  // 会员等级显示
  const displayMemberLevel = computed(() => {
    const level = userInfo.value?.member_level || props.memberLevel;
    
    // 如果已经是文字名称，直接返回
    if (level && isNaN(level)) {
      return level;
    }
    
    // 如果是数字ID，转换为名称
    const levelId = parseInt(level) || 0;
    return memberLevelMap[levelId]?.name || '普通会员';
  });

  // 会员等级图片路径
  const memberLevelImage = computed(() => {
    const level = userInfo.value?.member_level || props.memberLevel;
    
    // 如果是数字ID，生成图片路径
    if (level !== undefined && level !== null && !isNaN(level)) {
      const levelId = parseInt(level);
      return `/static/level/${levelId}.png`;
    }
    
    return '';
  });

  // 会员等级样式类
  const memberLevelClass = computed(() => {
    const level = userInfo.value?.member_level || props.memberLevel;
    
    // 如果有 member_level_type 字段，优先使用
    if (userInfo.value?.member_level_type) {
      return `level-${userInfo.value.member_level_type}`;
    }
    
    // 如果是数字ID，从映射表获取类型
    if (level !== undefined && level !== null && !isNaN(level)) {
      const levelId = parseInt(level);
      const levelType = memberLevelMap[levelId]?.type || 'normal';
      return `level-${levelType}`;
    }
    
    // 如果是文字名称，根据名称判断类型
    const levelName = String(level || '');
    if (levelName.includes('至尊')) return 'level-supreme';
    if (levelName.includes('黑金')) return 'level-black';
    if (levelName.includes('钻石')) return 'level-diamond';
    if (levelName.includes('黄金')) return 'level-gold';
    if (levelName.includes('铂金')) return 'level-platinum';
    
    return 'level-normal';
  });

  // 入场券数量（集福机会次数，对应数据库字段 fuka_chance）
  const displayTicketCount = computed(() => {
    return userInfo.value?.fuka_chance || userInfo.value?.ticket_count || props.ticketCount || 0;
  });

  // 复制邀请码
  function copyInviteCode() {
    const code = displayInviteCode.value;
    if (code === '未设置') {
      uni.showToast({
        title: '暂无邀请码',
        icon: 'none',
      });
      return;
    }

    uni.setClipboardData({
      data: code,
      success: () => {
        uni.showToast({
          title: '邀请码已复制',
          icon: 'success',
        });
      },
      fail: () => {
        uni.showToast({
          title: '复制失败',
          icon: 'none',
        });
      },
    });
  }

  // 查看会员详情
  function showMemberDetail() {
    if (!isLogin.value) {
      showAuthModal();
      return;
    }
    xxep.$router.go('/pages/user/member');
  }

  // 查看入场券
  function showTickets() {
    if (!isLogin.value) {
      showAuthModal();
      return;
    }
    
    // 显示入场券说明
    uni.showModal({
      title: '入场券说明',
      content: '本次与会名额仅有3千名。\n\n入场券预定费，到场后原路退还。\n\n每位到场参与人员可获得10万人民币现金。',
      confirmText: '我知道了',
      showCancel: false,
      success: (res) => {
        if (res.confirm) {
          // 用户点击确认后，可以跳转到入场券详情页（如果有的话）
          // xxep.$router.go('/pages/user/tickets');
        }
      }
    });
  }

  function onBind() {
    showAuthModal('changeMobile');
  }
</script>

<style lang="scss" scoped>
  .ss-user-info-wrap {
    box-sizing: border-box;

    .left-box {
      flex: 1;
      
      .avatar-box {
        width: 100rpx;
        height: 100rpx;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;

        .avatar-img {
          width: 100%;
          height: 100%;
        }
      }

      .info-content {
        display: flex;
        flex-direction: column;
        gap: 10rpx;

        .info-row {
          display: flex;
          align-items: center;
          line-height: 1.5;

          &.nickname-row {
            margin-bottom: 4rpx;
          }

          .nick-name {
            font-size: 34rpx;
            font-weight: 500;
            color: #333333;
          }

          .label {
            font-size: 24rpx;
            color: #999999;
            margin-right: 8rpx;
          }

          .value {
            font-size: 24rpx;
            color: #666666;
          }

          .copy-btn {
            margin-left: 12rpx;
            padding: 4rpx 16rpx;
            background: #fff4e6;
            border-radius: 8rpx;
            font-size: 20rpx;
            color: #ff6100;
            border: none;
            line-height: 1.4;
            
            &:active {
              background: #ffe7cc;
            }
          }
        }
      }
    }

    .right-box {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 12rpx;
      padding-top: 4rpx;

      .member-level-wrapper {
        .member-level-content {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 8rpx;
          
          .member-level-image {
            width: 55rpx;
            height: 55rpx;
          }
          
          .level-name {
            font-size: 22rpx;
            font-weight: 600;
            color: #333333;
            white-space: nowrap;
          }
        }
        
        .member-level {
          display: flex;
          align-items: center;
          font-size: 22rpx;
          font-weight: 500;
          padding: 6rpx 16rpx;
          border-radius: 12rpx;
          white-space: nowrap;
          
          .level-label {
            font-size: 20rpx;
            opacity: 0.8;
          }
          
          .level-name {
            font-size: 22rpx;
            font-weight: 600;
          }
          
          // 0 = 普通会员
          &.level-normal {
            color: #999999;
            background: #f5f5f5;
          }
          
          // 1 = 铂金会员
          &.level-platinum {
            color: #7f8c8d;
            background: linear-gradient(135deg, #ecf0f1 0%, #d5dbdb 100%);
          }
          
          // 2 = 黄金会员
          &.level-gold {
            color: #d4af37;
            background: linear-gradient(135deg, #fff9e6 0%, #ffe9b3 100%);
          }
          
          // 3 = 钻石会员
          &.level-diamond {
            color: #4a90e2;
            background: linear-gradient(135deg, #e8f4ff 0%, #d0e9ff 100%);
          }
          
          // 4 = 黑金会员
          &.level-black {
            color: #2c3e50;
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            
            .level-label, .level-name {
              color: #ecf0f1;
            }
          }
          
          // 5 = 至尊会员
          &.level-supreme {
            color: #8e44ad;
            background: linear-gradient(135deg, #e8d5f2 0%, #d4b3e6 100%);
          }
        }
      }

      .ticket-count {
        font-size: 22rpx;
        color: #666666;

        .count {
          color: #ff6100;
          font-weight: 600;
          font-size: 26rpx;
          margin: 0 4rpx;
        }
      }

      .qrcode-btn {
        margin-top: 8rpx;
        
        .sicon-qrcode {
          font-size: 40rpx;
          color: #333333;
        }
      }
    }

    .vip-img {
      width: 30rpx;
      height: 30rpx;
    }
  }

  .bind-mobile-box {
    width: 100%;
    height: 84rpx;
    padding: 0 34rpx 0 44rpx;
    box-sizing: border-box;
    background: #ffffff;
    box-shadow: 0px -8rpx 9rpx 0px rgba(#e0e0e0, 0.3);

    .cicon-mobile-o {
      font-size: 30rpx;
      color: #ff690d;
    }

    .mobile-title {
      font-size: 24rpx;
      font-weight: 500;
      color: #ff690d;
    }

    .bind-btn {
      width: 100rpx;
      height: 50rpx;
      background: #ff6100;
      border-radius: 25rpx;
      font-size: 24rpx;
      font-weight: 500;
      color: #ffffff;
    }
  }
</style>
