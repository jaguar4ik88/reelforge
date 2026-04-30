import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  LayoutGrid,
  Info,
  Images,
  AlignLeft,
  Upload,
  X,
  LayoutTemplate,
} from 'lucide-react'
import toast from 'react-hot-toast'
import { infographicApi } from '../services/api'
import { APP_BASE } from '../constants/routes'
import { useAuthContext } from '../context/AuthContext'

const FALLBACK_CARD_BY_EXAMPLE = 2
const FALLBACK_CARD_BY_PROMPT = 1

const GENERATION_ASPECT_RATIOS = ['9:16', '3:4', '1:1', '4:3', '16:9']

const TEMPLATE_CARD_ASPECT_RATIOS = ['1:1', '4:5', '9:16', '16:9']

function exampleAspectRatioToApi(ui) {
  const m = {
    '1:1': '1:1',
    '9:16': '9:16',
    '16:9': '16:9',
    '3:4': '4:5',
    '4:3': '16:9',
  }
  return m[ui] ?? '1:1'
}

const SELECT_CHEVRON_BG =
  "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E\")"

function isLikelyImageFile(file) {
  if (!file) return false
  const type = file.type?.toLowerCase() ?? ''
  if (type.startsWith('image/')) return true
  const name = file.name?.toLowerCase() ?? ''
  return /\.(jpe?g|png|gif|webp|bmp|svg|heic|heif|avif)$/i.test(name)
}

export default function ProductCardPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { user, refreshUser } = useAuthContext()
  const fileInputRef = useRef(null)
  const styleExampleInputRef = useRef(null)
  const productPreviewRef = useRef(null)
  const styleExamplePreviewRef = useRef(null)
  const templateExamplePreviewRef = useRef(null)
  const exampleCardFileInputRef = useRef(null)

  const [sidebarTab, setSidebarTab] = useState(/** @type {'example' | 'template' | 'prompt'} */ ('example'))
  const [productName, setProductName] = useState('')
  const [templateCharacteristics, setTemplateCharacteristics] = useState('')
  const [infographicPrompt, setInfographicPrompt] = useState('')
  const [productPreviewUrl, setProductPreviewUrl] = useState(/** @type {string | null} */ (null))

  const [styleExamplePreviewUrl, setStyleExamplePreviewUrl] = useState(/** @type {string | null} */ (null))
  const [exampleProductTitle, setExampleProductTitle] = useState('')
  const [exampleCharacteristics, setExampleCharacteristics] = useState('')
  const [generationAspectRatio, setGenerationAspectRatio] = useState('3:4')
  const [templateCardAspectRatio, setTemplateCardAspectRatio] = useState('1:1')
  const [productFile, setProductFile] = useState(/** @type {File | null} */ (null))
  const [templateExampleFile, setTemplateExampleFile] = useState(/** @type {File | null} */ (null))
  const [templateExamplePreviewUrl, setTemplateExamplePreviewUrl] = useState(/** @type {string | null} */ (null))
  const [selectedCardGalleryFilename, setSelectedCardGalleryFilename] = useState(/** @type {string | null} */ (null))
  const [exampleGalleryFilename, setExampleGalleryFilename] = useState(/** @type {string | null} */ (null))
  const [cardGalleryPickerContext, setCardGalleryPickerContext] = useState(/** @type {'template' | 'example'} */ ('template'))
  const [cardGalleryModalOpen, setCardGalleryModalOpen] = useState(false)
  const [cardGalleryItems, setCardGalleryItems] = useState(/** @type {{ filename: string, url: string }[]} */ ([]))
  const [cardGalleryLoading, setCardGalleryLoading] = useState(false)
  const [templateGenerating, setTemplateGenerating] = useState(false)
  const [styleExampleFile, setStyleExampleFile] = useState(/** @type {File | null} */ (null))

  useEffect(
    () => () => {
      if (productPreviewRef.current) {
        URL.revokeObjectURL(productPreviewRef.current)
        productPreviewRef.current = null
      }
      if (styleExamplePreviewRef.current) {
        URL.revokeObjectURL(styleExamplePreviewRef.current)
        styleExamplePreviewRef.current = null
      }
      if (templateExamplePreviewRef.current) {
        URL.revokeObjectURL(templateExamplePreviewRef.current)
        templateExamplePreviewRef.current = null
      }
    },
    []
  )

  const revokeStyleExamplePreview = useCallback(() => {
    if (styleExamplePreviewRef.current) {
      URL.revokeObjectURL(styleExamplePreviewRef.current)
      styleExamplePreviewRef.current = null
    }
    setStyleExamplePreviewUrl(null)
    setStyleExampleFile(null)
  }, [])

  const revokeTemplateExamplePreview = useCallback(() => {
    if (templateExamplePreviewRef.current) {
      URL.revokeObjectURL(templateExamplePreviewRef.current)
      templateExamplePreviewRef.current = null
    }
    setTemplateExamplePreviewUrl(null)
    setTemplateExampleFile(null)
  }, [])

  const onPickImage = useCallback(() => {
    fileInputRef.current?.click()
  }, [])

  const onPickStyleExample = useCallback(() => {
    styleExampleInputRef.current?.click()
  }, [])

  const onStyleExampleFiles = useCallback(
    (e) => {
      const file = e.target.files?.[0]
      e.target.value = ''
      if (!isLikelyImageFile(file)) {
        toast.error(t('infographic.imageTypeInvalid'))
        return
      }
      setExampleGalleryFilename(null)
      revokeStyleExamplePreview()
      const url = URL.createObjectURL(file)
      styleExamplePreviewRef.current = url
      setStyleExamplePreviewUrl(url)
      setStyleExampleFile(file)
    },
    [revokeStyleExamplePreview, t]
  )

  const onProductFiles = useCallback(
    (e) => {
      const file = e.target.files?.[0]
      e.target.value = ''
      if (!isLikelyImageFile(file)) {
        toast.error(t('infographic.imageTypeInvalid'))
        return
      }
      const url = URL.createObjectURL(file)
      if (productPreviewRef.current) {
        URL.revokeObjectURL(productPreviewRef.current)
      }
      productPreviewRef.current = url
      setProductPreviewUrl(url)
      setProductFile(file)
    },
    [t]
  )

  const onPickTemplateExampleFile = useCallback(() => {
    exampleCardFileInputRef.current?.click()
  }, [])

  const onTemplateExampleFiles = useCallback(
    (e) => {
      const file = e.target.files?.[0]
      e.target.value = ''
      if (!isLikelyImageFile(file)) {
        toast.error(t('infographic.imageTypeInvalid'))
        return
      }
      setSelectedCardGalleryFilename(null)
      revokeTemplateExamplePreview()
      const url = URL.createObjectURL(file)
      templateExamplePreviewRef.current = url
      setTemplateExamplePreviewUrl(url)
      setTemplateExampleFile(file)
    },
    [revokeTemplateExamplePreview, t]
  )

  const openCardExamplesGallery = useCallback(
    async (pickerContext) => {
      setCardGalleryPickerContext(pickerContext)
      setCardGalleryModalOpen(true)
      setCardGalleryLoading(true)
      try {
        const { data: res } = await infographicApi.cardExamples()
        const items = res?.data?.items ?? []
        setCardGalleryItems(Array.isArray(items) ? items : [])
      } catch {
        toast.error(t('infographic.cardGalleryLoadError'))
        setCardGalleryItems([])
      } finally {
        setCardGalleryLoading(false)
      }
    },
    [t]
  )

  const selectCardGalleryItem = useCallback(
    (filename) => {
      if (cardGalleryPickerContext === 'example') {
        revokeStyleExamplePreview()
        const item = cardGalleryItems.find((i) => i.filename === filename)
        setExampleGalleryFilename(filename)
        setStyleExamplePreviewUrl(item?.url ?? null)
      } else {
        setSelectedCardGalleryFilename(filename)
        revokeTemplateExamplePreview()
      }
      setCardGalleryModalOpen(false)
    },
    [
      cardGalleryItems,
      cardGalleryPickerContext,
      revokeStyleExamplePreview,
      revokeTemplateExamplePreview,
    ]
  )

  const photoFlow = user?.credits?.photo_flow
  const cardCreditsByExample =
    photoFlow?.card_by_example ?? photoFlow?.card_per_image ?? FALLBACK_CARD_BY_EXAMPLE
  const cardCreditsByPrompt =
    photoFlow?.card_by_prompt ?? photoFlow?.card_per_image ?? FALLBACK_CARD_BY_PROMPT

  const handleTemplateGenerateByExample = useCallback(async () => {
    if (!productFile || !productPreviewUrl) {
      toast.error(t('productCard.needProductPhoto'))
      return
    }
    if (!templateExampleFile && !selectedCardGalleryFilename) {
      toast.error(t('infographic.needExampleCard'))
      return
    }
    const title = productName.trim()
    if (!title) {
      toast.error(t('infographic.needProductTitle'))
      return
    }
    setTemplateGenerating(true)
    try {
      const form = new FormData()
      form.append('product_image', productFile)
      form.append('title', title.slice(0, 200))
      form.append('characteristics', templateCharacteristics)
      form.append('aspect_ratio', templateCardAspectRatio)
      if (templateExampleFile) {
        form.append('example_image', templateExampleFile)
      } else if (selectedCardGalleryFilename) {
        form.append('example_filename', selectedCardGalleryFilename)
      }
      const { data: res } = await infographicApi.generateByExample(form)
      const project = res?.data?.project
      const projectId = project?.id ?? project?.data?.id
      await refreshUser?.()
      toast.success(res?.message ?? t('infographic.generateQueued'))
      if (projectId != null) {
        navigate(`${APP_BASE}/projects/${projectId}`)
      }
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setTemplateGenerating(false)
    }
  }, [
    productFile,
    productPreviewUrl,
    templateExampleFile,
    selectedCardGalleryFilename,
    productName,
    templateCharacteristics,
    templateCardAspectRatio,
    refreshUser,
    navigate,
    t,
  ])

  const handleExampleGenerate = useCallback(async () => {
    if (!productFile || !productPreviewUrl) {
      toast.error(t('productCard.needProductPhoto'))
      return
    }
    if (!styleExampleFile && !exampleGalleryFilename) {
      toast.error(t('infographic.exampleNeedReference'))
      return
    }
    const title = exampleProductTitle.trim()
    if (!title) {
      toast.error(t('infographic.needProductTitle'))
      return
    }
    setTemplateGenerating(true)
    try {
      const form = new FormData()
      form.append('product_image', productFile)
      form.append('title', title.slice(0, 200))
      form.append('characteristics', exampleCharacteristics)
      form.append('aspect_ratio', exampleAspectRatioToApi(generationAspectRatio))
      if (styleExampleFile) {
        form.append('example_image', styleExampleFile)
      } else if (exampleGalleryFilename) {
        form.append('example_filename', exampleGalleryFilename)
      }
      const { data: res } = await infographicApi.generateByExample(form)
      const project = res?.data?.project
      const projectId = project?.id ?? project?.data?.id
      await refreshUser?.()
      toast.success(res?.message ?? t('infographic.generateQueued'))
      if (projectId != null) {
        navigate(`${APP_BASE}/projects/${projectId}`)
      }
    } catch (err) {
      const msg = err.response?.data?.message ?? t('common.error')
      toast.error(msg)
    } finally {
      setTemplateGenerating(false)
    }
  }, [
    productFile,
    productPreviewUrl,
    styleExampleFile,
    exampleGalleryFilename,
    exampleProductTitle,
    exampleCharacteristics,
    generationAspectRatio,
    refreshUser,
    navigate,
    t,
  ])

  const onPromptGenerateClick = useCallback(() => {
    if (!productPreviewUrl) {
      toast.error(t('productCard.needProductPhoto'))
      return
    }
    toast(t('infographic.promptComingSoon'), { duration: 5000 })
  }, [productPreviewUrl, t])

  const tabBtn = (tabId, labelKey, Icon, isActive) => (
    <button
      type="button"
      id={`product-card-tab-${tabId}`}
      role="tab"
      aria-selected={isActive}
      onClick={() => setSidebarTab(tabId)}
      className={`flex-1 flex items-center justify-center gap-2 py-3 px-2 rounded-lg text-sm font-semibold transition-all min-w-0 border ${
        isActive
          ? 'bg-brand-600/20 text-brand-300 border-brand-500/30'
          : 'text-gray-400 border-transparent hover:text-white hover:bg-white/5'
      }`}
    >
      <Icon className="w-4 h-4 shrink-0" aria-hidden />
      <span className="truncate">{t(labelKey)}</span>
    </button>
  )

  const generationSizeSelect = (
    <div className="space-y-2">
      <label className="text-xs font-medium text-gray-300 flex items-center gap-1 uppercase tracking-wide">
        {t('infographic.exampleSizeLabel')}
        <Info className="w-3 h-3 text-gray-600 normal-case" aria-hidden />
      </label>
      <select
        value={generationAspectRatio}
        onChange={(e) => setGenerationAspectRatio(e.target.value)}
        className="input-field text-sm py-2.5 cursor-pointer appearance-none bg-[length:1rem] bg-[right_0.75rem_center] bg-no-repeat pr-10"
        style={{ backgroundImage: SELECT_CHEVRON_BG }}
      >
        {GENERATION_ASPECT_RATIOS.map((ratio) => (
          <option key={ratio} value={ratio} className="bg-gray-900 text-white">
            {ratio}
          </option>
        ))}
      </select>
    </div>
  )

  const promptGenerateButton = (
    <button
      type="button"
      onClick={onPromptGenerateClick}
      className="btn-primary w-full justify-center py-3 text-sm font-semibold disabled:opacity-45 disabled:cursor-not-allowed"
      disabled={!productPreviewUrl}
      title={!productPreviewUrl ? t('productCard.needProductPhoto') : undefined}
    >
      {t('infographic.generateCta', { credits: cardCreditsByPrompt })}
    </button>
  )

  const productPreviewBlock = (
    <div className="space-y-2">
      <p className="text-xs font-medium text-gray-300">{t('infographic.yourProduct')}</p>
      <button
        type="button"
        onClick={onPickImage}
        className="relative w-full aspect-square max-h-44 rounded-xl border border-white/10 bg-white/5 overflow-hidden flex items-center justify-center hover:border-brand-500/40 transition-colors"
      >
        {productPreviewUrl ? (
          <img src={productPreviewUrl} alt="" className="w-full h-full object-contain" />
        ) : (
          <span className="text-xs text-gray-500 px-4 text-center">{t('infographic.tapToUploadProduct')}</span>
        )}
      </button>
    </div>
  )

  return (
    <div className="max-w-6xl mx-auto space-y-6 lg:space-y-8 pb-12 px-1 sm:px-0 min-h-[calc(100dvh-4rem)]">
      {cardGalleryModalOpen && (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          aria-labelledby="product-card-gallery-title"
          onClick={() => setCardGalleryModalOpen(false)}
        >
          <div
            className="w-full max-w-2xl max-h-[85vh] rounded-2xl border border-white/10 bg-gray-900 shadow-xl p-4 flex flex-col gap-3"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between gap-2 shrink-0">
              <h3 id="product-card-gallery-title" className="text-sm font-semibold text-white">
                {t('infographic.cardGalleryTitle')}
              </h3>
              <button
                type="button"
                onClick={() => setCardGalleryModalOpen(false)}
                className="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/10"
                aria-label="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <div className="overflow-y-auto min-h-0 pr-1">
              {cardGalleryLoading ? (
                <p className="text-sm text-gray-400 py-8 text-center">{t('infographic.cardGalleryLoading')}</p>
              ) : cardGalleryItems.length === 0 ? (
                <p className="text-sm text-gray-400 py-8 text-center">{t('infographic.cardGalleryEmpty')}</p>
              ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {cardGalleryItems.map((item) => {
                    const gallerySelected =
                      cardGalleryPickerContext === 'example'
                        ? exampleGalleryFilename === item.filename
                        : selectedCardGalleryFilename === item.filename
                    return (
                      <button
                        key={item.filename}
                        type="button"
                        onClick={() => selectCardGalleryItem(item.filename)}
                        className={`rounded-xl overflow-hidden border-2 bg-white/5 aspect-[4/5] transition-colors ${
                          gallerySelected
                            ? 'border-brand-500 ring-2 ring-brand-500/40'
                            : 'border-white/10 hover:border-brand-500/40'
                        }`}
                      >
                        <img src={item.url} alt="" className="w-full h-full object-cover" />
                      </button>
                    )
                  })}
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      <header className="flex flex-col gap-4">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div className="flex items-start gap-3 min-w-0">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-900/50 border border-brand-500/30">
              <LayoutTemplate className="h-5 w-5 text-brand-400" aria-hidden />
            </div>
            <div className="min-w-0">
              <h1 className="text-2xl sm:text-3xl font-bold text-white">{t('productCard.title')}</h1>
              <p className="text-sm text-gray-400 mt-1 max-w-2xl">{t('productCard.subtitle')}</p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => toast(t('infographic.howItWorksHint'), { duration: 6500 })}
            className="shrink-0 self-start flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-brand-300 border border-brand-500/35 bg-brand-600/10 hover:bg-brand-600/20 transition-colors"
          >
            <Info className="w-4 h-4" aria-hidden />
            {t('infographic.howItWorks')}
          </button>
        </div>

        <div>
          <div className="rounded-xl bg-white/5 p-1 flex gap-0.5 border border-white/10 shadow-sm" role="tablist">
            {tabBtn('example', 'infographic.tabExample', Images, sidebarTab === 'example')}
            {tabBtn('template', 'infographic.tabTemplate', LayoutGrid, sidebarTab === 'template')}
            {tabBtn('prompt', 'infographic.tabPrompt', AlignLeft, sidebarTab === 'prompt')}
          </div>
        </div>
      </header>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-start">
        <section className="card relative border border-white/10 rounded-3xl bg-gray-900/40 p-5 sm:p-6">
          <div className="flex items-start justify-between gap-4 mb-5">
            <h2 className="text-lg font-semibold text-white">{t('productCard.photosStepTitle')}</h2>
            <span className="text-2xl font-black italic text-brand-400/90 tabular-nums" aria-hidden>
              01
            </span>
          </div>

          <div className="space-y-4">
            {productPreviewBlock}

            {sidebarTab === 'example' && (
              <>
                <p className="text-xs font-medium text-gray-200 flex items-center gap-1 pt-1">
                  {t('infographic.exampleStyleHeading')}
                  <Info className="w-3 h-3 text-gray-500 shrink-0" aria-hidden />
                </p>
                <button
                  type="button"
                  onClick={onPickStyleExample}
                  className="relative w-full min-h-[140px] rounded-xl border-2 border-dashed border-white/20 bg-white/[0.03] hover:border-brand-500/40 hover:bg-white/[0.05] transition-colors flex flex-col items-center justify-center gap-2 px-4 py-6 text-center"
                >
                  {styleExamplePreviewUrl ? (
                    <img src={styleExamplePreviewUrl} alt="" className="max-h-36 w-full object-contain rounded-lg" />
                  ) : (
                    <>
                      <Upload className="w-8 h-8 text-gray-500" aria-hidden />
                      <span className="text-sm font-medium text-white">{t('infographic.exampleUploadTitle')}</span>
                      <span className="text-[11px] text-gray-500 max-w-[240px]">{t('infographic.exampleUploadSub')}</span>
                    </>
                  )}
                </button>
                <button
                  type="button"
                  onClick={() => openCardExamplesGallery('example')}
                  className="btn-secondary w-full flex flex-col items-center justify-center gap-1.5 text-sm py-3"
                >
                  <Images className="w-5 h-5 shrink-0" aria-hidden />
                  <span>{t('infographic.cardGalleryButton')}</span>
                </button>
                <input
                  ref={styleExampleInputRef}
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={onStyleExampleFiles}
                />
              </>
            )}

            {sidebarTab === 'template' && (
              <>
                <p className="text-xs font-medium text-gray-300 flex items-center gap-1 pt-1">
                  {t('infographic.exampleCardLabel')}
                  <Info className="w-3 h-3 text-gray-600" aria-hidden />
                </p>
                <button
                  type="button"
                  onClick={onPickTemplateExampleFile}
                  className="relative w-full min-h-[120px] rounded-xl border-2 border-dashed border-white/20 bg-white/[0.03] hover:border-brand-500/40 transition-colors flex flex-col items-center justify-center gap-2 px-4 py-5 text-center"
                >
                  {templateExamplePreviewUrl ? (
                    <img src={templateExamplePreviewUrl} alt="" className="max-h-32 w-full object-contain rounded-lg" />
                  ) : selectedCardGalleryFilename ? (
                    <span className="text-xs text-brand-300 px-2">{selectedCardGalleryFilename}</span>
                  ) : (
                    <>
                      <Upload className="w-7 h-7 text-gray-500" aria-hidden />
                      <span className="text-xs text-gray-500">{t('infographic.exampleCardUploadHint')}</span>
                    </>
                  )}
                </button>
                <button
                  type="button"
                  onClick={() => openCardExamplesGallery('template')}
                  className="btn-secondary w-full flex flex-col items-center justify-center gap-1.5 text-sm py-3"
                >
                  <Images className="w-5 h-5 shrink-0" aria-hidden />
                  <span>{t('infographic.cardGalleryButton')}</span>
                </button>
                <input
                  ref={exampleCardFileInputRef}
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={onTemplateExampleFiles}
                />
              </>
            )}

            {sidebarTab === 'prompt' && (
              <p className="text-sm text-gray-500 leading-relaxed pt-1">{t('infographic.promptHint')}</p>
            )}
          </div>

          <input ref={fileInputRef} type="file" accept="image/*" className="hidden" onChange={onProductFiles} />
        </section>

        <section className="card relative border border-white/10 rounded-3xl bg-gray-900/40 flex-1 min-w-0 p-5 sm:p-6 lg:sticky lg:top-24">
          <div className="flex items-start justify-between gap-4 mb-5">
            <h2 className="text-lg font-semibold text-white">{t('productCard.formStepTitle')}</h2>
            <span className="text-2xl font-black italic text-gray-500 tabular-nums" aria-hidden>
              02
            </span>
          </div>

          {sidebarTab === 'example' && (
            <div className="space-y-4">
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300 flex items-center gap-1">
                  {t('infographic.exampleTitleLabel')}
                  <Info className="w-3 h-3 text-gray-600" aria-hidden />
                </label>
                <input
                  type="text"
                  value={exampleProductTitle}
                  onChange={(e) => setExampleProductTitle(e.target.value.slice(0, 200))}
                  placeholder={t('infographic.exampleTitlePlaceholder')}
                  className="input-field text-sm py-2.5"
                  maxLength={200}
                />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300 flex items-center gap-1">
                  {t('infographic.exampleCharacteristicsLabel')}
                  <Info className="w-3 h-3 text-gray-600" aria-hidden />
                </label>
                <textarea
                  value={exampleCharacteristics}
                  onChange={(e) => setExampleCharacteristics(e.target.value.slice(0, 1000))}
                  placeholder={t('infographic.exampleCharacteristicsPlaceholder')}
                  rows={4}
                  className="input-field text-sm py-2.5 resize-y min-h-[6rem]"
                  maxLength={1000}
                />
              </div>
              {generationSizeSelect}
              <button
                type="button"
                onClick={handleExampleGenerate}
                disabled={templateGenerating}
                className="btn-primary w-full justify-center py-3 text-sm font-semibold disabled:opacity-45 disabled:cursor-not-allowed"
              >
                {templateGenerating
                  ? t('infographic.generating')
                    : t('infographic.generateCardCta', { credits: cardCreditsByExample })}
              </button>
            </div>
          )}

          {sidebarTab === 'template' && (
            <div className="space-y-4">
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300 flex items-center gap-1">
                  {t('infographic.productNameRequired')}
                  <Info className="w-3 h-3 text-gray-600" aria-hidden />
                </label>
                <div className="relative">
                  <input
                    type="text"
                    value={productName}
                    onChange={(e) => setProductName(e.target.value.slice(0, 200))}
                    placeholder={t('infographic.productNamePlaceholder')}
                    className="input-field text-sm py-2.5 pr-16"
                    maxLength={200}
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-500 tabular-nums pointer-events-none">
                    {productName.length}/200
                  </span>
                </div>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300 flex items-center gap-1">
                  {t('infographic.templateCharacteristicsEachLine')}
                  <Info className="w-3 h-3 text-gray-600" aria-hidden />
                </label>
                <textarea
                  value={templateCharacteristics}
                  onChange={(e) => setTemplateCharacteristics(e.target.value.slice(0, 2000))}
                  placeholder={t('infographic.templateCharacteristicsPlaceholder')}
                  rows={5}
                  className="input-field text-sm py-2.5 resize-y min-h-[7rem]"
                  maxLength={2000}
                />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300 flex items-center gap-1 uppercase tracking-wide">
                  {t('infographic.cardOutputSizeLabel')}
                  <Info className="w-3 h-3 text-gray-600 normal-case" aria-hidden />
                </label>
                <select
                  value={templateCardAspectRatio}
                  onChange={(e) => setTemplateCardAspectRatio(e.target.value)}
                  className="input-field text-sm py-2.5 cursor-pointer appearance-none bg-[length:1rem] bg-[right_0.75rem_center] bg-no-repeat pr-10"
                  style={{ backgroundImage: SELECT_CHEVRON_BG }}
                >
                  {TEMPLATE_CARD_ASPECT_RATIOS.map((ratio) => (
                    <option key={ratio} value={ratio} className="bg-gray-900 text-white">
                      {ratio}
                    </option>
                  ))}
                </select>
              </div>
              <button
                type="button"
                onClick={handleTemplateGenerateByExample}
                disabled={templateGenerating}
                className="btn-primary w-full justify-center py-3 text-sm font-semibold disabled:opacity-45 disabled:cursor-not-allowed"
              >
                {templateGenerating
                  ? t('infographic.generating')
                    : t('infographic.generateCardCta', { credits: cardCreditsByExample })}
              </button>
            </div>
          )}

          {sidebarTab === 'prompt' && (
            <div className="space-y-4">
              <div className="space-y-2">
                <label className="text-xs font-medium text-gray-300">{t('infographic.promptLabel')}</label>
                <textarea
                  lang="en"
                  dir="ltr"
                  spellCheck
                  inputMode="text"
                  autoComplete="off"
                  value={infographicPrompt}
                  onChange={(e) => setInfographicPrompt(e.target.value.slice(0, 2000))}
                  placeholder={t('infographic.promptPlaceholderEn')}
                  rows={8}
                  className="input-field text-sm py-3 resize-y min-h-[10rem]"
                  maxLength={2000}
                />
                <p className="text-[11px] text-amber-400/90 leading-relaxed">{t('infographic.promptEnglishOnly')}</p>
              </div>
              {generationSizeSelect}
              {promptGenerateButton}
            </div>
          )}
        </section>
      </div>
    </div>
  )
}
