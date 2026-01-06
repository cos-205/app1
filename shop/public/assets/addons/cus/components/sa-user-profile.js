const SaUserProfile = {
    template: `
    <div class="sa-user-profile" :class="type != 'oper' ? 'mode-'+mode : ''">
        <template v-if="type != 'oper'">
            <template v-if="userData">
                <template v-if="ishover">
                    <el-popover placement="top" trigger="hover" popper-class="profile-popover sa-user-profile sa-popper">
                        <div class="sa-flex">
                            <div class="avatar">
                                <sa-image v-if="userData.avatar" :url="userData.avatar"></sa-image>
                                <img v-else class="default-avatar" src="/assets/addons/cus/img/default-avatar.png" />
                                <!-- 0未知 1男  -->
                                <img v-if="userData && (userData.gender || userData.gender==0)" class="gender"
                                    :src="'/assets/addons/cus/img/gender-'+userData.gender+'.png'">
                            </div>
                            <div>
                                <div class="text-name sa-table-line-1">
                                    {{ userData.nickname }}
                                </div>
                                <div class="sa-flex">
                                    <div class="id" @click="onOpenUserDetail(id)">#{{ id }}</div>
                                    <div class="text-desc">
                                        {{ userData.username || userData.account }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <template #reference>
                            <div class="wrap">
                                <div v-if="isavatar" class="avatar">
                                    <sa-image v-if="userData.avatar" :url="userData.avatar"></sa-image>
                                    <img v-else class="default-avatar" src="/assets/addons/cus/img/default-avatar.png" />
                                </div>
                                <div class="text-name is-hover sa-table-line-1">
                                    {{ userData.nickname }}
                                </div>
                            </div>
                        </template>
                    </el-popover>
                </template>
                <div v-else class="wrap">
                    <div v-if="isavatar" class="avatar">
                        <sa-image v-if="userData.avatar" :url="userData.avatar"></sa-image>
                        <img v-else class="default-avatar" src="/assets/addons/cus/img/default-avatar.png" />
                    </div>
                    <div class="text-name sa-table-line-1">
                        {{ userData.nickname }}
                    </div>
                </div>
            </template>
            <template v-else>
                <template v-if="type == 'agent'">
                    {{ id ? '未找到用户' : id == 0 ? '平台直推' : '暂无推荐人' }}
                </template>
            </template>
        </template>
        <template v-if="type == 'oper'">
            <div class="wrap">
                <template v-if="userData">
                    <div class="avatar mr-2">
                        <sa-image v-if="userData.type != 'system' && userData.avatar" :url="userData.avatar"></sa-image>
                        <img v-else class="default-avatar" src="/assets/addons/cus/img/default-avatar.png" />
                    </div>
                    <div>
                        <template v-if="userData.type != 'system'">
                            <div class="text-name sa-table-line-1">
                                {{ userData.name || '未找到' }}
                            </div>
                            <div class="sa-flex">
                                <div class="id">#{{ id }}</div>
                                <div class="text-desc">
                                    {{ userData.type_text }}
                                </div>
                            </div>
                        </template>
                        <template v-else>{{ userData.type_text }}</template>
                    </div>
                </template>
                <template v-else>#{{id}}</template>
            </div>
        </template>
    </div>`,
    props: {
        user: Object,
        id: [Number, String],
        type: {
            type: String,
            default: 'user',
        },
        mode: {
            type: String,
            default: 'row',
        },
        isavatar: {
            type: Boolean,
            default: true,
        },
        ishover: {
            type: Boolean,
            default: true,
        },
    },
    setup(props) {
        const { ref, computed } = Vue

        const userData = computed(() => {
            let obj = props.user;

            if (props.type == 'user' && !props.user) {
                obj = {
                    avatar: '',
                    nickname: '未找到用户',
                    username: '',
                };
            }
            if (props.type == 'admin' && !props.user) {
                obj = {
                    avatar: '',
                    nickname: '未找到用户',
                    account: '',
                };
            }

            return obj;
        });

        function onOpenUserDetail(id) {
            Fast.api.open(`cus/user/user/detail?id=${id}`, "会员详情")
        }
        return {
            Fast,
            userData,
            onOpenUserDetail,
        }
    }
}