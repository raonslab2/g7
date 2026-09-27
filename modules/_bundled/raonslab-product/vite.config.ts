import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  build: {
    lib: {
      entry: path.resolve(__dirname, 'resources/js/index.ts'),
      name: 'RaonslabProduct',
      fileName: 'module',
      formats: ['iife'],
    },
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: !['0', 'false'].includes(process.env.G7_BUILD_SOURCEMAP ?? ''),
    target: 'es2020',
    rollupOptions: {
      output: {
        entryFileNames: 'js/module.iife.js',
        assetFileNames: (assetInfo) => assetInfo.name?.endsWith('.css')
          ? 'css/module.css'
          : 'assets/[name][extname]',
      },
    },
  },
});
