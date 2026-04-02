import { Link } from 'react-router-dom'
import { Plus, Film } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useProjects } from '../hooks/useProjects'
import { useAuthContext } from '../context/AuthContext'
import { projectsApi } from '../services/api'
import ProjectCard from '../components/ProjectCard'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'

export default function Products() {
  const { t } = useTranslation()
  const { user } = useAuthContext()
  const { projects, meta, loading, page, setPage, refresh } = useProjects()
  const handleDelete = async (id) => {
    if (!confirm(t('common.deleteConfirm'))) return
    try {
      await projectsApi.delete(id)
      toast.success(t('common.deleted'))
      refresh()
    } catch {
      toast.error(t('common.deleteError'))
    }
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white">{t('products.title')}</h1>
          <p className="text-gray-400 mt-1">{t('products.subtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link to="/projects/new-photo" className="btn-primary flex items-center gap-2">
            <Plus className="w-4 h-4" />
            {t('products.addFromPhoto')}
          </Link>
          <Link to="/projects/new" className="btn-secondary flex items-center gap-2 text-sm">
            {t('products.addByTemplate')}
          </Link>
        </div>
      </div>

      {user && (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
          <div className="card text-center">
            <p className="text-3xl font-bold gradient-text">{user.videos_this_month}</p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.videosMonth')}</p>
          </div>
          <div className="card text-center">
            <p className="text-3xl font-bold text-white">{user.video_limit}</p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.monthLimit')}</p>
          </div>
          <div className="card text-center">
            <p className="text-3xl font-bold text-white">{meta?.total ?? projects.length}</p>
            <p className="text-xs text-gray-500 mt-1">{t('products.totalLabel')}</p>
          </div>
          <div className="card text-center">
            <p className="text-3xl font-bold text-brand-400 capitalize">
              {t(`common.plan.${user.plan}`)}
            </p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.currentPlan')}</p>
          </div>
        </div>
      )}

      {loading ? (
        <div className="flex justify-center py-16"><Spinner size="lg" /></div>
      ) : projects.length === 0 ? (
        <div className="text-center py-20">
          <div className="w-20 h-20 rounded-2xl bg-brand-900/30 border border-brand-500/20 flex items-center justify-center mx-auto mb-5">
            <Film className="w-10 h-10 text-brand-400" />
          </div>
          <h2 className="text-xl font-semibold text-white mb-2">{t('products.emptyTitle')}</h2>
          <p className="text-gray-500 mb-6">{t('products.emptySub')}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-2">
            <Link to="/projects/new-photo" className="btn-primary inline-flex items-center gap-2">
              <Plus className="w-4 h-4" />
              {t('products.addFirstPhoto')}
            </Link>
            <Link to="/projects/new" className="btn-secondary inline-flex items-center gap-2 text-sm">
              {t('products.addFirstTemplate')}
            </Link>
          </div>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            {projects.map((p) => (
              <ProjectCard key={p.id} project={p} onDelete={handleDelete} />
            ))}
          </div>

          {meta && meta.last_page > 1 && (
            <div className="flex justify-center gap-2 mt-10">
              {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
                <button
                  key={p}
                  onClick={() => setPage(p)}
                  className={`w-9 h-9 rounded-lg text-sm font-medium transition-all ${
                    p === page ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10'
                  }`}
                >
                  {p}
                </button>
              ))}
            </div>
          )}
        </>
      )}
    </div>
  )
}
