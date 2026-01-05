<template>
  <view
    class="richtext"
    :style="[
      {
        marginLeft: styles.marginLeft + 'px',
        marginRight: styles.marginRight + 'px',
        marginBottom: styles.marginBottom + 'px',
        marginTop: styles.marginTop + 'px',
        padding: styles.padding + 'px',
      },
    ]"
  >
    <mp-html :content="state.content"></mp-html>
  </view>
</template>
<script setup>
  import { reactive, onMounted } from 'vue';
  import xxep from '@/xxep';
  const props = defineProps({
    data: {
      type: Object,
      default: {},
    },
    styles: {
      type: Object,
      default() {},
    },
  });
  const state = reactive({
    content: '',
  });
  onMounted(async () => {
    const { code, data } = await xxep.$api.data.richtext(props.data.id);
    if (code === 1) {
      state.content = data.content;
    }
  });
</script>
<style lang="scss" scoped>
  :deep() {
    image {
      display: block;
    }
  }
</style>
