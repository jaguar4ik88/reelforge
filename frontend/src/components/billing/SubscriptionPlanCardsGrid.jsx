import { Link } from 'react-router-dom'
import { Zap, Star, Building2 } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import SubscriptionPlanFeaturesList from './SubscriptionPlanFeaturesList'
import { pickLocalizedDescription } from '../../utils/planMarketing'

export const SUBSCRIPTION_VARIANT_MAP = {
  starter: { Icon: Zap, gradient: 'from-gray-700 to-gray-600' },
  pro: { Icon: Star, gradient: 'from-brand-600 to-purple-600' },
  business: { Icon: Building2, gradient: 'from-violet-600 to-indigo-600' },
}

/**
 * Same card chrome as the public Pricing page: rounded-3xl, POPULAR badge, gradients, features list.
 *
 * @param {{
 *   plans: Array<Record<string, unknown>>,
 *   language?: string,
 *   mode?: 'marketing' | 'checkout',
 *   registerHref?: string,
 *   payLabel?: string,
 *   canPay?: boolean,
 *   payingSubSlug?: string | null,
 *   onPay?: (slug: string) => void,
 * }} props
 */
export default function SubscriptionPlanCardsGrid({
  plans,
  language,
  mode = 'marketing',
  registerHref = '/register',
  payLabel = '',
  canPay = false,
  payingSubSlug = null,
  onPay,
}) {
  const { t } = useTranslation()
  const list = Array.isArray(plans) ? plans : []

  if (list.length === 0) return null

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-5xl mx-auto">
      {list.map((plan) => {
        const slug = String(plan.slug ?? '')
        const variant = SUBSCRIPTION_VARIANT_MAP[plan.display_variant] ?? SUBSCRIPTION_VARIANT_MAP.starter
        const Icon = variant.Icon
        const monthly = Number(plan.price_usd)
        const displayPrice = Number.isFinite(monthly) ? monthly.toFixed(2) : '—'
        const perCredit =
          plan.per_credit_usd != null && Number.isFinite(Number(plan.per_credit_usd))
            ? Number(plan.per_credit_usd).toFixed(3)
            : ''

        return (
          <div
            key={slug || plan.id}
            className={`relative flex h-full flex-col rounded-3xl border transition-all duration-200 ${
              plan.is_featured
                ? 'border-brand-500/60 bg-gray-900 shadow-2xl shadow-brand-900/40 scale-[1.02]'
                : 'border-white/10 bg-gray-900/60 hover:border-white/20'
            }`}
          >
            {plan.is_featured && (
              <div className="absolute -top-3.5 left-1/2 -translate-x-1/2">
                <span className="bg-brand-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                  {t('pricing.popular')}
                </span>
              </div>
            )}

            <div className="p-6 flex flex-col flex-1">
              <div className="flex items-center justify-between mb-4">
                <span className="text-lg font-bold text-white">{plan.name}</span>
                <div className={`w-9 h-9 rounded-xl bg-gradient-to-br ${variant.gradient} flex items-center justify-center`}>
                  <Icon className="w-4 h-4 text-white" />
                </div>
              </div>

              <p className="text-sm text-gray-400 mb-4 text-left min-h-[2.5rem]">{pickLocalizedDescription(plan, language)}</p>

              <div className="mb-1">
                <span className="text-4xl font-extrabold text-white">${displayPrice}</span>
                <span className="text-gray-400 text-sm ml-1">{t('pricing.perMonth')}</span>
              </div>
              <div className="mb-4" />

              {mode === 'checkout' && plan.amount_uah && (
                <div className="text-brand-300 text-sm mb-4">{t('credits.chargeInUah', { amount: plan.amount_uah })}</div>
              )}

              {mode === 'marketing' ? (
                <Link
                  to={registerHref}
                  className={`block text-center font-semibold py-3 rounded-xl mb-6 transition-all duration-200 active:scale-95 ${
                    plan.is_featured
                      ? 'bg-gradient-to-r from-brand-600 to-purple-600 hover:from-brand-500 hover:to-purple-500 text-white shadow-lg shadow-brand-900/40'
                      : 'bg-white/10 hover:bg-white/15 text-white border border-white/15'
                  }`}
                >
                  {t('pricing.startBtn')}
                </Link>
              ) : (
                <button
                  type="button"
                  disabled={!canPay || payingSubSlug === slug}
                  onClick={() => onPay?.(slug)}
                  className={`mb-6 w-full font-semibold py-3 rounded-xl transition-all disabled:opacity-40 disabled:cursor-not-allowed ${
                    plan.is_featured
                      ? 'bg-gradient-to-r from-brand-600 to-purple-600 hover:from-brand-500 hover:to-purple-500 text-white shadow-lg shadow-brand-900/40'
                      : 'bg-white/10 hover:bg-white/15 text-white border border-white/15'
                  }`}
                >
                  {payingSubSlug === slug ? t('common.loading') : payLabel}
                </button>
              )}

              <div className="flex-1 min-h-0">
                <SubscriptionPlanFeaturesList features={plan.features} />
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}
