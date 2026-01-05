// 开发环境配置
import packageInfo from '@/package.json';

const { version } = packageInfo;
export let baseUrl;
if (process.env.NODE_ENV === 'development') {
  baseUrl = import.meta.env.CUS_DEV_BASE_URL;
} else {
  baseUrl = import.meta.env.CUS_BASE_URL;
}

if (typeof baseUrl === 'undefined') {
  console.error('请检查.env配置文件是否存在');
}

export const apiPath = import.meta.env.CUS_API_PATH;

export const staticUrl = import.meta.env.CUS_STATIC_URL;

export default {
  baseUrl,
  apiPath,
  staticUrl,
};
