import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Coins, ArrowLeft, History } from 'lucide-react'
import toast from 'react-hot-toast'
import SeoHead from '../components/seo/SeoHead'
import api, { paymentsApi } from '../services/api'
import { useSite } from '../context/SiteContext'
import { useAuthContext } from '../context/AuthContext'
import { setWayForPayOrderReference, useWayForPayReturn } from '../hooks/useWayForPayReturn'
import { APP_BASE } from '../constants/routes'

/**
 * Nested route under AppLayout — must not wrap with AppLayout again.
 */
export default function Credits() {
  const { t } = useTranslation()
  const { payments } = useSite()
  const { refreshUser } = useAuthContext()
  const [searchParams, setSearchParams] = useSearchParams()
  const [packages, setPackages] = useState([])
  const [subscriptionPlans, setSubscriptionPlans] = useState([])
  const [loading, setLoading] = useState(true)
  const [payingSlug, setPayingSlug] = useState(null)
  const [payingSubSlug, setPayingSubSlug] = useState(null)
  const [checkout, setCheckout] = useState(null)
  const [purchases, setPurchases] = useState([])
  const formRef = useRef(null)

  const loadPurchases = useCallback(async () => {
    try {
      const res = await api.get('/credits/purchases')
      setPurchases(res.data?.data ?? [])
    } catch {
      setPurchases([])
    }
  }, [])

  useWayForPayReturn({ onSettled: loadPurchases })

  useEffect(() => {
    let cancelled = false
    Promise.all([
      api.get('/credits/packages'),
      api.get('/credits/subscription-plans'),
      paymentsApi.checkoutContext(),
      api.get('/credits/purchases').catch(() => ({ data: { data: [] } })),
    ])
      .then(([pkgRes, subRes, ctxRes, purRes]) => {
        if (cancelled) return
        setPackages(pkgRes.data?.data ?? [])
        setSubscriptionPlans(subRes.data?.data ?? [])
        setCheckout(ctxRes.data?.data ?? null)
        setPurchases(purRes.data?.data ?? [])
      })
      .catch(() => toast.error(t('common.error')))
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [t])

  useEffect(() => {
    if (searchParams.get('payment') !== 'fastspring_return') return
    toast.success(t('credits.fastspringReturn'))
    refreshUser?.()
    setSearchParams({}, { replace: true })
  }, [searchParams, setSearchParams, refreshUser, t])

  const startWayForPay = async (slug) => {
    if (!checkout?.wayforpay_available || checkout?.billing_provider !== 'wayforpay') {
      toast.error(t('credits.wayforpayDisabled'))
      return
    }
    setPayingSlug(slug)
    try {
      const res = await paymentsApi.wayforpayInvoice(slug)
      const { pay_url: payUrl, fields, order_reference: orderRef } = res.data?.data ?? {}
      setWayForPayOrderReference(orderRef)
      if (!payUrl || !fields?.length) {
        toast.error(t('common.error'))
        return
      }
      const form = formRef.current
      if (!form) return
      form.action = payUrl
      form.method = 'POST'
      form.innerHTML = ''
      fields.forEach(({ name, value }) => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = name
        input.value = value
        form.appendChild(input)
      })
      form.submit()
    } catch (e) {
      const msg = e.response?.data?.message
      toast.error(typeof msg === 'string' ? msg : t('common.error'))
    } finally {
      setPayingSlug(null)
    }
  }

  const startWayForPaySubscription = async (slug) => {
    if (!checkout?.wayforpay_available || checkout?.billing_provider !== 'wayforpay') {
      toast.error(t('credits.wayforpayDisabled'))
      return
    }
    setPayingSubSlug(slug)
    try {
      const res = await paymentsApi.wayforpaySubscriptionInvoice(slug)
      const { pay_url: payUrl, fields, order_reference: orderRef } = res.data?.data ?? {}
      setWayForPayOrderReference(orderRef)
      if (!payUrl || !fields?.length) {
        toast.error(t('common.error'))
        return
      }
      const form = formRef.current
      if (!form) return
      form.action = payUrl
      form.method = 'POST'
      form.innerHTML = ''
      fields.forEach(({ name, value }) => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = name
        input.value = value
        form.appendChild(input)
      })
      form.submit()
    } catch (e) {
      const msg = e.response?.data?.message
      toast.error(typeof msg === 'string' ? msg : t('common.error'))
    } finally {
      setPayingSubSlug(null)
    }
  }

  const startFastSpring = async (slug) => {
    if (!checkout?.fastspring_available || checkout?.billing_provider !== 'fastspring') {
      toast.error(t('credits.fastspringDisabled'))
      return
    }
    setPayingSlug(slug)
    /*
     * Do NOT pass noopener on window.open() here: with noopener, many browsers return null while still
     * opening a blank tab, so we never assign checkout_url and the tab stays empty.
     * Open a blank tab without noopener, then navigate it after the API returns.
     */
    const win = window.open('about:blank', '_blank')
    if (!win) {
      toast.error(t('credits.fastspringPopupBlocked'))
      setPayingSlug(null)
      return
    }
    try {
      const res = await paymentsApi.fastspringSession(slug)
      const url = res.data?.data?.checkout_url
      if (typeof url === 'string' && url) {
        win.location.replace(url)
        return
      }
      win.close()
      toast.error(t('common.error'))
    } catch (e) {
      try {
        win.close()
      } catch {
        /* ignore */
      }
      const msg = e.response?.data?.message
      toast.error(typeof msg === 'string' ? msg : t('common.error'))
    } finally {
      setPayingSlug(null)
    }
  }

  const payHandler =
    checkout?.billing_provider === 'wayforpay'
      ? startWayForPay
      : checkout?.billing_provider === 'fastspring'
        ? startFastSpring
        : () => {}
  const payLabel =
    checkout?.billing_provider === 'wayforpay'
      ? t('credits.payUah')
      : checkout?.billing_provider === 'fastspring'
        ? t('credits.payFastspring')
        : t('common.loading')

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <SeoHead titleKey="seo.creditsTitle" descriptionKey="seo.creditsDescription" noindex />
      <form ref={formRef} className="hidden" aria-hidden />

      <div className="mb-8">
        <Link
          to={`${APP_BASE}/dashboard`}
          className="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-4"
        >
          <ArrowLeft className="w-4 h-4" />
          {t('credits.back')}
        </Link>
        <h1 className="text-3xl font-bold text-white flex items-center gap-3">
          <Coins className="w-8 h-8 text-brand-400" />
          {t('credits.title')}
        </h1>
        <p className="text-gray-400 mt-2">{t('credits.subtitle')}</p>
        {checkout?.billing_provider === 'wayforpay' && checkout?.wayforpay_available && (
          <p className="text-sm text-amber-200/90 mt-3 border border-amber-500/30 rounded-xl px-4 py-3 bg-amber-950/30">
            {t('credits.wayforpayCheckoutNote', {
              rate: checkout.usd_to_uah ?? payments?.usd_to_uah ?? 42,
              discount: checkout.ua_discount_percent ?? payments?.ua_discount_percent ?? 0,
            })}
          </p>
        )}
        {checkout?.billing_provider === 'fastspring' && checkout?.fastspring_available && (
          <p className="text-sm text-sky-200/90 mt-3 border border-sky-500/30 rounded-xl px-4 py-3 bg-sky-950/30">
            {t('credits.fastspringCheckoutNote')}
          </p>
        )}
      </div>

      {loading ? (
        <div className="text-gray-400">{t('common.loading')}</div>
      ) : (
        <>
          {subscriptionPlans.length > 0 &&
            checkout?.billing_provider === 'wayforpay' &&
            checkout?.wayforpay_available && (
              <div className="mb-10">
                <h2 className="text-xl font-semibold text-white mb-2">{t('credits.subscriptionsTitle')}</h2>
                <p className="text-gray-400 text-sm mb-4">{t('credits.subscriptionsSubtitle')}</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {subscriptionPlans.map((plan) => (
                    <div
                      key={plan.slug}
                      className="rounded-2xl border border-brand-500/30 bg-brand-950/20 p-6 flex flex-col"
                    >
                      <div className="text-lg font-bold text-white mb-1">{plan.name}</div>
                      <div className="text-3xl font-extrabold text-white mb-1">
                        ${Number(plan.price_usd).toFixed(2)}{' '}
                        <span className="text-sm font-normal text-gray-500">USD</span>
                        <span className="text-sm font-normal text-gray-500"> {t('credits.perMonth')}</span>
                      </div>
                      <div className="text-gray-400 text-sm mb-4">
                        {t('credits.monthlyCredits', { count: plan.monthly_credits })}
                      </div>
                      {plan.amount_uah && (
                        <div className="text-brand-300 text-sm mb-4">
                          {t('credits.chargeInUah', { amount: plan.amount_uah })}
                        </div>
                      )}
                      <button
                        type="button"
                        disabled={!checkout || payingSubSlug === plan.slug}
                        onClick={() => startWayForPaySubscription(plan.slug)}
                        className="mt-auto btn-primary py-3 disabled:opacity-40 disabled:cursor-not-allowed"
                      >
                        {payingSubSlug === plan.slug ? t('common.loading') : payLabel}
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}

          {packages.length > 0 && (
            <>
              <h2 className="text-xl font-semibold text-white mb-4">{t('credits.oneTimeTitle')}</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {packages.map((pkg) => (
            <div
              key={pkg.slug}
              className="rounded-2xl border border-white/10 bg-gray-900/60 p-6 flex flex-col"
            >
              <div className="text-lg font-bold text-white mb-1">{pkg.name}</div>
              <div className="text-3xl font-extrabold text-white mb-1">
                ${Number(pkg.price_usd).toFixed(2)}{' '}
                <span className="text-sm font-normal text-gray-500">USD</span>
              </div>
              <div className="text-gray-400 text-sm mb-4">
                {pkg.credits_amount} {t('pricing.packages.credits')}
              </div>
              {checkout?.billing_provider === 'wayforpay' && checkout?.wayforpay_available && pkg.amount_uah && (
                <div className="text-brand-300 text-sm mb-4">
                  {t('credits.chargeInUah', { amount: pkg.amount_uah })}
                </div>
              )}
              <button
                type="button"
                disabled={
                  !checkout ||
                  payingSlug === pkg.slug ||
                  (checkout.billing_provider === 'wayforpay' && !checkout.wayforpay_available) ||
                  (checkout.billing_provider === 'fastspring' && !checkout.fastspring_available)
                }
                onClick={() => payHandler(pkg.slug)}
                className="mt-auto btn-primary py-3 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                {payingSlug === pkg.slug ? t('common.loading') : payLabel}
              </button>
            </div>
          ))}
              </div>
            </>
          )}

          <div className="mt-12 border-t border-white/10 pt-10">
            <h2 className="text-xl font-semibold text-white mb-2 flex items-center gap-2">
              <History className="w-5 h-5 text-brand-400" />
              {t('credits.purchaseHistoryTitle')}
            </h2>
            <p className="text-gray-500 text-sm mb-2">{t('credits.purchaseHistorySubtitle')}</p>
            <p className="text-gray-500 text-sm mb-4 border-l-2 border-amber-500/50 pl-3 py-1">
              {t('credits.purchaseHistoryPendingExplain')}
            </p>
            {loading ? (
              <p className="text-gray-500 text-sm">{t('common.loading')}</p>
            ) : purchases.length === 0 ? (
              <p className="text-gray-500">{t('credits.purchaseHistoryEmpty')}</p>
            ) : (
              <div className="overflow-x-auto rounded-2xl border border-white/10 bg-gray-900/40">
                <table className="min-w-full text-left text-sm">
                  <thead>
                    <tr className="border-b border-white/10 text-gray-400">
                      <th className="px-4 py-3 font-medium">{t('credits.purchaseHistoryDate')}</th>
                      <th className="px-4 py-3 font-medium">{t('credits.purchaseHistoryItem')}</th>
                      <th className="px-4 py-3 font-medium text-right">{t('credits.purchaseHistoryCredits')}</th>
                      <th className="px-4 py-3 font-medium text-right">{t('credits.purchaseHistoryAmount')}</th>
                      <th className="px-4 py-3 font-medium">{t('credits.purchaseHistoryStatus')}</th>
                      <th className="px-4 py-3 font-medium hidden sm:table-cell">{t('credits.purchaseHistoryRef')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {purchases.map((row) => {
                      const date = row.created_at
                        ? new Date(row.created_at).toLocaleString(undefined, {
                            dateStyle: 'short',
                            timeStyle: 'short',
                          })
                        : '—'
                      const usd =
                        row.amount_usd_cents != null
                          ? `$${(Number(row.amount_usd_cents) / 100).toFixed(2)}`
                          : '—'
                      const kindKey = `credits.purchaseKind.${row.kind}`
                      const statusKey = `credits.purchaseStatus.${row.status}`
                      const kindLabel = t(kindKey, row.kind)
                      const statusLabel = t(statusKey, row.status)
                      const refShort =
                        typeof row.order_reference === 'string' && row.order_reference.length > 14
                          ? `${row.order_reference.slice(0, 10)}…`
                          : row.order_reference ?? '—'

                      return (
                        <tr key={`${row.source}-${row.id}`} className="border-b border-white/5 text-gray-200">
                          <td className="px-4 py-3 whitespace-nowrap text-gray-400">{date}</td>
                          <td className="px-4 py-3">
                            <div className="font-medium text-white">{row.title}</div>
                            <div className="text-xs text-gray-500 mt-0.5">{kindLabel}</div>
                          </td>
                          <td className="px-4 py-3 text-right tabular-nums">
                            {row.credits != null ? `+${row.credits}` : '—'}
                          </td>
                          <td className="px-4 py-3 text-right tabular-nums">
                            <div>{usd}</div>
                            {row.amount_uah != null && (
                              <div className="text-xs text-gray-500">{row.amount_uah} UAH</div>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            <span
                              className={
                                row.status === 'completed'
                                  ? 'text-emerald-400'
                                  : row.status === 'failed'
                                    ? 'text-red-400'
                                    : 'text-amber-300'
                              }
                            >
                              {statusLabel}
                            </span>
                            {row.awaiting_payment_callback && (
                              <div className="text-xs text-amber-200/80 mt-1 max-w-[14rem]">
                                {t('credits.purchaseHistoryAwaitingCallback')}
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-3 font-mono text-xs text-gray-500 hidden sm:table-cell">
                            {refShort}
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      )}

      {!loading && packages.length === 0 && subscriptionPlans.length === 0 && (
        <p className="text-gray-500">{t('credits.empty')}</p>
      )}
    </div>
  )
}
