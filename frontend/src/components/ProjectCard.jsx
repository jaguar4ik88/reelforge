import { Link } from 'react-router-dom'
import { Film, Trash2, Clock, CheckCircle2, AlertCircle, FileEdit } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import StatusBadge from './ui/StatusBadge'

const statusIcons = {
  draft:      <FileEdit className="w-5 h-5 text-gray-400" />,
  processing: <Clock className="w-5 h-5 text-yellow-400 animate-spin-slow" />,
  done:       <CheckCircle2 className="w-5 h-5 text-green-400" />,
  failed:     <AlertCircle className="w-5 h-5 text-red-400" />,
}

export default function ProjectCard({ project, onDelete }) {
  const { t } = useTranslation()
  const firstImage = project.images?.[0]

  return (
    <div className="card group hover:border-white/20 transition-all duration-200">
      <Link to={`/projects/${project.id}`} className="block">
        <div className="aspect-[9/16] rounded-xl overflow-hidden bg-gray-800 mb-4 relative">
          {firstImage ? (
            <img
              src={firstImage.url}
              alt={project.title}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <Film className="w-12 h-12 text-gray-600" />
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
            <h3 className="font-semibold text-white truncate leading-tight">{project.title}</h3>
            {statusIcons[project.status]}
          </div>
          <div className="flex items-center justify-between">
            <span className="text-brand-400 font-bold text-lg">
              ${parseFloat(project.price).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <StatusBadge status={project.status} />
          </div>
          <p className="text-xs text-gray-500 line-clamp-2">{project.description}</p>
        </div>
      </Link>

      <div className="mt-4 pt-4 border-t border-white/10 flex items-center justify-between">
        <span className="text-xs text-gray-600">
          {new Date(project.created_at).toLocaleDateString()}
        </span>
        <button
          onClick={(e) => { e.preventDefault(); onDelete(project.id) }}
          className="text-gray-600 hover:text-red-400 transition-colors p-1 rounded"
          title={t('common.delete')}
        >
          <Trash2 className="w-4 h-4" />
        </button>
      </div>
    </div>
  )
}
