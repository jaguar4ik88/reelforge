import { useCallback, useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { ImagePlus, Sparkles, Trash2 } from 'lucide-react'
import { adminInfographicCanvasTemplatesApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { resolveBackendAssetUrlForCanvas } from '../../utils/apiBase'

export default function AdminInfographicCanvasTemplates() {
  const { t } = useTranslation()
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [uploading, setUploading] = useState(false)
  const [busyName, setBusyName] = useState(null)
  /** filename currently running AI layout generation */
  const [generatingName, setGeneratingName] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data } = await adminInfographicCanvasTemplatesApi.list()
      setItems(Array.isArray(data.data) ? data.data : [])
    } catch {
      toast.error(t('common.error'))
      setItems([])
    } finally {
      setLoading(false)
    }
  }, [t])

  useEffect(() => {
    load()
  }, [load])

  const onUpload = async (e) => {
    const file = e.target.files?.[0]
    e.target.value = ''
    if (!file) return
    setUploading(true)
    try {
      await adminInfographicCanvasTemplatesApi.upload(file)
      toast.success(t('admin.infographicCanvas.uploaded'))
      await load()
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setUploading(false)
    }
  }

  const handleGenerateLayers = async (row) => {
    const name = row.filename
    const hasLayers = row.editor?.texts?.length > 0
    if (hasLayers) {
      const ok = window.confirm(t('admin.infographicCanvas.confirmRegenerateLayers'))
      if (!ok) return
    }
    setGeneratingName(name)
    try {
      const { data } = await adminInfographicCanvasTemplatesApi.generateLayout(name, { force: true })
      const count = data.data?.texts_count ?? 0
      toast.success(t('admin.infographicCanvas.layersGenerated', { count }))
      await load()
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setGeneratingName(null)
    }
  }

  const handleDelete = async (row) => {
    const name = row.filename
    if (!window.confirm(t('admin.infographicCanvas.confirmDelete', { name }))) return
    setBusyName(name)
    try {
      await adminInfographicCanvasTemplatesApi.remove(name)
      toast.success(t('admin.infographicCanvas.deleted'))
      await load()
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setBusyName(null)
    }
  }

  const displayUrl = (url) => resolveBackendAssetUrlForCanvas(url) || url

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1">{t('admin.infographicCanvas.title')}</h1>
          <p className="text-gray-400 text-sm max-w-xl">{t('admin.infographicCanvas.subtitle')}</p>
        </div>
        <label className="btn-primary inline-flex items-center gap-2 justify-center cursor-pointer shrink-0">
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            className="hidden"
            onChange={onUpload}
            disabled={uploading}
          />
          <ImagePlus className="w-4 h-4" />
          {uploading ? t('admin.infographicCanvas.uploading') : t('admin.infographicCanvas.addPhoto')}
        </label>
      </div>

      {loading ? (
        <div className="flex justify-center py-24">
          <Spinner size="lg" />
        </div>
      ) : items.length === 0 ? (
        <div className="card text-center py-20 text-gray-500">{t('admin.infographicCanvas.empty')}</div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {items.map((row) => {
            const name = row.filename
            const isBusy = busyName === name || generatingName === name
            return (
              <article
                key={name}
                className="rounded-2xl border border-white/10 bg-gray-900/50 overflow-hidden flex flex-col"
              >
                <div className="aspect-[3/4] bg-gray-800 relative">
                  {row.url ? (
                    <img src={displayUrl(row.url)} alt="" className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-600 text-sm px-4 text-center">
                      {name}
                    </div>
                  )}
                </div>

                <div className="p-4 flex-1 flex flex-col gap-2">
                  <p className="text-sm font-mono text-gray-300 truncate" title={name}>
                    {name}
                  </p>
                  {row.editor?.texts?.length > 0 && (
                    <p className="text-xs text-emerald-400/90">{t('admin.infographicCanvas.hasEditorLayers')}</p>
                  )}
                  <button
                    type="button"
                    onClick={() => handleGenerateLayers(row)}
                    disabled={isBusy}
                    className="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-500/35 bg-amber-950/25 px-3 py-2 text-sm text-amber-100 hover:bg-amber-950/45 disabled:opacity-45"
                  >
                    {generatingName === name ? (
                      <Spinner size="sm" />
                    ) : (
                      <Sparkles className="w-4 h-4 shrink-0" />
                    )}
                    {generatingName === name ? t('admin.infographicCanvas.generatingLayers') : t('admin.infographicCanvas.generateLayers')}
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDelete(row)}
                    disabled={isBusy}
                    className="mt-auto inline-flex items-center justify-center gap-2 rounded-xl border border-red-500/40 bg-red-950/30 px-3 py-2 text-sm text-red-200 hover:bg-red-950/50 disabled:opacity-45"
                  >
                    <Trash2 className="w-4 h-4" />
                    {busyName === name ? '…' : t('admin.infographicCanvas.delete')}
                  </button>
                </div>
              </article>
            )
          })}
        </div>
      )}

      <p className="mt-8 text-xs text-gray-500 leading-relaxed max-w-2xl">{t('admin.infographicCanvas.hint')}</p>
    </div>
  )
}
