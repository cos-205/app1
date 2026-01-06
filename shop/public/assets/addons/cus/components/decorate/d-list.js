const DList = {
    template: `
    <div class="d-list">
        <div class="title">
            <slot name="title">数据</slot>
        </div>
        <div class="wrap">
            <draggable v-model="listData" item-key="element" :animation="300" handle=".sortable-drag" @end="onEnd">
                <template #item="{ element, index }">
                    <div class="list-item">
                        <div class="move sa-flex">
                            <i class="iconfont iconmove sortable-drag"></i>
                            <slot name="deleteIcon">
                                <span class="list-delete" @click="onDelete(index)">删除</span>
                            </slot>
                        </div>
                        <slot name="listitem" :element="element"></slot>
                    </div>
                </template>
            </draggable>
            <slot name="add" v-if="listData.length != leng">
                <el-button class="add-button" icon="Plus" @click="onAdd">添加</el-button>
            </slot>
        </div>
    </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: Array,
            default: [],
        },
        item: {
            type: Object,
            default: {},
        },
        leng: {
            type: Number,
            default: null,
        },
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue

        const listData = ref(props.modelValue);
        watch(
            () => props.modelValue,
            () => {
                listData.value = props.modelValue;
            },
        );

        function onAdd() {
            listData.value.push(JSON.parse(JSON.stringify(props.item)));
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