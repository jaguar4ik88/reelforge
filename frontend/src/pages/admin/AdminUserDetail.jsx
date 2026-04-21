import { useCallback, useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { ArrowLeft, History, Trash2 } from 'lucide-react'
import { adminUsersApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

export default function AdminUserDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { t } = useTranslation()
  const [user, setUser] = useState(null)
  const [balanceInput, setBalanceInput] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [purchases, setPurchases] = useState([])
  const [purchaseMeta, setPurchaseMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  })
  const [historyLoading, setHistoryLoading] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const loadUser = useCallback(async () => {
    setLoading(true)
    try {
      const { data: body } = await adminUsersApi.get(id)
      const u = body.data
      setUser(u)
      setBalanceInput(String(u.credits_balance ?? 0))
    } catch {
      toast.error(t('common.error'))
      setUser(null)
    } finally {
      setLoading(false)
    }
  }, [id, t])

  const loadPurchasesPage = useCallback(
    async (page) => {
      setHistoryLoading(true)
      try {
        const { data: body } = await adminUsersApi.purchases(id, { page, per_page: 10 })
        setPurchases(body.data ?? [])
        if (body.meta) setPurchaseMeta(body.meta)
      } catch {
        toast.error(t('common.error'))
      } finally {
        setHistoryLoading(false)
      }
    },
    [id, t]
  )

  useEffect(() => {
    loadUser()
  }, [loadUser])

  useEffect(() => {
    if (!user?.id) return
    loadPurchasesPage(1)
  }, [user?.id, loadPurchasesPage])

  const handleSaveCredits = async (e) => {
    e.preventDefault()
    const n = parseInt(balanceInput, 10)
    if (Number.isNaN(n) || n < 0) {
      toast.error(t('admin.users.invalidBalance'))
      return
    }
    setSaving(true)
    try {
      const { data: body } = await adminUsersApi.updateCredits(id, { balance: n })
      if (body.data?.balance != null) {
        setUser((prev) => (prev ? { ...prev, credits_balance: body.data.balance } : prev))
        setBalanceInput(String(body.data.balance))
      }
      toast.success(t('admin.users.creditsSaved'))
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setSaving(false)
    }
  }

  const canDeleteTarget = user && (user.role ?? 'client') !== 'admin'

  const handleDeleteUser = async () => {
    if (!user || !canDeleteTarget) return
    if (
      !window.confirm(
        t('admin.users.deleteConfirm', { email: user.email, name: user.name })
      )
    ) {
      return
    }
    setDeleting(true)
    try {
      await adminUsersApi.remove(id)
      toast.success(t('admin.users.userDeleted'))
      navigate(`${ADMIN_BASE}/users`)
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setDeleting(false)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center py-24">
        <Spinner size="lg" />
      </div>
    )
  }

  if (!user) {
    return (
      <div>
        <Link to={`${ADMIN_BASE}/users`} className="text-amber-400 hover:text-amber-300 text-sm inline-flex items-center gap-1 mb-6">
          <ArrowLeft className="w-4 h-4" />
          {t('admin.users.backToList')}
        </Link>
        <p className="text-gray-500">{t('admin.users.notFound')}</p>
      </div>
    )
  }

  return (
    <div>
      <Link to={`${ADMIN_BASE}/users`} className="text-amber-400 hover:text-amber-300 text-sm inline-flex items-center gap-1 mb-6">
        <ArrowLeft className="w-4 h-4" />
        {t('admin.users.backToList')}
      </Link>

      <h1 className="text-3xl font-bold text-white mb-1">{user.name}</h1>
      <p className="text-gray-400 text-sm mb-8">{user.email}</p>

      <div className="grid gap-6 md:grid-cols-2 mb-10">
        <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">{t('admin.users.profileSection')}</h2>
          <dl className="space-y-2 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">{t('admin.users.colRole')}</dt>
              <dd className="text-white capitalize">{user.role}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">{t('admin.users.fieldPlan')}</dt>
              <dd className="text-white capitalize">{user.plan}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">{t('admin.users.fieldLocale')}</dt>
              <dd className="text-white">{user.locale ?? '—'}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">{t('admin.users.fieldRegistered')}</dt>
              <dd className="text-gray-300">
                {user.created_at ? new Date(user.created_at).toLocaleString() : '—'}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">{t('admin.users.creditsSection')}</h2>
          <form onSubmit={handleSaveCredits} className="space-y-4">
            <div>
              <label htmlFor="admin-balance" className="block text-sm text-gray-400 mb-1">
                {t('admin.users.balanceLabel')}
              </label>
              <input
                id="admin-balance"
                type="number"
                min={0}
                value={balanceInput}
                onChange={(e) => setBalanceInput(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-gray-950 text-white focus:outline-none focus:ring-2 focus:ring-amber-500/40"
              />
              <p className="text-xs text-gray-500 mt-2">{t('admin.users.balanceHint')}</p>
            </div>
            <button type="submit" disabled={saving} className="btn-primary w-full sm:w-auto">
              {saving ? t('common.loading') : t('admin.users.saveCredits')}
            </button>
          </form>
        </div>
      </div>

      {canDeleteTarget && (
        <div className="mb-10 rounded-2xl border border-red-500/30 bg-red-950/20 p-6">
          <h2 className="text-lg font-semibold text-red-200 mb-2">{t('admin.users.dangerZone')}</h2>
          <p className="text-sm text-red-100/80 mb-4">{t('admin.users.deleteWarning')}</p>
          <button
            type="button"
            onClick={handleDeleteUser}
            disabled={deleting}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-red-500/50 bg-red-900/40 text-red-100 hover:bg-red-900/60 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {!deleting && <Trash2 className="w-4 h-4 shrink-0" aria-hidden />}
            {deleting && <Spinner size="sm" />}
            <span>{deleting ? t('common.loading') : t('admin.users.deleteUser')}</span>
          </button>
        </div>
      )}

      {user && !canDeleteTarget && (
        <p className="text-sm text-gray-500 mb-10">{t('admin.users.deleteAdminForbidden')}</p>
      )}

      <div className="border-t border-white/10 pt-10">
        <h2 className="text-xl font-semibold text-white mb-2 flex items-center gap-2">
          <History className="w-5 h-5 text-brand-400" />
          {t('admin.users.purchaseHistoryTitle')}
        </h2>
        <p className="text-gray-500 text-sm mb-4">{t('admin.users.purchaseHistorySubtitle')}</p>
        {historyLoading ? (
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
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
        {!historyLoading && purchaseMeta.total > 0 && purchaseMeta.last_page > 1 && (
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 text-sm text-gray-400">
            <p>
              {t('credits.purchaseHistoryPage', {
                current: purchaseMeta.current_page,
                last: purchaseMeta.last_page,
                total: purchaseMeta.total,
              })}
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                disabled={purchaseMeta.current_page <= 1 || historyLoading}
                onClick={() => loadPurchasesPage(purchaseMeta.current_page - 1)}
                className="px-3 py-1.5 rounded-lg border border-white/15 bg-gray-900/60 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-800/80"
              >
                {t('credits.purchaseHistoryPrev')}
              </button>
              <button
                type="button"
                disabled={purchaseMeta.current_page >= purchaseMeta.last_page || historyLoading}
                onClick={() => loadPurchasesPage(purchaseMeta.current_page + 1)}
                className="px-3 py-1.5 rounded-lg border border-white/15 bg-gray-900/60 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-800/80"
              >
                {t('credits.purchaseHistoryNext')}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
