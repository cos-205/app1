<template>
  <s-layout class="widthdraw-log-wrap" title="提现记录">
    <!-- 记录卡片 -->
    <view class="wallet-log-box ss-p-b-30">
      <view class="log-list" v-for="(item, index) in state.pagination.data" :key="item.id">
        <view class="head ss-flex ss-col-center ss-row-between">
          <view class="title">{{
            item.withdraw_type === 'bank'
              ? '提现至银行卡'
              : item.withdraw_type === 'alipay'
                ? '提现至支付宝'
                : '提现至微信'
          }}</view>
          <view
            class="num"
            :class="{
              'warning-color': item.status === 1,
              'success-color': item.status === 2,
              'danger-color': item.status < 0,
            }"
          >
            {{ item.amount }}元
          </view>
        </view>
        <view class="status-box item ss-flex ss-col-center ss-row-between">
          <view class="item-title">申请状态</view>
          <view
            class="status-text"
            :class="{
              'warning-color': item.status === 1,
              'danger-color': item.status < 0,
              'success-color': item.status === 2,
            }"
          >
            {{ item.status_text }}
          </view>
        </view>
        <view class="time-box item ss-flex ss-col-center ss-row-between">
          <text class="item-title">账户信息</text>
          <view class="time ss-ellipsis-1" v-if="item.withdraw_type === 'bank'"
            >{{ item.withdraw_info_hidden.开户行 }}[{{ item.withdraw_info_hidden.银行卡号 }}]</view
          >
          <view class="time ss-ellipsis-1" v-if="item.withdraw_type === 'alipay'">
            支付宝[{{ item.withdraw_info_hidden.支付宝账户 }}]
          </view>
          <view class="time ss-ellipsis-1" v-if="item.withdraw_type === 'wechat'">微信零钱</view>
        </view>
        <view class="time-box item ss-flex ss-col-center ss-row-between">
          <text class="item-title">提现单号</text>
          <view class="time"> {{ item.withdraw_sn }} </view>
        </view>
        <view class="time-box item ss-flex ss-col-center ss-row-between">
          <text class="item-title">手续费</text>
          <view class="time">{{ item.charge_fee }}元</view>
        </view>
        <view class="time-box item ss-flex ss-col-center ss-row-between">
          <text class="item-title">申请时间</text>
          <view class="time"> {{ item.createtime }}</view>
        </view>

        <view
          v-if="
            item.withdraw_type === 'wechat' &&
            item.status === 1 &&
            item.wechat_transfer_state === 'WAIT_USER_CONFIRM'
          "
          class="opt-box ss-flex ss-row-right"
        >
          <button class="ss-reset-button cancel-button" @tap="onCancel(item, index)"
            >取消操作</button
          >
          <button
            class="ss-reset-button confirm-button ui-BG-Main-Gradient"
            @tap="onRetry(item, index)"
          >
            继续提现
          </button>
        </view>
      </view>
    </view>
    <s-empty
      v-if="state.pagination.total === 0"
      icon="/static/comment-empty.png"
      text="暂无提现记录"
    ></s-empty>
    <uni-load-more
      v-if="state.pagination.total > 0"
      :status="state.loadStatus"
      :content-text="{
        contentdown: '上拉加载更多',
      }"
      @tap="loadmore"
    />
  </s-layout>
</template>

<script setup>
  import { reactive } from 'vue';
  import sheep from '@/sheep';
  import { onLoad, onReachBottom } from '@dcloudio/uni-app';
  import _ from 'lodash';
  const state = reactive({
    currentTab: 0,
    pagination: {
      data: [],
      current_page: 1,
      total: 1,
      last_page: 1,
    },
    loadStatus: '',
  });
  async function getList(page = 1, list_rows = 6) {
    const res = await sheep.$api.pay.withdraw.list({ list_rows, page });
    if (res.code === 1) {
      let logList = _.concat(state.pagination.data, res.data.data);
      state.pagination = {
        ...res.data,
        data: logList,
      };
      if (state.pagination.current_page < state.pagination.last_page) {
        state.loadStatus = 'more';
      } else {
        state.loadStatus = 'noMore';
      }
    }
  }
  // 加载更多
  function loadmore() {
    if (state.loadStatus !== 'noMore') {
      getList(state.pagination.current_page + 1);
    }
  }

  // 取消提现
  const onCancel = async (withdraw, index) => {
    if (sheep.$platform.name !== withdraw.platform) {
      sheep.$helper.toast('请在发起该次提现的平台操作');
      return;
    }

    let payload = {
      withdraw_sn: withdraw.withdraw_sn,
      type: withdraw.withdraw_type,
    };

    let { code, msg, data } = await sheep.$api.pay.withdraw.cancel(payload);
    if (code === -1) {
      sheep.$platform.useProvider('wechat').bind();
    }
    if (code === 1) {
      sheep.$helper.toast('提现已撤销, 请等待处理结果');
      // 刷新列表
      state.pagination.data[index] = data;
    } else {
      sheep.$helper.toast(msg);
    }
  };

  // 继续提现
  const onRetry = async (withdraw, index) => {
    if (sheep.$platform.name !== withdraw.platform) {
      sheep.$helper.toast('请在发起该次提现的平台操作');
      return;
    }
    let payload = {
      withdraw_sn: withdraw.withdraw_sn,
      type: withdraw.withdraw_type,
    };

    const { data } = await sheep.$api.pay.withdraw.retry(payload);

    if (payload.type === 'wechat') {
      sheep.$platform.useProvider('wechat').transfer(data.transfer_data);
    }
  };

  onLoad(() => {
    getList();
  });
  onReachBottom(() => {
    loadmore();
  });
</script>

<style lang="scss" scoped>
  // 记录卡片
  .log-list {
    min-height: 213rpx;
    background: $white;
    margin-bottom: 10rpx;
    padding-bottom: 10rpx;

    .head {
      padding: 0 35rpx;
      height: 80rpx;
      border-bottom: 1rpx solid $gray-e;
      margin-bottom: 20rpx;

      .title {
        font-size: 28rpx;
        font-weight: 500;
        color: $dark-3;
      }

      .num {
        font-size: 28rpx;
        font-weight: 500;
      }
    }

    .item {
      padding: 0 30rpx 10rpx;

      .item-icon {
        color: $gray-d;
        font-size: 36rpx;
        margin-right: 8rpx;
      }

      .item-title {
        width: 180rpx;
        font-size: 24rpx;
        font-weight: 400;
        color: #666666;
      }

      .status-text {
        font-size: 24rpx;
        font-weight: 500;
      }

      .time {
        font-size: 24rpx;
        font-weight: 400;
        color: #c0c0c0;
      }
    }
  }
  .warning-color {
    color: #faad14;
  }
  .danger-color {
    color: #ff4d4f;
  }
  .success-color {
    color: #67c23a;
  }

  .opt-box {
    margin-top: 10rpx;
    padding-top: 20rpx;
    padding-bottom: 10rpx;
    padding-right: 30rpx;
    border-top: 1rpx solid $gray-e;

    .cancel-button {
      width: 160rpx;
      height: 60rpx;
      font-size: 26rpx;
      border-radius: 30rpx;
      margin-right: 10rpx;
      background: $gray-e;
    }
    .confirm-button {
      width: 160rpx;
      height: 60rpx;
      font-size: 26rpx;
      border-radius: 30rpx;
    }
  }
</style>
