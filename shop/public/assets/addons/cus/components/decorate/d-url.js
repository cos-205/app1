const DUrl = {
    template: `
    <div class="d-url sa-flex">
        <el-input v-model="path" :placeholder="placeholder" @input="onChangeUrl">
        <template #append>
            <span class="cursor-pointer" @click="onSelectUrl">选择</span>
        </template>
        </el-input>
    </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '请输入或选择',
        },
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue

        const path = ref(props.modelValue);
        watch(
            () => props.modelValue,
            () => {
                path.value = props.modelValue;
            },
        );

        function onChangeUrl() {
            emit('update:modelValue', path.value);
        }
        function onSelectUrl() {
            Fast.api.open("cus/data/page/select", "选择链接", {
                callback(data) {
                    emit('update:modelValue', data.path);
                }
            })
        }
        return {
            path,
            onChangeUrl,
            onSelectUrl
        }
    }
}