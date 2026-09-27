import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: {
        lib: {
            entry: path.resolve(__dirname, 'resources/js/index.ts'),
            name: 'RaonslabAiWorkspace',
            fileName: 'module',
            formats: ['iife'],
        },
        outDir: 'dist',
        emptyOutDir: false,
        sourcemap: ['1', 'true'].includes(process.env.G7_BUILD_SOURCEMAP ?? ''),
        rollupOptions: {
            output: {
                entryFileNames: 'js/module.iife.js',
                assetFileNames: (asset) => asset.name?.endsWith('.css') ? 'css/module.css' : 'assets/[name][extname]',
            },
        },
        target: 'es2020',
    },
});
