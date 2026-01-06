/**
 * @description 搜索
 * @param { Object } modelValue
 *  modelValue = {
        drawer: false,
        data: {
            title: '',
            order: { field: 'id', value: '' },
            type: '',
            category_ids: [],
            create_time: [],
        },
        tools: {
            title: {
                type: 'tinput',
                label: '商品名称',
                placeholder: '',
                value: '',
            },
            order: {
                type: 'tinputprepend',
                label: '查询内容',
                placeholder: '请输入查询内容',
                value: {
                    field: 'id',
                    value: '',
                },
                options: {
                    data: [{
                        label: '订单ID',
                        value: 'id',
                    },
                    {
                        label: '订单编号',
                        value: 'order_sn',
                    },
                    {
                        label: '售后单号',
                        value: 'aftersale.aftersale_sn',
                    }]
                },
            },
            type: {
                type: 'tselect',
                label: '订单类型',
                placeholder: '',
                value: '',
                options: {
                    data: [{
                        name: '订单ID',
                        type: 'id',
                    }],
                    props: {
                        label: 'name',
                        value: 'type',
                    },
                },
            },
            category_ids: {
                type: 'tcascader',
                label: '商品分类',
                placeholder: '',
                value: [],
                options: {
                    data: [],
                    props: {
                        children: 'children',
                        label: 'name',
                        value: 'id',
                        checkStrictly: true,
                        emitPath: false,
                        multiple: true,
                    },
                },
            },
            create_time: {
                type: 'tdatetimerange',
                label: '下单时间',
                placeholder: '',
                value: [],
            },
        },
        condition: {}
    }
 * @function filter-change
 */

const SaFilter = {
    template: `
    <el-drawer custom-class="sa-filter" v-model="filter.drawer" direction="rtl">
        <el-container>
            <el-header class="sa-filter-header sa-flex sa-row-between">
                <div>筛选</div>
                <el-icon @click="onClose">
                    <Close />
                </el-icon>
            </el-header>
            <el-main>
                <el-form label-position="top" label-width="120px">
                    <el-form-item v-for="(value,key) in filter.tools" :label="value.label">
                        <template v-if="value.type=='tinput'">
                            <el-input v-model="filter.data[key]" :placeholder="value.placeholder || value.label"></el-input>
                        </template>
                        <template v-if="value.type=='tinputprepend'">
                            <el-input v-model="filter.data[key].value" :placeholder="value.placeholder || value.label">
                                <template #prepend>
                                    <el-select v-model="filter.data[key].field" placeholder="Select">
                                        <el-option v-for="option in value.options.data" :key="option.value"
                                            :label="option.label || option[value.options.props.label]"
                                            :value="option.value || option[value.options.props.value]">
                                        </el-option>
                                    </el-select>
                                </template>
                            </el-input>
                        </template>
                        <template v-if="value.type=='tselect'">
                            <el-select v-model="filter.data[key]" :placeholder="value.placeholder || value.label">
                                <el-option v-for="option in value.options.data" :key="option.value"
                                    :label="option.label || option[value.options.props.label]"
                                    :value="option.value || option[value.options.props.value]">
                                </el-option>
                            </el-select>
                        </template>
                        <template v-if="value.type=='tcascader'">
                            <el-cascader :ref="(el) => setCascaderRef(el, key)" v-model="filter.data[key]"
                                :options="value.options.data" :props="value.options.props"
                                :placeholder="value.placeholder || value.label" size="default" clearable></el-cascader>
                        </template>
                        <template v-if="value.type == 'tdatetimerange'">
                            <el-date-picker v-model="filter.data[key]" type="datetimerange"
                                value-format="YYYY-MM-DD HH:mm:ss" format="YYYY-MM-DD HH:mm:ss"
                                :default-time="[new Date(2000, 1, 1, 0, 0, 0), new Date(2000, 2, 1, 23, 59, 59)]"
                                range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" :editable="false">
                            </el-date-picker>
                        </template>
                    </el-form-item>
                    <slot name="supplement"></slot>
                </el-form>
            </el-main>
            <el-footer class="sa-footer--submit sa-flex sa-row-right">
                <el-button @click="onReset">重置</el-button>
                <el-button type="primary" @click="onConfirm">提交</el-button>
            </el-footer>
        </el-container>
    </el-drawer>`,
    emit: ['update:modelValue', 'filter-change'],
    props: {
        modelValue: {
            type: Object,
            default: ''
        }
    },
    setup(props, { emit }) {
        const { ref } = Vue
        const filter = ref(props.modelValue)

        let tempFilter = JSON.parse(JSON.stringify(filter.value))

        let cascaderRef = {};
        const setCascaderRef = (el, key) => {
            if (el) {
                cascaderRef[key] = el;
            }
        };

        function onConfirm() {
            filter.value.condition = {}
            for (key in filter.value.tools) {
                let props = {
                    label: 'label',
                    value: 'value'
                }
                if (filter.value.tools[key].type == 'tinput') {
                    if (filter.value.data[key]) {
                        filter.value.condition[key] = `${filter.value.tools[key].label}:${filter.value.data[key]}`
                    }
                } else if (filter.value.tools[key].type == 'tinputprepend') {
                    if (filter.value.tools[key].options.props) {
                        props = { ...props, ...filter.value.tools[key].options.props }
                    }
                    let findItem = filter.value.tools[key].options.data.find(item => item[props.value] == filter.value.data[key].field)
                    if (filter.value.data[key].value && findItem) {
                        filter.value.condition[key] = `${findItem[props.label]}:${filter.value.data[key].value}`
                    }
                } else if (filter.value.tools[key].type == 'tselect') {
                    if (filter.value.data[key] && filter.value.data[key] != 'all') {
                        if (filter.value.tools[key].options.props) {
                            props = { ...props, ...filter.value.tools[key].options.props }
                        }
                        let findItem = filter.value.tools[key].options.data.find(item => item[props.value] == filter.value.data[key])
                        if (findItem) {
                            filter.value.condition[key] = `${filter.value.tools[key].label}:${findItem[props.label]}`
                        }
                    }
                } else if (filter.value.tools[key].type == 'tcascader') {
                    let text = []
                    cascaderRef[key].getCheckedNodes().forEach(item => {
                        text.push(item.text)
                    })
                    if (text.length > 0) {
                        filter.value.condition[key] = `${filter.value.tools[key].label}:${text.join(',')}`
                    }
                } else if (filter.value.tools[key].type == 'tdatetimerange') {
                    if (filter.value.data[key].length > 0) {
                        filter.value.condition[key] = `${filter.value.tools[key].label}:${filter.value.data[key].join(' - ')}`
                    }
                }
            }
            emit('update:modelValue', filter.value)
            emit('filter-change')
        }
        function onReset() {
            for (key in filter.value.tools) {
                filter.value.data[key] = JSON.parse(JSON.stringify(tempFilter.tools[key].value))
            }
            filter.value.condition = {}
            emit('update:modelValue', filter.value)
            emit('filter-change')
        }
        function onClose() {
            filter.value.drawer = false
            emit('update:modelValue', filter.value)
        }
        return {
            filter,
            cascaderRef,
            setCascaderRef,
            onConfirm,
            onReset,
            onClose
        }
    }
}