import $store from '@/sheep/store';
import { staticUrl } from '@/sheep/config';
import { append_thumbnail_params } from './resize';

// 添加cdn域名前缀
export const _cdn = (url = '', cdnurl = '') => {
  if (!url) return '';
  if (url.indexOf('http') === 0) {
    return url;
  }
  if (cdnurl === '') {
    cdnurl = $store('app').info.cdnurl;
  }
  return cdnurl + url;
};


// 对象存储自动剪裁缩略图
export const _thumb = (url = '', params) => {
  url = _cdn(url);
  return append_thumbnail_params(url, params);
}

// 静态资源地址
export const _static = (url = '', staticurl = '') => {
  if (staticurl === '') {
    staticurl = staticUrl;
  }
  if (staticurl !== 'local') {
    url = _cdn(url, staticurl);
  }
  return url;
}

// css背景图片地址
export const _css = (url = '', staticurl = '') => {
  if (staticurl === '') {
    staticurl = staticUrl;
  }
  if (staticurl !== 'local') {
    url = _cdn(url, staticurl);
  }
  // #ifdef APP-PLUS
  if (staticurl === 'local') {
    url = plus.io.convertLocalFileSystemURL(url);
  }
  // #endif
  return `url(${url})`;
}

export default {
  cdn: _cdn,
  static: _static,
  css: _css,
  thumb: _thumb
}
