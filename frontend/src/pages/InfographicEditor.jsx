import { useCallback, useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  Download,
  LayoutTemplate,
  Trash2,
  ArrowUp,
  ArrowDown,
  ImagePlus,
  Ban,
  Minus,
  Plus,
  Type,
  Heading,
  FlipHorizontal2,
  Layers2,
} from 'lucide-react'
import { Canvas, FabricImage, IText, Textbox, Shadow } from 'fabric'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'
import { APP_BASE } from '../constants/routes'
import { resolveBackendAssetUrlForCanvas } from '../utils/apiBase'
import { infographicApi } from '../services/api'

/** Editor viewport (3:4). Export uses `multiplier` for full-resolution PNG. */
const VIEW_W = 540
const VIEW_H = 720
/** 1080×1440 output when downloading. */
const EXPORT_MULT = 2

const LAYER_TEMPLATE = 'template'
const LAYER_TEMPLATE_SLOT = 'template-slot'
const LAYER_PRODUCT = 'product'
const LAYER_USER = 'user'

/** @param {import('fabric').FabricObject} obj */
function setInfographicLayer(obj, layer) {
  obj.set('infographicLayer', layer)
}

/** @param {import('fabric').Canvas} canvas */
function removeObjectsByLayer(canvas, layer) {
  canvas.getObjects().forEach((o) => {
    if (o.get('infographicLayer') === layer) {
      canvas.remove(o)
    }
  })
}

/** Remove template background + text slots from manifest (keep product & user objects). */
/** @param {import('fabric').Canvas} canvas */
function removeTemplateStack(canvas) {
  removeObjectsByLayer(canvas, LAYER_TEMPLATE)
  removeObjectsByLayer(canvas, LAYER_TEMPLATE_SLOT)
}

/** @param {import('fabric').Canvas} canvas */
function bringProductLayersToFront(canvas) {
  canvas.getObjects().forEach((o) => {
    if (o.get('infographicLayer') === LAYER_PRODUCT) {
      canvas.bringObjectToFront(o)
    }
  })
}

/**
 * Build editable Fabric text from server manifest entry (templates-layout.json).
 * @param {Record<string, unknown>} spec
 */
function fabricObjectFromTextSpec(spec) {
  const left = Math.max(0, (Number(spec.leftRatio) || 0.08) * VIEW_W)
  const top = Math.max(0, (Number(spec.topRatio) || 0.08) * VIEW_H)
  const fontSize = Math.max(8, Math.min(160, Number(spec.fontSize) || 16))
  const fill = typeof spec.fill === 'string' ? spec.fill : '#ffffff'
  const fontWeight = String(spec.fontWeight ?? '400')
  const fontFamily =
    typeof spec.fontFamily === 'string'
      ? spec.fontFamily
      : 'system-ui, -apple-system, "Segoe UI", sans-serif'
  const content = typeof spec.content === 'string' ? spec.content : ''
  const useShadow = spec.shadow !== false
  const shadow = useShadow
    ? new Shadow({ color: 'rgba(0,0,0,0.42)', blur: 5, offsetX: 0, offsetY: 2 })
    : undefined

  const kind = typeof spec.kind === 'string' ? spec.kind : ''
  const widthRatio = Number(spec.widthRatio) || 0
  const useTextbox = kind === 'textbox' || widthRatio > 0

  if (useTextbox) {
    const w = VIEW_W * (widthRatio > 0 ? widthRatio : 0.42)
    const tb = new Textbox(content, {
      left,
      top,
      width: Math.max(48, Math.min(w, VIEW_W - left - 4)),
      fontSize,
      fill,
      fontWeight,
      fontFamily,
      shadow,
    })
    setInfographicLayer(tb, LAYER_TEMPLATE_SLOT)
    return tb
  }

  const it = new IText(content, {
    left,
    top,
    fontSize,
    fill,
    fontWeight,
    fontFamily,
    shadow,
  })
  setInfographicLayer(it, LAYER_TEMPLATE_SLOT)
  return it
}

/** Editable template image: cover canvas, user may move/scale/delete. */
/** @param {import('fabric').FabricImage} img */
function layoutTemplateCover(img) {
  const iw = img.width || 1
  const ih = img.height || 1
  const scale = Math.max(VIEW_W / iw, VIEW_H / ih)
  img.scale(scale)
  img.set({
    left: VIEW_W / 2,
    top: VIEW_H / 2,
    originX: 'center',
    originY: 'center',
  })
}

function isLikelyImageFile(file) {
  if (!file) return false
  const type = file.type?.toLowerCase() ?? ''
  if (type.startsWith('image/')) return true
  const name = file.name?.toLowerCase() ?? ''
  return /\.(jpe?g|png|gif|webp|bmp|svg|heic|heif|avif)$/i.test(name)
}

export default function InfographicEditor() {
  const { t } = useTranslation()
  const canvasElRef = useRef(null)
  const fabricRef = useRef(null)
  const productFileInputRef = useRef(null)
  const decorativeFileInputRef = useRef(null)
  const productPreviewRef = useRef(null)

  const [ready, setReady] = useState(false)
  const [zoom, setZoom] = useState(1)
  const [cutOutEnabled, setCutOutEnabled] = useState(true)
  const [cutoutBusy, setCutoutBusy] = useState(false)
  const [cardGalleryItems, setCardGalleryItems] = useState(
    /** @type {{ filename: string, url: string, editor?: { texts?: Record<string, unknown>[] } }[]} */ ([])
  )
  const [cardGalleryLoading, setCardGalleryLoading] = useState(false)
  const [selectedReferenceFilename, setSelectedReferenceFilename] = useState(/** @type {string | null} */ (null))

  useEffect(() => {
    const el = canvasElRef.current
    if (!el) return undefined

    const canvas = new Canvas(el, {
      width: VIEW_W,
      height: VIEW_H,
      backgroundColor: '#f8fafc',
      preserveObjectStacking: true,
    })

    fabricRef.current = canvas
    setReady(true)

    return () => {
      setReady(false)
      canvas.dispose()
      fabricRef.current = null
    }
  }, [])

  useEffect(() => {
    let cancelled = false
    setCardGalleryLoading(true)
    infographicApi
      .canvasTemplates()
      .then(({ data: res }) => {
        if (cancelled) return
        const items = res?.data?.items ?? []
        setCardGalleryItems(Array.isArray(items) ? items : [])
      })
      .catch(() => {
        if (!cancelled) {
          toast.error(t('infographic.cardGalleryLoadError'))
          setCardGalleryItems([])
        }
      })
      .finally(() => {
        if (!cancelled) setCardGalleryLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [t])

  useEffect(
    () => () => {
      if (productPreviewRef.current) {
        URL.revokeObjectURL(productPreviewRef.current)
        productPreviewRef.current = null
      }
    },
    []
  )

  const getCanvas = useCallback(() => fabricRef.current, [])

  const setPlainBackground = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    removeTemplateStack(canvas)
    canvas.backgroundColor = '#f8fafc'
    setSelectedReferenceFilename(null)
    canvas.renderAll()
  }, [getCanvas])

  const applyReferenceBackground = useCallback(
    async (/** @type {{ url: string, filename: string, editor?: { texts?: Record<string, unknown>[] } }} */ item) => {
      const canvas = getCanvas()
      if (!canvas) return
      const { url, filename, editor } = item
      removeTemplateStack(canvas)
      try {
        const loadUrl = resolveBackendAssetUrlForCanvas(url)
        const img = await FabricImage.fromURL(loadUrl, { crossOrigin: 'anonymous' })
        layoutTemplateCover(img)
        setInfographicLayer(img, LAYER_TEMPLATE)
        canvas.add(img)
        canvas.sendObjectToBack(img)

        const texts = editor?.texts
        if (Array.isArray(texts)) {
          for (const row of texts) {
            if (row && typeof row === 'object') {
              canvas.add(fabricObjectFromTextSpec(/** @type {Record<string, unknown>} */ (row)))
            }
          }
        }

        bringProductLayersToFront(canvas)
        setSelectedReferenceFilename(filename)
        canvas.renderAll()
      } catch (err) {
        console.error(err)
        toast.error(t('infographic.imageLoadError'))
      }
    },
    [getCanvas, t]
  )

  const onPickProductPhoto = useCallback(() => {
    productFileInputRef.current?.click()
  }, [])

  const onPickDecorativeImage = useCallback(() => {
    decorativeFileInputRef.current?.click()
  }, [])

  const onProductFiles = useCallback(
    async (e) => {
      const file = e.target.files?.[0]
      e.target.value = ''
      if (!isLikelyImageFile(file)) {
        toast.error(t('infographic.imageTypeInvalid'))
        return
      }
      const canvas = getCanvas()
      if (!canvas) return

      if (productPreviewRef.current) {
        URL.revokeObjectURL(productPreviewRef.current)
      }
      productPreviewRef.current = null

      removeObjectsByLayer(canvas, LAYER_PRODUCT)

      let objectUrl
      try {
        let blobSource = /** @type {Blob | File} */ (file)
        if (cutOutEnabled) {
          setCutoutBusy(true)
          try {
            const { removeBackground } = await import('@imgly/background-removal')
            blobSource = await removeBackground(file, { model: 'medium' })
          } catch (err) {
            console.error(err)
            toast.error(t('infographic.cutOutFailed'))
            blobSource = file
          } finally {
            setCutoutBusy(false)
          }
        }

        objectUrl = URL.createObjectURL(blobSource)
        productPreviewRef.current = objectUrl

        const img = await FabricImage.fromURL(objectUrl, { crossOrigin: 'anonymous' })
        const maxW = VIEW_W * 0.72
        if (img.width > maxW) {
          img.scaleToWidth(maxW)
        }
        img.set({
          left: VIEW_W * 0.5 - (img.getScaledWidth() / 2 || 0),
          top: VIEW_H * 0.38 - (img.getScaledHeight() / 2 || 0),
        })
        setInfographicLayer(img, LAYER_PRODUCT)
        canvas.add(img)
        canvas.setActiveObject(img)
        canvas.renderAll()
      } catch (err) {
        console.error(err)
        toast.error(t('infographic.imageLoadError'))
        if (objectUrl) URL.revokeObjectURL(objectUrl)
      }
    },
    [cutOutEnabled, getCanvas, t]
  )

  const onDecorativeFiles = useCallback(
    async (e) => {
      const file = e.target.files?.[0]
      e.target.value = ''
      if (!isLikelyImageFile(file)) {
        toast.error(t('infographic.imageTypeInvalid'))
        return
      }
      const canvas = getCanvas()
      if (!canvas) return
      const url = URL.createObjectURL(file)
      try {
        const img = await FabricImage.fromURL(url, { crossOrigin: 'anonymous' })
        const maxW = VIEW_W * 0.5
        if (img.width > maxW) {
          img.scaleToWidth(maxW)
        }
        img.set({
          left: VIEW_W * 0.2,
          top: VIEW_H * 0.15,
        })
        setInfographicLayer(img, LAYER_USER)
        canvas.add(img)
        canvas.setActiveObject(img)
        canvas.renderAll()
      } catch (err) {
        console.error(err)
        toast.error(t('infographic.imageLoadError'))
      }
    },
    [getCanvas, t]
  )

  const addHeading = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const text = new IText(t('infographic.defaultHeading'), {
      left: VIEW_W * 0.08,
      top: VIEW_H * 0.06,
      fontSize: 26,
      fontWeight: '700',
      fill: '#ffffff',
      fontFamily: 'system-ui, -apple-system, "Segoe UI", sans-serif',
      shadow: new Shadow({ color: 'rgba(0,0,0,0.45)', blur: 6, offsetX: 0, offsetY: 2 }),
    })
    canvas.add(text)
    setInfographicLayer(text, LAYER_USER)
    canvas.setActiveObject(text)
    canvas.renderAll()
  }, [getCanvas, t])

  const addBodyText = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const text = new IText(t('infographic.defaultBody'), {
      left: VIEW_W * 0.08,
      top: VIEW_H * 0.16,
      fontSize: 16,
      fontWeight: '400',
      fill: '#f1f5f9',
      fontFamily: 'system-ui, -apple-system, "Segoe UI", sans-serif',
      shadow: new Shadow({ color: 'rgba(0,0,0,0.35)', blur: 4, offsetX: 0, offsetY: 1 }),
    })
    canvas.add(text)
    setInfographicLayer(text, LAYER_USER)
    canvas.setActiveObject(text)
    canvas.renderAll()
  }, [getCanvas, t])

  const flipSelection = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const obj = canvas.getActiveObject()
    if (!obj) return
    obj.set('flipX', !obj.flipX)
    canvas.requestRenderAll()
  }, [getCanvas])

  const deleteSelection = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const active = canvas.getActiveObjects()
    if (!active.length) return
    active.forEach((o) => canvas.remove(o))
    canvas.discardActiveObject()
    if (active.some((o) => o.get('infographicLayer') === LAYER_TEMPLATE)) {
      setSelectedReferenceFilename(null)
    }
    canvas.renderAll()
  }, [getCanvas])

  const bringForward = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const obj = canvas.getActiveObject()
    if (!obj) return
    canvas.bringObjectForward(obj)
    canvas.renderAll()
  }, [getCanvas])

  const sendBackward = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const obj = canvas.getActiveObject()
    if (!obj) return
    canvas.sendObjectBackwards(obj)
    canvas.renderAll()
  }, [getCanvas])

  const downloadPng = useCallback(async () => {
    const canvas = getCanvas()
    if (!canvas) return
    try {
      const blob = await canvas.toBlob({
        format: 'png',
        multiplier: EXPORT_MULT,
      })
      if (!blob || blob.size === 0) {
        toast.error(t('infographic.downloadFailed'))
        return
      }
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `infographic-${Date.now()}.png`
      a.style.display = 'none'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
      toast.success(t('infographic.downloadStarted'))
    } catch (err) {
      console.error(err)
      toast.error(t('infographic.downloadFailed'))
    }
  }, [getCanvas, t])

  useEffect(() => {
    if (!ready) return undefined
    const onKey = (e) => {
      if (e.key !== 'Delete' && e.key !== 'Backspace') return
      const tag = e.target?.tagName
      if (tag === 'INPUT' || tag === 'TEXTAREA') return
      const canvas = fabricRef.current
      if (!canvas) return
      const primary = canvas.getActiveObject()
      if (primary?.isEditing) return
      const active = canvas.getActiveObjects()
      if (!active.length) return
      e.preventDefault()
      active.forEach((o) => canvas.remove(o))
      canvas.discardActiveObject()
      if (active.some((o) => o.get('infographicLayer') === LAYER_TEMPLATE)) {
        setSelectedReferenceFilename(null)
      }
      canvas.renderAll()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [ready])

  const zoomOut = useCallback(() => setZoom((z) => Math.max(0.5, Math.round((z - 0.1) * 10) / 10)), [])
  const zoomIn = useCallback(() => setZoom((z) => Math.min(1.5, Math.round((z + 0.1) * 10) / 10)), [])
  const zoomPercent = Math.round(zoom * 100)

  return (
    <div className="flex flex-row gap-0 h-[calc(100dvh-3rem)] min-h-[480px] -m-2 sm:-m-4 lg:-m-6">
      <aside className="w-full max-w-[300px] shrink-0 flex flex-col border-r border-white/10 bg-gray-900/60 min-h-0">
        <div className="p-4 border-b border-white/10 shrink-0">
          <div className="flex items-center gap-2">
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-900/50 border border-brand-500/30">
              <LayoutTemplate className="h-4 w-4 text-brand-400" aria-hidden />
            </div>
            <h2 className="text-base font-bold text-white leading-tight">{t('infographic.sidebarTitle')}</h2>
          </div>
        </div>

        <div className="flex-1 min-h-0 overflow-y-auto p-4 space-y-4">
          <div className="space-y-2">
            <p className="text-xs font-semibold text-white">{t('infographic.productPhotoSection')}</p>
            <button
              type="button"
              onClick={onPickProductPhoto}
              disabled={!ready || cutoutBusy}
              className="btn-secondary w-full justify-center py-2.5 text-sm font-medium disabled:opacity-50"
            >
              {cutoutBusy ? t('infographic.cutOutProcessing') : t('infographic.addProductPhoto')}
            </button>
            <label className="flex items-start gap-2 cursor-pointer">
              <input
                type="checkbox"
                className="mt-0.5 rounded border-white/20 bg-gray-900 text-brand-500 focus:ring-brand-500/40"
                checked={cutOutEnabled}
                onChange={(e) => setCutOutEnabled(e.target.checked)}
                disabled={cutoutBusy}
              />
              <span className="text-[11px] text-gray-400 leading-snug">{t('infographic.cutOutLabel')}</span>
            </label>
            <p className="text-[10px] text-gray-600 leading-relaxed">{t('infographic.cutOutHint')}</p>
            <input
              ref={productFileInputRef}
              type="file"
              accept="image/*"
              className="hidden"
              onChange={onProductFiles}
            />
          </div>

          <div>
            <p className="text-xs font-semibold text-white mb-1">{t('infographic.canvasEditorReferenceGridTitle')}</p>
            <p className="text-[11px] text-gray-500 leading-relaxed mb-3">{t('infographic.canvasEditorReferenceGridHint')}</p>
            <p className="text-[10px] text-gray-600 leading-relaxed mb-2">{t('infographic.templateManifestHint')}</p>

            {cardGalleryLoading ? (
              <p className="text-xs text-gray-500 py-6 text-center">{t('infographic.cardGalleryLoading')}</p>
            ) : (
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={setPlainBackground}
                  className={`rounded-xl border-2 aspect-[4/5] flex flex-col items-center justify-center gap-1.5 bg-white/5 transition-colors ${
                    selectedReferenceFilename === null
                      ? 'border-brand-500 ring-2 ring-brand-500/35'
                      : 'border-white/10 hover:border-brand-500/40'
                  }`}
                >
                  <Ban className="w-6 h-6 text-gray-500" aria-hidden />
                  <span className="text-[10px] text-gray-400 px-1 text-center leading-snug">
                    {t('infographic.canvasEditorPlainBackground')}
                  </span>
                </button>

                {cardGalleryItems.map((item) => {
                  const sel = selectedReferenceFilename === item.filename
                  const thumbSrc = resolveBackendAssetUrlForCanvas(item.url)
                  const hasEditorLayers = Boolean(item.editor?.texts?.length)
                  return (
                    <button
                      key={item.filename}
                      type="button"
                      title={hasEditorLayers ? t('infographic.templateHasTextLayers') : item.filename}
                      onClick={() => applyReferenceBackground(item)}
                      className={`relative rounded-xl overflow-hidden border-2 aspect-[4/5] transition-colors ${
                        sel ? 'border-brand-500 ring-2 ring-brand-500/35' : 'border-white/10 hover:border-brand-500/40'
                      }`}
                    >
                      {hasEditorLayers ? (
                        <span
                          className="absolute top-1 right-1 z-[1] flex items-center justify-center rounded-md bg-brand-600/90 p-0.5 text-white shadow-sm"
                          aria-hidden
                        >
                          <Layers2 className="h-3 w-3" />
                        </span>
                      ) : null}
                      <img src={thumbSrc} alt="" className="w-full h-full object-cover" />
                    </button>
                  )
                })}
              </div>
            )}

            {!cardGalleryLoading && cardGalleryItems.length === 0 && (
              <p className="text-[11px] text-gray-600 mt-2">{t('infographic.canvasTemplatesEmpty')}</p>
            )}
          </div>

          <div className="rounded-lg border border-white/10 bg-white/[0.03] p-3 space-y-2">
            <p className="text-[11px] text-gray-400 leading-relaxed">{t('infographic.canvasEditorCallout')}</p>
            <Link
              to={`${APP_BASE}/product-card`}
              className="text-xs font-medium text-brand-300 hover:text-brand-200 underline-offset-2 hover:underline"
            >
              {t('infographic.canvasEditorAiCta')}
            </Link>
          </div>

          <p className="text-[11px] text-gray-500 leading-relaxed">{t('infographic.hint')}</p>
          <input
            ref={decorativeFileInputRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={onDecorativeFiles}
          />
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0 min-h-0 gap-0 bg-gray-950/80">
        <header className="shrink-0 flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-white/10 bg-gray-900/40">
          <div className="flex flex-wrap items-center gap-2">
            <button
              type="button"
              onClick={onPickDecorativeImage}
              className="btn-secondary text-sm py-2 px-3"
              disabled={!ready}
            >
              <ImagePlus className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.addDecorativeImage')}
            </button>
            <button type="button" onClick={addHeading} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <Heading className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.addHeadingTool')}
            </button>
            <button type="button" onClick={addBodyText} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <Type className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.addTextTool')}
            </button>
            <button type="button" onClick={flipSelection} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <FlipHorizontal2 className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.flipHorizontal')}
            </button>
            <button type="button" onClick={bringForward} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <ArrowUp className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.bringFront')}
            </button>
            <button type="button" onClick={sendBackward} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <ArrowDown className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.sendBack')}
            </button>
            <button type="button" onClick={deleteSelection} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
              <Trash2 className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
              {t('infographic.delete')}
            </button>
          </div>
          <button
            type="button"
            onClick={downloadPng}
            className="btn-primary text-sm py-2.5 px-5 font-semibold shrink-0"
            disabled={!ready}
          >
            <Download className="w-4 h-4 inline mr-1.5 -mt-0.5" aria-hidden />
            {t('infographic.download')}
          </button>
        </header>

        <div className="flex-1 min-h-0 flex flex-col items-center overflow-hidden">
          <div className="flex-1 w-full overflow-auto flex items-center justify-center p-4">
            <div
              className="shadow-2xl shadow-black/60 ring-1 ring-white/10 rounded-sm transition-transform duration-150"
              style={{
                transform: `scale(${zoom})`,
                transformOrigin: 'center center',
              }}
            >
              <div style={{ width: VIEW_W, height: VIEW_H }}>
                <canvas ref={canvasElRef} className="block" />
              </div>
            </div>
          </div>

          <div className="shrink-0 flex items-center justify-center gap-3 py-3 border-t border-white/10 bg-gray-900/30">
            <span className="text-[11px] text-gray-500 hidden sm:inline">{t('infographic.canvasEditorZoom')}</span>
            <button
              type="button"
              onClick={zoomOut}
              className="p-2 rounded-lg border border-white/10 text-gray-300 hover:bg-white/5"
              aria-label="Zoom out"
            >
              <Minus className="w-4 h-4" />
            </button>
            <span className="text-xs font-medium text-gray-300 tabular-nums w-12 text-center">{zoomPercent}%</span>
            <button
              type="button"
              onClick={zoomIn}
              className="p-2 rounded-lg border border-white/10 text-gray-300 hover:bg-white/5"
            >
              <Plus className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
