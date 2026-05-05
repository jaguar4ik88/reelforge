/**
 * Backend origin for OAuth redirects (must match APP_URL host/port).
 * Set VITE_API_URL in .env (e.g. http://127.0.0.1:8000).
 */
export function getApiOrigin() {
  const raw = import.meta.env.VITE_API_URL
  if (!raw) return ''
  return String(raw).replace(/\/$/, '')
}

function loopbackBucket(hostname) {
  const h = String(hostname).toLowerCase()
  if (h === 'localhost' || h === '127.0.0.1' || h === '[::1]') return '__loopback__'
  return h
}

/**
 * True when two URL origins refer to the same host (localhost vs 127.0.0.1 treated as same bucket).
 */
function sameBackendOrigin(assetUrl, apiOriginString) {
  try {
    const a = new URL(assetUrl)
    const b = new URL(apiOriginString)
    return a.protocol === b.protocol && a.port === b.port && loopbackBucket(a.hostname) === loopbackBucket(b.hostname)
  } catch {
    return false
  }
}

/**
 * Laravel `URL::asset('storage/...')` points at the API origin (e.g. :8000). Vite dev runs on :5173;
 * Fabric loads images with crossOrigin=anonymous, which triggers CORS and fails without headers on
 * PHP's static file handler. Rewrite to the current page origin so `vite.config.js` `/storage` proxy
 * serves bytes same-origin.
 *
 * In production, if the SPA and API share a host or you front storage via the app origin, this is a no-op
 * when URLs already match `window.location.origin`.
 */
export function resolveBackendAssetUrlForCanvas(url) {
  if (!url || typeof url !== 'string') return url
  try {
    const asset = new URL(url, window.location.href)
    if (!asset.pathname.startsWith('/storage/')) return url
    if (asset.origin === window.location.origin) return url

    const apiOrigin = getApiOrigin()
    if (apiOrigin && sameBackendOrigin(asset.href, apiOrigin)) {
      return `${window.location.origin}${asset.pathname}${asset.search}`
    }
    if (import.meta.env.DEV && !apiOrigin) {
      return `${window.location.origin}${asset.pathname}${asset.search}`
    }
  } catch {
    /* ignore */
  }
  return url
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
 * - Different origin → window.location.href (cross-domain, e.g. example.com → app.example.com).
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
