import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { Search, Users } from 'lucide-react'
import { adminUsersApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

export default function AdminUsers() {
  const { t } = useTranslation()
  const [rows, setRows] = useState([])
  const [meta, setMeta] = useState(null)
  const [loading, setLoading] = useState(true)
  const [q, setQ] = useState('')
  const [debouncedQ, setDebouncedQ] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebouncedQ(q.trim()), 350)
    return () => clearTimeout(id)
  }, [q])

  const load = useCallback(
    async (page = 1) => {
      setLoading(true)
      try {
        const { data: body } = await adminUsersApi.list({
          page,
          per_page: 20,
          q: debouncedQ || undefined,
        })
        setRows(body.data ?? [])
        setMeta(body.meta ?? null)
      } catch {
        toast.error(t('common.error'))
      } finally {
        setLoading(false)
      }
    },
    [debouncedQ, t]
  )

  useEffect(() => {
    load(1)
  }, [load])

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1 flex items-center gap-2">
            <Users className="w-8 h-8 text-amber-400" />
            {t('admin.users.title')}
          </h1>
          <p className="text-gray-400 text-sm">{t('admin.users.subtitle')}</p>
        </div>
      </div>

      <div className="mb-6">
        <div className="relative max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
          <input
            type="search"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder={t('admin.users.searchPlaceholder')}
            className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-white/10 bg-gray-900/60 text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
          />
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-24">
          <Spinner size="lg" />
        </div>
      ) : rows.length === 0 ? (
        <div className="card text-center py-20 text-gray-500">{t('admin.users.empty')}</div>
      ) : (
        <>
          <div className="overflow-x-auto rounded-2xl border border-white/10 bg-gray-900/40">
            <table className="min-w-full text-left text-sm">
              <thead>
                <tr className="border-b border-white/10 text-gray-400">
                  <th className="px-4 py-3 font-medium">{t('admin.users.colId')}</th>
                  <th className="px-4 py-3 font-medium">{t('admin.users.colName')}</th>
                  <th className="px-4 py-3 font-medium">{t('admin.users.colEmail')}</th>
                  <th className="px-4 py-3 font-medium">{t('admin.users.colRole')}</th>
                  <th className="px-4 py-3 font-medium text-right">{t('admin.users.colCredits')}</th>
                  <th className="px-4 py-3 font-medium">{t('admin.users.colActions')}</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.id} className="border-b border-white/5 text-gray-200">
                    <td className="px-4 py-3 tabular-nums text-gray-400">{row.id}</td>
                    <td className="px-4 py-3 font-medium text-white">{row.name}</td>
                    <td className="px-4 py-3 text-gray-300">{row.email}</td>
                    <td className="px-4 py-3 capitalize text-gray-400">{row.role}</td>
                    <td className="px-4 py-3 text-right tabular-nums text-amber-200/90">
                      {row.credits_balance ?? 0}
                    </td>
                    <td className="px-4 py-3">
                      <Link
                        to={`${ADMIN_BASE}/users/${row.id}`}
                        className="text-amber-400 hover:text-amber-300 text-sm font-medium"
                      >
                        {t('admin.users.open')}
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {meta && meta.last_page > 1 && (
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 text-sm text-gray-400">
              <p>
                {t('credits.purchaseHistoryPage', {
                  current: meta.current_page,
                  last: meta.last_page,
                  total: meta.total,
                })}
              </p>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  disabled={meta.current_page <= 1 || loading}
                  onClick={() => load(meta.current_page - 1)}
                  className="px-3 py-1.5 rounded-lg border border-white/15 bg-gray-900/60 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-800/80"
                >
                  {t('credits.purchaseHistoryPrev')}
                </button>
                <button
                  type="button"
                  disabled={meta.current_page >= meta.last_page || loading}
                  onClick={() => load(meta.current_page + 1)}
                  className="px-3 py-1.5 rounded-lg border border-white/15 bg-gray-900/60 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-800/80"
                >
                  {t('credits.purchaseHistoryNext')}
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  )
}
