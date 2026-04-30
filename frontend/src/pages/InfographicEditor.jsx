import { useCallback, useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  Download,
  LayoutTemplate,
  Trash2,
  ArrowUp,
  ArrowDown,
  Info,
  ImagePlus,
} from 'lucide-react'
import { Canvas, FabricImage } from 'fabric'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'
import { APP_BASE } from '../constants/routes'

/** Editor viewport (3:4). Export uses `multiplier` for full-resolution PNG. */
const VIEW_W = 540
const VIEW_H = 720
/** 1080×1440 output when downloading. */
const EXPORT_MULT = 2

/**
 * Any image the browser reports as image/* is OK to try with FabricImage.
 */
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
  const fileInputRef = useRef(null)
  const productPreviewRef = useRef(null)
  /** Fabric object for the main product photo — replaced on each upload. */
  const productFabricObjectRef = useRef(/** @type {import('fabric').FabricImage | null} */ (null))

  const [ready, setReady] = useState(false)

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

  const onPickImage = useCallback(() => {
    fileInputRef.current?.click()
  }, [])

  const onImageFiles = useCallback(
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
      if (productPreviewRef.current) {
        URL.revokeObjectURL(productPreviewRef.current)
      }
      productPreviewRef.current = url

      try {
        const prev = productFabricObjectRef.current
        if (prev && canvas.getObjects().includes(prev)) {
          canvas.remove(prev)
        }
        productFabricObjectRef.current = null

        const img = await FabricImage.fromURL(url, { crossOrigin: 'anonymous' })
        const maxW = VIEW_W * 0.7
        if (img.width > maxW) {
          img.scaleToWidth(maxW)
        }
        img.set({
          left: VIEW_W * 0.15,
          top: VIEW_H * 0.35,
        })
        productFabricObjectRef.current = img
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

  const deleteSelection = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const active = canvas.getActiveObjects()
    if (!active.length) return
    active.forEach((o) => canvas.remove(o))
    canvas.discardActiveObject()
    canvas.renderAll()
  }, [getCanvas])

  const bringForward = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const obj = canvas.getActiveObject()
    if (!obj) return
    canvas.bringObjectToFront(obj)
    canvas.renderAll()
  }, [getCanvas])

  const sendBackward = useCallback(() => {
    const canvas = getCanvas()
    if (!canvas) return
    const obj = canvas.getActiveObject()
    if (!obj) return
    canvas.sendObjectToBack(obj)
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
      canvas.renderAll()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [ready])

  return (
    <div className="flex flex-col gap-4 h-[calc(100dvh-3rem)] min-h-[480px]">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shrink-0">
        <div className="flex items-start gap-3">
          <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-900/50 border border-brand-500/30">
            <LayoutTemplate className="h-5 w-5 text-brand-400" aria-hidden />
          </div>
          <div>
            <h1 className="text-xl font-bold text-white">{t('infographic.title')}</h1>
            <p className="text-sm text-gray-400 mt-0.5 max-w-xl">{t('infographic.canvasPageSubtitle')}</p>
            <Link
              to={`${APP_BASE}/product-card`}
              className="inline-block mt-2 text-xs font-medium text-brand-300 hover:text-brand-200 underline-offset-2 hover:underline"
            >
              {t('productCard.navLinkFromCanvas')}
            </Link>
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button type="button" onClick={onPickImage} className="btn-secondary text-sm py-2 px-3" disabled={!ready}>
            <ImagePlus className="w-4 h-4 inline mr-1 -mt-0.5" aria-hidden />
            {t('infographic.addImage')}
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
          <button type="button" onClick={downloadPng} className="btn-primary text-sm py-2 px-4" disabled={!ready}>
            <Download className="w-4 h-4 inline mr-1.5 -mt-0.5" aria-hidden />
            {t('infographic.download')}
          </button>
        </div>
      </div>

      <div className="flex flex-1 min-h-0 gap-4 flex-col lg:flex-row">
        <aside className="w-full lg:w-[280px] shrink-0 flex flex-col rounded-2xl border border-white/10 bg-gray-900/50 backdrop-blur-sm overflow-hidden min-h-0">
          <div className="flex flex-col gap-3 p-4 overflow-y-auto min-h-0 flex-1 lg:max-h-[calc(100dvh-10rem)]">
            <div className="flex items-start gap-2">
              <Info className="w-4 h-4 text-brand-400 shrink-0 mt-0.5" aria-hidden />
              <div className="space-y-2">
                <p className="text-xs text-gray-300 leading-relaxed">{t('infographic.canvasPageAside')}</p>
                <p className="text-[11px] text-gray-500 leading-relaxed">{t('infographic.hint')}</p>
              </div>
            </div>
            <input ref={fileInputRef} type="file" accept="image/*" className="hidden" onChange={onImageFiles} />
          </div>
        </aside>

        <div className="flex-1 min-h-0 overflow-auto rounded-xl border border-white/10 bg-gray-900/40 p-4 flex justify-center items-start">
          <div
            className="shadow-2xl shadow-black/50 ring-1 ring-white/10 rounded-sm overflow-hidden"
            style={{ width: VIEW_W, height: VIEW_H }}
          >
            <canvas ref={canvasElRef} className="block" />
          </div>
        </div>
      </div>
    </div>
  )
}
