import { useState, useCallback, useMemo, useEffect } from 'react'
import { useNavigate, useLocation, Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Camera, Clapperboard, ChevronDown, Sparkles } from 'lucide-react'
import toast from 'react-hot-toast'
import ImageUploader from '../components/ImageUploader'
import Spinner from '../components/ui/Spinner'
import ZoomableImage from '../components/ui/ZoomableImage'
import { photoFlowApi } from '../services/api'
import { APP_BASE } from '../constants/routes'
import { useAuthContext } from '../context/AuthContext'
import AspectRatioSelector from '../components/photo/AspectRatioSelector'

const CONTENT_ICONS = { photo: Camera, video: Clapperboard }

const CATEGORY_IDS = ['apparel', 'electronics', 'home', 'beauty', 'food', 'sports', 'other']

const DEFAULT_PHOTO_FLOW = {
  improvement: 1,
  photo_per_image: 2,
  photo_scene_credits: {
    from_wishes: 2,
    no_watermark: 2,
    studio: 2,
  },
  card_per_image: 1,
  card_by_example: 2,
  card_by_prompt: 1,
  video: [
    { seconds: 5, credits: 10 },
    { seconds: 20, credits: 20 },
  ],
}

export default function CreateProductPhotoFlow({ flowVariant = 'photoOnly' }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const { user } = useAuthContext()
  const pricing = user?.credits?.photo_flow ?? DEFAULT_PHOTO_FLOW
  const maxBatchQuantity = Math.max(1, user?.generation_limits?.max_batch_quantity ?? 1)
  const creditBalance = user?.credits?.balance ?? 0
  const hasActiveSubscription = user?.has_active_subscription === true
  const photoGuidedVideoGate = user?.photo_guided_video
  const videoTabAllowed = photoGuidedVideoGate?.allowed !== false

  const templateState = location.state
  const templateId = templateState?.templateId

  const [files, setFiles] = useState([])
  const [loading, setLoading] = useState(false)
  /** Reuse after running “Analyze photos” on step 1 so Generate does not create a second draft. */
  const [analysisProjectId, setAnalysisProjectId] = useState(null)
  const [analyzedFileSignature, setAnalyzedFileSignature] = useState('')
  const [analysisQualities, setAnalysisQualities] = useState([])
  const [analyzing, setAnalyzing] = useState(false)
  const [fileChangeInvalidated, setFileChangeInvalidated] = useState(false)

  const [productName, setProductName] = useState('')
  const [category, setCategory] = useState(() => templateState?.productCategory || 'other')

  useEffect(() => {
    if (templateState?.productCategory) {
      setCategory(templateState.productCategory)
    }
  }, [templateState?.productCategory])

  const [contentType, setContentType] = useState(() => (flowVariant === 'videoOnly' ? 'video' : 'photo'))
  const [sceneStyle, setSceneStyle] = useState('from_wishes')
  const [photoPrompt, setPhotoPrompt] = useState('')
  const [videoDescription, setVideoDescription] = useState('')
  const [videoDuration, setVideoDuration] = useState(5)
  const [batchCount, setBatchCount] = useState(1)

  useEffect(() => {
    setBatchCount((c) => Math.min(Math.max(1, c), maxBatchQuantity))
  }, [maxBatchQuantity])

  useEffect(() => {
    if (flowVariant === 'photoOnly' && contentType === 'video') {
      setContentType('photo')
    }
  }, [flowVariant, contentType])
  const [advancedOpen, setAdvancedOpen] = useState(false)
  /** FLUX Kontext output aspect ratio (photo). */
  const [outputAspectRatio, setOutputAspectRatio] = useState('3:4')

  const filesSignature = useMemo(
    () => files.map((f) => `${f.name}:${f.size}`).join('|'),
    [files]
  )

  /** Draft project from last successful analysis, matching current files (optional; generate can create a new project). */
  const analysisSynced =
    analysisProjectId != null && analyzedFileSignature === filesSignature && files.length >= 1

  /** Generate requires photos and a product name; analysis is optional. */
  const canStartGeneration = files.length >= 1 && productName.trim() !== ''

  /** AI analysis can run on photos only; API still needs a string title — we send a placeholder if empty. */
  const analyzeReady = files.length >= 1

  useEffect(() => {
    if (!analyzedFileSignature) return
    if (filesSignature !== analyzedFileSignature) {
      setAnalysisProjectId(null)
      setAnalyzedFileSignature('')
      setAnalysisQualities([])
      setFileChangeInvalidated(true)
      setProductName('')
    }
  }, [filesSignature, analyzedFileSignature])

  const photoSceneOptions = [
    { id: 'from_wishes', titleKey: 'photoFlow.scene.fromWishes.title', subKey: 'photoFlow.scene.fromWishes.sub' },
    { id: 'no_watermark', titleKey: 'photoFlow.scene.noWatermark.title', subKey: 'photoFlow.scene.noWatermark.sub' },
    { id: 'studio', titleKey: 'photoFlow.scene.studio.title', subKey: 'photoFlow.scene.studio.sub' },
  ]

  const contentTypes = useMemo(() => {
    if (flowVariant === 'videoOnly') {
      return [{ id: 'video', labelKey: 'photoFlow.content.video' }]
    }
    return [{ id: 'photo', labelKey: 'photoFlow.content.photo' }]
  }, [flowVariant])
  const showContentTypeSwitcher = contentTypes.length > 1

  const photoSceneCost = useMemo(() => {
    const m = pricing.photo_scene_credits ?? DEFAULT_PHOTO_FLOW.photo_scene_credits
    return m?.[sceneStyle] ?? pricing.photo_per_image
  }, [pricing, sceneStyle])

  const tabCredits = useMemo(() => ({
    photo: photoSceneCost,
    video: (pricing.video ?? DEFAULT_PHOTO_FLOW.video).find((v) => v.seconds === videoDuration)?.credits ?? 10,
  }), [pricing, videoDuration, photoSceneCost])

  const unitCost = tabCredits[contentType] ?? pricing.photo_per_image
  const generationCost = unitCost * batchCount

  const clearFiles = useCallback(() => {
    setFiles([])
    setProductName('')
    setCategory('other')
    setPhotoPrompt('')
    setVideoDescription('')
    setSceneStyle('from_wishes')
    setVideoDuration(5)
    setContentType(flowVariant === 'videoOnly' ? 'video' : 'photo')
    setBatchCount(1)
    setAdvancedOpen(false)
    setOutputAspectRatio('3:4')
    setAnalysisProjectId(null)
    setAnalyzedFileSignature('')
    setAnalysisQualities([])
    setFileChangeInvalidated(false)
  }, [flowVariant])

  const userWishesForApi = useMemo(() => {
    if (contentType === 'photo') return photoPrompt.trim()
    return videoDescription.trim()
  }, [contentType, photoPrompt, videoDescription])

  const templateSceneCost = useMemo(() => {
    const m = pricing.photo_scene_credits ?? DEFAULT_PHOTO_FLOW.photo_scene_credits
    return m?.studio ?? pricing.photo_per_image
  }, [pricing])

  const videoBlockBannerLine = useMemo(() => {
    if (videoTabAllowed || !photoGuidedVideoGate?.code) return null
    const min = photoGuidedVideoGate.min_balance ?? 10
    switch (photoGuidedVideoGate.code) {
      case 'low_credits':
        return t('photoFlow.videoTabBlockedLowCredits', { min })
      case 'no_subscription':
        return t('photoFlow.videoTabBlockedNoSubscription')
      case 'starter_plan':
        return t('photoFlow.videoTabBlockedStarter')
      default:
        return t('photoFlow.videoTabBlockedGeneric')
    }
  }, [videoTabAllowed, photoGuidedVideoGate, t])

  const templateGenerateAllowed =
    hasActiveSubscription && templateSceneCost >= 1 && creditBalance >= templateSceneCost
  const standardGenerateAllowed =
    generationCost >= 1 &&
    creditBalance >= generationCost &&
    (contentType !== 'video' || videoTabAllowed)

  const handleRunPhotoAnalysis = async () => {
    if (files.length < 1) {
      toast.error(t('photoFlow.validation.photoRequired'))
      return
    }
    const name = productName.trim() || t('photoFlow.temporaryNameForApi')
    setAnalyzing(true)
    try {
      let projectId = analysisProjectId
      const sameAsLastAnalyze = projectId && filesSignature === analyzedFileSignature
      if (!sameAsLastAnalyze) {
        const { data: createRes } = await photoFlowApi.createFromPhoto(files, {
          productName: name,
          category,
        })
        projectId = createRes.data.id
      }
      const { data: anRes } = await photoFlowApi.analyzeProduct(projectId)
      const p = anRes.data?.product
      if (p) {
        if (typeof p.name === 'string' && p.name.trim() !== '') {
          setProductName(p.name.slice(0, 200))
        }
        if (typeof p.category === 'string' && CATEGORY_IDS.includes(p.category)) {
          setCategory(p.category)
        }
        const qs = Array.isArray(p.qualities) ? p.qualities : []
        setAnalysisQualities(qs)
      }
      setAnalysisProjectId(projectId)
      setAnalyzedFileSignature(filesSignature)
      setFileChangeInvalidated(false)
      toast.success(t('photoFlow.analyzeSuccess'))
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setAnalyzing(false)
    }
  }

  const handleTemplateGenerate = async () => {
    if (files.length < 1) {
      toast.error(t('photoFlow.validation.photoRequired'))
      return
    }
    const name = productName.trim()
    if (!name) {
      toast.error(t('photoFlow.validation.nameRequired'))
      return
    }
    if (!templateId) return
    if (!hasActiveSubscription) {
      toast.error(t('photoFlow.templateMode.subscribersOnlyToast'))
      return
    }
    if (creditBalance < templateSceneCost) {
      toast.error(t('photoFlow.generateBlockedNoCreditsToast', { count: templateSceneCost }))
      return
    }

    setLoading(true)
    try {
      const { data: createRes } = await photoFlowApi.createFromPhoto(files, {
        productName: name,
        category,
        templateId,
      })
      const projectId = createRes.data.id

      const payload = {
        content_type: 'photo',
        scene_style: 'studio',
        user_wishes: undefined,
        product_name: name,
        product_category: category,
        quantity: 1,
        aspect_ratio: outputAspectRatio,
      }

      const { data: genRes } = await photoFlowApi.startGeneration(projectId, payload)

      toast.success(genRes.message || t('photoFlow.generateQueued'))
      navigate(`${APP_BASE}/projects/${projectId}`)
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setLoading(false)
    }
  }

  const handleGenerate = async () => {
    if (files.length < 1) {
      toast.error(t('photoFlow.validation.photoRequired'))
      return
    }
    const name = productName.trim()
    if (!name) {
      toast.error(t('photoFlow.validation.nameRequired'))
      return
    }
    if (creditBalance < generationCost) {
      toast.error(t('photoFlow.generateBlockedNoCreditsToast', { count: generationCost }))
      return
    }
    if (contentType === 'video' && !videoTabAllowed) {
      const min = photoGuidedVideoGate?.min_balance ?? 10
      const code = photoGuidedVideoGate?.code
      if (code === 'low_credits') {
        toast.error(t('photoFlow.videoTabBlockedLowCredits', { min }))
      } else if (code === 'starter_plan') {
        toast.error(t('photoFlow.videoTabBlockedStarter'))
      } else if (code === 'no_subscription') {
        toast.error(t('photoFlow.videoTabBlockedNoSubscription'))
      } else {
        toast.error(t('photoFlow.videoTabBlockedGeneric'))
      }
      return
    }

    const sceneForApi = contentType === 'photo' ? sceneStyle : 'studio'

    setLoading(true)
    try {
      let projectId
      if (analysisProjectId && filesSignature === analyzedFileSignature) {
        projectId = analysisProjectId
      } else {
        const { data: createRes } = await photoFlowApi.createFromPhoto(files, {
          productName: name,
          category,
        })
        projectId = createRes.data.id
      }

      const payload = {
        content_type: contentType,
        scene_style: sceneForApi,
        user_wishes: userWishesForApi || undefined,
        product_name: name,
        product_category: category,
        quantity: batchCount,
        ...(contentType === 'video' ? { video_duration_seconds: videoDuration } : {}),
        ...(contentType === 'photo' ? { aspect_ratio: outputAspectRatio } : {}),
      }

      const { data: genRes } = await photoFlowApi.startGeneration(projectId, payload)

      toast.success(genRes.message || t('photoFlow.generateQueued'))
      navigate(`${APP_BASE}/projects/${projectId}`)
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setLoading(false)
    }
  }

  if (templateId) {
    const previewUrl = templateState?.previewUrl
    const templateName = templateState?.templateName || t('photoFlow.templateMode.fallbackTitle')
    const templateReady = files.length >= 1 && productName.trim() !== ''

    return (
      <div className="max-w-5xl mx-auto space-y-6 lg:space-y-8 pb-12 px-1 sm:px-0">
        <div className="flex flex-col gap-2">
          <Link
            to={`${APP_BASE}/templates`}
            className="text-sm text-brand-400 hover:text-brand-300 w-fit"
          >
            ← {t('photoFlow.templateMode.backToTemplates')}
          </Link>
          <h1 className="text-3xl font-bold text-white mb-1">{t('photoFlow.templateMode.title')}</h1>
          <p className="text-gray-400">{t('photoFlow.templateMode.subtitle')}</p>
        </div>

        <div className="flex flex-col lg:flex-row gap-6 lg:gap-8 lg:items-start">
          {/* Reference — left on desktop, top on mobile */}
          <section className="card border border-white/10 rounded-3xl bg-gray-900/40 overflow-hidden w-full lg:w-[min(100%,22rem)] lg:flex-shrink-0 lg:sticky lg:top-24">
            <div className="px-4 sm:px-5 pt-4 sm:pt-5 pb-2">
              <h2 className="text-xs font-semibold text-gray-400 uppercase tracking-wide">{t('photoFlow.templateMode.referenceLabel')}</h2>
              <p className="text-base sm:text-lg font-semibold text-white mt-1 leading-snug">{templateName}</p>
            </div>
            <div className="relative mx-4 mb-4 aspect-[3/4] max-h-[min(70vh,32rem)] min-h-[12rem] overflow-hidden rounded-2xl border border-white/10 bg-gray-800 sm:mx-5 sm:mb-5">
              {previewUrl ? (
                <ZoomableImage
                  src={previewUrl}
                  alt=""
                  className="absolute inset-0 h-full w-full rounded-2xl"
                  imageClassName="h-full w-full object-cover"
                />
              ) : (
                <div className="flex h-full min-h-[12rem] items-center justify-center px-4 text-center text-sm text-gray-500">
                  {templateName}
                </div>
              )}
            </div>
          </section>

          {/* Product + generate — right */}
          <section className="card relative border border-white/10 rounded-3xl bg-gray-900/40 flex-1 min-w-0 p-5 sm:p-6">
            <div className="mb-5">
              <h2 className="text-lg font-semibold text-white">{t('photoFlow.yourProduct')}</h2>
            </div>

            <ImageUploader files={files} onChange={setFiles} maxFiles={4} minRequiredForHint={1} />

            <p className="text-sm text-gray-500 mt-4">{t('photoFlow.uploadMultiHint')}</p>

            <div className="mt-6 space-y-4">
              <input
                type="text"
                className="input-field w-full"
                value={productName}
                onChange={(e) => setProductName(e.target.value.slice(0, 200))}
                placeholder={t('photoFlow.productName')}
              />
              <div>
                <label className="text-xs text-gray-500 block mb-1.5">{t('photoFlow.category')}</label>
                <select
                  className="input-field w-full"
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                >
                  {CATEGORY_IDS.map((id) => (
                    <option key={id} value={id}>
                      {t(`photoFlow.categories.${id}`)}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <p className="text-xs text-gray-500 mt-4 leading-relaxed">{t('photoFlow.templateMode.hint')}</p>

            <div className="mt-6 rounded-2xl border border-white/10 bg-gray-900/30 overflow-hidden">
              <button
                type="button"
                onClick={() => setAdvancedOpen((o) => !o)}
                aria-expanded={advancedOpen}
                className="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left text-sm font-semibold text-white hover:bg-white/5 transition-colors"
              >
                <span>{t('photoFlow.advanced.title')}</span>
                <ChevronDown
                  className={`w-5 h-5 shrink-0 text-gray-400 transition-transform duration-200 ${advancedOpen ? 'rotate-180' : ''}`}
                  aria-hidden
                />
              </button>
              {advancedOpen && (
                <div className="border-t border-white/10 px-4 pb-4 pt-1">
                  <p id="photo-flow-template-format-label" className="text-sm font-semibold text-white mb-3">
                    {t('photoFlow.advanced.formatLabel')}
                  </p>
                  <p className="text-xs text-gray-500 mb-3">{t('photoFlow.advanced.formatHint')}</p>
                  <AspectRatioSelector
                    labelId="photo-flow-template-format-label"
                    value={outputAspectRatio}
                    onChange={setOutputAspectRatio}
                  />
                </div>
              )}
            </div>

            {!hasActiveSubscription && (
              <div className="mt-6 rounded-2xl border border-amber-500/30 bg-amber-950/25 px-4 py-3 text-sm text-amber-100/95">
                {t('photoFlow.templateMode.subscribersOnly')}
              </div>
            )}
            {hasActiveSubscription && templateReady && creditBalance < templateSceneCost && (
              <div className="mt-6 rounded-2xl border border-red-500/25 bg-red-950/20 px-4 py-3 text-sm text-red-100/90">
                {t('photoFlow.generateBlockedNoCreditsBanner', { count: templateSceneCost })}{' '}
                <Link to={`${APP_BASE}/credits`} className="text-brand-400 hover:text-brand-300 underline underline-offset-2 font-medium">
                  {t('photoFlow.topUpCreditsLink')}
                </Link>
              </div>
            )}

            <button
              type="button"
              onClick={handleTemplateGenerate}
              disabled={loading || !templateReady || !templateGenerateAllowed}
              className="btn-primary w-full flex items-center justify-center gap-2 py-3.5 mt-6 disabled:opacity-45 disabled:cursor-not-allowed"
            >
              {loading && <Spinner size="sm" />}
              {loading
                ? t('photoFlow.creatingAndGenerating')
                : t('photoFlow.templateMode.generateWithCredits', { count: templateSceneCost })}
            </button>
          </section>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-6xl mx-auto space-y-8 pb-12 px-1 sm:px-0">
      <div>
        <h1 className="text-3xl font-bold text-white mb-1">
          {flowVariant === 'videoOnly' ? t('photoFlow.pageTitleVideo') : t('photoFlow.title')}
        </h1>
        <p className="text-gray-400">
          {flowVariant === 'videoOnly' ? t('photoFlow.pageSubtitleVideo') : t('photoFlow.subtitle')}
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-start">
      <section className="card relative border border-white/10 rounded-3xl bg-gray-900/40">
        <div className="flex items-start justify-between gap-4 mb-5">
          <h2 className="text-lg font-semibold text-white">{t('photoFlow.yourProduct')}</h2>
          <span className="text-2xl font-black italic text-brand-400/90 tabular-nums" aria-hidden>
            01
          </span>
        </div>

        <ImageUploader files={files} onChange={setFiles} maxFiles={4} minRequiredForHint={1} />

        <p className="text-sm text-gray-500 mt-4">{t('photoFlow.uploadMultiHint')}</p>

        <div className="flex items-center justify-between mt-2 text-xs text-gray-500">
          <span>{t('photoFlow.photoCount', { count: files.length })}</span>
          {files.length > 0 && (
            <button type="button" onClick={clearFiles} className="text-brand-400 hover:text-brand-300 underline-offset-2 hover:underline">
              {t('photoFlow.clearPhotos')}
            </button>
          )}
        </div>

        <div className="mt-6 space-y-2">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('photoFlow.analyzeBlockTitle')}</p>
          <p className="text-xs text-gray-500 leading-relaxed">{t('photoFlow.analyzeHint')}</p>
          <button
            type="button"
            onClick={handleRunPhotoAnalysis}
            disabled={!analyzeReady || analyzing || loading}
            className="btn-secondary w-full flex items-center justify-center gap-2 py-3 disabled:opacity-45 disabled:cursor-not-allowed"
          >
            {analyzing && <Spinner size="sm" />}
            {!analyzing && <Sparkles className="w-4 h-4 shrink-0" aria-hidden />}
            {analyzing ? t('photoFlow.analyzingPhotos') : t('photoFlow.analyzePhotos')}
          </button>
        </div>

        {fileChangeInvalidated && analyzeReady && (
          <p className="text-xs text-amber-200/80 mt-3 rounded-lg border border-amber-500/20 bg-amber-950/20 px-3 py-2">
            {t('photoFlow.photosChangedReanalyze')}
          </p>
        )}

        {analysisQualities.length > 0 && (
          <div className="mt-4 rounded-2xl border border-white/10 bg-gray-900/50 px-4 py-3">
            <p className="text-xs font-medium text-gray-400 mb-2">{t('photoFlow.qualitiesFromAi')}</p>
            <ul className="list-disc list-inside text-sm text-gray-200 space-y-0.5">
              {analysisQualities.map((q, i) => (
                <li key={`${i}-${q}`}>{q}</li>
              ))}
            </ul>
          </div>
        )}

        {analyzeReady && (
          <div className="mt-6 space-y-4">
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('photoFlow.nameAfterAnalysisTitle')}</p>
            <p className="text-xs text-gray-500 -mt-1">{t('photoFlow.nameAfterAnalysisSub')}</p>
            <input
              type="text"
              className="input-field w-full"
              value={productName}
              onChange={(e) => setProductName(e.target.value.slice(0, 200))}
              placeholder={t('photoFlow.productName')}
            />
            <div>
              <label className="text-xs text-gray-500 block mb-1.5">{t('photoFlow.category')}</label>
              <select
                className="input-field w-full"
                value={category}
                onChange={(e) => setCategory(e.target.value)}
              >
                {CATEGORY_IDS.map((id) => (
                  <option key={id} value={id}>
                    {t(`photoFlow.categories.${id}`)}
                  </option>
                ))}
              </select>
            </div>
          </div>
        )}

        {analyzeReady && !analysisSynced && !fileChangeInvalidated && !analyzing && (
          <p className="text-xs text-gray-500 mt-4 leading-relaxed">{t('photoFlow.runAnalyzeOptional')}</p>
        )}

        {analysisSynced && (
          <p className="text-xs text-brand-400/90 mt-4">
            {flowVariant === 'videoOnly' ? t('photoFlow.step1ReadyHintVideo') : t('photoFlow.step1ReadyHint')}
          </p>
        )}
      </section>

      <section className="card relative border border-white/10 rounded-3xl bg-gray-900/40 lg:sticky lg:top-24">
        <div className="flex items-start justify-between gap-4 mb-5">
          <h2 className="text-lg font-semibold text-white">{t('photoFlow.configureGeneration')}</h2>
          <span className="text-2xl font-black italic text-gray-500 tabular-nums" aria-hidden>
            02
          </span>
        </div>

        <div>
          {showContentTypeSwitcher && (
            <section className="mb-6">
              <h3 className="text-sm font-semibold text-white mb-3">{t('photoFlow.contentType')}</h3>
              <div className="flex rounded-xl bg-gray-900/80 p-1 border border-white/10 gap-1">
                {contentTypes.map(({ id, labelKey }) => {
                  const Icon = CONTENT_ICONS[id]
                  const active = contentType === id
                  const disabled = id === 'video' && !videoTabAllowed
                  return (
                    <button
                      key={id}
                      type="button"
                      disabled={disabled}
                      title={disabled ? t('photoFlow.videoTabDisabledTitle') : undefined}
                      onClick={() => !disabled && setContentType(id)}
                      className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-1 rounded-lg text-sm font-medium transition-all min-w-0
                        ${disabled ? 'opacity-45 cursor-not-allowed text-gray-500' : ''}
                        ${active && !disabled ? 'bg-white text-gray-950 shadow' : !disabled ? 'text-gray-400 hover:text-white' : ''}`}
                    >
                      <Icon className="w-4 h-4 shrink-0" />
                      <span className="truncate">{t(labelKey)}</span>
                    </button>
                  )
                })}
              </div>
            </section>
          )}
          {flowVariant === 'videoOnly' && videoBlockBannerLine && (
            <p className="mb-6 text-xs text-amber-100/90 rounded-xl border border-amber-500/25 bg-amber-950/25 px-3 py-2 leading-relaxed">
              {videoBlockBannerLine}{' '}
              <Link to={`${APP_BASE}/credits`} className="text-brand-400 hover:text-brand-300 underline underline-offset-2 font-medium">
                {t('photoFlow.topUpCreditsLink')}
              </Link>
            </p>
          )}
          {contentType === 'photo' && (
            <section className="space-y-6 mb-6">
              <div>
                <h3 className="text-sm font-semibold text-white mb-3">{t('photoFlow.photo.sceneTitle')}</h3>
                <div className="grid gap-3 sm:grid-cols-3">
                  {photoSceneOptions.map(({ id, titleKey, subKey }) => {
                    const active = sceneStyle === id
                    return (
                      <button
                        key={id}
                        type="button"
                        onClick={() => setSceneStyle(id)}
                        className={`text-left rounded-2xl border p-3 sm:p-4 transition-all flex flex-col h-full
                          ${active
                            ? 'border-brand-500 bg-brand-600/10 ring-1 ring-brand-500/40'
                            : 'border-white/10 bg-gray-900/40 hover:border-white/20'}`}
                      >
                        <p className="font-semibold text-white text-sm leading-tight">{t(titleKey)}</p>
                        <p className="text-xs text-gray-500 mt-1 flex-1">{t(subKey)}</p>
                      </button>
                    )
                  })}
                </div>
              </div>
              <div>
                <h3 className="text-sm font-semibold text-white mb-1">{t('photoFlow.photo.wishesLabel')}</h3>
                <p className="text-xs text-amber-400/90 mb-2 leading-relaxed border border-amber-500/20 rounded-lg px-2.5 py-1.5 bg-amber-500/5">
                  {t('photoFlow.promptEnglishNote')}
                </p>
                <p className="text-sm text-gray-500 mb-3">{t('photoFlow.photo.wishesHint')}</p>
                <textarea
                  className="input-field min-h-[100px] resize-y"
                  placeholder={t('photoFlow.photo.wishesPlaceholder')}
                  value={photoPrompt}
                  onChange={(e) => setPhotoPrompt(e.target.value.slice(0, 2000))}
                  maxLength={2000}
                />
                <p className="text-xs text-gray-600 text-right mt-1">{photoPrompt.length}/2000</p>
              </div>
            </section>
          )}

          {contentType === 'video' && (
            <section className="space-y-6 mb-6">
              <div>
                <h3 className="text-sm font-semibold text-white mb-3">{t('photoFlow.video.durationTitle')}</h3>
                <div className="flex flex-wrap gap-2">
                  {(pricing.video ?? DEFAULT_PHOTO_FLOW.video).map((tier) => {
                    const active = videoDuration === tier.seconds
                    return (
                      <button
                        key={tier.seconds}
                        type="button"
                        onClick={() => setVideoDuration(tier.seconds)}
                        className={`px-4 py-2.5 rounded-xl text-sm font-medium border transition-all
                          ${active
                            ? 'border-brand-500 bg-brand-600/15 text-white ring-1 ring-brand-500/40'
                            : 'border-white/10 bg-gray-900/50 text-gray-300 hover:border-white/20'}`}
                      >
                        {t('photoFlow.video.durationSeconds', { seconds: tier.seconds })}
                      </button>
                    )
                  })}
                </div>
              </div>
              <div>
                <h3 className="text-sm font-semibold text-white mb-1">{t('photoFlow.video.descriptionTitle')}</h3>
                <p className="text-xs text-amber-400/90 mb-2 leading-relaxed border border-amber-500/20 rounded-lg px-2.5 py-1.5 bg-amber-500/5">
                  {t('photoFlow.promptEnglishNote')}
                </p>
                <p className="text-sm text-gray-500 mb-3">{t('photoFlow.video.descriptionHint')}</p>
                <textarea
                  className="input-field min-h-[120px] resize-y"
                  placeholder={t('photoFlow.video.descriptionPlaceholder')}
                  value={videoDescription}
                  onChange={(e) => setVideoDescription(e.target.value.slice(0, 2000))}
                  maxLength={2000}
                />
                <p className="text-xs text-gray-600 text-right mt-1">{videoDescription.length}/2000</p>
              </div>
            </section>
          )}

          {contentType === 'photo' && (
            <div className="mb-6 rounded-2xl border border-white/10 bg-gray-900/30 overflow-hidden">
              <button
                type="button"
                onClick={() => setAdvancedOpen((o) => !o)}
                aria-expanded={advancedOpen}
                className="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left text-sm font-semibold text-white hover:bg-white/5 transition-colors"
              >
                <span>{t('photoFlow.advanced.title')}</span>
                <ChevronDown
                  className={`w-5 h-5 shrink-0 text-gray-400 transition-transform duration-200 ${advancedOpen ? 'rotate-180' : ''}`}
                  aria-hidden
                />
              </button>
              {advancedOpen && (
                <div className="border-t border-white/10 px-4 pb-4 pt-1">
                  <p id="photo-flow-format-label" className="text-sm font-semibold text-white mb-3">
                    {t('photoFlow.advanced.formatLabel')}
                  </p>
                  <p className="text-xs text-gray-500 mb-3">{t('photoFlow.advanced.formatHint')}</p>
                  <AspectRatioSelector
                    labelId="photo-flow-format-label"
                    value={outputAspectRatio}
                    onChange={setOutputAspectRatio}
                  />
                </div>
              )}
            </div>
          )}

          {canStartGeneration && generationCost >= 1 && creditBalance < generationCost && (
            <p className="text-sm text-amber-200/90 mb-3 rounded-xl border border-amber-500/20 bg-amber-950/20 px-3 py-2">
              {t('photoFlow.generateBlockedNoCreditsBanner', { count: generationCost })}{' '}
              <Link to={`${APP_BASE}/credits`} className="text-brand-400 hover:text-brand-300 underline underline-offset-2 font-medium">
                {t('photoFlow.topUpCreditsLink')}
              </Link>
            </p>
          )}

          <div className="flex flex-row gap-2 items-stretch">
            <button
              type="button"
              onClick={handleGenerate}
              disabled={loading || analyzing || !canStartGeneration || !standardGenerateAllowed}
              className="btn-primary flex-1 flex items-center justify-center gap-2 py-3 min-w-0 disabled:opacity-45 disabled:cursor-not-allowed"
            >
              {loading && <Spinner size="sm" />}
              {loading ? t('photoFlow.creatingAndGenerating') : t('photoFlow.generateWithCredits', { count: generationCost })}
            </button>
            <div className="flex flex-col w-24 sm:w-36 shrink-0 justify-end">
              <label htmlFor="photo-flow-quantity" className="sr-only">
                {t('photoFlow.quantityLabel')}
              </label>
              <select
                id="photo-flow-quantity"
                value={batchCount}
                onChange={(e) => setBatchCount(Number(e.target.value))}
                disabled={loading || analyzing}
                className="input-field w-full py-3 min-h-[48px] text-center"
              >
                {Array.from({ length: maxBatchQuantity }, (_, i) => i + 1).map((n) => (
                  <option key={n} value={n}>
                    {t('photoFlow.quantityOption', { count: n })}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
      </section>
      </div>
    </div>
  )
}
