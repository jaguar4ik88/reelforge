import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Camera, Clapperboard, FileImage, Sparkles, ChevronLeft, ChevronRight } from 'lucide-react'
import toast from 'react-hot-toast'
import ImageUploader from '../components/ImageUploader'
import Spinner from '../components/ui/Spinner'
import { photoFlowApi } from '../services/api'

const CONTENT_ICONS = { photo: Camera, card: FileImage, video: Clapperboard }

function fileFingerprint(file) {
  return `${file.name}-${file.size}-${file.lastModified}`
}

export default function CreateProductPhotoFlow() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [step, setStep] = useState(0)
  const [loading, setLoading] = useState(false)
  const [step0Loading, setStep0Loading] = useState(false)
  const [files, setFiles] = useState([])
  const [projectId, setProjectId] = useState(null)
  const [createdForKey, setCreatedForKey] = useState(null)
  const [contentType, setContentType] = useState('photo')
  const [sceneStyle, setSceneStyle] = useState('in_use')
  const [wishes, setWishes] = useState('')

  const STEPS = [t('photoFlow.steps.upload'), t('photoFlow.steps.configure')]

  const sceneOptions = [
    { id: 'in_use', titleKey: 'photoFlow.scene.inUse.title', subKey: 'photoFlow.scene.inUse.sub' },
    { id: 'environment', titleKey: 'photoFlow.scene.environment.title', subKey: 'photoFlow.scene.environment.sub' },
    { id: 'studio', titleKey: 'photoFlow.scene.studio.title', subKey: 'photoFlow.scene.studio.sub' },
  ]

  const contentTypes = [
    { id: 'photo', labelKey: 'photoFlow.content.photo', hintKey: 'photoFlow.contentHints.photo' },
    { id: 'card', labelKey: 'photoFlow.content.card', hintKey: 'photoFlow.contentHints.card' },
    { id: 'video', labelKey: 'photoFlow.content.video', hintKey: 'photoFlow.contentHints.video' },
  ]

  const validateStep = () => {
    if (step === 0 && files.length < 1) {
      toast.error(t('photoFlow.validation.photoRequired'))
      return false
    }
    return true
  }

  const next = async () => {
    if (!validateStep()) return
    const file = files[0]
    const key = fileFingerprint(file)
    if (projectId && createdForKey === key) {
      setStep(1)
      return
    }
    setStep0Loading(true)
    try {
      const { data } = await photoFlowApi.createFromPhoto(file)
      setProjectId(data.data.id)
      setCreatedForKey(key)
      toast.success(data.message || t('photoFlow.projectCreated'))
      setStep(1)
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setStep0Loading(false)
    }
  }
  const prev = () => setStep(0)

  const handleAiIdea = () => {
    toast(t('photoFlow.aiIdeaSoon'), { icon: '✨' })
  }

  const handleGenerate = async () => {
    if (!validateStep() || !projectId) {
      toast.error(t('photoFlow.validation.photoRequired'))
      return
    }
    setLoading(true)
    try {
      const { data } = await photoFlowApi.startGeneration(projectId, {
        content_type: contentType,
        scene_style: sceneStyle,
        user_wishes: wishes || undefined,
      })
      toast.success(data.message || t('photoFlow.generateQueued'))
      navigate(`/projects/${projectId}`)
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-2xl mx-auto">
      <div className="mb-8 flex items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1">{t('photoFlow.title')}</h1>
          <p className="text-gray-400">{t('photoFlow.subtitle')}</p>
        </div>
        <div
          className="shrink-0 w-14 h-14 rounded-2xl border border-white/10 bg-white/5 flex items-center justify-center text-2xl font-bold text-brand-300"
          aria-hidden
        >
          0{step + 1}
        </div>
      </div>

      <div className="flex items-center gap-3 mb-8">
        {STEPS.map((label, i) => (
          <div key={label} className="flex items-center gap-3">
            <div className={`flex items-center gap-2 ${i <= step ? 'text-brand-400' : 'text-gray-600'}`}>
              <div
                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border
                ${i < step ? 'bg-brand-600 border-brand-600 text-white' :
                  i === step ? 'border-brand-400 text-brand-400' :
                    'border-gray-700 text-gray-600'}`}
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

      <div className="card space-y-8">
        {step === 0 && (
          <div>
            <h2 className="text-lg font-semibold text-white mb-1">{t('photoFlow.uploadTitle')}</h2>
            <p className="text-sm text-gray-400 mb-5">{t('photoFlow.uploadSub')}</p>
            <ImageUploader files={files} onChange={setFiles} maxFiles={1} minRequiredForHint={1} />
          </div>
        )}

        {step === 1 && (
          <>
            <section>
              <h2 className="text-lg font-semibold text-white mb-3">{t('photoFlow.contentType')}</h2>
              <div className="flex rounded-xl bg-gray-900/80 p-1 border border-white/10">
                {contentTypes.map(({ id, labelKey }) => {
                  const Icon = CONTENT_ICONS[id]
                  const active = contentType === id
                  return (
                    <button
                      key={id}
                      type="button"
                      onClick={() => setContentType(id)}
                      className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-2 rounded-lg text-sm font-medium transition-all
                        ${active ? 'bg-white text-gray-950 shadow' : 'text-gray-400 hover:text-white'}`}
                    >
                      <Icon className="w-4 h-4 shrink-0" />
                      <span className="truncate">{t(labelKey)}</span>
                    </button>
                  )
                })}
              </div>
              <p className="text-sm text-gray-500 mt-3">
                {t(contentTypes.find((c) => c.id === contentType).hintKey)}
              </p>
            </section>

            <section>
              <h2 className="text-lg font-semibold text-white mb-3">{t('photoFlow.howShow')}</h2>
              <div className="grid gap-3 sm:grid-cols-3">
                {sceneOptions.map(({ id, titleKey, subKey }) => {
                  const active = sceneStyle === id
                  return (
                    <button
                      key={id}
                      type="button"
                      onClick={() => setSceneStyle(id)}
                      className={`text-left rounded-2xl border p-4 transition-all
                        ${active
                          ? 'border-brand-500 bg-brand-600/10 ring-1 ring-brand-500/40'
                          : 'border-white/10 bg-gray-900/40 hover:border-white/20'}`}
                    >
                      <div className="aspect-[4/3] rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 mb-3 border border-white/5" />
                      <p className="font-semibold text-white text-sm">{t(titleKey)}</p>
                      <p className="text-xs text-gray-500 mt-1">{t(subKey)}</p>
                    </button>
                  )
                })}
              </div>
            </section>

            <section>
              <div className="flex items-center justify-between gap-3 mb-2">
                <h2 className="text-lg font-semibold text-white">{t('photoFlow.wishes')}</h2>
                <button
                  type="button"
                  onClick={handleAiIdea}
                  className="text-xs font-medium text-brand-300 hover:text-brand-200 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-600/15 border border-brand-500/25"
                >
                  <Sparkles className="w-3.5 h-3.5" />
                  {t('photoFlow.aiIdea')}
                </button>
              </div>
              <p className="text-sm text-gray-500 mb-3">{t('photoFlow.wishesHint')}</p>
              <textarea
                className="input-field min-h-[120px] resize-y"
                placeholder={t('photoFlow.wishesPlaceholder')}
                value={wishes}
                onChange={(e) => setWishes(e.target.value.slice(0, 2000))}
                maxLength={2000}
              />
              <p className="text-xs text-gray-600 text-right mt-1">{wishes.length}/2000</p>
            </section>
          </>
        )}
      </div>

      <div className="flex items-center justify-between mt-6">
        <button
          type="button"
          onClick={prev}
          disabled={step === 0}
          className="btn-secondary flex items-center gap-2 disabled:invisible"
        >
          <ChevronLeft className="w-4 h-4" />
          {t('create.back')}
        </button>

        {step === 0 ? (
          <button
            type="button"
            onClick={() => next()}
            disabled={step0Loading || files.length < 1}
            className="btn-primary flex items-center gap-2 min-w-[140px] justify-center"
          >
            {step0Loading && <Spinner size="sm" />}
            {step0Loading ? t('photoFlow.creatingProject') : t('create.next')}
            {!step0Loading && <ChevronRight className="w-4 h-4" />}
          </button>
        ) : (
          <button
            type="button"
            onClick={handleGenerate}
            disabled={loading || files.length < 1}
            className="btn-primary flex items-center gap-2 min-w-[160px] justify-center"
          >
            {loading && <Spinner size="sm" />}
            {loading ? t('photoFlow.generating') : t('photoFlow.generate')}
          </button>
        )}
      </div>
    </div>
  )
}
