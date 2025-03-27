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
    host: 'localhost',
    cors: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    },
    fs: {
      // Allow serving files from one level up to the project root
      allow: ['..']
    }
  },
  optimizeDeps: {
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