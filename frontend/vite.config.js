import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const API_TARGET = env.VITE_API_TARGET || 'https://localhost:8000'
  const proxyOpts = { target: API_TARGET, changeOrigin: true, secure: false }

  return {
    plugins: [react()],
    build: {
      rollupOptions: {
        input: {
          main:  'index.html',
        },
      },
      outDir: '../public/react',
      emptyOutDir: true,
    },

    server: {
      port: 3000,
      proxy: {
        '/api': proxyOpts,
        '/admin': proxyOpts,
        '/devis': {
          ...proxyOpts,
          bypass(req) {
            if (req.url && !req.url.includes('/pdf')) return req.url
            return null
          },
        },
      },
    },
  }
})
