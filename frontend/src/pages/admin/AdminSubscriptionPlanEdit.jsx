import { useEffect, useState } from 'react'
import { Link, useMatch, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { ArrowLeft } from 'lucide-react'
import { adminSubscriptionPlansApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

const emptyForm = {
  slug: '',
  name: '',
  description_en: '',
  description_uk: '',
  monthly_credits: 100,
  price_usd: '9.00',
  features_json: '[{"key":"creditsMonth","params":{"count":100}}]',
  is_active: true,
  is_featured: false,
  display_variant: 'starter',
  sort_order: 10,
}

export default function AdminSubscriptionPlanEdit() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id } = useParams()
  const isNew = Boolean(useMatch({ path: `${ADMIN_BASE}/subscription-plans/new`, end: true }))
  const [loading, setLoading] = useState(!isNew)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState(emptyForm)

  useEffect(() => {
    if (isNew) {
      setLoading(false)
      return
    }
    let cancelled = false
    ;(async () => {
      setLoading(true)
      try {
        const { data } = await adminSubscriptionPlansApi.get(id)
        const row = data.data
        if (cancelled) return
        const usd = row.price_cents != null ? (Number(row.price_cents) / 100).toFixed(2) : '0.00'
        setForm({
          slug: row.slug ?? '',
          name: row.name ?? '',
          description_en: row.description_en ?? '',
          description_uk: row.description_uk ?? '',
          monthly_credits: row.monthly_credits ?? 0,
          price_usd: usd,
          features_json: JSON.stringify(row.features ?? [], null, 2),
          is_active: Boolean(row.is_active),
          is_featured: Boolean(row.is_featured),
          display_variant: row.display_variant ?? 'starter',
          sort_order: row.sort_order ?? 0,
        })
      } catch {
        toast.error(t('common.error'))
        navigate(`${ADMIN_BASE}/subscription-plans`)
      } finally {
        if (!cancelled) setLoading(false)
      }
    })()
    return () => {
      cancelled = true
    }
  }, [id, isNew, navigate, t])

  const submit = async (e) => {
    e.preventDefault()
    let features
    try {
      features = JSON.parse(form.features_json || '[]')
      if (!Array.isArray(features)) throw new Error('not array')
    } catch {
      toast.error(t('admin.subscriptionPlans.invalidFeaturesJson'))
      return
    }
    let priceCents
    try {
      const n = parseFloat(String(form.price_usd).replace(',', '.'))
      if (!Number.isFinite(n) || n <= 0) throw new Error('bad')
      priceCents = Math.round(n * 100)
    } catch {
      toast.error(t('common.error'))
      return
    }

    const payload = {
      slug: form.slug.trim(),
      name: form.name.trim(),
      description_en: form.description_en.trim() || null,
      description_uk: form.description_uk.trim() || null,
      monthly_credits: Number(form.monthly_credits),
      price_cents: priceCents,
      currency: 'USD',
      features,
      is_active: form.is_active,
      is_featured: form.is_featured,
      display_variant: form.display_variant || null,
      sort_order: Number(form.sort_order) || 0,
    }

    setSaving(true)
    try {
      if (isNew) {
        await adminSubscriptionPlansApi.create(payload)
        toast.success(t('admin.subscriptionPlans.created'))
      } else {
        await adminSubscriptionPlansApi.update(id, payload)
        toast.success(t('admin.subscriptionPlans.saved'))
      }
      navigate(`${ADMIN_BASE}/subscription-plans`)
    } catch (e) {
      const msg = e.response?.data?.message
      toast.error(typeof msg === 'string' ? msg : t('common.error'))
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <Spinner />
      </div>
    )
  }

  return (
    <div>
      <Link
        to={`${ADMIN_BASE}/subscription-plans`}
        className="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-6"
      >
        <ArrowLeft className="w-4 h-4" />
        {t('admin.subscriptionPlans.backToList')}
      </Link>

      <h1 className="text-2xl font-bold text-white mb-6">
        {isNew ? t('admin.subscriptionPlans.formTitle') : t('admin.subscriptionPlans.editTitle')}
      </h1>

      <form onSubmit={submit} className="max-w-2xl space-y-4">
        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldSlug')} *</label>
          <input
            className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
            value={form.slug}
            onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
            disabled={!isNew}
            required
          />
        </div>
        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldName')} *</label>
          <input
            className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
            value={form.name}
            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            required
          />
        </div>
        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldDescriptionEn')}</label>
          <textarea
            className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white min-h-[72px]"
            value={form.description_en}
            onChange={(e) => setForm((f) => ({ ...f, description_en: e.target.value }))}
          />
        </div>
        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldDescriptionUk')}</label>
          <textarea
            className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white min-h-[72px]"
            value={form.description_uk}
            onChange={(e) => setForm((f) => ({ ...f, description_uk: e.target.value }))}
          />
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldMonthlyCredits')} *</label>
            <input
              type="number"
              min={1}
              className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
              value={form.monthly_credits}
              onChange={(e) => setForm((f) => ({ ...f, monthly_credits: e.target.value }))}
              required
            />
          </div>
          <div>
            <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldPriceUsd')} *</label>
            <input
              className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
              value={form.price_usd}
              onChange={(e) => setForm((f) => ({ ...f, price_usd: e.target.value }))}
              required
            />
          </div>
        </div>
        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldFeaturesJson')}</label>
          <p className="text-xs text-gray-600 mb-2">{t('admin.subscriptionPlans.fieldFeaturesJsonHelp')}</p>
          <textarea
            className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white font-mono text-xs min-h-[160px]"
            value={form.features_json}
            onChange={(e) => setForm((f) => ({ ...f, features_json: e.target.value }))}
          />
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldVariant')}</label>
            <select
              className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
              value={form.display_variant}
              onChange={(e) => setForm((f) => ({ ...f, display_variant: e.target.value }))}
            >
              <option value="starter">{t('admin.subscriptionPlans.variantStarter')}</option>
              <option value="pro">{t('admin.subscriptionPlans.variantPro')}</option>
              <option value="business">{t('admin.subscriptionPlans.variantBusiness')}</option>
            </select>
          </div>
          <div>
            <label className="text-xs text-gray-500 block mb-1">{t('admin.subscriptionPlans.fieldSort')}</label>
            <input
              type="number"
              min={0}
              className="w-full rounded-xl bg-gray-900 border border-white/10 px-3 py-2 text-white"
              value={form.sort_order}
              onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))}
            />
          </div>
        </div>
        <div className="flex flex-wrap gap-6">
          <label className="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
            />
            {t('admin.subscriptionPlans.fieldActive')}
          </label>
          <label className="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_featured}
              onChange={(e) => setForm((f) => ({ ...f, is_featured: e.target.checked }))}
            />
            {t('admin.subscriptionPlans.fieldFeatured')}
          </label>
        </div>
        <button type="submit" disabled={saving} className="btn-primary px-6 py-2.5 disabled:opacity-50">
          {saving ? t('common.loading') : isNew ? t('admin.subscriptionPlans.create') : t('admin.subscriptionPlans.save')}
        </button>
      </form>
    </div>
  )
}
