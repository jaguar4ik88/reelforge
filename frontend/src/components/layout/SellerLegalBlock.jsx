import { useTranslation } from 'react-i18next'
import { useSite } from '../../context/SiteContext'

/** @param {{ compact?: boolean, plain?: boolean, showTitle?: boolean }} props */
export function hasSellerProfile(seller) {
  if (!seller || typeof seller !== 'object') return false
  return Object.values(seller).some((v) => typeof v === 'string' && v.trim() !== '')
}

/**
 * WayForPay / UA law: full legal name, tax id, addresses, phone, email.
 * Data from GET /api/site → seller (SELLER_* env vars on backend).
 */
export default function SellerLegalBlock({ compact = false, plain = false, showTitle = true }) {
  const { t } = useTranslation()
  const { seller } = useSite()

  if (!hasSellerProfile(seller)) return null

  const row = (labelKey, value, href) => {
    if (!value || typeof value !== 'string' || !value.trim()) return null
    const v = value.trim()
    const inner = href ? (
      <a href={href} className="text-brand-400 hover:underline break-words">
        {v}
      </a>
    ) : (
      <span className="break-words">{v}</span>
    )
    return (
      <div className={compact ? 'text-xs' : 'text-sm'}>
        <dt className="text-gray-500">{t(labelKey)}</dt>
        <dd className="text-gray-300 mt-0.5">{inner}</dd>
      </div>
    )
  }

  const sectionClass = compact
    ? 'border-t border-white/10 pt-4 mt-6 text-left'
    : plain
      ? 'text-left'
      : 'rounded-2xl border border-white/10 bg-gray-900/40 p-6 text-left'

  const titleClass = compact
    ? 'text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3'
    : plain
      ? 'text-sm font-semibold text-gray-400 mb-3'
      : 'text-lg font-semibold text-white mb-4'

  const ariaLabel = showTitle ? t('landing.sellerSectionTitle') : t('landing.sellerPageTitle')

  return (
    <section className={sectionClass} aria-label={ariaLabel}>
      {showTitle ? (
        <h2 className={titleClass}>{t('landing.sellerSectionTitle')}</h2>
      ) : null}
      <dl className={compact ? 'space-y-2' : 'space-y-3'}>
        {row('landing.sellerCompanyName', seller.company_name)}
        {row('landing.sellerTaxId', seller.tax_id)}
        {row('landing.sellerLegalAddress', seller.legal_address)}
        {row('landing.sellerPhysicalAddress', seller.physical_address)}
        {row(
          'landing.sellerPhone',
          seller.phone,
          seller.phone ? `tel:${String(seller.phone).replace(/\s/g, '')}` : undefined,
        )}
        {row('landing.sellerEmail', seller.email, seller.email ? `mailto:${seller.email.trim()}` : undefined)}
      </dl>
    </section>
  )
}
