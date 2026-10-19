import { createHash } from 'node:crypto';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    publicDir: false,
    define: { 'process.env.NODE_ENV': JSON.stringify('production') },
    plugins: [
        vue(),
        {
            name: 'mix-manifest',
            enforce: 'post',
            generateBundle(options, bundle) {
                const manifest = {};

                for (const [file, output] of Object.entries(bundle)) {
                    const hash = createHash('md5')
                        .update(output.code ?? output.source)
                        .digest('hex');
                    manifest[`/${file}`] = `/${file}?id=${hash}`;
                }

                this.emitFile({
                    type: 'asset',
                    fileName: 'mix-manifest.json',
                    source: JSON.stringify(manifest, null, 4),
                });
            },
        },
    ],
    resolve: { alias: { vue: 'vue/dist/vue.esm.js' } },
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
