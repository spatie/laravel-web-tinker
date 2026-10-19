import { createHash } from 'node:crypto';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    publicDir: false,
    resolve: {
        alias: { vue: 'vue/dist/vue.esm.js' },
    },
    plugins: [
        vue(),
        {
            name: 'mix-manifest',
            enforce: 'post',
            generateBundle(options, bundle) {
                const manifest = {};

                for (const [fileName, asset] of Object.entries(bundle)) {
                    const hash = createHash('md5')
                        .update(asset.type === 'chunk' ? asset.code : asset.source)
                        .digest('hex');
                    manifest[`/${fileName}`] = `/${fileName}?id=${hash}`;
                }

                this.emitFile({
                    type: 'asset',
                    fileName: 'mix-manifest.json',
                    source: JSON.stringify(manifest, null, 4),
                });
            },
        },
    ],
    define: { 'process.env.NODE_ENV': JSON.stringify('production') },
    build: {
        outDir: 'public',
        emptyOutDir: false,
        lib: {
            entry: 'resources/js/app.js',
            name: 'WebTinker',
            formats: ['iife'],
            fileName: () => 'app.js',
            cssFileName: 'app',
        },
    },
});
