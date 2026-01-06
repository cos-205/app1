const CenterHeader = {
  template: `
  <div v-if="platform == 'WechatMiniProgram' || platform == 'App'" class="center-header" :style="headerStyle()">
      <img class="system" :src="'/assets/addons/cus/img/decorate/header-' + system + '.png'" />
      <div class="WechatMiniProgram-wrap sa-flex sa-row-right">
          <center-navbar :header="header" :platform="platform" :isScroll="isScroll" :page="page"></center-navbar>
          <img v-if="platform == 'WechatMiniProgram'" class="WechatMiniProgram"
              :src="'/assets/addons/cus/img/decorate/header-' + platform + '.png'" />
      </div>
  </div>
  <div v-if="platform == 'WechatOfficialAccount' || platform == 'H5'" class="center-header">
      <img class="system" :src="'/assets/addons/cus/img/decorate/header-' + system + '.png'" />
      <img v-if="platform == 'WechatOfficialAccount'" class="WechatOfficialAccount"
          :src="'/assets/addons/cus/img/decorate/header-' + platform + '.png'" />
      <center-navbar :style="headerStyle()" :header="header" :platform="platform" :isScroll="isScroll" :page="page">
      </center-navbar>
  </div>`,
  emit: ['update:modelValue', 'update:show'],
  props: {
    header: {
      type: Object,
      default: {},
    },
    page: String,
    system: String,
    platform: String,
    isScroll: {
      type: Boolean,
      default: true,
    },
  },
  setup(props, { emit }) {
    const { ref, watch } = Vue

    console.log(props.header, 'header')

    const sys1 = {
      ios: '78px',
      android: '72px',
    };
    const sys2 = {
      ios: '34px',
      android: '28px',
    };

    function headerStyle() {
      let mode = {};
      if (props.platform == 'WechatMiniProgram') {
        if (props.header) {
          if (props.header.navbar.mode == 'inner') {
            mode = {
              position: 'absolute',
              top: 0,
              right: 0,
              'z-index': 20,
            };
            if (props.isScroll) {
              mode = {
                ...mode,
                background:
                  props.header.navbar.type == 'color'
                    ? props.header.navbar.color
                    : props.header.navbar.src
                      ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                      : props.header.navbar.src,
              };
            }
          }
          if (props.header.navbar.mode == 'normal') {
            mode = {
              background:
                props.header.navbar.type == 'color'
                  ? props.header.navbar.color
                  : props.header.navbar.src
                    ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                    : props.header.navbar.src,
            };
          }
        }
      }
      if (props.platform == 'WechatOfficialAccount') {
        if (props.header) {
          if (props.header.navbar.mode == 'inner') {
            mode = {
              position: 'absolute',
              top: sys1[props.systemType],
              left: 0,
              'z-index': 20,
            };
            if (props.isScroll) {
              mode = {
                ...mode,
                background:
                  props.header.navbar.type == 'color'
                    ? props.header.navbar.color
                    : props.header.navbar.src
                      ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                      : props.header.navbar.src,
              };
            }
          } else if (props.header.navbar.mode == 'normal') {
            mode = {
              background:
                props.header.navbar.type == 'color'
                  ? props.header.navbar.color
                  : props.header.navbar.src
                    ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                    : props.header.navbar.src,
            };
          }
        }
      }
      if (props.platform == 'H5') {
        if (props.header) {
          if (props.header.navbar.mode == 'inner') {
            mode = {
              position: 'absolute',
              top: sys2[props.systemType],
              right: 0,
              'z-index': 20,
            };
            if (props.isScroll) {
              mode = {
                ...mode,
                background:
                  props.header.navbar.type == 'color'
                    ? props.header.navbar.color
                    : props.header.navbar.src
                      ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                      : props.header.navbar.src,
              };
            }
          }
          if (props.header.navbar.mode == 'normal') {
            mode = {
              background:
                props.header.navbar.type == 'color'
                  ? props.header.navbar.color
                  : props.header.navbar.src
                    ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                    : props.header.navbar.src,
            };
          }
        }
      }
      if (props.platform == 'App') {
        if (props.header) {
          if (props.header.navbar.mode == 'inner') {
            mode = {
              position: 'absolute',
              top: 0,
              right: 0,
              'z-index': 20,
            };
            if (props.isScroll) {
              mode = {
                ...mode,
                background:
                  props.header.navbar.type == 'color'
                    ? props.header.navbar.color
                    : props.header.navbar.src
                      ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                      : props.header.navbar.src,
              };
            }
          }
          if (props.header.navbar.mode == 'normal') {
            mode = {
              background:
                props.header.navbar.type == 'color'
                  ? props.header.navbar.color
                  : props.header.navbar.src
                    ? 'url(' + Fast.api.cdnurl(props.header.navbar.src) + ')'
                    : props.header.navbar.src,
            };
          }
        }
      }

      return mode
    }

    return {
      headerStyle,
    }
  }
}