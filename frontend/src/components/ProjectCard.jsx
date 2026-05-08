import { Link } from 'react-router-dom'
import { Film, Trash2, Clock, CheckCircle2, AlertCircle, FileEdit } from 'lucide-react'
import { APP_BASE } from '../constants/routes'
import { useTranslation } from 'react-i18next'
import StatusBadge from './ui/StatusBadge'

const statusIcons = {
  draft:      <FileEdit className="w-5 h-5 text-rf-mutedFg" />,
  processing: <Clock className="w-5 h-5 text-yellow-400 animate-spin-slow" />,
  done:       <CheckCircle2 className="w-5 h-5 text-green-400" />,
  failed:     <AlertCircle className="w-5 h-5 text-red-400" />,
}

function isMp4Url(url) {
  return typeof url === 'string' && /\.mp4(\?|$)/i.test(url)
}

export default function ProjectCard({ project, onDelete }) {
  const { t } = useTranslation()
  /** Prefer generated output; originals only as fallback. */
  const generatedUrl = project.result_url || project.video_url || null
  const thumbUrl = generatedUrl || project.images?.[0]?.url || null
  const showVideoThumb = Boolean(generatedUrl) && isMp4Url(generatedUrl)

  return (
    <div className="card group hover:border-rf-border transition-all duration-200">
      <Link to={`${APP_BASE}/projects/${project.id}`} className="block">
        <div className="aspect-[9/16] rounded-xl overflow-hidden bg-rf-meter mb-4 relative">
          {showVideoThumb && thumbUrl ? (
            <video
              src={thumbUrl}
              muted
              playsInline
              preload="metadata"
              className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
          ) : thumbUrl ? (
            <img
              src={thumbUrl}
              alt={project.title}
              className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <Film className="w-12 h-12 text-rf-mutedFg" />
            </div>
          )}
          {project.status === 'done' && (
            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end justify-center pb-4">
              <span className="text-xs font-semibold text-white bg-green-500/80 px-3 py-1 rounded-full">
                {t('status.done')}
              </span>
            </div>
          )}
        </div>

        <div className="space-y-2">
          <div className="flex items-start justify-between gap-2">
            <h3 className="font-semibold text-rf-text truncate leading-tight">{project.title}</h3>
            {statusIcons[project.status]}
          </div>
          <div className="flex items-center justify-between">
            <span className="text-brand-400 font-bold text-lg">
              ${parseFloat(project.price).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <StatusBadge status={project.status} />
          </div>
          <p className="text-xs text-rf-mutedFg line-clamp-2">{project.description}</p>
        </div>
      </Link>

      <div
        className={`mt-4 flex items-center border-t border-rf-border pt-4 ${
          typeof onDelete === 'function' ? 'justify-between' : 'justify-start'
        }`}
      >
        <span className="text-xs text-rf-mutedFg">
          {new Date(project.created_at).toLocaleDateString()}
        </span>
        {typeof onDelete === 'function' && (
          <button
            type="button"
            onClick={(e) => {
              e.preventDefault()
              onDelete(project.id)
            }}
            className="text-rf-mutedFg hover:text-red-400 transition-colors p-1 rounded"
            title={t('common.delete')}
          >
            <Trash2 className="w-4 h-4" />
          </button>
        )}
      </div>
    </div>
  )
}
