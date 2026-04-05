import { useEffect, useRef, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { useProject } from '../hooks/useProjects'
import { APP_BASE } from '../constants/routes'
import StatusBadge from '../components/ui/StatusBadge'
import Spinner from '../components/ui/Spinner'
import ZoomableImage from '../components/ui/ZoomableImage'
import {
  Download,
  ArrowLeft,
  Film,
  Image,
  RefreshCw,
  Clock,
  CheckCircle2,
  AlertCircle,
  Sparkles,
  Share2,
  Video,
} from 'lucide-react'

function ProcessingAnimation({ label, sub, variant = 'film' }) {
  const Icon = variant === 'photo' ? Image : Film
  return (
    <div className="w-full max-w-xl mx-auto">
      <div className="aspect-[4/5] max-h-[min(70vh,640px)] rounded-2xl bg-gray-900 border border-white/10 flex flex-col items-center justify-center gap-4 shadow-2xl">
        <div className="relative">
          <div className="w-16 h-16 rounded-full border-4 border-brand-500/20 border-t-brand-500 animate-spin" />
          <Icon className="absolute inset-0 m-auto w-7 h-7 text-brand-400" />
        </div>
        <div className="text-center px-6">
          <p className="text-white font-semibold">{label}</p>
          <p className="text-gray-500 text-sm mt-1">{sub}</p>
        </div>
      </div>
    </div>
  )
}

function ImageResult({ url }) {
  return (
    <div className="relative mx-auto w-full max-w-xl">
      <div className="overflow-hidden rounded-2xl shadow-2xl shadow-brand-900/40 ring-4 ring-white/10">
        <ZoomableImage
          src={url}
          alt=""
          className="block w-full"
          imageClassName="max-h-[min(70vh,640px)] w-full object-cover"
        />
      </div>
    </div>
  )
}

const IMPROVE_CREDIT_COST = 1

function PhotoGuidedProjectBody({ project, t }) {
  const [improveNote, setImproveNote] = useState('')
  const isProcessing = project.status === 'processing'
  const isDone         = project.status === 'done'
  const hasResult      = Boolean(project.result_url)
  const genSettings    = project.generation?.settings ?? {}
  const contentType    = genSettings.content_type ?? 'photo'
  const showCreateVideo =
    isDone && hasResult && (contentType === 'photo' || contentType === 'card')

  const handleCreateVideo = () => {
    toast(t('project.createVideoSoon'), { icon: '🎬' })
  }

  const handleImprove = () => {
    toast(t('project.improveSoonDetail', { count: IMPROVE_CREDIT_COST }), { icon: '✨' })
  }

  const qualities = Array.isArray(project.product_meta?.qualities)
    ? project.product_meta.qualities
    : []

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[1fr_minmax(300px,400px)] gap-8 lg:gap-10 items-start">
      <div>
        <div className="rounded-2xl overflow-hidden bg-gray-900/50 border border-white/10">
          {isDone && hasResult ? (
            <div className="relative overflow-hidden rounded-2xl bg-black/40">
              <ZoomableImage
                src={project.result_url}
                alt=""
                className="block w-full"
                imageClassName="max-h-[min(70vh,720px)] w-full object-contain"
              />
            </div>
          ) : isProcessing ? (
            <ProcessingAnimation
              variant="photo"
              label={t('project.processingPhoto')}
              sub={t('project.processingPhotoSub')}
            />
          ) : (
            <div className="aspect-[4/5] max-h-[min(70vh,640px)] flex flex-col items-center justify-center gap-3 px-6">
              <Image className="w-16 h-16 text-gray-700" />
              <p className="text-gray-500 text-sm text-center">
                {project.status === 'failed' ? t('project.failedSub') : t('project.noVideo')}
              </p>
            </div>
          )}
        </div>

      </div>

      <aside className="space-y-6">
        {qualities.length > 0 && (
          <div className="card">
            <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
              {t('project.qualities')}
            </h2>
            <div className="flex flex-wrap gap-2">
              {qualities.map((q) => (
                <span
                  key={q}
                  className="text-xs px-3 py-1 rounded-full bg-white/5 border border-white/10 text-gray-200"
                >
                  {q}
                </span>
              ))}
            </div>
          </div>
        )}

        {showCreateVideo && (
          <button
            type="button"
            onClick={handleCreateVideo}
            className="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gray-900 hover:bg-gray-800 border border-white/10 text-white font-medium transition-colors"
          >
            <Video className="w-5 h-5 shrink-0" />
            {t('project.createVideo')}
          </button>
        )}

        <div className="card">
          <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
            <Sparkles className="w-3.5 h-3.5 text-brand-400" />
            {t('project.promptLabel')}
          </h2>
          {project.generation?.image_caption && (
            <div className="mb-3">
              <p className="text-xs text-gray-500 mb-1">{t('project.imageCaption')}</p>
              <p className="text-xs text-gray-400 italic leading-relaxed">
                {project.generation.image_caption}
              </p>
            </div>
          )}
          {project.generation?.final_prompt ? (
            <p className="text-sm text-gray-300 leading-relaxed bg-gray-900/60 rounded-xl p-3 border border-white/5 font-mono whitespace-pre-wrap">
              {project.generation.final_prompt}
            </p>
          ) : (
            <p className="text-sm text-gray-500">{t('project.noVideo')}</p>
          )}
        </div>

        <div className="card">
          <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            {t('project.improvements')}
          </h2>
          <textarea
            value={improveNote}
            onChange={(e) => setImproveNote(e.target.value)}
            placeholder={t('project.improvementsPlaceholder')}
            rows={3}
            disabled={isProcessing}
            className="w-full rounded-xl bg-gray-900/80 border border-white/10 px-3 py-2.5 text-sm text-white placeholder:text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500/40 mb-3 disabled:opacity-50"
          />
          <button
            type="button"
            onClick={handleImprove}
            disabled={isProcessing}
            className="btn-primary w-full flex items-center justify-center gap-2 py-3 disabled:opacity-50"
          >
            {t('project.improveButton', { count: IMPROVE_CREDIT_COST })}
          </button>
        </div>

        {project.images?.length > 0 && (
          <div className="card">
            <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
              {t('project.referencePhotos')}
            </h2>
            <div className="grid grid-cols-4 gap-2">
              {project.images.map((img) => (
                <div
                  key={img.id}
                  className="aspect-[9/16] overflow-hidden rounded-lg bg-gray-800 ring-1 ring-white/10 transition-all hover:ring-brand-500/50"
                >
                  <ZoomableImage
                    variant="thumb"
                    src={img.url}
                    alt=""
                    className="h-full w-full"
                    imageClassName="h-full w-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>
        )}

      </aside>
    </div>
  )
}

export default function ProjectView() {
  const { id }                        = useParams()
  const { t }                         = useTranslation()
  const { project, loading, refresh } = useProject(id)
  const pollRef                       = useRef(null)

  useEffect(() => {
    if (project?.status === 'processing') {
      pollRef.current = setInterval(refresh, 5000)
    } else {
      clearInterval(pollRef.current)
    }
    return () => clearInterval(pollRef.current)
  }, [project?.status, refresh])

  if (loading) return <div className="flex justify-center py-20"><Spinner size="lg" /></div>

  if (!project) {
    return (
      <div className="text-center py-20">
        <p className="text-gray-400">{t('project.notFound')}</p>
        <Link to={`${APP_BASE}/dashboard`} className="btn-secondary mt-4 inline-block">{t('project.backDashboard')}</Link>
      </div>
    )
  }

  const isPhotoGuided  = project.creation_flow === 'photo_guided'
  const isProcessing   = project.status === 'processing'
  const isDone         = project.status === 'done'

  const downloadUrl = project.result_url || project.video_url || null
  const canDownload   = isDone && Boolean(downloadUrl)
  const downloadName  = project.video_url
    ? `reelforge-${project.id}.mp4`
    : `reelforge-${project.id}.png`

  const handleShare = async () => {
    const url = window.location.href
    try {
      if (navigator.share) {
        await navigator.share({ title: project.title, url })
      } else {
        await navigator.clipboard.writeText(url)
        toast.success(t('project.linkCopied'))
      }
    } catch (e) {
      if (e?.name !== 'AbortError') {
        try {
          await navigator.clipboard.writeText(url)
          toast.success(t('project.linkCopied'))
        } catch {
          toast.error(t('common.error'))
        }
      }
    }
  }

  return (
    <div>
      <div className="flex flex-col gap-4 mb-8">
        <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
          <div className="flex flex-col sm:flex-row sm:items-start gap-4 flex-1 min-w-0">
            <div className="flex items-center gap-3 flex-wrap shrink-0">
              <Link
                to={`${APP_BASE}/gallery`}
                className="text-sm text-gray-400 hover:text-white transition-colors"
              >
                {t('project.backGallery')}
              </Link>
              <span className="text-gray-600 hidden sm:inline">·</span>
              <Link to={`${APP_BASE}/dashboard`} className="text-gray-400 hover:text-white transition-colors">
                <ArrowLeft className="w-5 h-5" />
              </Link>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-3 flex-wrap">
                <h1 className="text-2xl font-bold text-white truncate">{project.title}</h1>
                <StatusBadge status={project.status} />
              </div>
              <p className="text-gray-500 text-sm mt-0.5">
                {isPhotoGuided
                  ? `${t('project.photoFlowBadge')} · ${new Date(project.created_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`
                  : new Date(project.created_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}
              </p>
            </div>
          </div>
          <div className="flex flex-wrap gap-2 shrink-0">
            {canDownload && (
              <a
                href={downloadUrl}
                download={downloadName}
                target="_blank"
                rel="noreferrer"
                className="btn-primary inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm"
              >
                <Download className="w-4 h-4 shrink-0" />
                {t('project.download')}
              </a>
            )}
            <button
              type="button"
              onClick={handleShare}
              className="btn-secondary inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm"
            >
              <Share2 className="w-4 h-4 shrink-0" />
              {t('project.share')}
            </button>
          </div>
        </div>
      </div>

      {isPhotoGuided ? (
        <PhotoGuidedProjectBody project={project} t={t} />
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div>
            {isDone && project.video_url ? (
              <div className="relative max-w-sm mx-auto">
                <div className="rounded-2xl overflow-hidden shadow-2xl shadow-brand-900/40 ring-4 ring-white/10">
                  <video src={project.video_url} controls className="w-full" playsInline />
                </div>
              </div>
            ) : isDone && project.result_url ? (
              <ImageResult url={project.result_url} />
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
                    <div key={img.id} className="aspect-[9/16] overflow-hidden rounded-lg bg-gray-800">
                      <ZoomableImage
                        variant="thumb"
                        src={img.url}
                        alt={`Slide ${img.order}`}
                        className="h-full w-full"
                        imageClassName="h-full w-full object-cover"
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div className="card">
              <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">{t('project.videoGen')}</h2>

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
                <button type="button" onClick={refresh} className="btn-secondary flex items-center gap-2">
                  <RefreshCw className="w-4 h-4" />
                  {t('project.refresh')}
                </button>
              </div>
            </div>

            {project.generation?.final_prompt && (
              <div className="card">
                <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                  <Sparkles className="w-3.5 h-3.5 text-brand-400" />
                  {t('project.promptSent')}
                </h2>
                {project.generation.image_caption && (
                  <div className="mb-3">
                    <p className="text-xs text-gray-500 mb-1">{t('project.imageCaption')}</p>
                    <p className="text-xs text-gray-400 italic leading-relaxed">
                      {project.generation.image_caption}
                    </p>
                  </div>
                )}
                <p className="text-xs text-gray-300 leading-relaxed bg-gray-900/60 rounded-xl p-3 border border-white/5 font-mono whitespace-pre-wrap">
                  {project.generation.final_prompt}
                </p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
