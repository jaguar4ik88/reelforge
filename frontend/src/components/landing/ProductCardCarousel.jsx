import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { LANDING_CARD_EXAMPLE_SRCS } from '../../constants/landingCardExamples'

const PLACEHOLDER_COUNT = 3

/** One full cycle (first row + gap) takes this long — slow, right-to-left drift */
const MARQUEE_DURATION_SEC = 120

const cardShell =
  'relative h-52 w-40 flex-shrink-0 overflow-hidden rounded-xl border border-white/10 bg-gray-900/60 shadow-lg sm:h-64 sm:w-48'

function usePrefersReducedMotion() {
  const [reduced, setReduced] = useState(() =>
    typeof window !== 'undefined' ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false
  )
  useEffect(() => {
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)')
    const fn = () => setReduced(mq.matches)
    mq.addEventListener('change', fn)
    return () => mq.removeEventListener('change', fn)
  }, [])
  return reduced
}

export default function ProductCardCarousel() {
  const { t } = useTranslation()
  const prefersReducedMotion = usePrefersReducedMotion()
  const hasImages = LANDING_CARD_EXAMPLE_SRCS.length > 0
  const trackRef = useRef(null)
  const firstSetRef = useRef(null)
  const [shiftPx, setShiftPx] = useState(0)

  useLayoutEffect(() => {
    if (!hasImages || prefersReducedMotion) return

    const measure = () => {
      const track = trackRef.current
      const first = firstSetRef.current
      if (!track || !first) return
      const gapStr = getComputedStyle(track).gap || getComputedStyle(track).columnGap || '16px'
      const gap = Number.parseFloat(gapStr) || 16
      setShiftPx(first.offsetWidth + gap)
    }

    measure()
    const ro = new ResizeObserver(measure)
    if (trackRef.current) ro.observe(trackRef.current)
    window.addEventListener('resize', measure)
    return () => {
      ro.disconnect()
      window.removeEventListener('resize', measure)
    }
  }, [hasImages, prefersReducedMotion, LANDING_CARD_EXAMPLE_SRCS.length])

  if (!hasImages) {
    const gradients = [
      'from-brand-900/40 via-gray-900 to-purple-900/30',
      'from-purple-900/40 via-gray-900 to-brand-900/30',
      'from-gray-800 via-gray-900 to-amber-900/20',
    ]
    return (
      <div
        className="relative mx-auto max-w-6xl rounded-2xl border border-white/10 bg-gray-950/40 px-2 py-6 sm:px-4"
        role="region"
        aria-label={t('landing.cardExamples.sliderLabel')}
      >
        <div className="flex flex-wrap justify-center gap-4">
          {Array.from({ length: PLACEHOLDER_COUNT }, (_, i) => (
            <div
              key={i}
              className={`${cardShell} flex flex-col items-center justify-center bg-gradient-to-br p-4 text-center ${gradients[i]}`}
            >
              <span className="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-400/80">
                {t('landing.cardExamples.placeholderBadge', { n: i + 1 })}
              </span>
              <p className="max-w-[10rem] text-xs leading-relaxed text-gray-400">{t('landing.cardExamples.empty')}</p>
            </div>
          ))}
        </div>
      </div>
    )
  }

  const imageCards = (keyPrefix) =>
    LANDING_CARD_EXAMPLE_SRCS.map((src, i) => (
      <div key={`${keyPrefix}-${i}`} className={cardShell}>
        <img
          src={src}
          alt=""
          className="h-full w-full object-cover"
          loading={i < 4 ? 'eager' : 'lazy'}
          decoding="async"
        />
      </div>
    ))

  if (prefersReducedMotion) {
    return (
      <div
        className="relative mx-auto max-w-6xl rounded-2xl border border-white/10 bg-gray-950/40 px-2 py-4 sm:px-4"
        role="region"
        aria-label={t('landing.cardExamples.sliderLabel')}
      >
        <p className="mb-3 text-center text-xs text-gray-500">{t('landing.cardExamples.reducedMotionHint')}</p>
        <div className="flex gap-4 overflow-x-auto pb-2 pt-1 [scrollbar-width:thin] sm:gap-6">{imageCards('static')}</div>
      </div>
    )
  }

  return (
    <div
      className="relative mx-auto max-w-6xl overflow-hidden rounded-2xl border border-white/10 bg-gray-950/40 py-6"
      role="region"
      aria-label={t('landing.cardExamples.sliderLabel')}
    >
      <div
        className="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 bg-gradient-to-r from-gray-950 sm:w-20"
        aria-hidden
      />
      <div
        className="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 bg-gradient-to-l from-gray-950 sm:w-20"
        aria-hidden
      />

      <div
        ref={trackRef}
        className={`flex w-max will-change-transform gap-4 sm:gap-6 ${shiftPx > 0 ? 'rf-marquee-track' : ''}`}
        style={{
          '--marquee-end': shiftPx > 0 ? `-${shiftPx}px` : '0px',
          '--marquee-duration': `${MARQUEE_DURATION_SEC}s`,
        }}
      >
        <div ref={firstSetRef} className="flex shrink-0 gap-4 sm:gap-6">
          {imageCards('a')}
        </div>
        <div className="flex shrink-0 gap-4 sm:gap-6" aria-hidden>
          {imageCards('b')}
        </div>
      </div>
    </div>
  )
}
