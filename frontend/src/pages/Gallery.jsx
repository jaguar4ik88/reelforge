import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import { APP_BASE } from '../constants/routes'
import { Film, Image as ImageIcon, LayoutGrid, Clock, Loader2 } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useProjects } from '../hooks/useProjects'
import Spinner from '../components/ui/Spinner'
import StatusBadge from '../components/ui/StatusBadge'

function GalleryThumb({ project }) {
  const { t } = useTranslation()
  const done = project.status === 'done'
  const videoOut = Boolean(project.video_url)
  const imageOut = Boolean(project.result_url)
  const hasGenerated = done && (videoOut || imageOut)

  const isVideoIcon =
    videoOut ||
    (project.creation_flow === 'photo_guided' && project.generation_preview?.content_type === 'video')

  if (hasGenerated && videoOut) {
    return (
      <video
        src={project.video_url}
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
  const { projects, loading } = useProjects()
  const [productId, setProductId] = useState('all')
  const [kind, setKind] = useState('all')

  const filtered = useMemo(() => {
    return projects.filter((p) => {
      if (productId !== 'all' && String(p.id) !== productId) return false
      if (kind === 'video') {
        const isVideoOutput =
          Boolean(p.video_url) ||
          (p.creation_flow === 'photo_guided' && p.generation_preview?.content_type === 'video')
        if (p.status !== 'done' || !isVideoOutput) return false
      }
      if (kind === 'draft' && p.status !== 'draft') return false
      if (kind === 'processing' && p.status !== 'processing') return false
      return true
    })
  }, [projects, productId, kind])

  const typeFilters = [
    { id: 'all', icon: LayoutGrid, label: t('gallery.type.all') },
    { id: 'video', icon: Film, label: t('gallery.type.video') },
    { id: 'draft', icon: ImageIcon, label: t('gallery.type.draft') },
    { id: 'processing', icon: Clock, label: t('gallery.type.processing') },
  ]

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
          <label className="text-xs text-gray-500 uppercase tracking-wide shrink-0">{t('gallery.byProduct')}</label>
          <select
            value={productId}
            onChange={(e) => setProductId(e.target.value)}
            className="input-field text-sm py-2 w-full"
          >
            <option value="all">{t('gallery.allProducts')}</option>
            {projects.map((p) => (
              <option key={p.id} value={String(p.id)}>
                {p.title}
              </option>
            ))}
          </select>
        </div>

        <div className="flex flex-col gap-2 flex-1 min-w-0">
          <span className="text-xs text-gray-500 uppercase tracking-wide">{t('gallery.byType')}</span>
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

      {loading ? (
        <div className="flex justify-center py-20">
          <Spinner size="lg" />
        </div>
      ) : filtered.length === 0 ? (
        <div className="card text-center py-16 text-gray-500">{t('gallery.empty')}</div>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {filtered.map((p) => {
            const isVideoIcon =
              Boolean(p.video_url) ||
              (p.creation_flow === 'photo_guided' && p.generation_preview?.content_type === 'video')

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
      )}
    </div>
  )
}
