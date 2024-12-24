import { defineConfig } from 'vite';

import autoprefixer from 'autoprefixer';

const dest = './assets';

const entries = [
	'./resources/ts/main.ts',
	'./resources/sass/all.scss',
	'./resources/sass/optional.scss',
	'./resources/sass/required.scss',
];

export default defineConfig(() => {
	return {
		base: './',
		css: {
			devSourcemap: true,
			preprocessorOptions: {
			},
			postcss: {
				plugins: [autoprefixer()],
			},
		},
		build: {
			manifest: false,
			outDir: dest,
			rollupOptions: {
				input: entries,
        output: {
          entryFileNames: '[name].js',
          chunkFileNames: '[name].js',
          assetFileNames: '[name].[ext]',
        },
			},
		},
	};
});
