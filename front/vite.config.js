import { loadEnv } from 'vite';
import uni from '@dcloudio/vite-plugin-uni';
import path from 'path';
// import viteCompression from 'vite-plugin-compression';
import uniReadPagesV3Plugin from './xxep/router/utils/uni-read-pages-v3';
import mpliveMainfestPlugin from './xxep/libs/mplive-manifest-plugin';


// https://vitejs.dev/config/
export default (command, mode) => {
	const env = loadEnv(mode, __dirname, 'CUS_');
	return {
		envPrefix: "CUS_",
		plugins: [
			uni(),
			// viteCompression({
			// 	verbose: false
			// }),
			uniReadPagesV3Plugin({
				pagesJsonDir: path.resolve(__dirname, './pages.json'),
				includes: ['path', 'aliasPath', 'name', 'meta'],
			}),
			mpliveMainfestPlugin(env.CUS_MPLIVE_ON)
		],
		server: {
			host: true,
			// open: true,
			port: env.CUS_DEV_PORT,
			hmr: {
				overlay: true,
			},
		},
	};
};
