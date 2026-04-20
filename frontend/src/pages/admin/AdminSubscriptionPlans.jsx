import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { Plus, Pencil } from 'lucide-react'
import { adminSubscriptionPlansApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

export default function AdminSubscriptionPlans() {
  const { t } = useTranslation()
  const [rows, setRows] = useState([])
  const [loading, setLoading] = useState(true)

  const load = () => {
    setLoading(true)
    adminSubscriptionPlansApi
      .list()
      .then((res) => setRows(res.data?.data ?? []))
      .catch(() => toast.error(t('common.error')))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    load()
    // eslint-disable-next-line react-hooks/exhaustive-deps -- load once on mount
  }, [])

  const onDelete = async (row) => {
    if (!window.confirm(t('admin.subscriptionPlans.confirmDelete', { name: row.name }))) return
    try {
      await adminSubscriptionPlansApi.remove(row.id)
      toast.success(t('admin.subscriptionPlans.deleted'))
      load()
    } catch (e) {
      const msg = e.response?.data?.message
      toast.error(typeof msg === 'string' ? msg : t('common.error'))
    }
  }

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1">{t('admin.subscriptionPlans.title')}</h1>
          <p className="text-gray-400 text-sm">{t('admin.subscriptionPlans.subtitle')}</p>
        </div>
        <Link
          to={`${ADMIN_BASE}/subscription-plans/new`}
          className="inline-flex items-center justify-center gap-2 btn-primary px-4 py-2.5 text-sm"
        >
          <Plus className="w-4 h-4" />
          {t('admin.subscriptionPlans.new')}
        </Link>
      </div>

      {loading ? (
        <div className="flex justify-center py-20">
          <Spinner />
        </div>
      ) : rows.length === 0 ? (
        <div className="card text-center py-20 text-gray-500">{t('admin.subscriptionPlans.empty')}</div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-white/10 bg-gray-900/40">
          <table className="min-w-full text-left text-sm text-gray-200">
            <thead>
              <tr className="border-b border-white/10 text-gray-400">
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colSlug')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colName')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colCredits')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colPrice')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colFeatured')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colActive')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colSort')}</th>
                <th className="px-4 py-3 font-medium">{t('admin.subscriptionPlans.colActions')}</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-b border-white/5">
                  <td className="px-4 py-3 font-mono text-xs text-gray-400">{row.slug}</td>
                  <td className="px-4 py-3 font-medium text-white">{row.name}</td>
                  <td className="px-4 py-3 tabular-nums">{row.monthly_credits}</td>
                  <td className="px-4 py-3 tabular-nums">${Number(row.price_usd).toFixed(2)}</td>
                  <td className="px-4 py-3">{row.is_featured ? '✓' : '—'}</td>
                  <td className="px-4 py-3">
                    {row.is_active ? t('admin.templates.statusActive') : t('admin.templates.statusInactive')}
                  </td>
                  <td className="px-4 py-3 tabular-nums">{row.sort_order}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <Link
                        to={`${ADMIN_BASE}/subscription-plans/${row.id}/edit`}
                        className="inline-flex items-center gap-1 text-brand-400 hover:text-brand-300 text-xs"
                      >
                        <Pencil className="w-3 h-3" />
                        {t('admin.subscriptionPlans.edit')}
                      </Link>
                      <button
                        type="button"
                        onClick={() => onDelete(row)}
                        className="text-red-400/90 hover:text-red-300 text-xs"
                      >
                        {t('common.delete')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
