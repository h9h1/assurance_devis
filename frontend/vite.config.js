import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  // Set VITE_API_TARGET in .env to override.
  // Use the HTTPS address your Symfony/DDEV server actually listens on.
  const API_TARGET = env.VITE_API_TARGET || 'https://localhost:8000'

  const proxyOpts = {
    target: API_TARGET,
    changeOrigin: true,
    secure: false,          // accept self-signed certs (DDEV / symfony server:start)
  }

  return {
    plugins: [react()],
    server: {
      port: 3000,
      proxy: {
        '/api': proxyOpts,
        // Only proxy the PDF download; all other /devis/* are React Router pages
        '/devis': {
          ...proxyOpts,
          bypass(req) {
            if (req.url && !req.url.includes('/pdf')) return req.url
            return null
          },
        },
      },
    },
    build: {
      outDir: '../public/react',
      emptyOutDir: true,
    },
  }
})
