import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    publicDir: false,
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/frontend', import.meta.url)),
            '@styles': fileURLToPath(new URL('./resources/css/frontend', import.meta.url)),
        },
    },
    plugins: [vue(), laravel({
        input: ['resources/js/frontend/app.js'],
        buildDirectory: 'build/frontend',
        hotFile: 'public/frontend.hot',
        refresh: ['resources/views/frontend/**', 'lang/**'],
    })],
    build: { minify: true, cssMinify: true, sourcemap: false },
    server: { host: '0.0.0.0', port: 5175, strictPort: true, watch: { ignored: ['**/storage/framework/views/**'] } },
});
