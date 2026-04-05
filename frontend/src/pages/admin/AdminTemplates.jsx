import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { adminTemplatesApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

export default function AdminTemplates() {
  const { t } = useTranslation()
  const [rows, setRows] = useState([])
  const [meta, setMeta] = useState(null)
  const [loading, setLoading] = useState(true)
  const [busyId, setBusyId] = useState(null)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await adminTemplatesApi.list(page)
      const payload = data.data
      setRows(payload.data ?? [])
      setMeta(payload.meta ?? null)
    } catch {
      toast.error(t('common.error'))
    } finally {
      setLoading(false)
    }
  }, [t])

  useEffect(() => {
    load(1)
  }, [load])

  const handleDelete = async (row) => {
    if (!window.confirm(t('admin.templates.confirmDelete', { name: row.name }))) return
    setBusyId(row.id)
    try {
      await adminTemplatesApi.remove(row.id)
      toast.success(t('admin.templates.deleted'))
      await load(meta?.current_page ?? 1)
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setBusyId(null)
    }
  }

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1">{t('admin.templates.title')}</h1>
          <p className="text-gray-400 text-sm">{t('admin.templates.subtitle')}</p>
        </div>
        <Link to={`${ADMIN_BASE}/templates/new`} className="btn-primary inline-flex items-center gap-2 justify-center">
          <Plus className="w-4 h-4" />
          {t('admin.templates.new')}
        </Link>
      </div>

      {loading ? (
        <div className="flex justify-center py-24">
          <Spinner size="lg" />
        </div>
      ) : rows.length === 0 ? (
        <div className="card text-center py-20 text-gray-500">{t('admin.templates.empty')}</div>
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {rows.map((row) => (
              <article
                key={row.id}
                className="rounded-2xl border border-white/10 bg-gray-900/50 overflow-hidden flex flex-col"
              >
                <div className="aspect-[4/3] bg-gray-800 relative">
                  {row.preview_url ? (
                    <img src={row.preview_url} alt="" className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-600 text-sm">
                      {t('admin.templates.noPreview')}
                    </div>
                  )}
                </div>
                <div className="p-4 flex-1 flex flex-col gap-2">
                  <h2 className="text-lg font-semibold text-white leading-tight">{row.name}</h2>
                  <p className="text-xs text-gray-500 font-mono truncate" title={row.slug}>
                    {t('admin.templates.slugLabel')}: {row.slug}
                  </p>
                  <div className="flex flex-wrap gap-2 text-xs">
                    <span className="px-2 py-0.5 rounded-md bg-white/5 text-gray-300">
                      {t('admin.templates.colCategory')}: {row.category ?? '—'}
                    </span>
                    <span
                      className={`px-2 py-0.5 rounded-md ${
                        row.is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-gray-500/15 text-gray-400'
                      }`}
                    >
                      {row.is_active ? t('admin.templates.statusActive') : t('admin.templates.statusInactive')}
                    </span>
                    <span className="px-2 py-0.5 rounded-md bg-white/5 text-gray-400">
                      {t('admin.templates.colSort')}: {row.sort_order}
                    </span>
                    <span className="px-2 py-0.5 rounded-md bg-white/5 text-gray-500">ID: {row.id}</span>
                  </div>
                  <p className="text-xs text-gray-500 mt-1">
                    {row.updated_at
                      ? `${t('admin.templates.updated')}: ${new Date(row.updated_at).toLocaleString()}`
                      : null}
                  </p>
                  <div className="flex gap-2 mt-auto pt-3">
                    <Link
                      to={`${ADMIN_BASE}/templates/${row.id}/edit`}
                      className="btn-primary flex-1 flex items-center justify-center gap-2 py-2.5 text-sm"
                    >
                      <Pencil className="w-4 h-4" />
                      {t('admin.templates.edit')}
                    </Link>
                    <button
                      type="button"
                      disabled={busyId === row.id}
                      onClick={() => handleDelete(row)}
                      className="btn-secondary px-3 py-2.5 text-red-400 border-red-500/30 hover:bg-red-500/10"
                      title={t('common.delete')}
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </article>
            ))}
          </div>

          {meta && meta.last_page > 1 && (
            <div className="flex justify-center items-center gap-6 mt-10 text-sm text-gray-500">
              <button
                type="button"
                disabled={meta.current_page <= 1}
                onClick={() => load(meta.current_page - 1)}
                className="text-brand-400 disabled:opacity-30 px-3 py-1"
              >
                ←
              </button>
              <span>
                {meta.current_page} / {meta.last_page}
              </span>
              <button
                type="button"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => load(meta.current_page + 1)}
                className="text-brand-400 disabled:opacity-30 px-3 py-1"
              >
                →
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
