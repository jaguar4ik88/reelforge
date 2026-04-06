/**
 * @param {string} siteUrl  Canonical origin, no trailing slash
 * @returns {object|null}
 */
export function buildLandingJsonLd(siteUrl) {
  if (!siteUrl) return null
  const orgId = `${siteUrl}/#organization`
  return {
    '@context': 'https://schema.org',
    '@graph': [
      {
        '@id': orgId,
        '@type': 'Organization',
        name: 'ReelForge',
        url: siteUrl,
      },
      {
        '@type': 'WebSite',
        name: 'ReelForge',
        url: siteUrl,
        inLanguage: ['en-US', 'uk-UA'],
        publisher: { '@id': orgId },
      },
    ],
  }
}
