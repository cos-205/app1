const DSlider = {
    template: `
    <div class="d-slider sa-flex">
    <el-slider v-model="sliderValue" :show-tooltip="false" @input="onChangeSlider"></el-slider>
    <el-input v-model="sliderInput" type="number" @input="onChangeInput">
      <template #suffix>
        <span class="el-input__icon">PX</span>
      </template>
    </el-input>
  </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: Number,
            default: 0,
        },
        mult: {
            type: Number,
            default: 1,
        },
    },
    setup(props, { emit }) {
        const { ref, watch } = Vue

        const sliderValue = ref(props.modelValue / props.mult);
        const sliderInput = ref(props.modelValue);
        watch(() => props.modelValue, () => {
            sliderValue.value = props.modelValue / props.mult;
            sliderInput.value = props.modelValue;
        })

        function onChangeSlider(sv) {
            sliderInput.value = Number(sv * props.mult);
            emit('update:modelValue', Number(sv * props.mult));
        }

        function onChangeInput(si) {
            sliderValue.value = Number(si / props.mult);
            emit('update:modelValue', Number(si));
        }
        return {
            sliderValue,
            sliderInput,
            onChangeSlider,
            onChangeInput
        }
    }
}