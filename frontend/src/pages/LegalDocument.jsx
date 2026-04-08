import { useTranslation } from 'react-i18next'
import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SeoHead from '../components/seo/SeoHead'
import { useSite } from '../context/SiteContext'

export default function LegalDocument({ titleKey, bodyKey, seoTitleKey, seoDescKey }) {
  const { t } = useTranslation()
  const { siteName } = useSite()

  return (
    <div className="min-h-screen bg-gray-950 text-white">
      <SeoHead titleKey={seoTitleKey} descriptionKey={seoDescKey} />
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 right-1/4 w-96 h-96 bg-brand-900/15 rounded-full blur-3xl" />
        <div className="absolute bottom-20 left-10 w-64 h-64 bg-purple-900/10 rounded-full blur-3xl" />
      </div>

      <LandingNav />

      <main className="relative z-10 max-w-3xl w-full mx-auto px-6 pt-16 pb-16">
        <h1 className="text-3xl sm:text-4xl font-bold text-white mb-8">{t(titleKey)}</h1>
        <div className="text-gray-400 text-sm sm:text-base leading-relaxed whitespace-pre-line space-y-4">
          {t(bodyKey, { siteName })}
        </div>
      </main>

      <LandingFooter />
    </div>
  )
}
