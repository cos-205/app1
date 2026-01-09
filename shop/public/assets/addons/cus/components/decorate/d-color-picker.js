const DColorPicker = {
    template: `
    <div class="d-color-picker sa-flex">
        <img class="empty" src="/assets/addons/cus/img/decorate/picker.png" />
        <el-color-picker v-model="color" @change="onChangeColor"></el-color-picker>
        <el-input v-model="color" @input="onChangeColor"></el-input>
        <el-checkbox v-if="isshow" class="sa-m-l-16" v-model="flag" :true-label="1" :false-label="0" @change="onChangeFlag">
        </el-checkbox>
    </div>`,
    emit: ['update:modelValue', 'update:show'],
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        show: {
            type: Number,
            default: 0,
        },
        isshow: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue

        const color = ref(props.modelValue);
        watch(
            () => props.modelValue,
            () => {
                color.value = props.modelValue;
            },
        );

        function onChangeColor() {
            emit('update:modelValue', color.value);
        }

        const flag = ref(props.show);
        watch(
            () => props.show,
            () => {
                flag.value = props.show;
            },
        );

        function onChangeFlag() {
            emit('update:show', flag.value);
        }
        return {
            color,
            onChangeColor,
            flag,
            onChangeFlag
        }
    }
}