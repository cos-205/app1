<template>
  <view>
    <view v-for="(item, index) in popupList" :key="item.src">
      <su-popup
        v-if="index === currentIndex"
        :show="item.isShow"
        type="center"
        backgroundColor="none"
        round="0"
        :showClose="true"
        :isMaskClick="false"
        @close="onClose(index)"
      >
        <view class="img-box">
          <image
            class="modal-img"
            :src="xxep.$url.cdn(item.src)"
            mode="widthFix"
            @tap.stop="onPopup(item.url)"
          />
        </view>
      </su-popup>
    </view>
  </view>
</template>

<script setup>
  import xxep from '@/xxep';
  import { computed, ref } from 'vue';
  import { onShow } from '@dcloudio/uni-app';
  import { saveAdvHistory } from '@/xxep/hooks/useModal';

  // const modalStore = xxep.$store('modal');
  const modalStore = JSON.parse(uni.getStorageSync('modal-store') || '{}');
  const advHistory = modalStore.advHistory || [];
  const currentIndex = ref(0);
  const popupList = computed(() => {
    const list = xxep.$store('app').template.basic?.popupImage?.list || [];
    const newList = [];
    if (list.length > 0) {
      list.forEach((adv) => {
        if (adv.show === 1 && advHistory.includes(adv.src)) {
          adv.isShow = false;
        } else {
          adv.isShow = true;
          newList.push(adv);
        }
        saveAdvHistory(adv);
      });
    }
    return newList;
  });

  function onPopup(path) {
    xxep.$router.go(path);
  }

  function onClose(index) {
    currentIndex.value = index + 1;
    popupList.value[index].isShow = false;
  }
</script>

<style lang="scss" scoped>
  .img-box {
    width: 610rpx;
    // height: 800rpx;
  }
  .modal-img {
    width: 100%;
    height: 100%;
  }
</style>
