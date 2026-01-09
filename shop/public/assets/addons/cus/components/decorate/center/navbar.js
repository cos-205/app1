const CenterNavbar = {
    template: `
    <div class="center-navbar" v-if="
            header.navbar.mode == 'normal' ||
            (header.navbar.mode == 'inner' && header.navbar.alwaysShow) ||
            isScroll
        ">
        <div class="navbar-wrap">
            <div v-if="page == 'diypage'" class="item"
                :style="{ width: scale + 'px', height: 32 + 'px', top: 0 + 'px', left: 0 + 'px' }">
                <img class="header-diypage" src="/assets/addons/cus/img/decorate/header-diypage.png" />
            </div>
            <template v-for="l in header.navbar.list[pType]" :key="l">
                <div class="item" :style="{
                    width: l.width * scale + 'px',
                    height: 32 + 'px',
                    top: 0 + 'px',
                    left: l.left * scale + 'px',
                }">
                    <div v-if="l.type == 'text'" class="text sa-table-line-1" :style="{ color: l.textColor }">
                        {{ l.text }}
                    </div>
                    <sa-image v-if="l.type == 'image'" :url="l.src" :size="20" :suffix="null"></sa-image>
                    <div v-if="l.type == 'search'" class="search sa-line-1"
                        :style="{ borderRadius: l.borderRadius + 'px' }">
                        <el-icon class="sa-m-r-8">
                            <Search />
                        </el-icon>
                        {{ l.placeholder }}
                    </div>
                </div>
            </template>
        </div>
    </div>`,
    emit: ['update:modelValue', 'update:show'],
    props: ['header', 'page', 'platform', 'isScroll'],
    setup(props) {

        const { computed } = Vue

        const scale = (375 - 12) / 8;

        const pType = computed(() => (props.platform == 'WechatMiniProgram' ? 'mp' : 'app'));
        return {
            scale,
            pType
        }
    }
}