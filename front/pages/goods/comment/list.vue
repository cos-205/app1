<!-- 页面  -->
<template>
  <s-layout title="全部评价">
    <su-tabs
      :list="state.type"
      :scrollable="false"
      @change="onTabsChange"
      :current="state.currentTab"
    ></su-tabs>
    <view class="ss-m-t-20">
      <view class="list-item" v-for="item in state.pagination.data" :key="item">
        <comment-item :item="item"></comment-item>
      </view>
    </view>
    <s-empty v-if="state.pagination.total === 0" text="暂无数据" icon="/static/data-empty.png" />
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
  import xxep from '@/xxep';
  import { onLoad, onReachBottom } from '@dcloudio/uni-app';
  import { computed, reactive } from 'vue';
  import _ from 'lodash';
  import commentItem from '../components/detail/comment-item.vue';

  const pagination = {
    data: [],
    current_page: 1,
    total: 1,
    last_page: 1,
  };
  const state = reactive({
    list: [],
    type: [],
    currentTab: 0,
    pagination: {
      data: [],
      current_page: 1,
      total: 1,
      last_page: 1,
    },
    commentId: 0,
    code: 'all',
  });
  // 切换选项卡
  function onTabsChange(e) {
    state.pagination = pagination;
    state.currentTab = e.index;
    state.code = e.code;
    getList(state.commentId, e.code);
  }
  async function getType(id) {
    const { code, data } = await xxep.$api.goods.getType(id);
    if (code === 1) {
      state.type = data;
    }
  }
  async function getList(id, code, page = 1, list_rows = 6) {
    state.loadStatus = 'loading';
    let res = await xxep.$api.goods.comment(id, {
      type: code,
      list_rows,
      page,
    });
    if (res.code === 1) {
      let orderList = _.concat(state.pagination.data, res.data.data);
      state.pagination = {
        ...res.data,
        data: orderList,
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
      getList(state.commentId, state.code, state.pagination.current_page + 1);
    }
  }
  onLoad((options) => {
    state.commentId = options.id;
    getType(state.commentId);
    getList(state.commentId);
  });
  // 上拉加载更多
  onReachBottom(() => {
    loadmore();
  });
</script>

<style lang="scss" scoped>
  .list-item {
    padding: 32rpx 30rpx 20rpx 20rpx;
    background: #fff;
  }
</style>
