const DCube = {
    template: `
    <div>
        <div class="d-cube">
            <table>
                <tbody>
                    <tr v-for="trItem in map.tr" :key="trItem">
                        <td class="image-cube-item" :class="
                state.item.minRow <= trItem &&
                trItem <= state.item.maxRow &&
                state.item.minCol <= tdItem &&
                tdItem <= state.item.maxCol
                ? 'is-active'
                : ''
            " :style="{
                width: scale + 'px',
                height: scale + 'px',
            }" v-for="tdItem in map.td" :key="tdItem" @click.stop="onSelectImageCube(trItem, tdItem)"
                            @mouseover="onMouseoverImageCube(trItem, tdItem)">
                            <el-icon>
                                <Plus />
                            </el-icon>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="type=='page' && page == 'diypage'" class="position-left" :style="{
                    width: scale * 2 + 'px',
                }">
                <img src="/assets/addons/cus/img/decorate/header-diypage.png" />
            </div>
            <div v-if="type=='page' && platform == 'WechatMiniProgram'" class="WechatMiniProgram">
                <img :src="'/assets/addons/cus/img/decorate/header-' + platform + '.png'" />
            </div>
            <div class="position-item sa-flex sa-row-center" :class="state.active == sindex ? 'is-active' : ''"
                v-for="(style, sindex) in state.cubeData" :style="{
            width: style.width * scale + 'px',
            height: style.height * scale + 'px',
            top: style.top * scale + 'px',
            left: style.left * scale + 'px',
            }" :key="style" @mouseover="onCancelImageCubeSelect" @click.stop="onSelectImageCubePosition(sindex)">
                {{ style.width }}*{{ style.height }}
                <el-icon class="circle-close-filled" @click.stop="onDeleteImageCube(sindex)">
                    <circle-close-filled />
                </el-icon>
            </div>
        </div>
        <div v-if="state.cubeData.length>0">
            <slot name="item" :item="state.cubeData[state.active]"></slot>
        </div>
    </div>`,
    emit: ['update:modelValue'],
    props: {
        modelValue: {
            type: Object,
            default: {},
        },
        type: String, // 组件类型
        page: String, // template类型
        platform: String, // 平台
        item: Object, // 列表item
        scale: {
            type: Number,
            default: 66
        },
        map: {
            type: Object,
            default: {
                tr: 4,
                td: 4
            }
        },
    },
    setup(props, { emit }) {
        const { computed, reactive, watch } = Vue

        const cubeType = computed(() => props.platform == 'WechatMiniProgram' ? 'mp' : 'app')

        // 修改平台
        watch(
            () => props.platform,
            () => {
                if (props.type == 'imageCube') {
                    state.cubeData = props.modelValue || [];
                    state.selectedIndex = props.modelValue.length > 0 ? 0 : null;
                } else if (props.type == 'page') {
                    state.cubeData = props.modelValue[cubeType.value] || [];
                    state.selectedIndex = props.modelValue[cubeType.value].length > 0 ? 0 : null;
                }
            })

        const state = reactive({
            start: { row: 0, col: 0 },
            isFlag: false,
            item: props.item,
            cubeData: [],
            active: 0,
        })

        if (props.type == 'imageCube') {
            state.cubeData = props.modelValue || []
        } else if (props.type == 'page') {
            state.cubeData = props.modelValue[cubeType.value] || []
        }
        watch(() => props.modelValue, () => {
            if (props.type == 'imageCube') {
                state.cubeData = props.modelValue || []
            } else if (props.type == 'page') {
                state.cubeData = props.modelValue[cubeType.value] || []
            }
        })

        // 广告魔方
        function onSelectImageCube(row, col) {
            // 开始的坐标
            if (!state.isFlag) {
                state.start.row = row;
                state.start.col = col;
            }
            // 结束存储数据
            if (state.isFlag) {
                state.cubeData.push(JSON.parse(JSON.stringify(state.item)));
                state.active = state.cubeData.length - 1;
                onClearImageCubeItem();
                onUpdateImageCube();
            }
            state.isFlag = !state.isFlag;
            onMouseoverImageCube(row, col);
        }
        function onMouseoverImageCube(row, col) {
            if (state.isFlag) {
                let squaresArr = [
                    state.start.row + '*' + state.start.col,
                    row + '*' + col,
                    state.start.row + '*' + col,
                    row + '*' + state.start.col,
                ];
                let min = squaresArr.sort()[0].split('*');
                let max = squaresArr.sort()[3].split('*');
                // 面积不可重叠
                const flag = state.cubeData.some((f) => {
                    return isOverlap(f, {
                        width: Math.abs(state.start.col - col) + 1,
                        height: Math.abs(state.start.row - row) + 1,
                        left: min[1] - 1,
                        top: min[0] - 1,
                    });
                });
                if (!flag) {
                    // 宽高
                    state.item = {
                        ...props.item,
                        width: Math.abs(state.start.col - col) + 1,
                        height: Math.abs(state.start.row - row) + 1,
                        top: min[0] - 1, // 定位
                        left: min[1] - 1,
                        minRow: min[0], // xy轴最大最小值
                        maxRow: max[0],
                        minCol: min[1],
                        maxCol: max[1],
                    }
                }
            }
        }
        function onDeleteImageCube(index) {
            state.cubeData.splice(index, 1);
            state.active = state.cubeData.length - 1;
            onUpdateImageCube();
        }
        function onCancelImageCubeSelect() {
            state.isFlag = false;
            onClearImageCubeItem();
        }
        function onSelectImageCubePosition(index) {
            state.active = index;
        }
        function onUpdateImageCube() {
            let deleteData = ['minRow', 'maxRow', 'minCol', 'maxCol'];
            state.cubeData.forEach((item) => {
                deleteData.forEach((d) => {
                    delete item[d];
                });
            });
            if (props.type == 'imageCube') {
                emit('update:modelValue', state.cubeData)
            } else if (props.type == 'page') {
                emit('update:modelValue', {
                    mp: props.platform == 'WechatMiniProgram' ? state.cubeData : props.modelValue.mp,
                    app: props.platform != 'WechatMiniProgram' ? state.cubeData : props.modelValue.app
                })
            }

            // console.log(state.cubeData, '广告魔方数据')
        }
        function onClearImageCubeItem() {
            state.item = props.item
        }
        function isOverlap(obj1, obj2) {
            const l1 = { x: obj1.left, y: obj1.top };
            const r1 = { x: obj1.left + obj1.width, y: obj1.top + obj1.height };
            const l2 = { x: obj2.left, y: obj2.top };
            const r2 = { x: obj2.left + obj2.width, y: obj2.top + obj2.height };
            if (l1.x >= r2.x || l2.x >= r1.x || l1.y >= r2.y || l2.y >= r1.y) return false;
            return true;
        }
        return {
            state,
            onSelectImageCube,
            onMouseoverImageCube,
            onDeleteImageCube,
            onCancelImageCubeSelect,
            onSelectImageCubePosition,
            onUpdateImageCube,
            onClearImageCubeItem,
            isOverlap
        }
    }
}