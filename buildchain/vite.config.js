import vue from '@vitejs/plugin-vue'
import ViteRestart from 'vite-plugin-restart';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import path from 'path';

// https://vitejs.dev/config/
export default ({ command }) => ({
  base: command === 'serve' ? '' : '/dist/',
  build: {
    manifest: true,
    outDir: '../src/web/assets/dist',
    rollupOptions: {
      input: {
        app: '/src/js/app.ts',
        welcome: '/src/js/welcome.ts',
        translationmanager: '/src/js/translationmanager.ts',
        import: '/src/js/import.ts'
      },
      output: {
        sourcemap: true
      },
    }
  },
  plugins: [
    nodeResolve({
      moduleDirectories: [
        path.resolve('./node_modules'),
      ],
    }),
    ViteRestart({
      reload: [
        '../src/templates/**/*',
      ],
    }),
    vue(),
  ],
  publicDir: '../src/web/assets/public',
  resolve: {
    alias: {
      '@': '/src',
      'axios': '/node_modules/axios/index.d.ts',
      'vue': '/node_modules/vue/dist/vue.esm-browser.js',
      'vuedraggable': '/node_modules/vuedraggable/src/vuedraggable.js'
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3001,
    strictPort: true,
  }
});
