/**
 * @param {string} siteUrl  Canonical origin, no trailing slash
 * @param {string} siteName  Brand name (from config /api/site)
 * @param {string} description  Short product description for SoftwareApplication
 * @returns {object|null}
 */
export function buildLandingJsonLd(siteUrl, siteName = 'ReelForge', description = '') {
  if (!siteUrl) return null
  return {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: siteName,
    url: siteUrl,
    description: description || `${siteName} — AI product photo and video for e-commerce.`,
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    offers: {
      '@type': 'Offer',
      priceCurrency: 'USD',
    },
  }
}
