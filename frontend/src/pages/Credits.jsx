import { useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Coins, ArrowLeft } from 'lucide-react'
import toast from 'react-hot-toast'
import SeoHead from '../components/seo/SeoHead'
import api, { paymentsApi } from '../services/api'
import { useSite } from '../context/SiteContext'
import { useAuthContext } from '../context/AuthContext'
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
  const [loading, setLoading] = useState(true)
  const [payingSlug, setPayingSlug] = useState(null)
  const [checkout, setCheckout] = useState(null)
  const formRef = useRef(null)

  useEffect(() => {
    let cancelled = false
    Promise.all([api.get('/credits/packages'), paymentsApi.checkoutContext()])
      .then(([pkgRes, ctxRes]) => {
        if (cancelled) return
        setPackages(pkgRes.data?.data ?? [])
        setCheckout(ctxRes.data?.data ?? null)
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
    const p = searchParams.get('payment')
    if (p === 'return') {
      toast.success(t('credits.wayforpayReturn'))
      refreshUser?.()
      setSearchParams({}, { replace: true })
    } else if (p === 'fastspring_return') {
      toast.success(t('credits.fastspringReturn'))
      refreshUser?.()
      setSearchParams({}, { replace: true })
    }
  }, [searchParams, setSearchParams, refreshUser, t])

  const startWayForPay = async (slug) => {
    if (!checkout?.wayforpay_available || checkout?.billing_provider !== 'wayforpay') {
      toast.error(t('credits.wayforpayDisabled'))
      return
    }
    setPayingSlug(slug)
    try {
      const res = await paymentsApi.wayforpayInvoice(slug)
      const { pay_url: payUrl, fields } = res.data?.data ?? {}
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

  const startFastSpring = async (slug) => {
    if (!checkout?.fastspring_available || checkout?.billing_provider !== 'fastspring') {
      toast.error(t('credits.fastspringDisabled'))
      return
    }
    setPayingSlug(slug)
    const win = window.open('', '_blank', 'noopener,noreferrer')
    if (!win) {
      toast.error(t('credits.fastspringPopupBlocked'))
      setPayingSlug(null)
      return
    }
    try {
      const res = await paymentsApi.fastspringSession(slug)
      const url = res.data?.data?.checkout_url
      if (typeof url === 'string' && url) {
        win.location.href = url
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
      )}

      {!loading && packages.length === 0 && (
        <p className="text-gray-500">{t('credits.empty')}</p>
      )}
    </div>
  )
}
