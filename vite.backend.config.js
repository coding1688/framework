import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    publicDir: false,
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/backend', import.meta.url)),
            '@styles': fileURLToPath(new URL('./resources/css/backend', import.meta.url)),
        },
    },
    plugins: [vue(), laravel({
        input: ['resources/js/backend/spa.js', 'resources/js/backend/forbidden.js'],
        buildDirectory: 'build/backend',
        hotFile: 'public/backend.hot',
        refresh: ['resources/views/backend/**', 'lang/**'],
    })],
    build: { minify: true, cssMinify: true, sourcemap: false },
    server: { host: '0.0.0.0', port: 5174, strictPort: true, watch: { ignored: ['**/storage/framework/views/**'] } },
});
