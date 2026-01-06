const SaPagination = {
    template: `
    <el-pagination class="sa-pagination" v-model:current-page="paginationData.page" v-model:page-size="paginationData.list_rows"
        :page-sizes="[10, 50, 100]" :pager-count="5" layout="total, sizes, prev, pager, next, jumper"
        :total="paginationData.total" @size-change="onChangeSize" @current-change="onChangeCurrent">
    </el-pagination>`,
    emit: ['update:modelValue', 'pagination-change'],
    props: {
        modelValue: {
            type: Object,
            default: {
                page: 1,
                list_rows: 10,
                total: 0,
            }
        }
    },
    setup(props, { emit }) {
        const { ref } = Vue

        const paginationData = ref(props.modelValue)

        function onChangeSize(e) {
            paginationData.value.list_rows = e
            emit('update:modelValue', paginationData.value)
            emit('pagination-change')
        }

        function onChangeCurrent(e) {
            paginationData.value.page = e
            emit('update:modelValue', paginationData.value)
            emit('pagination-change')
        }

        return {
            paginationData,
            onChangeSize,
            onChangeCurrent
        }
    }
}