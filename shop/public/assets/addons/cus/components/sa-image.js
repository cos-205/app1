const SaImage = {
    template: `
    <div class="sa-image" :style="itemStyle">
        <el-image v-for="item in urlList" :src="Fast.api.cdnurl(item)" :fit="fit"
            :preview-src-list="ispreview?[Fast.api.cdnurl(item)]:[]" :preview-teleported="true">
            <template #error>
                <el-icon>
                    <Picture />
                </el-icon>
            </template>
        </el-image>
    </div>`,
    props: {
        url: {
            type: String,
            default: ''
        },
        size: {
            type: [String, Number],
            default: ''
        },
        fit: {
            type: String,
            default: 'contain'
        },
        radius: {
            type: [String, Number],
            default: 4
        },
        ispreview: {
            type: Boolean,
            default: true
        },
    },
    setup(props) {
        const { ref, watch, computed } = Vue

        const urlList = ref(props.url ? props.url.split(',') : [''])
        watch(() => props.url, () => {
            urlList.value = props.url ? props.url.split(',') : ['']
        })

        const itemStyle = computed(() => {
            let style = {}
            if (props.size) {
                style = {
                    width: `${props.size}px`,
                    height: `${props.size}px`,
                    'border-radius': `${props.radius}px`
                }
            }
            return style
        })
        return {
            Fast,
            urlList,
            itemStyle,
        }
    }
}