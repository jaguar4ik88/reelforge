import { useState, useCallback, useMemo, useEffect } from 'react'
import { useNavigate, useLocation, Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Camera, Clapperboard, FileImage, ChevronDown } from 'lucide-react'
import toast from 'react-hot-toast'
import ImageUploader from '../components/ImageUploader'
import Spinner from '../components/ui/Spinner'
import ZoomableImage from '../components/ui/ZoomableImage'
import { photoFlowApi } from '../services/api'
import { APP_BASE } from '../constants/routes'
import { useAuthContext } from '../context/AuthContext'
import AspectRatioSelector from '../components/photo/AspectRatioSelector'

const CONTENT_ICONS = { photo: Camera, card: FileImage, video: Clapperboard }

const CATEGORY_IDS = ['apparel', 'electronics', 'home', 'beauty', 'food', 'sports', 'other']

const DEFAULT_PHOTO_FLOW = {
  improvement: 1,
  photo_per_image: 2,
  photo_scene_credits: {
    from_wishes: 2,
    in_use: 2,
    studio: 2,
  },
  card_per_image: 1,
  video: [
    { seconds: 5, credits: 10 },
    { seconds: 20, credits: 20 },
  ],
}

export default function CreateProductPhotoFlow() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const { user } = useAuthContext()
  const pricing = user?.credits?.photo_flow ?? DEFAULT_PHOTO_FLOW

  const templateState = location.state
  const templateId = templateState?.templateId

  const [files, setFiles] = useState([])
  const [loading, setLoading] = useState(false)

  const [productName, setProductName] = useState('')
  const [category, setCategory] = useState(() => templateState?.productCategory || 'other')

  useEffect(() => {
    if (templateState?.productCategory) {
      setCategory(templateState.productCategory)
    }
  }, [templateState?.productCategory])

  const [contentType, setContentType] = useState('photo')
  const [sceneStyle, setSceneStyle] = useState('from_wishes')
  const [photoPrompt, setPhotoPrompt] = useState('')
  const [cardCopy, setCardCopy] = useState('')
  const [videoDescription, setVideoDescription] = useState('')
  const [videoDuration, setVideoDuration] = useState(5)
  const [batchCount, setBatchCount] = useState(1)
  const [advancedOpen, setAdvancedOpen] = useState(false)
  /** FLUX Kontext output aspect ratio (photo & product card). */
  const [outputAspectRatio, setOutputAspectRatio] = useState('3:4')

  /** Step 2 is enabled when photos + name are filled (no project on server yet). */
  const step1Ready = files.length >= 1 && productName.trim() !== ''

  const photoSceneOptions = [
    { id: 'from_wishes', titleKey: 'photoFlow.scene.fromWishes.title', subKey: 'photoFlow.scene.fromWishes.sub' },
    { id: 'in_use', titleKey: 'photoFlow.scene.inUse.title', subKey: 'photoFlow.scene.inUse.sub' },
    { id: 'studio', titleKey: 'photoFlow.scene.studio.title', subKey: 'photoFlow.scene.studio.sub' },
  ]

  const contentTypes = [
    { id: 'photo', labelKey: 'photoFlow.content.photo' },
    { id: 'card', labelKey: 'photoFlow.content.card' },
    { id: 'video', labelKey: 'photoFlow.content.video' },
  ]

  const photoSceneCost = useMemo(() => {
    const m = pricing.photo_scene_credits ?? DEFAULT_PHOTO_FLOW.photo_scene_credits
    return m?.[sceneStyle] ?? pricing.photo_per_image
  }, [pricing, sceneStyle])

  const tabCredits = useMemo(() => ({
    photo: photoSceneCost,
    card: pricing.card_per_image,
    video: (pricing.video ?? DEFAULT_PHOTO_FLOW.video).find((v) => v.seconds === videoDuration)?.credits ?? 10,
  }), [pricing, videoDuration, photoSceneCost])

  const unitCost = tabCredits[contentType] ?? pricing.photo_per_image
  const generationCost = unitCost * batchCount

  const clearFiles = useCallback(() => {
    setFiles([])
    setProductName('')
    setCategory('other')
    setPhotoPrompt('')
    setCardCopy('')
    setVideoDescription('')
    setSceneStyle('from_wishes')
    setVideoDuration(5)
    setContentType('photo')
    setBatchCount(1)
    setAdvancedOpen(false)
    setOutputAspectRatio('3:4')
  }, [])

  const userWishesForApi = useMemo(() => {
    if (contentType === 'photo') return photoPrompt.trim()
    if (contentType === 'card') return cardCopy.trim()
    return videoDescription.trim()
  }, [contentType, photoPrompt, cardCopy, videoDescription])

  const templateSceneCost = useMemo(() => {
    const m = pricing.photo_scene_credits ?? DEFAULT_PHOTO_FLOW.photo_scene_credits
    return m?.studio ?? pricing.photo_per_image
  }, [pricing])

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

    const sceneForApi = contentType === 'photo' ? sceneStyle : 'studio'

    setLoading(true)
    try {
      const { data: createRes } = await photoFlowApi.createFromPhoto(files, {
        productName: name,
        category,
      })
      const projectId = createRes.data.id

      const payload = {
        content_type: contentType,
        scene_style: sceneForApi,
        user_wishes: userWishesForApi || undefined,
        product_name: name,
        product_category: category,
        quantity: batchCount,
        ...(contentType === 'video' ? { video_duration_seconds: videoDuration } : {}),
        ...(contentType === 'photo' || contentType === 'card' ? { aspect_ratio: outputAspectRatio } : {}),
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

            <button
              type="button"
              onClick={handleTemplateGenerate}
              disabled={loading || !templateReady}
              className="btn-primary w-full flex items-center justify-center gap-2 py-3.5 mt-6"
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
    <div className="max-w-xl mx-auto space-y-8 pb-12">
      <div>
        <h1 className="text-3xl font-bold text-white mb-1">{t('photoFlow.title')}</h1>
        <p className="text-gray-400">{t('photoFlow.subtitle')}</p>
      </div>

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

        <div className="mt-6 space-y-4">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{t('photoFlow.thisIs')}</p>
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

        {step1Ready && (
          <p className="text-xs text-brand-400/90 mt-4">{t('photoFlow.step1ReadyHint')}</p>
        )}
      </section>

      <section className={`card relative border border-white/10 rounded-3xl bg-gray-900/40 ${!step1Ready ? 'opacity-55' : ''}`}>
        <div className="flex items-start justify-between gap-4 mb-5">
          <h2 className="text-lg font-semibold text-white">{t('photoFlow.configureGeneration')}</h2>
          <span className="text-2xl font-black italic text-gray-500 tabular-nums" aria-hidden>
            02
          </span>
        </div>

        {!step1Ready && (
          <div className="absolute inset-0 z-10 flex items-center justify-center rounded-3xl bg-gray-950/75 backdrop-blur-[2px] px-6">
            <p className="text-sm text-center text-gray-300 max-w-xs">{t('photoFlow.step2Locked')}</p>
          </div>
        )}

        <div className={!step1Ready ? 'pointer-events-none select-none' : ''}>
          <section className="mb-6">
            <h3 className="text-sm font-semibold text-white mb-3">{t('photoFlow.contentType')}</h3>
            <div className="flex rounded-xl bg-gray-900/80 p-1 border border-white/10 gap-1">
              {contentTypes.map(({ id, labelKey }) => {
                const Icon = CONTENT_ICONS[id]
                const active = contentType === id
                return (
                  <button
                    key={id}
                    type="button"
                    onClick={() => setContentType(id)}
                    className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-1 rounded-lg text-sm font-medium transition-all min-w-0
                      ${active ? 'bg-white text-gray-950 shadow' : 'text-gray-400 hover:text-white'}`}
                  >
                    <Icon className="w-4 h-4 shrink-0" />
                    <span className="truncate">{t(labelKey)}</span>
                  </button>
                )
              })}
            </div>
          </section>

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
                        <div className="aspect-[4/3] rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 mb-2 border border-white/5 shrink-0" />
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

          {contentType === 'card' && (
            <section className="mb-6">
              <h3 className="text-sm font-semibold text-white mb-1">{t('photoFlow.card.copyTitle')}</h3>
              <p className="text-xs text-emerald-400/90 mb-2 leading-relaxed border border-emerald-500/25 rounded-lg px-2.5 py-1.5 bg-emerald-500/5">
                {t('photoFlow.card.onImageCopyNote')}
              </p>
              <p className="text-sm text-gray-500 mb-3">{t('photoFlow.card.copyHint')}</p>
              <textarea
                className="input-field min-h-[120px] resize-y"
                placeholder={t('photoFlow.card.copyPlaceholder')}
                value={cardCopy}
                onChange={(e) => setCardCopy(e.target.value.slice(0, 2000))}
                maxLength={2000}
              />
              <p className="text-xs text-gray-600 text-right mt-1">{cardCopy.length}/2000</p>
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

          {(contentType === 'photo' || contentType === 'card') && (
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

          <div className="flex flex-row gap-2 items-stretch">
            <button
              type="button"
              onClick={handleGenerate}
              disabled={loading || !step1Ready}
              className="btn-primary flex-1 flex items-center justify-center gap-2 py-3 min-w-0"
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
                disabled={loading || !step1Ready}
                className="input-field w-full py-3 min-h-[48px] text-center"
              >
                {Array.from({ length: 10 }, (_, i) => i + 1).map((n) => (
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
  )
}
