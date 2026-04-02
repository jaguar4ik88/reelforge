import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { projectsApi, imagesApi } from '../services/api'
import FormField from '../components/ui/FormField'
import TemplateSelector from '../components/TemplateSelector'
import ImageUploader from '../components/ImageUploader'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { ChevronRight, ChevronLeft } from 'lucide-react'

export default function CreateProject() {
  const { t } = useTranslation()
  const navigate = useNavigate()

  const STEPS = [t('create.steps.details'), t('create.steps.template'), t('create.steps.photos')]

  const [step, setStep]       = useState(0)
  const [loading, setLoading] = useState(false)
  const [errors, setErrors]   = useState({})
  const [files, setFiles]     = useState([])

  const [form, setForm] = useState({
    title: '', price: '', description: '', template_id: null,
  })

  const set = (key) => (e) => {
    setForm((f) => ({ ...f, [key]: e.target.value }))
    setErrors((er) => ({ ...er, [key]: null }))
  }

  const validateStep = () => {
    if (step === 0) {
      const e = {}
      if (!form.title.trim())           e.title       = t('create.validation.titleRequired')
      if (!form.price)                  e.price       = t('create.validation.priceRequired')
      if (!form.description.trim())     e.description = t('create.validation.descRequired')
      if (form.description.length < 10) e.description = t('create.validation.descMin')
      setErrors(e)
      return Object.keys(e).length === 0
    }
    if (step === 1 && !form.template_id) {
      toast.error(t('create.validation.templateRequired'))
      return false
    }
    if (step === 2 && files.length < 3) {
      toast.error(t('create.validation.imagesMin'))
      return false
    }
    return true
  }

  const next = () => { if (validateStep()) setStep((s) => Math.min(s + 1, 2)) }
  const prev = () => setStep((s) => Math.max(s - 1, 0))

  const submit = async () => {
    if (!validateStep()) return
    setLoading(true)
    try {
      const { data: projectRes } = await projectsApi.create(form)
      const projectId = projectRes.data.id
      await imagesApi.upload(projectId, files)
      toast.success(t('create.created'))
      navigate(`/projects/${projectId}`)
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setErrors(data.errors)
      toast.error(data?.message ?? t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-2xl mx-auto">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-1">{t('create.title')}</h1>
        <p className="text-gray-400">{t('create.subtitle')}</p>
      </div>

      {/* Step indicator */}
      <div className="flex items-center gap-3 mb-8">
        {STEPS.map((label, i) => (
          <div key={i} className="flex items-center gap-3">
            <div className={`flex items-center gap-2 ${i <= step ? 'text-brand-400' : 'text-gray-600'}`}>
              <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border
                ${i < step  ? 'bg-brand-600 border-brand-600 text-white' :
                  i === step ? 'border-brand-400 text-brand-400' :
                               'border-gray-700 text-gray-600'
                }`}
              >
                {i < step ? '✓' : i + 1}
              </div>
              <span className="text-sm font-medium hidden sm:block">{label}</span>
            </div>
            {i < STEPS.length - 1 && (
              <div className={`flex-1 h-px w-8 ${i < step ? 'bg-brand-600' : 'bg-gray-800'}`} />
            )}
          </div>
        ))}
      </div>

      <div className="card">
        {step === 0 && (
          <div className="space-y-5">
            <h2 className="text-lg font-semibold text-white mb-4">{t('create.steps.details')}</h2>

            <FormField label={t('create.productTitle')} error={errors.title}>
              <input type="text" className="input-field"
                placeholder={t('create.titlePlaceholder')}
                value={form.title} onChange={set('title')} />
            </FormField>

            <FormField label={t('create.price')} error={errors.price}>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                <input type="number" className="input-field pl-8"
                  placeholder="99.99" min="0" step="0.01"
                  value={form.price} onChange={set('price')} />
              </div>
            </FormField>

            <FormField label={t('create.description')} error={errors.description}>
              <textarea className="input-field h-28 resize-none"
                placeholder={t('create.descPlaceholder')}
                value={form.description} onChange={set('description')} maxLength={2000} />
              <p className="text-xs text-gray-600 text-right mt-1">{form.description.length}/2000</p>
            </FormField>
          </div>
        )}

        {step === 1 && (
          <div>
            <h2 className="text-lg font-semibold text-white mb-4">{t('create.chooseTemplate')}</h2>
            <TemplateSelector
              value={form.template_id}
              onChange={(id) => setForm((f) => ({ ...f, template_id: id }))}
            />
          </div>
        )}

        {step === 2 && (
          <div>
            <h2 className="text-lg font-semibold text-white mb-1">{t('create.uploadPhotos')}</h2>
            <p className="text-sm text-gray-400 mb-5">{t('create.uploadSub')}</p>
            <ImageUploader files={files} onChange={setFiles} />
          </div>
        )}
      </div>

      <div className="flex items-center justify-between mt-6">
        <button onClick={prev} disabled={step === 0} className="btn-secondary flex items-center gap-2 disabled:invisible">
          <ChevronLeft className="w-4 h-4" />
          {t('create.back')}
        </button>

        {step < 2 ? (
          <button onClick={next} className="btn-primary flex items-center gap-2">
            {t('create.next')}
            <ChevronRight className="w-4 h-4" />
          </button>
        ) : (
          <button
            onClick={submit}
            disabled={loading || files.length < 3}
            className="btn-primary flex items-center gap-2 min-w-[160px] justify-center"
          >
            {loading && <Spinner size="sm" />}
            {loading ? t('create.creating') : t('create.createProject')}
          </button>
        )}
      </div>
    </div>
  )
}
