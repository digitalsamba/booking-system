import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import spaFallback from './spa-fallback'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue(), spaFallback()],
  base: '',
  root: process.cwd(),
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 3002,
    host: '0.0.0.0',
    cors: true,
    allowedHosts: ['dev4.wbcnf.net', '.wbcnf.net'],
    strictPort: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      },
      // Proxy specific public API endpoints, not the general /public route
      '^/public/(availability|booking|user/.+|branding/.+)': {
        target: 'https://api.dev4.wbcnf.net',
        changeOrigin: true,
        secure: true
      }
    },
    fs: {
      // Allow serving files from one level up to the project root
      allow: ['..']
    }
  },
  optimizeDeps: {
    include: ['vue', 'vuetify', 'pinia', 'vue-router', 'axios'],
    exclude: []
  },
  build: {
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'index.html')
      }
    }
  }
})