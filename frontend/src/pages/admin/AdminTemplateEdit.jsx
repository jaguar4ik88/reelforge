import { useEffect, useState } from 'react'
import { Link, useMatch, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { ArrowLeft, ImagePlus } from 'lucide-react'
import { adminTemplatesApi } from '../../services/api'
import Spinner from '../../components/ui/Spinner'
import { ADMIN_BASE } from '../../constants/routes'

const emptyForm = {
  name: '',
  category: '',
  is_active: true,
  sort_order: 0,
  generation_prompt: '',
  negative_prompt: '',
  config_json: '{}',
}

export default function AdminTemplateEdit() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id } = useParams()
  const isNew = Boolean(useMatch({ path: `${ADMIN_BASE}/templates/new`, end: true }))

  const [loading, setLoading] = useState(!isNew)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState(emptyForm)
  const [previewFile, setPreviewFile] = useState(null)
  const [existingPreviewUrl, setExistingPreviewUrl] = useState(null)

  useEffect(() => {
    if (isNew) {
      setLoading(false)
      return
    }
    let cancelled = false
    ;(async () => {
      setLoading(true)
      try {
        const { data } = await adminTemplatesApi.get(id)
        const row = data.data
        if (cancelled) return
        setForm({
          name: row.name ?? '',
          category: row.category ?? '',
          is_active: Boolean(row.is_active),
          sort_order: row.sort_order ?? 0,
          generation_prompt: row.generation_prompt ?? '',
          negative_prompt: row.negative_prompt ?? '',
          config_json: JSON.stringify(row.config ?? {}, null, 2),
        })
        setExistingPreviewUrl(row.preview_url ?? null)
      } catch {
        toast.error(t('common.error'))
        navigate(`${ADMIN_BASE}/templates`)
      } finally {
        if (!cancelled) setLoading(false)
      }
    })()
    return () => {
      cancelled = true
    }
  }, [id, isNew, navigate, t])

  const buildFormData = () => {
    const fd = new FormData()
    fd.append('name', form.name.trim())
    if (form.category.trim()) fd.append('category', form.category.trim())
    fd.append('is_active', form.is_active ? '1' : '0')
    fd.append('sort_order', String(Number(form.sort_order) || 0))
    if (form.generation_prompt.trim()) fd.append('generation_prompt', form.generation_prompt)
    if (form.negative_prompt.trim()) fd.append('negative_prompt', form.negative_prompt)
    try {
      const parsed = JSON.parse(form.config_json || '{}')
      fd.append('config_json', JSON.stringify(parsed))
    } catch {
      throw new Error('config_json')
    }
    if (previewFile) fd.append('preview', previewFile)
    return fd
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    try {
      const fd = buildFormData()
      if (isNew) {
        await adminTemplatesApi.create(fd)
        toast.success(t('admin.templates.created'))
      } else {
        await adminTemplatesApi.update(id, fd)
        toast.success(t('admin.templates.saved'))
      }
      navigate(`${ADMIN_BASE}/templates`)
    } catch (err) {
      if (err.message === 'config_json') {
        toast.error(t('admin.templates.invalidJson'))
      } else {
        toast.error(err.response?.data?.message ?? t('common.error'))
      }
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center py-24">
        <Spinner size="lg" />
      </div>
    )
  }

  return (
    <div className="max-w-2xl">
      <div className="mb-6">
        <Link
          to={`${ADMIN_BASE}/templates`}
          className="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          {t('admin.templates.backToList')}
        </Link>
      </div>

      <h1 className="text-3xl font-bold text-white mb-6">
        {isNew ? t('admin.templates.formTitle') : t('admin.templates.editTitle')}
      </h1>

      <form onSubmit={handleSubmit} className="card space-y-5">
        <p className="text-sm text-gray-500">{t('admin.templates.slugAutoHint')}</p>

        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.templates.fieldName')} *</label>
          <input
            className="input-field w-full"
            value={form.name}
            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            required
            maxLength={255}
          />
        </div>

        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.templates.fieldCategory')}</label>
          <input
            className="input-field w-full"
            value={form.category}
            onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
          />
        </div>

        <div className="flex flex-wrap gap-4 items-center">
          <label className="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
              className="rounded border-white/20"
            />
            {t('admin.templates.fieldActive')}
          </label>
          <div className="flex items-center gap-2">
            <label className="text-xs text-gray-500">{t('admin.templates.fieldSort')}</label>
            <input
              type="number"
              min={0}
              className="input-field w-24 py-1.5"
              value={form.sort_order}
              onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))}
            />
          </div>
        </div>

        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.templates.fieldGenerationPrompt')}</label>
          <textarea
            className="input-field w-full min-h-[120px] font-mono text-xs"
            value={form.generation_prompt}
            onChange={(e) => setForm((f) => ({ ...f, generation_prompt: e.target.value }))}
            placeholder="English prompt for FLUX / Kontext…"
          />
        </div>

        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.templates.fieldNegativePrompt')}</label>
          <textarea
            className="input-field w-full min-h-[72px] font-mono text-xs"
            value={form.negative_prompt}
            onChange={(e) => setForm((f) => ({ ...f, negative_prompt: e.target.value }))}
          />
        </div>

        <div>
          <label className="text-xs text-gray-500 block mb-1">{t('admin.templates.fieldConfigJson')}</label>
          <p className="text-xs text-gray-600 mb-2 leading-relaxed">{t('admin.templates.fieldConfigJsonHelp')}</p>
          <textarea
            className="input-field w-full min-h-[100px] font-mono text-xs"
            value={form.config_json}
            onChange={(e) => setForm((f) => ({ ...f, config_json: e.target.value }))}
          />
        </div>

        <div>
          <label className="text-xs text-gray-500 block mb-1 flex items-center gap-2">
            <ImagePlus className="w-3.5 h-3.5" />
            {t('admin.templates.fieldPreview')}
          </label>
          {!isNew && existingPreviewUrl && !previewFile && (
            <div className="mb-3 rounded-xl overflow-hidden border border-white/10 max-w-xs">
              <img src={existingPreviewUrl} alt="" className="w-full h-40 object-cover" />
            </div>
          )}
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onChange={(e) => setPreviewFile(e.target.files?.[0] ?? null)}
            className="text-sm text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/10 file:text-white"
          />
        </div>

        <div className="flex flex-wrap gap-3 pt-2">
          <button type="submit" disabled={saving} className="btn-primary flex items-center justify-center gap-2 px-8 py-3">
            {saving && <Spinner size="sm" />}
            {isNew ? t('admin.templates.create') : t('admin.templates.save')}
          </button>
          <Link to={`${ADMIN_BASE}/templates`} className="btn-secondary px-8 py-3">
            {t('common.cancel')}
          </Link>
        </div>
      </form>
    </div>
  )
}
