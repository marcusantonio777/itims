import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    build: {
        outDir: resolve(process.cwd(), 'assets/dashboard'),
        emptyOutDir: true,
        cssCodeSplit: false,
        sourcemap: false,
        minify: true,
        target: 'es2020',
        lib: {
            entry: resolve(process.cwd(), 'frontend/dashboard/main.tsx'),
            formats: ['es'],
            fileName: () => 'dashboard.js',
            cssFileName: 'dashboard',
        },
        rollupOptions: {
            output: {
                assetFileNames: 'dashboard.[ext]',
            },
        },
    },
});
