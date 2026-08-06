import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        // For docker: backend service is http://backend:80, for host dev: http://localhost:8000
        target: process.env.VITE_PROXY_TARGET || process.env.API_PROXY_TARGET || 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})
