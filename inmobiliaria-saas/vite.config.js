import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173',
        cors: true,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
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
