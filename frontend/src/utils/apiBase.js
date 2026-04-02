/**
 * Backend origin for OAuth redirects (must match APP_URL host/port).
 * Set VITE_API_URL in .env (e.g. http://127.0.0.1:8000).
 */
export function getApiOrigin() {
  const raw = import.meta.env.VITE_API_URL
  if (!raw) return ''
  return String(raw).replace(/\/$/, '')
}

export function getOAuthRedirectUrl(provider) {
  const origin = getApiOrigin()
  if (!origin) return '#'
  return `${origin}/auth/${provider}/redirect`
}
