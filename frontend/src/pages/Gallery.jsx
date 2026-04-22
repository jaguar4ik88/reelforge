import { useState } from 'react'
import { Link } from 'react-router-dom'
import { APP_BASE } from '../constants/routes'
import { Film, Image as ImageIcon, LayoutGrid, Clock, Loader2, ChevronLeft, ChevronRight } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useProjects, useProjectFilterOptions } from '../hooks/useProjects'
import Spinner from '../components/ui/Spinner'
import StatusBadge from '../components/ui/StatusBadge'

function isVideoResultUrl(url) {
  if (!url || typeof url !== 'string') return false
  return /\.(mp4|webm|mov)(\?|#|$)/i.test(url)
}

function GalleryThumb({ project }) {
  const { t } = useTranslation()
  const done = project.status === 'done'
  /** API omits `video_url` for photo_guided; MP4 is only in `result_url`. */
  const videoOut =
    Boolean(project.video_url) ||
    (project.creation_flow === 'photo_guided' &&
      project.generation_preview?.content_type === 'video' &&
      Boolean(project.result_url)) ||
    (Boolean(project.result_url) && isVideoResultUrl(project.result_url))
  const imageOut = Boolean(project.result_url) && !videoOut
  const hasGenerated = done && (videoOut || imageOut)
  const refImageUrl = project.images?.[0]?.url

  const isVideoIcon =
    videoOut ||
    (project.creation_flow === 'photo_guided' && project.generation_preview?.content_type === 'video')

  if (hasGenerated && videoOut && (project.video_url || project.result_url)) {
    return (
      <video
        src={project.video_url || project.result_url}
        muted
        playsInline
        preload="metadata"
        className="h-[150px] w-full object-cover"
      />
    )
  }

  if (hasGenerated && imageOut) {
    return (
      <img
        src={project.result_url}
        alt=""
        className="h-[150px] w-full object-cover"
      />
    )
  }

  if (refImageUrl) {
    return (
      <img
        src={refImageUrl}
        alt=""
        className="h-[150px] w-full object-cover"
      />
    )
  }

  if (project.status === 'processing') {
    return (
      <div
        className="w-full flex flex-col items-center justify-center gap-2 bg-gray-800/80 text-brand-400 h-[150px]"
      >
        <Loader2 className="w-8 h-8 animate-spin" />
        <span className="text-[10px] uppercase tracking-wide text-gray-500">{t('gallery.thumbProcessing')}</span>
      </div>
    )
  }

  return (
    <div className="w-full h-[150px] flex flex-col items-center justify-center gap-1 bg-gray-800/90 text-gray-500 px-2">
      <ImageIcon className="w-10 h-10 opacity-40" />
      <span className="text-[10px] text-center leading-tight">{t('gallery.thumbPlaceholder')}</span>
    </div>
  )
}

export default function Gallery() {
  const { t } = useTranslation()
  const [productId, setProductId] = useState('all')
  const [kind, setKind] = useState('all')
  const productOptions = useProjectFilterOptions()
  const { projects, meta, loading, error, page, setPage, refresh } = useProjects({
    projectFilter: productId,
    typeFilter: kind,
  })

  const typeFilters = [
    { id: 'all', icon: LayoutGrid, label: t('gallery.type.all') },
    { id: 'video', icon: Film, label: t('gallery.type.video') },
    { id: 'draft', icon: ImageIcon, label: t('gallery.type.draft') },
    { id: 'processing', icon: Clock, label: t('gallery.type.processing') },
  ]

  const lastPage = meta?.last_page ?? 1
  const canPrev = page > 1
  const canNext = page < lastPage

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-white mb-1">{t('gallery.title')}</h1>
        <p className="text-gray-400 text-sm">{t('gallery.subtitle')}</p>
      </div>

      <div className="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-4 pb-2 border-b border-white/10">
        <div className="flex items-center gap-2 text-white font-semibold shrink-0">
          <span className="text-sm">{t('gallery.filters')}</span>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center gap-2 min-w-[160px] flex-1 sm:max-w-xs">
          <select
            value={productId}
            onChange={(e) => setProductId(e.target.value)}
            className="input-field text-sm py-2 w-full"
          >
            <option value="all">{t('gallery.allProducts')}</option>
            {productOptions.map((p) => (
              <option key={p.id} value={String(p.id)}>
                {p.title}
              </option>
            ))}
          </select>
        </div>

        <div className="flex flex-col gap-2 flex-1 min-w-0">
          <div className="flex flex-wrap gap-2">
            {typeFilters.map(({ id, icon: Icon, label }) => (
              <button
                key={id}
                type="button"
                onClick={() => setKind(id)}
                className={`inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm transition-colors ${
                  kind === id
                    ? 'bg-brand-600/25 text-brand-200 border border-brand-500/40'
                    : 'text-gray-400 bg-gray-900/50 border border-white/10 hover:border-white/20'
                }`}
              >
                <Icon className={`w-4 h-4 shrink-0 ${id === 'processing' && kind === id ? 'text-amber-400' : ''}`} />
                {label}
              </button>
            ))}
          </div>
        </div>
      </div>

      {error && !loading ? (
        <div className="card text-center py-12 space-y-3 border border-red-500/30 bg-red-950/20">
          <p className="text-red-300 text-sm">{t('gallery.loadError')}</p>
          <p className="text-gray-500 text-xs break-words max-w-lg mx-auto">{error}</p>
          <button type="button" onClick={() => refresh()} className="btn-secondary text-sm px-4 py-2">
            {t('gallery.retry')}
          </button>
        </div>
      ) : loading ? (
        <div className="flex justify-center py-20">
          <Spinner size="lg" />
        </div>
      ) : projects.length === 0 ? (
        <div className="card text-center py-16 text-gray-500">{t('gallery.empty')}</div>
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {projects.map((p) => {
              const isVideoIcon =
                Boolean(p.video_url) ||
                (p.creation_flow === 'photo_guided' &&
                  p.generation_preview?.content_type === 'video' &&
                  Boolean(p.result_url)) ||
                (Boolean(p.result_url) && isVideoResultUrl(p.result_url))

              return (
                <Link
                  key={p.id}
                  to={`${APP_BASE}/projects/${p.id}`}
                  className="group flex flex-col rounded-2xl overflow-hidden border border-white/10 bg-gray-900/60 hover:border-brand-500/40 transition-all"
                >
                  <div className="relative w-full h-[150px] overflow-hidden bg-gray-800 flex items-center justify-center">
                    <GalleryThumb project={p} />
                    <div className="absolute top-2 right-2 w-8 h-8 rounded-lg bg-black/55 backdrop-blur-sm flex items-center justify-center border border-white/10">
                      {isVideoIcon ? (
                        <Film className="w-4 h-4 text-white" />
                      ) : (
                        <ImageIcon className="w-4 h-4 text-white" />
                      )}
                    </div>
                  </div>
                  <div className="p-3 flex flex-col gap-1.5 min-h-[4.5rem]">
                    <StatusBadge status={p.status} />
                    <p className="font-medium text-white text-sm leading-snug line-clamp-2">{p.title}</p>
                    <p className="text-xs text-gray-500">
                      {new Date(p.created_at).toLocaleString(undefined, {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                      })}
                    </p>
                  </div>
                </Link>
              )
            })}
          </div>

          {lastPage > 1 && (
            <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4 border-t border-white/10">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  disabled={!canPrev}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm border border-white/15 bg-gray-900/50 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:border-white/25"
                >
                  <ChevronLeft className="w-4 h-4" />
                  {t('gallery.prev')}
                </button>
                <span className="text-sm text-gray-400 px-2 tabular-nums">
                  {t('gallery.page', { current: page, total: lastPage })}
                </span>
                <button
                  type="button"
                  disabled={!canNext}
                  onClick={() => setPage((p) => p + 1)}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm border border-white/15 bg-gray-900/50 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:border-white/25"
                >
                  {t('gallery.next')}
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              {meta?.total != null && (
                <span className="text-xs text-gray-500">
                  {t('gallery.totalProjects', { count: meta.total })}
                </span>
              )}
            </div>
          )}
        </>
      )}
    </div>
  )
}
