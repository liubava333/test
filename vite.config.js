import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
// import { vitePolyfills } from 'vite-plugin-node-polyfills';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    formats: ['woff2'],
                }),
            ],
        }),
        // vitePolyfills({
        //     // Включает полифилы для встроенных модулей Node.js (fs, path, module и т.д.)
        //     nodeOptions: {
        //         global: true,
        //         process: true,
        //     },
        //     // Это заставит Vite корректно обрабатывать импорты вроде `node:module`
        //     protocolImports: true,
        // }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    resolve: {
        alias: {
            // Добавляем этот алиас для поддержки компиляции шаблонов из Blade [1]
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
});
