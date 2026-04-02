import { Link } from 'react-router-dom'
import { Coins, Layers, Package, MessageSquare, Images } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'

const shortcuts = [
  { to: '/templates',   icon: Layers,        key: 'templates',   color: 'from-violet-600 to-purple-600' },
  { to: '/products',    icon: Package,       key: 'products',    color: 'from-brand-600 to-rose-600' },
  { to: '/create',      icon: MessageSquare, key: 'create',      color: 'from-emerald-600 to-teal-600' },
  { to: '/gallery',     icon: Images,        key: 'gallery',     color: 'from-amber-600 to-orange-600' },
]

export default function Dashboard() {
  const { t } = useTranslation()
  const { user } = useAuthContext()

  return (
    <div>
      <div className="mb-10">
        <h1 className="text-3xl font-bold text-white">
          {user?.name
            ? t('dashboardHome.title', { name: user.name.split(' ')[0] })
            : t('dashboardHome.titleGuest')}
        </h1>
        <p className="text-gray-400 mt-2 max-w-xl">{t('dashboardHome.subtitle')}</p>
      </div>

      {user && (
        <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
          <div className="card text-center">
            <p className="text-3xl font-bold gradient-text">{user.videos_this_month}</p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.videosMonth')}</p>
          </div>
          <div className="card text-center">
            <p className="text-3xl font-bold text-white">{user.video_limit}</p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.monthLimit')}</p>
          </div>
          <div className="card text-center">
            <div className="flex items-center justify-center gap-2 mb-1">
              <Coins className="w-7 h-7 text-amber-400" aria-hidden />
              <p className="text-3xl font-bold text-amber-300 tabular-nums">
                {user.credits?.balance ?? 0}
              </p>
            </div>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.creditsBalance')}</p>
            {user.credits?.video_generation_cost != null && (
              <p className="text-[11px] text-gray-600 mt-1">
                {t('dashboard.creditsPerVideo', { count: user.credits.video_generation_cost })}
              </p>
            )}
          </div>
          <div className="card text-center">
            <p className="text-3xl font-bold text-brand-400 capitalize">
              {t(`common.plan.${user.plan}`)}
            </p>
            <p className="text-xs text-gray-500 mt-1">{t('dashboard.currentPlan')}</p>
          </div>
        </div>
      )}

      <h2 className="text-lg font-semibold text-white mb-4">{t('dashboardHome.shortcuts')}</h2>
      <div className="grid sm:grid-cols-2 gap-4">
        {shortcuts.map(({ to, icon: Icon, key, color }) => (
          <Link
            key={to}
            to={to}
            className="group card hover:border-brand-500/40 transition-all duration-200 flex items-center gap-4 p-5"
          >
            <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${color} flex items-center justify-center flex-shrink-0 shadow-lg`}>
              <Icon className="w-7 h-7 text-white" />
            </div>
            <div>
              <h3 className="font-semibold text-white group-hover:text-brand-300 transition-colors">
                {t(`nav.${key}`)}
              </h3>
              <p className="text-sm text-gray-500 mt-0.5">{t(`dashboardHome.desc.${key}`)}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
