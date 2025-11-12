import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Add timestamp to force cache refresh
                entryFileNames: 'assets/[name]-[hash]-' + Date.now() + '.js',
                chunkFileNames: 'assets/[name]-[hash]-' + Date.now() + '.js',
                assetFileNames: 'assets/[name]-[hash]-' + Date.now() + '.[ext]'
            }
        }
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
    },
});
