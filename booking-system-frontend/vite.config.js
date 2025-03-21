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
    port: 3002,  // Match your current working port
    host: '0.0.0.0',
    strictPort: true,
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
    },
    // Explicitly tell Vite to serve index.html for SPA routing
    middlewareMode: 'html'
  }
})