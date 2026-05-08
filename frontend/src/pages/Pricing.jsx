import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SeoHead from '../components/seo/SeoHead'
import SubscriptionPlanCardsGrid from '../components/billing/SubscriptionPlanCardsGrid'
import { useSite } from '../context/SiteContext'
import api from '../services/api'

export default function Pricing() {
  const { t, i18n } = useTranslation()
  const { registrationEnabled } = useSite()
  const startHref = registrationEnabled ? '/register' : '/login'
  const [subscriptionPlans, setSubscriptionPlans] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    api
      .get('/credits/subscription-plans')
      .then((subRes) => {
        if (cancelled) return
        setSubscriptionPlans(subRes.data?.data ?? [])
      })
      .catch(() => {
        if (!cancelled) setSubscriptionPlans([])
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [])

  return (
    <div className="min-h-screen bg-rf-page text-rf-text">
      <SeoHead titleKey="seo.pricingTitle" descriptionKey="seo.pricingDescription" />

      <LandingNav />

      <div className="relative z-10 text-center pt-16 pb-12 px-6">
        <h1 className="text-5xl sm:text-6xl font-extrabold tracking-tight mb-4">{t('pricing.title')}</h1>
        <p className="text-rf-mutedFg text-lg max-w-xl mx-auto">{t('pricing.subtitle')}</p>
      </div>

      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 pb-20">
        {loading ? (
          <p className="text-center text-rf-mutedFg">{t('common.loading')}</p>
        ) : subscriptionPlans.length === 0 ? (
          <p className="text-center text-rf-mutedFg text-sm">{t('pricing.plansEmpty')}</p>
        ) : (
          <SubscriptionPlanCardsGrid
            plans={subscriptionPlans}
            language={i18n.language}
            mode="marketing"
            registerHref={startHref}
          />
        )}

        <p className="text-center text-rf-mutedFg text-sm mt-10">{t('pricing.note')}</p>
      </div>

      <div className="relative z-10 max-w-2xl mx-auto px-6 pb-24 text-center">
        <div className="bg-gradient-to-r from-brand-900/40 to-purple-900/40 border border-brand-500/20 rounded-3xl p-10">
          <h2 className="text-3xl font-bold mb-3">{t('pricing.cta.title')}</h2>
          <p className="text-rf-mutedFg mb-6">{t('pricing.cta.subtitle')}</p>
          <Link to={startHref} className="btn-primary px-10 py-3 text-base inline-block">
            {registrationEnabled ? t('pricing.cta.btn') : t('landing.ctaLogin')}
          </Link>
        </div>
      </div>

      <LandingFooter />
    </div>
  )
}
