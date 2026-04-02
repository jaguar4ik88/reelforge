import { Link } from 'react-router-dom'
import { Trans, useTranslation } from 'react-i18next'
import { Film, Zap, Upload, Download, Smartphone, CheckCircle } from 'lucide-react'
import LandingNav from '../components/layout/LandingNav'

const featureIcons = [Upload, Film, Zap, Download, Smartphone, CheckCircle]
const featureKeys  = ['upload', 'template', 'generate', 'download', 'mobile', 'noSkills']

export default function Landing() {
  const { t } = useTranslation()

  return (
    <div className="min-h-screen bg-gray-950">
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

        <h1 className="text-5xl sm:text-7xl font-extrabold text-white leading-tight mb-6">
          <Trans i18nKey="landing.hero" components={[<span className="gradient-text" />]} />
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

        <div className="mt-20 flex justify-center gap-4">
          {['dark', 'neon', 'warm'].map((style, i) => (
            <div
              key={style}
              className={`w-28 rounded-2xl overflow-hidden shadow-2xl ${
                i === 1 ? 'scale-110 shadow-brand-900/50' : 'opacity-70 scale-95'
              }`}
            >
              <div className={`aspect-[9/16] bg-gradient-to-b ${
                style === 'dark' ? 'from-gray-900 to-gray-700' :
                style === 'neon' ? 'from-[#0A0A2E] to-[#1a0040]' :
                'from-orange-600 to-rose-600'
              } flex flex-col justify-end p-3`}>
                <div className="space-y-1">
                  <div className="h-2 bg-white/30 rounded w-3/4" />
                  <div className="h-3 rounded w-1/2" style={{
                    background: style === 'dark' ? '#FFD700' : style === 'neon' ? '#00FFCC' : '#FFF200'
                  }} />
                  <div className="h-1.5 bg-white/20 rounded w-full" />
                </div>
              </div>
            </div>
          ))}
        </div>
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
                <p className="text-sm text-gray-400 leading-relaxed">{t(`landing.features.${key}.desc`)}</p>
              </div>
            )
          })}
        </div>
      </section>

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

      <footer className="relative z-10 border-t border-white/10 py-8 text-center text-gray-600 text-sm">
        <p>{t('landing.footer')}</p>
      </footer>
    </div>
  )
}
