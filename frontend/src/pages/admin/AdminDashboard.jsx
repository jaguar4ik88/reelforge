import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../../context/AuthContext'
import { adminStatsApi } from '../../services/api'
import { isAdminRole } from '../../constants/routes'
import Spinner from '../../components/ui/Spinner'

export default function AdminDashboard() {
  const { t } = useTranslation()
  const { user } = useAuthContext()
  const [stats, setStats] = useState(null)
  const [statsLoading, setStatsLoading] = useState(false)

  useEffect(() => {
    if (!isAdminRole(user?.role)) return
    let cancelled = false
    setStatsLoading(true)
    adminStatsApi
      .overview()
      .then(({ data: body }) => {
        if (!cancelled) setStats(body.data ?? null)
      })
      .catch(() => {
        if (!cancelled) setStats(null)
      })
      .finally(() => {
        if (!cancelled) setStatsLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [user?.role])

  return (
    <div>
      <h1 className="text-3xl font-bold text-white mb-2">{t('admin.dashboard.title')}</h1>
      <p className="text-gray-400 mb-8">{t('admin.dashboard.subtitle')}</p>

      <div className="card max-w-lg mb-8">
        <p className="text-sm text-gray-500 mb-1">{t('admin.dashboard.signedInAs')}</p>
        <p className="text-white font-medium">{user?.name}</p>
        <p className="text-xs text-gray-500 mt-2 capitalize">
          {t('admin.dashboard.role')}: {user?.role ?? '—'}
        </p>
      </div>

      {isAdminRole(user?.role) && (
        <div className="mb-4">
          <h2 className="text-xl font-semibold text-white mb-4">{t('admin.stats.sectionTitle')}</h2>
          {statsLoading ? (
            <div className="flex justify-center py-12">
              <Spinner size="lg" />
            </div>
          ) : stats ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5">
                <p className="text-sm text-gray-500 mb-1">{t('admin.stats.usersCount')}</p>
                <p className="text-2xl font-bold text-white tabular-nums">{stats.users_count}</p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5">
                <p className="text-sm text-gray-500 mb-1">{t('admin.stats.totalCreditsBalance')}</p>
                <p className="text-2xl font-bold text-amber-200/95 tabular-nums">
                  {stats.total_credits_balance}
                </p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5">
                <p className="text-sm text-gray-500 mb-1">{t('admin.stats.projectsCount')}</p>
                <p className="text-2xl font-bold text-white tabular-nums">{stats.projects_count}</p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5">
                <p className="text-sm text-gray-500 mb-1">{t('admin.stats.paymentOrdersCount')}</p>
                <p className="text-2xl font-bold text-white tabular-nums">{stats.payment_orders_count}</p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5">
                <p className="text-sm text-gray-500 mb-1">{t('admin.stats.paymentOrdersCompleted')}</p>
                <p className="text-2xl font-bold text-emerald-300/90 tabular-nums">
                  {stats.payment_orders_completed}
                </p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-gray-900/50 p-5 sm:col-span-2 lg:col-span-1">
                <p className="text-sm text-gray-500 mb-2">{t('admin.stats.usersByRole')}</p>
                <ul className="space-y-1 text-sm text-gray-300">
                  {Object.entries(stats.users_by_role ?? {}).map(([role, count]) => (
                    <li key={role} className="flex justify-between gap-4">
                      <span className="capitalize">{role}</span>
                      <span className="tabular-nums text-white">{count}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          ) : (
            <p className="text-gray-500 text-sm">{t('admin.stats.loadError')}</p>
          )}
        </div>
      )}
    </div>
  )
}
