import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.jsx',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: [
                ...refreshPaths,
                'app/Filament/**',
                'app/Livewire/**',
                'app/Providers/Filament/**',
            ],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: { alias: { '@': '/resources/js' } },
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            'react-dom/client',
            '@inertiajs/react',
            'axios',
            'zustand',
            'lucide-react',
            'uuid',
            'qrcode.react',
            'laravel-echo',
            'pusher-js',
        ],
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // React + Inertia — core runtime, jarang berubah
                    vendor: ['react', 'react-dom', '@inertiajs/react'],
                    // State management — pisah agar bisa cache terpisah
                    store: ['zustand'],
                    // Icons — besar, jarang berubah
                    icons: ['lucide-react'],
                },
            },
        },
        // Kurangi threshold warning chunk size
        chunkSizeWarningLimit: 800,
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
            interval: 100,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
