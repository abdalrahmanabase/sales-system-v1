import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Enable minification and compression
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log statements in production
                drop_debugger: true, // Remove debugger statements
            },
        },
        // Enable gzip compression
        rollupOptions: {
            output: {
                manualChunks: {
                    // Separate vendor chunks for better caching
                    vendor: ['axios'],
                },
            },
        },
        // Enable source maps for debugging (can be disabled in production)
        sourcemap: process.env.NODE_ENV === 'development',
        // Set chunk size warning limit
        chunkSizeWarningLimit: 1000,
    },
    // Enable dependency optimization
    optimizeDeps: {
        include: ['axios'],
    },
    // Enable server-side rendering optimizations
    ssr: {
        noExternal: ['@tailwindcss/vite'],
    },
});
