const DTextColor = {
    template: `
    <div class="d-text-color sa-flex">
        <el-input v-model="modelValue.text" :placeholder="placeholder" :maxlength="maxlength"
            :show-word-limit="showWordLimit" @input="onChange"></el-input>
        <div class="color sa-flex">
            <img class="empty" src="/assets/addons/cus/img/decorate/picker.png" />
            <el-color-picker v-model="modelValue.color" @change="onChange" />
        </div>
    </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: Object,
            default: {
                text: '',
                color: ''
            }
        },
        placeholder: {
            type: String,
            default: '请输入',
        },
        maxlength: {
            type: [String, Number],
            default: '',
        },
        showWordLimit: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue

        const textColor = ref(props.modelValue);
        watch(
            () => props.modelValue,
            () => {
                textColor.value = props.modelValue;
            },
        );

        function onChange() {
            emit('update:modelValue', textColor.value);
        }
        return {
            textColor,
            onChange
        }
    }
}