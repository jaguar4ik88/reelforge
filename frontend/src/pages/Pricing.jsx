import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Check, Zap, Star, Sparkles, Crown, ChevronDown } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import LandingNav from '../components/layout/LandingNav'

// Статические данные плана (цены, иконки, стили) — не переводятся
const PLAN_META = [
  {
    id: 'starter',
    icon: Zap,
    monthlyPrice: 14,
    yearlyPrice: 9,
    perCredit: '0.15',
    credits: 100,
    color: 'from-gray-700 to-gray-600',
    popular: false,
    featureKeys: ['creditsMonth', 'videosUpTo', 'imagesAtOnce', 'videosConcurrent', 'basicTemplates', 'cancelAnytime'],
    featureParams: [{ count: 100 }, { count: 20 }, { count: 2 }, { count: 1 }, {}, {}],
  },
  {
    id: 'plus',
    icon: Star,
    monthlyPrice: 24,
    yearlyPrice: 16,
    perCredit: '0.08',
    credits: 300,
    color: 'from-brand-600 to-purple-600',
    popular: true,
    featureKeys: ['creditsMonth', 'videosUpTo', 'imagesAtOnce', 'videosConcurrent', 'allTemplates', 'prioritySupport', 'cancelAnytime'],
    featureParams: [{ count: 300 }, { count: 60 }, { count: 5 }, { count: 2 }, {}, {}, {}],
  },
  {
    id: 'creator',
    icon: Sparkles,
    monthlyPrice: 49,
    yearlyPrice: 33,
    perCredit: '0.05',
    credits: 1000,
    color: 'from-violet-600 to-indigo-600',
    popular: false,
    featureKeys: ['creditsMonth', 'videosUpTo', 'imagesAtOnce', 'videosConcurrent', 'allPremiumTemplates', 'prioritySupport', 'noWatermark', 'cancelAnytime'],
    featureParams: [{ count: 1000 }, { count: 200 }, { count: 7 }, { count: 3 }, {}, {}, {}, {}],
  },
  {
    id: 'ultimate',
    icon: Crown,
    monthlyPrice: 124,
    yearlyPrice: 82,
    perCredit: '0.04',
    credits: 3000,
    color: 'from-amber-500 to-orange-500',
    popular: false,
    featureKeys: ['creditsMonth', 'videosUpTo', 'imagesAtOnce', 'videosConcurrent', 'exclusiveTemplates', 'personalSupport', 'noWatermark', 'apiAccess', 'cancelAnytime'],
    featureParams: [{ count: 3000 }, { count: 600 }, { count: 10 }, { count: 5 }, {}, {}, {}, {}, {}],
  },
]

const PACKAGES = [
  { credits: 50,   price: 9.99,  labelKey: 'trial' },
  { credits: 150,  price: 24.99, labelKey: 'start', bonus: 25 },
  { credits: 350,  price: 49.99, labelKey: 'pro',   bonus: 100 },
  { credits: 1000, price: 99.99, labelKey: 'max',   bonus: 500 },
]

const FAQ_KEYS = ['whatAreCredits', 'runOut', 'unused', 'cancel', 'yearlyDiscount', 'teams', 'videoQuality', 'paymentMethods']

function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false)
  return (
    <div className="border border-white/10 rounded-2xl overflow-hidden">
      <button
        onClick={() => setOpen(o => !o)}
        className="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-white/5 transition-colors"
      >
        <span className="font-medium text-white">{q}</span>
        <ChevronDown
          className={`w-5 h-5 text-gray-400 flex-shrink-0 ml-4 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
        />
      </button>
      {open && (
        <div className="px-6 pb-5 text-gray-400 text-sm leading-relaxed border-t border-white/10 pt-4">
          {a}
        </div>
      )}
    </div>
  )
}

export default function Pricing() {
  const { t } = useTranslation()
  const [yearly, setYearly] = useState(false)

  return (
    <div className="min-h-screen bg-gray-950 text-white">
      {/* Background glows */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 left-1/4 w-96 h-96 bg-brand-900/25 rounded-full blur-3xl" />
        <div className="absolute top-1/2 -right-20 w-80 h-80 bg-purple-900/20 rounded-full blur-3xl" />
        <div className="absolute bottom-20 left-10 w-64 h-64 bg-amber-900/10 rounded-full blur-3xl" />
      </div>

      <LandingNav />

      {/* Hero */}
      <div className="relative z-10 text-center pt-16 pb-12 px-6">
        <h1 className="text-5xl sm:text-6xl font-extrabold tracking-tight mb-4">
          {t('pricing.title')}
        </h1>
        <p className="text-gray-400 text-lg max-w-xl mx-auto mb-10">
          {t('pricing.subtitle')}
        </p>

        {/* Toggle monthly / yearly */}
        <div className="inline-flex items-center gap-1 bg-white/10 border border-white/15 rounded-full p-1">
          <button
            onClick={() => setYearly(false)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 ${
              !yearly ? 'bg-white text-gray-900 shadow' : 'text-gray-400 hover:text-white'
            }`}
          >
            {t('pricing.monthly')}
          </button>
          <button
            onClick={() => setYearly(true)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 flex items-center gap-2 ${
              yearly ? 'bg-white text-gray-900 shadow' : 'text-gray-400 hover:text-white'
            }`}
          >
            {t('pricing.yearly')}
            <span className="bg-brand-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              {t('pricing.discount')}
            </span>
          </button>
        </div>

        {yearly && (
          <p className="mt-4 text-sm text-brand-400 flex items-center justify-center gap-2">
            <Check className="w-4 h-4" />
            {t('pricing.saveNote')}
          </p>
        )}
      </div>

      {/* Plans grid */}
      <div className="relative z-10 max-w-6xl mx-auto px-6 pb-20">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          {PLAN_META.map((plan) => {
            const Icon = plan.icon
            const price = yearly ? plan.yearlyPrice : plan.monthlyPrice

            return (
              <div
                key={plan.id}
                className={`relative flex flex-col rounded-3xl border transition-all duration-200 ${
                  plan.popular
                    ? 'border-brand-500/60 bg-gray-900 shadow-2xl shadow-brand-900/40 scale-[1.02]'
                    : 'border-white/10 bg-gray-900/60 hover:border-white/20'
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span className="bg-brand-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                      {t('pricing.popular')}
                    </span>
                  </div>
                )}

                <div className="p-6 flex flex-col flex-1">
                  {/* Header */}
                  <div className="flex items-center justify-between mb-5">
                    <span className="text-lg font-bold text-white">
                      {t(`pricing.plans.${plan.id}.name`)}
                    </span>
                    <div className={`w-9 h-9 rounded-xl bg-gradient-to-br ${plan.color} flex items-center justify-center`}>
                      <Icon className="w-4 h-4 text-white" />
                    </div>
                  </div>

                  {/* Price */}
                  <div className="mb-1">
                    <span className="text-4xl font-extrabold text-white">{price}</span>
                    <span className="text-gray-400 text-sm ml-1">{t('pricing.perMonth')}</span>
                  </div>
                  <p className="text-xs text-gray-500 mb-6">
                    {plan.perCredit} {t('pricing.perCredit')}
                  </p>

                  {/* CTA */}
                  <Link
                    to="/register"
                    className={`block text-center font-semibold py-3 rounded-xl mb-6 transition-all duration-200 active:scale-95 ${
                      plan.popular
                        ? 'bg-gradient-to-r from-brand-600 to-purple-600 hover:from-brand-500 hover:to-purple-500 text-white shadow-lg shadow-brand-900/40'
                        : 'bg-white/10 hover:bg-white/15 text-white border border-white/15'
                    }`}
                  >
                    {t('pricing.startBtn')}
                  </Link>

                  {/* Features */}
                  <ul className="space-y-3 flex-1">
                    {plan.featureKeys.map((key, i) => (
                      <li key={key} className="flex items-start gap-2.5 text-sm text-gray-300">
                        <Check className="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5" />
                        {t(`pricing.features.${key}`, plan.featureParams[i])}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            )
          })}
        </div>

        <p className="text-center text-gray-500 text-sm mt-10">
          {t('pricing.note')}
        </p>
      </div>

      {/* Credit packages */}
      <div className="relative z-10 max-w-4xl mx-auto px-6 pb-24">
        <div className="text-center mb-10">
          <h2 className="text-3xl font-bold mb-3">{t('pricing.packages.title')}</h2>
          <p className="text-gray-400">{t('pricing.packages.subtitle')}</p>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {PACKAGES.map((pkg) => (
            <div
              key={pkg.credits}
              className="bg-gray-900/60 border border-white/10 hover:border-brand-500/40 rounded-2xl p-5 text-center transition-all duration-200 cursor-pointer"
            >
              <div className="text-xs text-gray-500 font-medium uppercase tracking-wide mb-2">
                {t(`pricing.packages.labels.${pkg.labelKey}`)}
              </div>
              <div className="text-2xl font-extrabold text-white mb-1">{pkg.credits}</div>
              {pkg.bonus && (
                <div className="text-xs text-brand-400 font-semibold mb-1">
                  {t('pricing.packages.bonus', { count: pkg.bonus })}
                </div>
              )}
              <div className="text-xs text-gray-400 mb-4">{t('pricing.packages.credits')}</div>
              <div className="font-bold text-white">${pkg.price}</div>
            </div>
          ))}
        </div>
      </div>

      {/* FAQ */}
      <div className="relative z-10 max-w-3xl mx-auto px-6 pb-28">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-3">{t('pricing.faq.title')}</h2>
          <p className="text-gray-400">{t('pricing.faq.subtitle')}</p>
        </div>
        <div className="flex flex-col gap-3">
          {FAQ_KEYS.map((key) => (
            <FaqItem
              key={key}
              q={t(`pricing.faq.items.${key}.q`)}
              a={t(`pricing.faq.items.${key}.a`)}
            />
          ))}
        </div>
      </div>

      {/* Bottom CTA */}
      <div className="relative z-10 max-w-2xl mx-auto px-6 pb-24 text-center">
        <div className="bg-gradient-to-r from-brand-900/40 to-purple-900/40 border border-brand-500/20 rounded-3xl p-10">
          <h2 className="text-3xl font-bold mb-3">{t('pricing.cta.title')}</h2>
          <p className="text-gray-400 mb-6">{t('pricing.cta.subtitle')}</p>
          <Link to="/register" className="btn-primary px-10 py-3 text-base inline-block">
            {t('pricing.cta.btn')}
          </Link>
        </div>
      </div>

      <footer className="relative z-10 border-t border-white/10 py-8 text-center text-gray-600 text-sm">
        © {new Date().getFullYear()} ReelForge. {t('common.allRightsReserved')}
      </footer>
    </div>
  )
}
