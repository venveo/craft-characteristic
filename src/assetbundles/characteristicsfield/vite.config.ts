import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import fs from 'fs'
const env = loadEnv('development', './')
export default defineConfig({
  root: './src',
  plugins: [vue()],
  build: {
    manifest: true,
    rollupOptions: {
      input: './src/main.ts'
    }
  },
  server: {
    https: {
      key: env.VITE_DEV_SERVER_SSL_KEY ? fs.readFileSync(env.VITE_DEV_SERVER_SSL_KEY) : null,
      cert: env.VITE_DEV_SERVER_SSL_CERT ? fs.readFileSync(env.VITE_DEV_SERVER_SSL_CERT) : null,
    },
    host: env.VITE_DEV_SERVER_HOST
  }
})
