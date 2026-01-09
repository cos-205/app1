const SaFilterCondition = {
    template: `
    <div class="condition-wrap">
        <div class="condition-item" v-for="(value,key) in filter.condition">
            {{value}}
            <el-icon class="close">
                <close />
            </el-icon>
            <el-icon class="circle-close-filled" @click="onDeleteFilter(key)">
                <circle-close-filled />
            </el-icon>
        </div>
    </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: Object,
            default: ''
        }
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue
        const filter = ref(props.modelValue)

        watch(() => props.modelValue, () => {
            filter.value = props.modelValue
        })

        function onDeleteFilter(key) {
            filter.value.data[key] = JSON.parse(JSON.stringify(filter.value.tools[key].value))
            delete filter.value.condition[key]
            emit('update:modelValue', filter.value)
            emit('filter-delete', key)
        }
        return {
            filter,
            onDeleteFilter,
        }
    }
}