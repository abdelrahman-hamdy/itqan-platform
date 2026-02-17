import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/filament-custom.css', 'resources/js/app.js'],
            // Only refresh on specific file changes to prevent meeting interruptions
            refresh: [
                'resources/views/**',
                'routes/**',
                'resources/js/**',
                'resources/css/**',
            ],
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Use content hash for cache busting (consistent across builds)
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]'
            }
        }
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        // Prevent HMR from reloading on non-essential changes
        watch: {
            ignored: [
                '**/storage/**',
                '**/vendor/**',
                '**/node_modules/**',
                '**/.git/**',
                '**/bootstrap/cache/**',
            ],
        },
    },
});
