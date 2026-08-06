import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        // Inside Docker frontend container, backend is http://backend:80
        // Outside Docker (host npm dev), set API_PROXY_TARGET=http://localhost:8000 or VITE_PROXY_TARGET
        target: process.env.VITE_PROXY_TARGET || process.env.API_PROXY_TARGET || 'http://backend:80',
        changeOrigin: true
      },
      '/uploads': {
        target: process.env.VITE_PROXY_TARGET || process.env.API_PROXY_TARGET || 'http://backend:80',
        changeOrigin: true
      }
    }
  }
})
