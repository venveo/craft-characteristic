import vue from '@vitejs/plugin-vue'
import ViteRestart from 'vite-plugin-restart';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import path from 'path';
// Big thanks to Andrew Welch (@nystudio107) for his work on Vite tooling
// https://vitejs.dev/config/
export default ({ command }) => ({
  base: command === 'serve' ? '' : '/dist/',
  build: {
    emptyOutDir: true,
    manifest: true,
    outDir: '../src/web/assets/dist',
    rollupOptions: {
      input: {
        app: './src/js/app.ts'
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
        './src/templates/**/*',
      ],
    }),
    vue(),
  ],
  publicDir: '../src/web/assets/public',
  resolve: {
    preserveSymlinks: true,
    alias: {
      '@': path.resolve(__dirname, './src')
    },
  },
  server: {
    origin: 'http://localhost:3001/src/',
    host: '0.0.0.0',
    port: 3001,
    strictPort: true,
  }
});