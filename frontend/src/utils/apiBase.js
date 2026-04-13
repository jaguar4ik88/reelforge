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
 * True when hostname is typical local dev (Vite / Laravel).
 */
function isLoopbackHostname(hostname) {
  return (
    hostname === 'localhost' ||
    hostname === '127.0.0.1' ||
    hostname === '[::1]'
  )
}

/**
 * App base URL for post-login redirects (e.g. https://app.example.com).
 * If VITE_APP_URL is unset → current origin.
 * If the build was made with a loopback VITE_APP_URL but the user is on a real host,
 * use current origin so production is not redirected to localhost:5173.
 */
export function getAppUrl() {
  const raw = import.meta.env.VITE_APP_URL
  if (!raw) return window.location.origin
  const configured = String(raw).replace(/\/$/, '')
  let configuredOrigin
  try {
    configuredOrigin = new URL(
      /^https?:\/\//i.test(configured) ? configured : `https://${configured}`
    ).origin
  } catch {
    return window.location.origin
  }
  if (!isLoopbackHostname(window.location.hostname) && isLoopbackHostname(new URL(configuredOrigin).hostname)) {
    return window.location.origin
  }
  return configured
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
