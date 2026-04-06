import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Film, Zap, Download, Smartphone, CheckCircle, ImagePlus, Sparkles, LayoutGrid } from 'lucide-react'
import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SeoHead from '../components/seo/SeoHead'
import { buildLandingJsonLd } from '../components/seo/landingJsonLd'
import { getSiteUrl } from '../utils/siteUrl'
import { useSite } from '../context/SiteContext'
import FaqSection from '../components/landing/FaqSection'
import ProductCardCarousel from '../components/landing/ProductCardCarousel'
import LandingSeoArticle from '../components/landing/LandingSeoArticle'

const featureIcons = [ImagePlus, LayoutGrid, Film, Download, Smartphone, CheckCircle]
const featureKeys = ['upload', 'template', 'generate', 'download', 'mobile', 'noSkills']

const howItWorksIcons = [ImagePlus, Sparkles, Zap]
const howItWorksKeys = ['step1', 'step2', 'step3']

export default function Landing() {
  const { t } = useTranslation()
  const { siteName } = useSite()
  const jsonLd = buildLandingJsonLd(getSiteUrl(), siteName, t('seo.jsonLdDescription', { siteName }))

  return (
    <div className="min-h-screen bg-gray-950">
      <SeoHead
        titleKey="seo.landingTitle"
        descriptionKey="seo.landingDescription"
        ogTitleKey="seo.landingOgTitle"
        ogDescriptionKey="seo.landingOgDescription"
        keywordsKey="seo.landingKeywords"
        jsonLd={jsonLd}
      />
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 left-1/4 w-96 h-96 bg-brand-900/25 rounded-full blur-3xl" />
        <div className="absolute top-1/3 -right-20 w-80 h-80 bg-purple-900/20 rounded-full blur-3xl" />
        <div className="absolute bottom-20 left-10 w-64 h-64 bg-brand-900/15 rounded-full blur-3xl" />
      </div>

      <LandingNav />

      <section className="relative z-10 max-w-4xl mx-auto px-6 pt-20 pb-28 text-center">
        <div className="inline-flex items-center gap-2 bg-brand-900/40 border border-brand-500/30 rounded-full px-4 py-1.5 mb-8">
          <Zap className="w-3.5 h-3.5 text-brand-400" />
          <span className="text-xs text-brand-300 font-medium">{t('landing.badge')}</span>
        </div>

        <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold text-white leading-tight mb-6 max-w-4xl mx-auto">
          <span className="block">{t('landing.heroH1Line1')}</span>
          <span className="block mt-2 sm:mt-3 gradient-text">{t('landing.heroH1Line2')}</span>
        </h1>

        <p className="text-lg text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
          {t('landing.heroSub')}
        </p>

        <div className="flex items-center justify-center gap-4 flex-wrap">
          <Link to="/register" className="btn-primary text-base px-8 py-3">
            {t('landing.ctaFree')}
          </Link>
          <Link to="/login" className="btn-secondary text-base px-8 py-3">
            {t('landing.ctaLogin')}
          </Link>
        </div>
      </section>

      <section className="relative z-10 max-w-5xl mx-auto px-6 pb-24" aria-labelledby="how-it-works-heading">
        <div className="text-center mb-12">
          <h2 id="how-it-works-heading" className="text-3xl sm:text-4xl font-bold text-white mb-3">
            {t('landing.howItWorksTitle')}
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">{t('landing.howItWorksSubtitle')}</p>
        </div>

        <ol className="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
          {howItWorksKeys.map((key, idx) => {
            const Icon = howItWorksIcons[idx]
            return (
              <li key={key} className="relative">
                <div className="h-full rounded-2xl border border-white/10 bg-gray-900/50 p-6 pt-8 text-center md:text-left backdrop-blur-sm hover:border-brand-500/25 transition-colors">
                  <div className="absolute -top-3 left-1/2 flex h-10 w-10 -translate-x-1/2 items-center justify-center rounded-xl border border-brand-500/50 bg-gradient-to-br from-brand-600/80 to-purple-700/80 text-sm font-bold text-white shadow-lg md:left-6 md:translate-x-0">
                    {idx + 1}
                  </div>
                  <div className="mt-4 mb-4 flex justify-center md:justify-start">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-900/50 border border-brand-500/30">
                      <Icon className="h-6 w-6 text-brand-400" aria-hidden />
                    </div>
                  </div>
                  <h3 className="text-lg font-semibold text-white mb-2">
                    {t(`landing.howItWorks.${key}.title`)}
                  </h3>
                  <p className="text-sm text-gray-400 leading-relaxed">
                    {t(`landing.howItWorks.${key}.desc`)}
                  </p>
                </div>
              </li>
            )
          })}
        </ol>
      </section>

      <section className="relative z-10 max-w-6xl mx-auto px-6 pb-24">
        <h2 className="text-3xl font-bold text-white text-center mb-12">
          {t('landing.featuresTitle')}
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {featureKeys.map((key, idx) => {
            const Icon = featureIcons[idx]
            return (
              <div key={key} className="card hover:border-brand-500/30 transition-all duration-200">
                <div className="w-10 h-10 rounded-xl bg-brand-900/50 border border-brand-500/30 flex items-center justify-center mb-4">
                  <Icon className="w-5 h-5 text-brand-400" />
                </div>
                <h3 className="font-semibold text-white mb-2">{t(`landing.features.${key}.title`)}</h3>
                <p className="text-sm text-gray-400 leading-relaxed">
                  {t(`landing.features.${key}.desc`, { siteName })}
                </p>
              </div>
            )
          })}
        </div>
      </section>

      <section className="relative z-10 max-w-5xl mx-auto px-6 pb-24" aria-labelledby="card-examples-heading">
        <div className="text-center mb-12">
          <h2 id="card-examples-heading" className="text-3xl sm:text-4xl font-bold text-white mb-3">
            {t('landing.cardExamplesTitle')}
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">{t('landing.cardExamplesSubtitle')}</p>
        </div>
        <ProductCardCarousel />
      </section>

      <section className="relative z-10 max-w-5xl mx-auto px-6 pb-24" aria-labelledby="video-social-heading">
        <div className="text-center mb-12">
          <h2 id="video-social-heading" className="text-3xl sm:text-4xl font-bold text-white mb-3">
            {t('landing.videoSectionTitle')}
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto leading-relaxed">{t('landing.videoSectionSubtitle')}</p>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 max-w-2xl mx-auto">
          {[t('landing.videoExample1'), t('landing.videoExample2')].map((label, i) => (
            <div
              key={i}
              className="relative mx-auto w-full max-w-[220px] aspect-[9/16] rounded-2xl border border-white/10 bg-gradient-to-b from-gray-900/80 to-gray-950 flex flex-col items-center justify-center gap-3 p-6 text-center"
            >
              <Film className="w-10 h-10 text-brand-400/90" aria-hidden />
              <span className="text-sm text-gray-400 leading-snug">{label}</span>
            </div>
          ))}
        </div>
      </section>



      <FaqSection className="pb-28" />
      <section className="relative z-10 max-w-2xl mx-auto px-6 pb-24 text-center">
        <div className="card border-brand-500/20">
          <Film className="w-12 h-12 text-brand-400 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-white mb-3">{t('landing.ctaTitle')}</h2>
          <p className="text-gray-400 mb-6">{t('landing.ctaSub')}</p>
          <Link to="/register" className="btn-primary inline-block px-8 py-3 text-base">
            {t('landing.ctaBtn')}
          </Link>
        </div>
      </section>

      <LandingSeoArticle />

      <LandingFooter />
    </div>
  )
}
