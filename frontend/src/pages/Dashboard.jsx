import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  Coins,
  Layers,
  Images,
  Image,
  Video as VideoIcon,
  Sparkles,
} from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import { APP_BASE } from '../constants/routes'
import { homeApi } from '../services/api'
import ProjectCard from '../components/ProjectCard'
import Spinner from '../components/ui/Spinner'

const shortcuts = [
  { to: `${APP_BASE}/credits`, icon: Coins, key: 'credits', color: 'from-sky-600 to-cyan-600' },
  { to: `${APP_BASE}/templates`, icon: Layers, key: 'templates', color: 'from-violet-600 to-purple-600' },
  { to: `${APP_BASE}/gallery`, icon: Images, key: 'gallery', color: 'from-amber-600 to-orange-600' },
]

function StatCard({ label, value, icon: Icon }) {
  return (
    <div className="card flex flex-col gap-2 rounded-2xl border border-white/10 p-4">
      <div className="flex items-center gap-2 text-gray-500">
        <Icon className="h-4 w-4 shrink-0 text-brand-400" />
        <span className="text-xs font-medium uppercase tracking-wide">{label}</span>
      </div>
      <p className="text-2xl font-bold tabular-nums text-white">{value}</p>
    </div>
  )
}

export default function Dashboard() {
  const { t } = useTranslation()
  const { user } = useAuthContext()
  const [home, setHome] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    homeApi
      .dashboard()
      .then((res) => {
        const payload = res.data?.data
        if (!cancelled && payload) setHome(payload)
      })
      .catch(() => {
        if (!cancelled) setHome(null)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [user?.subscription?.slug, user?.has_active_subscription])

  const planKey = home?.plan ? `common.plan.${home.plan}` : ''
  const legacyPlanLabel =
    planKey && t(planKey) !== planKey ? t(planKey) : home?.plan ?? '—'
  const planLabel = home?.subscription?.name?.trim()
    ? home.subscription.name
    : legacyPlanLabel

  return (
    <div>
      <div className="mb-10">
        <h1 className="text-3xl font-bold text-white">
          {user?.name
            ? t('dashboardHome.title', { name: user.name.split(' ')[0] })
            : t('dashboardHome.titleGuest')}
        </h1>
        <p className="mt-2 max-w-xl text-gray-400">{t('dashboardHome.subtitle')}</p>
      </div>

      {loading && (
        <div className="mb-10 flex justify-center py-8">
          <Spinner size="lg" />
        </div>
      )}

      {!loading && home && (
        <>
          <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <h2 className="text-lg font-semibold text-white">{t('dashboardHome.overview')}</h2>
            <Link to={`${APP_BASE}/gallery`} className="btn-secondary self-start px-5 py-2 text-sm sm:self-auto">
              {t('dashboardHome.viewGallery')}
            </Link>
          </div>

          <div className="mb-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard label={t('dashboardHome.stats.plan')} value={planLabel} icon={Sparkles} />
            <StatCard label={t('dashboardHome.stats.credits')} value={home.credits_balance} icon={Coins} />
            <StatCard label={t('dashboardHome.stats.images')} value={home.images_generated} icon={Image} />
            <StatCard label={t('dashboardHome.stats.videos')} value={home.videos_generated} icon={VideoIcon} />
          </div>

          <h3 className="mb-4 text-lg font-semibold text-white">{t('dashboardHome.recentProjects')}</h3>
          {home.recent_projects?.length > 0 ? (
            <div className="mb-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {home.recent_projects.map((p) => (
                <ProjectCard key={p.id} project={p} />
              ))}
            </div>
          ) : (
            <p className="mb-12 text-sm text-gray-500">{t('dashboardHome.emptyProjects')}</p>
          )}
        </>
      )}

      <h2 className="mb-4 text-lg font-semibold text-white">{t('dashboardHome.shortcuts')}</h2>
      <div className="grid gap-4 sm:grid-cols-2">
        {shortcuts.map(({ to, icon: Icon, key, color }) => (
          <Link
            key={to}
            to={to}
            className="group card flex items-center gap-4 p-5 transition-all duration-200 hover:border-brand-500/40"
          >
            <div
              className={`flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br ${color} shadow-lg`}
            >
              <Icon className="h-7 w-7 text-white" />
            </div>
            <div>
              <h3 className="font-semibold text-white transition-colors group-hover:text-brand-300">
                {t(`nav.${key}`)}
              </h3>
              <p className="mt-0.5 text-sm text-gray-500">{t(`dashboardHome.desc.${key}`)}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
