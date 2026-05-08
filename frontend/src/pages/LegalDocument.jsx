import { useTranslation } from 'react-i18next'
import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SeoHead from '../components/seo/SeoHead'
import { useSite } from '../context/SiteContext'

export default function LegalDocument({ titleKey, bodyKey, seoTitleKey, seoDescKey }) {
  const { t } = useTranslation()
  const { siteName } = useSite()

  return (
    <div className="min-h-screen bg-rf-page text-rf-text">
      <SeoHead titleKey={seoTitleKey} descriptionKey={seoDescKey} />

      <LandingNav />

      <main className="relative z-10 max-w-3xl w-full mx-auto px-6 pt-16 pb-16">
        <h1 className="text-3xl sm:text-4xl font-bold text-rf-text mb-8">{t(titleKey)}</h1>
        <div className="text-rf-mutedFg text-sm sm:text-base leading-relaxed whitespace-pre-line space-y-4">
          {t(bodyKey, { siteName })}
        </div>
      </main>

      <LandingFooter />
    </div>
  )
}
