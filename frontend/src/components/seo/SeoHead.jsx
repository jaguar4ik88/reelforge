import { Helmet } from 'react-helmet-async'
import { useTranslation } from 'react-i18next'
import { useLocation } from 'react-router-dom'
import { getOgImageUrl, getSiteUrl } from '../../utils/siteUrl'

/**
 * @param {object} props
 * @param {string} [props.titleKey]
 * @param {string} [props.descriptionKey]
 * @param {boolean} [props.noindex]
 * @param {object|null} [props.jsonLd]  schema.org object for application/ld+json
 */
export default function SeoHead({
  titleKey = 'seo.defaultTitle',
  descriptionKey = 'seo.defaultDescription',
  noindex = false,
  jsonLd = null,
}) {
  const { t, i18n } = useTranslation()
  const { pathname } = useLocation()
  const base = getSiteUrl()
  const pathOnly = pathname.split('?')[0] || '/'
  const canonical = base ? `${base}${pathOnly}` : ''

  const title = t(titleKey)
  const description = t(descriptionKey)
  const lang = (i18n.resolvedLanguage || i18n.language || 'en').split('-')[0]
  const htmlLang = lang === 'uk' ? 'uk' : 'en'
  const ogLocale = lang === 'uk' ? 'uk_UA' : 'en_US'
  const ogImage = !noindex ? getOgImageUrl() : ''

  return (
    <Helmet htmlAttributes={{ lang: htmlLang }} prioritizeSeoTags>
      <title>{title}</title>
      <meta name="description" content={description} />
      {canonical ? <link rel="canonical" href={canonical} /> : null}
      {noindex ? (
        <meta name="robots" content="noindex, nofollow" />
      ) : (
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
      )}

      <meta property="og:type" content="website" />
      <meta property="og:title" content={title} />
      <meta property="og:description" content={description} />
      {canonical ? <meta property="og:url" content={canonical} /> : null}
      <meta property="og:site_name" content="ReelForge" />
      <meta property="og:locale" content={ogLocale} />
      {ogImage ? <meta property="og:image" content={ogImage} /> : null}

      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={title} />
      <meta name="twitter:description" content={description} />
      {ogImage ? <meta name="twitter:image" content={ogImage} /> : null}

      {jsonLd ? (
        <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
      ) : null}
    </Helmet>
  )
}
