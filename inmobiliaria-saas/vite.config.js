import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://192.168.1.16:5173',
        cors: {
            origin: [
                'http://127.0.0.1',
                'http://localhost',
                'http://192.168.1.16',
            ],
        },
        hmr: {
            host: '192.168.1.16',
            port: 5173,
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
