<template>
  <s-layout class="set-wrap" :title="state.title" :bgStyle="{ color: '#FFF' }">
    <view class="ss-p-30 richtext"><mp-html :content="state.content"></mp-html></view>
  </s-layout>
</template>

<script setup>
  import { onLoad } from '@dcloudio/uni-app';
  import { reactive } from 'vue';
  import xxep from '@/xxep';

  const state = reactive({
    title: '',
    content: '',
  });

  async function getRichTextContent(id) {
    const { code, data } = await xxep.$api.data.richtext(id);
    if (code === 1) {
      state.content = data.content;
      if (state.title === '') {
        state.title = data.title;
        uni.setNavigationBarTitle({
          title: state.title,
        });
      }
    }
  }
  onLoad((options) => {
    if (options.title) {
      state.title = options.title;
      uni.setNavigationBarTitle({
        title: state.title,
      });
    }
    getRichTextContent(options.id);
  });
</script>

<style lang="scss" scoped>
  .set-title {
    margin: 0 30rpx;
  }

  :deep() {
    image {
      display: block;
    }
  }
</style>
