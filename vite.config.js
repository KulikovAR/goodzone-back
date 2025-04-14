import {defineConfig} from 'vite';
import svgr from "vite-plugin-svgr";
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import viteJSX from 'vite-plugin-react-js-as-jsx';

export default defineConfig({
    base: './',
    server: {
        host: '0.0.0.0',
        port: 3000,
        open: '/public/',
        hmr: {
            host: 'localhost'
        }
    },
    plugins: [
        svgr(),
        viteJSX([], {jsxInject: true}),
        laravel({
            input: [
                'resources/css/filament.css',
                // 'src/index.js'
            ],
            refresh: true,
        }),
        react()
    ]
});
