import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const laravelRoutes = [
    '/dashboard',
    '/meeting',
    '/plans',
    '/meetings',
    '/notes',
    '/summaries',
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/logout',
    '/profile',
    '/verify-email',
    '/email',
    '/confirm-password',
    '/password',
];

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        port: 5174,
        strictPort: true,
        proxy: Object.fromEntries(
            laravelRoutes.map((path) => [
                path,
                {
                    target: 'http://127.0.0.1:8000',
                    changeOrigin: false,
                },
            ]),
        ),
    },
});


