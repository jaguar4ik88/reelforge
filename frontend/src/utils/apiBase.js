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

/**
 * App subdomain base URL (e.g. https://app.reelforge.com).
 * If not set — falls back to current origin (local dev stays on same domain).
 */
export function getAppUrl() {
  const raw = import.meta.env.VITE_APP_URL
  if (!raw) return window.location.origin
  return String(raw).replace(/\/$/, '')
}

/**
 * Redirect to the app subdomain after auth.
 *
 * - Same origin → React Router navigate (no full reload, better DX locally).
 * - Different origin → window.location.href (cross-domain, e.g. reelforge.com → app.reelforge.com).
 *
 * @param {string} path         e.g. '/app/dashboard'
 * @param {Function} navigate   React Router navigate function
 */
export function redirectToApp(path, navigate) {
  const appUrl = getAppUrl()
  const isSameOrigin = appUrl === window.location.origin

  if (isSameOrigin) {
    navigate(path, { replace: true })
  } else {
    window.location.href = appUrl + path
  }
}
