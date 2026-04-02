import { useEffect, useRef, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useProject } from '../hooks/useProjects'
import { videoApi } from '../services/api'
import StatusBadge from '../components/ui/StatusBadge'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { Download, Zap, ArrowLeft, Film, RefreshCw, Clock, CheckCircle2, AlertCircle } from 'lucide-react'

function VideoPlayer({ url }) {
  return (
    <div className="relative max-w-[280px] mx-auto">
      <div className="rounded-3xl overflow-hidden shadow-2xl shadow-brand-900/40 ring-4 ring-white/10">
        <video src={url} controls playsInline className="w-full aspect-[9/16] object-cover bg-black" />
      </div>
    </div>
  )
}

function ProcessingAnimation({ label, sub }) {
  return (
    <div className="max-w-[280px] mx-auto">
      <div className="aspect-[9/16] rounded-3xl bg-gray-900 border border-white/10 flex flex-col items-center justify-center gap-4 shadow-2xl">
        <div className="relative">
          <div className="w-16 h-16 rounded-full border-4 border-brand-500/20 border-t-brand-500 animate-spin" />
          <Film className="absolute inset-0 m-auto w-7 h-7 text-brand-400" />
        </div>
        <div className="text-center">
          <p className="text-white font-semibold">{label}</p>
          <p className="text-gray-500 text-xs mt-1">{sub}</p>
        </div>
      </div>
    </div>
  )
}

export default function ProjectView() {
  const { id }                              = useParams()
  const { t }                               = useTranslation()
  const { project, loading, refresh }       = useProject(id)
  const [generating, setGenerating]         = useState(false)
  const pollRef                             = useRef(null)

  useEffect(() => {
    if (project?.status === 'processing') {
      pollRef.current = setInterval(refresh, 5000)
    } else {
      clearInterval(pollRef.current)
    }
    return () => clearInterval(pollRef.current)
  }, [project?.status, refresh])

  const handleGenerate = async () => {
    setGenerating(true)
    try {
      await videoApi.generate(id)
      toast.success(t('project.started'))
      refresh()
    } catch (err) {
      toast.error(err.response?.data?.message ?? t('common.error'))
    } finally {
      setGenerating(false)
    }
  }

  if (loading) return <div className="flex justify-center py-20"><Spinner size="lg" /></div>

  if (!project) return (
    <div className="text-center py-20">
      <p className="text-gray-400">{t('project.notFound')}</p>
      <Link to="/dashboard" className="btn-secondary mt-4 inline-block">{t('project.backDashboard')}</Link>
    </div>
  )

  const isPhotoGuided = project.creation_flow === 'photo_guided'
  const canGenerate  = !isPhotoGuided && (project.status === 'draft' || project.status === 'failed')
  const isProcessing = project.status === 'processing'
  const isDone       = project.status === 'done'

  return (
    <div>
      <div className="flex items-center gap-4 mb-8">
        <Link to="/dashboard" className="text-gray-400 hover:text-white transition-colors">
          <ArrowLeft className="w-5 h-5" />
        </Link>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-3 flex-wrap">
            <h1 className="text-2xl font-bold text-white truncate">{project.title}</h1>
            <StatusBadge status={project.status} />
          </div>
          <p className="text-gray-500 text-sm mt-0.5">
            {isPhotoGuided
              ? `${t('project.photoFlowBadge')} · ${new Date(project.created_at).toLocaleDateString()}`
              : `${project.template?.name ?? '—'} · ${new Date(project.created_at).toLocaleDateString()}`}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          {isDone && project.video_url ? (
            <VideoPlayer url={project.video_url} />
          ) : isProcessing ? (
            <ProcessingAnimation label={t('project.processing')} sub={t('project.processingSub')} />
          ) : (
            <div className="max-w-[280px] mx-auto">
              <div className="aspect-[9/16] rounded-3xl bg-gray-900 border-2 border-dashed border-white/15 flex flex-col items-center justify-center gap-3">
                <Film className="w-16 h-16 text-gray-700" />
                <p className="text-gray-500 text-sm text-center px-6">
                  {project.status === 'failed' ? t('project.failedSub') : t('project.noVideo')}
                </p>
              </div>
            </div>
          )}

          {isDone && project.video_url && (
            <div className="mt-4 max-w-[280px] mx-auto">
              <a
                href={project.video_url}
                download={`reelforge-${project.id}.mp4`}
                className="btn-primary w-full flex items-center justify-center gap-2 py-3"
              >
                <Download className="w-4 h-4" />
                {t('project.download')}
              </a>
            </div>
          )}
        </div>

        <div className="space-y-6">
          <div className="card">
            <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">{t('project.product')}</h2>
            <div className="space-y-3">
              <div>
                <p className="text-xs text-gray-500">{t('project.productTitle')}</p>
                <p className="text-white font-medium">{project.title}</p>
              </div>
              <div>
                <p className="text-xs text-gray-500">{t('project.price')}</p>
                <p className="text-brand-400 font-bold text-xl">
                  ${parseFloat(project.price).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                </p>
              </div>
              <div>
                <p className="text-xs text-gray-500">{t('project.description')}</p>
                <p className="text-gray-300 text-sm leading-relaxed">{project.description}</p>
              </div>
            </div>
          </div>

          {project.images?.length > 0 && (
            <div className="card">
              <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                {t('project.photos', { count: project.images.length })}
              </h2>
              <div className="grid grid-cols-5 gap-2">
                {project.images.map((img) => (
                  <div key={img.id} className="aspect-[9/16] rounded-lg overflow-hidden bg-gray-800">
                    <img src={img.url} alt={`Slide ${img.order}`} className="w-full h-full object-cover" />
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="card">
            <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">{t('project.videoGen')}</h2>

            {isPhotoGuided && (
              <div className="p-3 mb-4 rounded-xl bg-brand-600/10 border border-brand-500/25">
                <p className="text-sm text-brand-200">{t('project.photoGuidedVideoBlock')}</p>
                <p className="text-xs text-gray-500 mt-2">{t('project.photoGuidedGenNote')}</p>
              </div>
            )}

            {isProcessing && (
              <div className="flex items-center gap-3 p-3 bg-yellow-500/10 rounded-xl border border-yellow-500/20 mb-4">
                <Clock className="w-5 h-5 text-yellow-400 flex-shrink-0" />
                <div>
                  <p className="text-sm text-yellow-300 font-medium">{t('project.processing')}</p>
                  <p className="text-xs text-gray-500">{t('project.processingSub')}</p>
                </div>
              </div>
            )}

            {isDone && (
              <div className="flex items-center gap-3 p-3 bg-green-500/10 rounded-xl border border-green-500/20 mb-4">
                <CheckCircle2 className="w-5 h-5 text-green-400 flex-shrink-0" />
                <div>
                  <p className="text-sm text-green-300 font-medium">{t('project.ready')}</p>
                  <p className="text-xs text-gray-500">{t('project.readySub')}</p>
                </div>
              </div>
            )}

            {project.status === 'failed' && (
              <div className="flex items-center gap-3 p-3 bg-red-500/10 rounded-xl border border-red-500/20 mb-4">
                <AlertCircle className="w-5 h-5 text-red-400 flex-shrink-0" />
                <div>
                  <p className="text-sm text-red-300 font-medium">{t('project.failed')}</p>
                  <p className="text-xs text-gray-500">{t('project.failedSub')}</p>
                </div>
              </div>
            )}

            <div className="flex gap-3 flex-wrap">
              {canGenerate && (
                <button
                  onClick={handleGenerate}
                  disabled={generating || project.images?.length < 3}
                  className="btn-primary flex items-center gap-2"
                >
                  {generating ? <Spinner size="sm" /> : <Zap className="w-4 h-4" />}
                  {generating ? t('project.generating') : t('project.generate')}
                </button>
              )}
              <button onClick={refresh} className="btn-secondary flex items-center gap-2">
                <RefreshCw className="w-4 h-4" />
                {t('project.refresh')}
              </button>
            </div>

            {!isPhotoGuided && project.images?.length < 3 && (
              <p className="text-xs text-yellow-400 mt-3">{t('project.needImages')}</p>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
