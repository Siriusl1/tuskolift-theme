import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import precompress from './vite-plugins/precompress.js';
import buildVersion from './vite-plugins/build-version.js';

export default defineConfig({
  plugins: [
    vue(),
    // 生成 dist/assets/version.json，供 functions.php 读取（构建哈希版本号）。
    buildVersion(),
    // 生成 .gz / .br 预压缩文件（只生成文件，不开服务器压缩）。
    precompress(),
  ],

  // 关掉 public 目录拷贝：之前会把 public/favicon.svg、icons.svg 原样复制进 dist/，
  // 但前台从来没有引用过它们，只是给 dist 添垃圾。dist 里现在只保留打包产物。
  publicDir: false,

  build: {
    outDir: '../dist',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/index.js',      // JS 永远叫 index.js
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',  // CSS 永远叫 index.css
      }
    }
  },
});
