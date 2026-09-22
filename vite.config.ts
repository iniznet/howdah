import { defineConfig } from 'vite';

// The entry list is the theme's config/entries.php; the handle keys the
// manifest entry the assets package resolves. A preset may replace this
// file when it needs a plugin.
export default defineConfig({
	build: {
		outDir: 'build',
		manifest: 'manifest.json',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				'howdah-app': 'resources/js/app.ts',
			},
		},
	},
});
