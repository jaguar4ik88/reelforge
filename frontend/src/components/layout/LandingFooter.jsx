import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useSite } from '../../context/SiteContext'
import { hasSellerProfile } from './SellerLegalBlock'

/** Pricing & Blog first; legal links; optional Contacts last */
const footerNavCore = [
  { to: '/pricing', labelKey: 'navLinks.pricing' },
  { to: '/blog', labelKey: 'navLinks.blog' },
  { to: '/terms', labelKey: 'navLinks.terms' },
  { to: '/privacy', labelKey: 'navLinks.privacy' },
  { to: '/refund', labelKey: 'navLinks.refund' },
]

export default function LandingFooter() {
  const { t } = useTranslation()
  const { siteName, seller } = useSite()

  const footerNav = hasSellerProfile(seller)
    ? [...footerNavCore, { to: '/contacts', labelKey: 'navLinks.contacts' }]
    : footerNavCore

  return (
    <footer className="relative z-10 border-t border-white/10 py-10 px-6">
      <div className="max-w-6xl mx-auto flex flex-col gap-8">
        <div className="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between sm:gap-12">
          <p className="text-gray-600 text-sm text-center sm:text-left order-2 sm:order-1">
            {t('landing.footer', {
              year: new Date().getFullYear(),
              siteName,
            })}
          </p>
          <nav
            className="flex flex-wrap justify-center sm:justify-end gap-x-8 gap-y-3 text-sm order-1 sm:order-2"
            aria-label={t('landing.footerNavLabel')}
          >
            {footerNav.map(({ to, labelKey }) => (
              <Link key={to} to={to} className="text-gray-500 hover:text-gray-300 transition-colors">
                {t(labelKey)}
              </Link>
            ))}
          </nav>
        </div>
        <p className="text-center text-xs text-gray-600 pt-8 mt-2 border-t border-white/10">
          {t('landing.developedBy')}{' '}
          <a
            href="https://aidentika.online/"
            target="_blank"
            rel="noopener noreferrer"
            className="text-gray-500 hover:text-brand-400 transition-colors"
          >
            Aidentika Apps
          </a>
        </p>
      </div>
    </footer>
  )
}
