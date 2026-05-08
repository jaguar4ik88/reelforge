import { useEffect, useRef, useState, useCallback } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { useProject } from '../hooks/useProjects'
import { APP_BASE } from '../constants/routes'
import StatusBadge from '../components/ui/StatusBadge'
import Spinner from '../components/ui/Spinner'
import ZoomableImage from '../components/ui/ZoomableImage'
import { projectsApi } from '../services/api'
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
  Trash2,
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

function PhotoGuidedGenerationPlaceholder({ referenceUrl, contentType, t }) {
  const Icon = contentType === 'video' ? Film : Image
  return (
    <div className="relative w-full max-w-xl mx-auto aspect-[4/5] max-h-[min(70vh,720px)] rounded-2xl overflow-hidden border border-brand-500/25 shadow-2xl shadow-brand-900/30 bg-gray-950">
      {referenceUrl ? (
        <img
          src={referenceUrl}
          alt=""
          className="absolute inset-0 w-full h-full object-cover scale-110 blur-lg opacity-35"
          draggable={false}
          aria-hidden
        />
      ) : null}
      <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/90 to-gray-950/70" aria-hidden />
      <div className="relative z-10 h-full min-h-[280px] flex flex-col items-center justify-center gap-5 px-6 text-center">
        <div className="relative">
          <div className="w-20 h-20 rounded-full border-4 border-brand-500/25 border-t-brand-500 animate-spin" />
          <Icon className="absolute inset-0 m-auto w-9 h-9 text-brand-400" aria-hidden />
        </div>
        <div className="space-y-2">
          <p className="text-white text-lg font-semibold tracking-tight">{t('project.generatingProjectTitle')}</p>
          <p className="text-gray-400 text-sm leading-relaxed max-w-sm mx-auto">
            {contentType === 'video'
              ? t('project.generatingProjectSubVideo')
              : t('project.generatingProjectSubPhoto')}
          </p>
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

function ProjectDeleteFooter({ t, busy, disabled, onDelete }) {
  return (
    <div className="pt-3 mt-1 border-t border-red-500/20">
      <button
        type="button"
        onClick={onDelete}
        disabled={disabled || busy}
        className="w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl border border-red-500/35 bg-red-950/25 text-red-200 text-sm font-medium hover:bg-red-950/45 transition-colors disabled:opacity-45 disabled:cursor-not-allowed"
      >
        {busy ? <Spinner size="sm" /> : <Trash2 className="w-4 h-4 shrink-0" aria-hidden />}
        {busy ? t('project.deleteProjectBusy') : t('project.deleteProject')}
      </button>
      <p className="text-[11px] text-gray-600 text-center mt-2 leading-snug">{t('project.deleteProjectHint')}</p>
    </div>
  )
}

function PhotoGuidedProjectBody({
  project,
  t,
  canDownload,
  downloadUrl,
  downloadName,
  onShare,
  onDeleteProject,
  deleteBusy,
}) {
  const [improveNote, setImproveNote] = useState('')
  const genStatus = project.generation?.status
  const generationRunning =
    genStatus === 'pending' || genStatus === 'processing'
  const isProcessing =
    project.status === 'processing' || generationRunning
  const isDone         = project.status === 'done'
  const hasResult      = Boolean(project.result_url)
  const genSettings    = project.generation?.settings ?? {}
  const contentType    = genSettings.content_type ?? 'photo'
  const resultUrl      = project.result_url || ''
  const showAsVideoPlayer =
    isDone && hasResult && contentType === 'video' && /\.mp4(\?|$)/i.test(String(resultUrl))
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
    <div className="grid grid-cols-1 lg:grid-cols-[1fr_minmax(280px,380px)] gap-8 lg:gap-10 items-start">
      <div className="flex flex-col gap-6 min-w-0">
        <div className="rounded-2xl overflow-hidden bg-gray-900/50 border border-white/10">
          {isDone && hasResult ? (
            showAsVideoPlayer ? (
              <div className="relative overflow-hidden rounded-2xl bg-black shadow-2xl shadow-brand-900/40 ring-4 ring-white/10">
                <video
                  src={resultUrl}
                  controls
                  className="w-full max-h-[min(70vh,720px)]"
                  playsInline
                />
              </div>
            ) : (
              <div className="relative overflow-hidden rounded-2xl bg-black/40">
                <ZoomableImage
                  src={project.result_url}
                  alt=""
                  className="block w-full"
                  imageClassName="max-h-[min(70vh,720px)] w-full object-contain"
                />
              </div>
            )
          ) : isProcessing ? (
            <PhotoGuidedGenerationPlaceholder
              referenceUrl={project.images?.[0]?.url}
              contentType={contentType}
              t={t}
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
            <p className="text-sm text-gray-500">{t('project.noPromptYet')}</p>
          )}
        </div>
      </div>

      <aside className="flex flex-col gap-4 min-w-0">
        {qualities.length > 0 && (
          <div className="card py-3">
            <h2 className="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
              {t('project.qualities')}
            </h2>
            <div className="flex flex-wrap gap-1.5">
              {qualities.map((q) => (
                <span
                  key={q}
                  className="text-[11px] px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-gray-300"
                >
                  {q}
                </span>
              ))}
            </div>
          </div>
        )}

        {canDownload ? (
          <a
            href={downloadUrl}
            download={downloadName}
            target="_blank"
            rel="noreferrer"
            className="btn-primary w-full inline-flex items-center justify-center gap-2 py-3.5 text-sm font-medium"
          >
            <Download className="w-5 h-5 shrink-0" />
            {t('project.download')}
          </a>
        ) : (
          <button
            type="button"
            disabled
            className="btn-primary w-full inline-flex items-center justify-center gap-2 py-3.5 text-sm font-medium opacity-45 cursor-not-allowed pointer-events-none"
          >
            <Download className="w-5 h-5 shrink-0" />
            {t('project.download')}
          </button>
        )}

        <button
          type="button"
          onClick={onShare}
          className="btn-secondary w-full inline-flex items-center justify-center gap-2 py-3.5 text-sm font-medium"
        >
          <Share2 className="w-5 h-5 shrink-0" />
          {t('project.share')}
        </button>

        {showCreateVideo && (
          <button
            type="button"
            onClick={handleCreateVideo}
            className="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gray-900 hover:bg-gray-800 border border-white/10 text-white text-sm font-medium transition-colors"
          >
            <Video className="w-5 h-5 shrink-0" />
            {t('project.createVideo')}
          </button>
        )}

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
            <div className="grid grid-cols-2 gap-2">
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

        <ProjectDeleteFooter
          t={t}
          busy={deleteBusy}
          disabled={isProcessing}
          onDelete={onDeleteProject}
        />
      </aside>
    </div>
  )
}

export default function ProjectView() {
  const { id }                        = useParams()
  const { t }                         = useTranslation()
  const { project, loading, refresh } = useProject(id)
  const pollRef                       = useRef(null)
  const navigate                      = useNavigate()
  const [deleteBusy, setDeleteBusy]   = useState(false)

  const handleDeleteProject = useCallback(async () => {
    if (!id) return
    if (!window.confirm(t('project.deleteProjectConfirm'))) return
    setDeleteBusy(true)
    try {
      await projectsApi.delete(Number(id))
      toast.success(t('project.deleteProjectSuccess'))
      navigate(`${APP_BASE}/gallery`)
    } catch (err) {
      const msg = err?.response?.data?.message ?? t('project.deleteProjectError')
      toast.error(msg)
    } finally {
      setDeleteBusy(false)
    }
  }, [id, t, navigate])

  useEffect(() => {
    const gen = project?.generation?.status
    const pollActive =
      project?.status === 'processing' ||
      (project?.creation_flow === 'photo_guided' &&
        (gen === 'pending' || gen === 'processing'))
    if (pollActive) {
      pollRef.current = setInterval(refresh, 5000)
    } else {
      clearInterval(pollRef.current)
    }
    return () => clearInterval(pollRef.current)
  }, [project?.status, project?.creation_flow, project?.generation?.status, refresh])

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
  const downloadName = (() => {
    const u = downloadUrl || ''
    if (/\.mp4(\?|$)/i.test(u)) return `product-${project.id}.mp4`
    if (/\.webm(\?|$)/i.test(u)) return `product-${project.id}.webm`
    if (/\.jpe?g(\?|$)/i.test(u)) return `product-${project.id}.jpg`
    if (/\.webp(\?|$)/i.test(u)) return `product-${project.id}.webp`
    return `product-${project.id}.png`
  })()

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
              {isPhotoGuided && project.product_meta?.card_photo_analysis && (
                <div className="mt-3 p-3 rounded-lg bg-amber-950/30 border border-amber-500/25 text-left max-w-3xl">
                  <p className="text-xs font-semibold text-amber-200/90 mb-2">
                    {t('project.cardAnalysisTest')}
                  </p>
                  <pre className="text-[10px] leading-relaxed text-gray-300 overflow-x-auto max-h-56 overflow-y-auto font-mono whitespace-pre-wrap break-words">
                    {JSON.stringify(project.product_meta.card_photo_analysis, null, 2)}
                  </pre>
                </div>
              )}
            </div>
          </div>
          {!isPhotoGuided && (
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
          )}
        </div>
      </div>

      {isPhotoGuided ? (
        <PhotoGuidedProjectBody
          project={project}
          t={t}
          canDownload={canDownload}
          downloadUrl={downloadUrl}
          downloadName={downloadName}
          onShare={handleShare}
          onDeleteProject={handleDeleteProject}
          deleteBusy={deleteBusy}
        />
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

            <ProjectDeleteFooter
              t={t}
              busy={deleteBusy}
              disabled={isProcessing}
              onDelete={handleDeleteProject}
            />
          </div>
        </div>
      )}
    </div>
  )
}
