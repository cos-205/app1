function isArray(value) {
    if (typeof Array.isArray === 'function') {
        return Array.isArray(value);
    } else {
        return Object.prototype.toString.call(value) === '[object Array]';
    }
}

function isObject(value) {
    return Object.prototype.toString.call(value) === '[object Object]';
}

function isNumber(value) {
    return !isNaN(Number(value));
}

function isFunction(value) {
    return typeof value == 'function';
}

function isString(value) {
    return typeof value == 'string';
}

function isEmpty(value) {
    if (isArray(value)) {
        return value.length === 0;
    }

    if (isObject(value)) {
        return Object.keys(value).length === 0;
    }

    return value === '' || typeof value === 'undefined' || value === null;
}

function isBoolean(value) {
    return typeof value === 'boolean';
}

function composeFilter(search, op) {
    let filter = {};
    Object.keys(search).forEach((k) => {
        if (isObject(search[k])) {
            if (!isEmpty(search[k].value) && search[k].value !== 'all') {
                let stype = '=';
                if (op && op[search[k].field]) {
                    if (isObject(op[search[k].field])) {
                        stype = op[search[k].field].type || '=';
                    } else {
                        stype = op[search[k].field];
                    }
                }
                filter[search[k].field] = [search[k].value, stype];
            }
        } else {
            if (!isEmpty(search[k]) && search[k] !== 'all') {
                let stype = '=';
                if (op && op[k]) {
                    if (isObject(op[k])) {
                        stype = op[k].type || '=';
                    } else {
                        stype = op[k];
                    }
                }
                filter[k] = [
                    isArray(search[k]) ? search[k].join(`${op[k].spacer ? op[k].spacer : ' - '}`) : search[k],
                    stype,
                ];
            }
        }
    });
    return { search: JSON.stringify(filter) };
}

function onClipboard(text) {
    const clipboard = new ClipboardJS(`<div></div>`, {
        text: () => text,
    });
    clipboard.on('success', () => {
        ElementPlus.ElMessage({
            message: '复制成功',
            type: 'success',
        });
        clipboard.destroy();
    });
    clipboard.on('error', () => {
        ElementPlus.ElMessage({
            message: '复制失败',
            type: 'warning',
        });
        clipboard.destroy();
    });
    clipboard.onClick(event);
}

function createApp(id, testIndex) {
    const { createApp } = Vue

    const app = createApp(testIndex)

    app.use(ElementPlus, { locale: ElementPlusLocaleZhCn })
    for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
        app.component(key, component)
    }

    app.component('draggable', window.vuedraggable)

    app.component('sa-image', SaImage)
    app.component('sa-uploader', SaUploader)
    app.component('sa-user-profile', SaUserProfile)
    app.component('sa-filter', SaFilter)
    app.component('sa-filter-condition', SaFilterCondition)
    app.component('sa-pagination', SaPagination)

    app.mount(`#${id}`)
};