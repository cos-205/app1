const DGoodsSelect = {
  template: `
    <div class="d-goods-select">
      <div class="card">
        <div class="title">
          <slot name="title">商品选择</slot>
        </div>
        <div class="wrap">
          <draggable
            class="sa-flex sa-flex-wrap"
            v-model="listData"
            item-key="element"
            :animation="300"
            @end="onEnd"
          >
            <template #item="{ element, index }">
              <div class="goods-item">
                <sa-image :url="type=='goods'?element.image:element.feeds_img" size="44"></sa-image>
                <div class="goods-delete" @click="onDelete(index)">
                  <el-icon>
                    <Delete />
                  </el-icon>
                </div>
              </div>
            </template>
          </draggable>
          <slot name="add">
            <el-button class="add-button" icon="Plus" @click="onAdd">添加</el-button>
          </slot>
        </div>
      </div>
    </div>`,
  emit: ['update:modelValue'],
  props: {
    modelValue: {
      type: Array,
      default: [],
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    max: {
      type: Number,
      default: 0,
    },
    type: {
      type: String,
      default: 'goods',
    },
  },
  setup(props, { emit }) {
    const { ref, watch } = Vue

    const listData = ref(props.modelValue || []);
    watch(
      () => props.modelValue,
      () => {
        listData.value = props.modelValue;
      },
    );

    function onAdd() {
      let multiple = true
      let ids = []
      listData.value.forEach(item => {
        ids.push(item.id)
      })
      if (props.type == 'goods') {
        Fast.api.open(`cus/goods/goods/select?multiple=${multiple}&ids=${ids.join(',')}`, "选择商品", {
          callback(data) {
            emit('update:modelValue', data);
          }
        })
      } else if (props.type == 'mplive') {
        Fast.api.open(`cus/app/mplive/room/select`, "选择直播间", {
          callback(data) {
            emit('update:modelValue', data);
          }
        })
      }
    }
    function onDelete(index) {
      listData.value.splice(index, 1);
    }
    function onEnd() {
      emit('update:modelValue', listData.value);
    }
    return {
      listData,
      onAdd,
      onDelete,
      onEnd
    }
  }
}