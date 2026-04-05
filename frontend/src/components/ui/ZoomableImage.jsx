import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { ZoomIn, X } from 'lucide-react'
import { useTranslation } from 'react-i18next'

/**
 * Hover: expand icon. Click: fullscreen lightbox (Escape / backdrop / close to dismiss).
 */
export default function ZoomableImage({
  src,
  alt = '',
  className = '',
  imageClassName = '',
  variant = 'hero',
}) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  useEffect(() => {
    if (!open) return
    const onKey = (e) => {
      if (e.key === 'Escape') setOpen(false)
    }
    window.addEventListener('keydown', onKey)
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = prev
    }
  }, [open])

  if (!src) return null

  const iconClass =
    variant === 'thumb'
      ? 'w-7 h-7 sm:w-8 sm:h-8'
      : 'w-10 h-10 sm:w-12 sm:h-12'

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className={`group relative block w-full overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/60 ${className}`}
        aria-label={t('common.zoomImage')}
      >
        <img src={src} alt={alt} className={imageClassName} />
        <span
          className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 transition-colors duration-200 group-hover:bg-black/40"
          aria-hidden
        >
          <ZoomIn
            className={`${iconClass} text-white opacity-0 drop-shadow-md transition-opacity duration-200 group-hover:opacity-100`}
            strokeWidth={1.75}
          />
        </span>
      </button>

      {open &&
        createPortal(
          <div
            className="fixed inset-0 z-[200] box-border flex cursor-zoom-out items-center justify-center overflow-y-auto bg-black/93 px-3 py-14 sm:px-6 sm:py-16"
            role="dialog"
            aria-modal="true"
            aria-label={t('common.zoomImage')}
            onClick={() => setOpen(false)}
          >
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation()
                setOpen(false)
              }}
              className="absolute right-3 top-3 z-10 cursor-pointer rounded-full bg-white/10 p-2.5 text-white transition-colors hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
              aria-label={t('common.close')}
            >
              <X className="h-6 w-6" strokeWidth={1.75} />
            </button>
            <div
              className="relative z-[1] my-auto flex min-h-0 w-full max-w-[min(96vw,100%)] cursor-default items-center justify-center"
              onClick={(e) => e.stopPropagation()}
            >
              <img
                src={src}
                alt={alt}
                className="h-auto w-auto max-h-[min(85dvh,85vh)] max-w-full object-contain shadow-2xl"
                decoding="async"
              />
            </div>
          </div>,
          document.body
        )}
    </>
  )
}
