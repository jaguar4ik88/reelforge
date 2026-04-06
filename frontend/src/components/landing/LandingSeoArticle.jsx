import { useTranslation } from 'react-i18next'
import { useSite } from '../../context/SiteContext'

export default function LandingSeoArticle() {
  const { t } = useTranslation()
  const { siteName } = useSite()

  return (
    <section
      className="relative z-10 max-w-3xl mx-auto px-6 py-16 border-t border-white/10"
      aria-labelledby="landing-seo-article-heading"
    >
      <h2 id="landing-seo-article-heading" className="sr-only">
        {t('landing.seoSectionTitle', { siteName })}
      </h2>
      <div className="text-sm sm:text-base text-gray-400 space-y-4 leading-relaxed">
        <p className="mb-4">{t('landing.seo.intro', { siteName })}</p>
        <h3 className="text-base font-semibold text-white mt-8 mb-3">{t('landing.seo.whoTitle', { siteName })}</h3>
        <p className="mb-4">{t('landing.seo.whoBody', { siteName })}</p>
        <h3 className="text-base font-semibold text-white mt-8 mb-3">{t('landing.seo.whatTitle')}</h3>
        <p className="mb-4">{t('landing.seo.whatBody')}</p>
        <h3 className="text-base font-semibold text-white mt-8 mb-3">{t('landing.seo.howTitle')}</h3>
        <p className="mb-4">{t('landing.seo.howBody')}</p>
        <h3 className="text-base font-semibold text-white mt-8 mb-3">{t('landing.seo.whyTitle', { siteName })}</h3>
        <p>{t('landing.seo.whyBody', { siteName })}</p>
      </div>
    </section>
  )
}
