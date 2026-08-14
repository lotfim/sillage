import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';

const rootDir = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
	root: rootDir,
	resolve: {
		alias: {
			jquery: path.resolve(rootDir, 'src/admin/jquery-shim.js'),
		},
	},
	build: {
		outDir: path.resolve(rootDir, 'admin'),
		emptyOutDir: false,
		sourcemap: false,
		rollupOptions: {
			input: path.resolve(rootDir, 'src/admin/sillage-admin.js'),
			output: {
				entryFileNames: 'js/sillage-admin.js',
				assetFileNames: (assetInfo) => {
					if (assetInfo.name && assetInfo.name.endsWith('.css')) {
						return 'css/sillage-admin.css';
					}
					return 'assets/[name][extname]';
				},
			},
		},
	},
});
