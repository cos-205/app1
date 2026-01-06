const SaUploader = {
    template: `
    <div class="sa-uploader">
        <draggable class="sa-flex sa-flex-wrap" v-model="urlList" :animation="300" item-key="element"
            handle=".sortable-drag" @end="emit('update:modelValue', multiple ? urlList : urlList.join(','))">
            <template #item="{ element, index }">
                <div class="sa-uploader-item" :style="itemStyle">
                    <template v-if="element.includes('.avi') || element.includes('.mov') || element.includes('.rmvb') || element.includes('.rm')
                        || element.includes('.flv') || element.includes('.mp4') || element.includes('.3gp')
                        ">
                        <el-dialog class="sa-dialog-video sa-dialog" v-model="previewVisible" fullscreen>123
                            <video :src="Fast.api.cdnurl(element)" controls></video>
                        </el-dialog>
                        <video class="sa-video" :src="Fast.api.cdnurl(element)" @click.stop="previewVisible = true"></video>
                    </template>
                    <el-image v-else :ref="'imageRef_'+index" :src="Fast.api.cdnurl(element)" fit="contain"
                        :preview-src-list="[Fast.api.cdnurl(element)]" :preview-teleported="true"
                        @load="onImageLoaded"
                        >
                        <template #error>
                            <el-icon>
                                <Picture />
                            </el-icon>
                        </template>
                    </el-image>
                    <div class="mask">
                        <el-icon v-if="multiple" class="sortable-drag">
                            <Rank />
                        </el-icon>
                        <el-icon @click="onDeleteFile(index)">
                            <CircleCloseFilled />
                        </el-icon>
                    </div>
                </div>
            </template>
        </draggable>
        <div v-if="multiple || (!multiple && urlList.length==0)" class="sa-uploader-item add" :style="itemStyle"
            @click="onSelectFile">
            <el-image fit="contain">
                <template #error>
                    <el-icon>
                        <Plus />
                    </el-icon>
                </template>
            </el-image>
        </div>
    </div>`,
    emit: ['update:modelValue', 'success'],
    props: {
        modelValue: {
            type: [Array, String],
            default: ''
        },
        size: {
            type: [String, Number],
            default: ''
        },
        multiple: {
            type: Boolean,
            default: false
        },
        type: {
            type: String,
            default: ''
        },
    },
    setup(props, { emit }) {
        const { ref, computed, watch, nextTick, getCurrentInstance } = Vue

        const { proxy } = getCurrentInstance();

        const urlList = ref(props.modelValue ? isArray(props.modelValue) ? props.modelValue : props.modelValue.split(',') : [])
        watch(() => props.modelValue, () => {
            urlList.value = props.modelValue ? isArray(props.modelValue) ? props.modelValue : props.modelValue.split(',') : []
        })

        const itemStyle = computed(() => ({
            width: `${props.size}px`,
            height: `${props.size}px`
        }))

        const previewVisible = ref(false)

        // 图片加载完毕
        function onImageLoaded() {
            nextTick(() => {
                if (props.type === 'size') {
                    emit('success', { image_width: proxy.$refs.imageRef_0._.refs.container.firstChild.naturalWidth, image_height: proxy.$refs.imageRef_0._.refs.container.firstChild.naturalHeight })
                }
            })
        }

        function onSelectFile() {
            Fast.api.open(`general/attachment/select?multiple=${props.multiple}`, "选择", {
                callback: function (data) {
                    data.url.split(',').forEach(item => {
                        urlList.value.push(item)
                    });
                    emit('update:modelValue', props.multiple ? urlList.value : urlList.value.join(','))
                }
            });
        }
        function onDeleteFile(index) {
            urlList.value.splice(index, 1)
            emit('update:modelValue', props.multiple ? urlList.value : urlList.value.join(','))
        }


        return {
            Fast,
            props,
            emit,
            urlList,
            itemStyle,
            previewVisible,
            onSelectFile,
            onDeleteFile,
            onImageLoaded
        }
    }
}