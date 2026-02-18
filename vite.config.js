import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    build: {
        // Optimizaciones para producción
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Eliminar console.log en producción
                drop_debugger: true,
            },
        },
        // Code splitting optimizado
        rollupOptions: {
            output: {
                manualChunks: {
                    // Separar vendor chunks para mejor caching
                    'react-vendor': ['react', 'react-dom', 'react-router-dom'],
                    'chart-vendor': ['recharts'],
                },
                // Optimizar nombres de archivos
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        // Optimizar tamaño de chunks
        chunkSizeWarningLimit: 1000,
        // Generar source maps solo en desarrollo
        sourcemap: process.env.NODE_ENV !== 'production',
        // Optimizar assets
        assetsInlineLimit: 4096, // Inline assets pequeños (< 4KB)
    },
    // Optimizar resolución de módulos
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
