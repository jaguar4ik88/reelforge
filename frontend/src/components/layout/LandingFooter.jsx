import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useSite } from '../../context/SiteContext'

const footerNav = [
  { to: '/terms', labelKey: 'navLinks.terms' },
  { to: '/privacy', labelKey: 'navLinks.privacy' },
  { to: '/refund', labelKey: 'navLinks.refund' },
  { to: '/pricing', labelKey: 'navLinks.pricing' },
  { to: '/blog', labelKey: 'navLinks.blog' },
]

export default function LandingFooter() {
  const { t } = useTranslation()
  const { siteName } = useSite()

  return (
    <footer className="relative z-10 border-t border-white/10 py-10 px-6">
      <div className="max-w-6xl mx-auto flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between sm:gap-12">
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
    </footer>
  )
}
