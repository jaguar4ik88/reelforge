/**
 * Canonical marketing / app origin for SEO (Open Graph, JSON-LD, sitemap).
 * Set VITE_SITE_URL in production (e.g. https://example.com) so meta tags
 * stay correct behind proxies and on staging.
 */
export function getSiteUrl() {
  const raw = import.meta.env.VITE_SITE_URL
  if (raw) return String(raw).replace(/\/$/, '')
  if (typeof window !== 'undefined') return window.location.origin
  return ''
}

/** Optional absolute URL for og:image / twitter:image (1200×630 recommended). */
export function getOgImageUrl() {
  const raw = import.meta.env.VITE_OG_IMAGE_URL
  if (!raw) return ''
  return String(raw).replace(/\/$/, '')
}
