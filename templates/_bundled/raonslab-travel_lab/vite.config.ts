import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import dts from 'vite-plugin-dts';
import path from 'path';

/**
 * RAON Travel Lab 사용자 템플릿 빌드 설정
 *
 * Basic·Composite 컴포넌트 + Tailwind v4 CSS 를 담는 IIFE 번들 (전역 RaonslabTravelLab)
 */
export default defineConfig({
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },

  plugins: [
    react(),
    tailwindcss(),
    dts({
      insertTypesEntry: true,
      entryRoot: 'src',
      include: ['src/**/*.ts', 'src/**/*.tsx'],
      exclude: ['src/**/*.test.ts', 'src/**/*.test.tsx', 'node_modules'],
    }),
  ],

  build: {
    lib: {
      entry: path.resolve(__dirname, 'src/index.ts'),
      name: 'RaonslabTravelLab',
      fileName: 'components',
      formats: ['iife'],
    },

    outDir: 'dist',
    emptyOutDir: false, // 동봉 vendor 보존 — 산출물 정리는 빌드 커맨드가 한다
    // 배포용 빌드(--production)는 G7_BUILD_SOURCEMAP=0 을 주입해 소스맵을 생성하지 않는다.
    // 미설정(로컬 npm run build)이면 생성 — 개발 디버깅 경험을 유지한다.
    sourcemap: !['0', 'false'].includes(process.env.G7_BUILD_SOURCEMAP ?? ''),

    rollupOptions: {
      external: ['react', 'react-dom', 'react/jsx-runtime'],

      output: {
        globals: {
          react: 'React',
          'react-dom': 'ReactDOM',
          'react/jsx-runtime': 'ReactJSXRuntime',
        },

        entryFileNames: 'js/components.iife.js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) =>
          assetInfo.name?.endsWith('.css') ? 'css/[name][extname]' : 'assets/[name][extname]',
      },
    },

    minify: 'esbuild',
    target: 'es2020',
  },

  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
      '@components': path.resolve(__dirname, 'src/components'),
    },
  },
});
