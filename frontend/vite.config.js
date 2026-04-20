import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

/**
 * Vite runs internal static middleware before plugin `middlewares.use()` handlers, so `/app/profile`
 * 404s. Post-hook + unshift runs this *first* and rewrites to `/index.html` (same query string).
 */
function spaHistoryFallback() {
  return {
    name: 'reelforge-spa-history-fallback',
    configureServer(server) {
      return () => {
        const handle = (req, res, next) => {
          if (req.method !== 'GET' && req.method !== 'HEAD') {
            return next()
          }
          const raw = req.url ?? '/'
          const pathname = raw.split('?')[0]
          if (
            pathname.startsWith('/api') ||
            pathname.startsWith('/@') ||
            pathname.startsWith('/node_modules') ||
            pathname.startsWith('/.well-known')
          ) {
            return next()
          }
          const lastSeg = pathname.split('/').pop() ?? ''
          if (lastSeg.includes('.') && lastSeg !== 'index.html') {
            return next()
          }
          if (pathname === '/' || pathname === '/index.html') {
            return next()
          }
          const qs = raw.includes('?') ? `?${raw.split('?').slice(1).join('?')}` : ''
          req.url = `/index.html${qs}`
          next()
        }
        server.middlewares.stack.unshift({ route: '', handle })
      }
    },
  }
}

export default defineConfig({
  appType: 'spa',
  plugins: [spaHistoryFallback(), react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.VITE_API_URL || 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
