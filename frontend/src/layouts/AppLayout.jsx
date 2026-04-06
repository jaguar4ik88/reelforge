import { Outlet, NavLink, Link, useNavigate, useLocation } from 'react-router-dom'
import { Film, LayoutDashboard, Layers, MessageSquare, Images, LogOut, User, Coins, ImagePlus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import { useSite } from '../context/SiteContext'
import LanguageSwitcher from '../components/ui/LanguageSwitcher'
import SeoHead from '../components/seo/SeoHead'
import toast from 'react-hot-toast'
import { APP_BASE } from '../constants/routes'

export default function AppLayout() {
  const { t } = useTranslation()
  const { user, logout } = useAuthContext()
  const { siteName } = useSite()
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const fullBleed =
    [`${APP_BASE}/templates`, `${APP_BASE}/gallery`, `${APP_BASE}/create`].includes(pathname) ||
    pathname.startsWith(`${APP_BASE}/projects/new-photo`)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
    toast.success(t('nav.logout'))
  }

  const creditCost = user?.credits?.video_generation_cost ?? 10
  const creditBalance = user?.credits?.balance ?? 0
  /** Full bar ≈ credits for 10 generations (visual scale, not a hard cap). */
  const creditsMeterMax = Math.max(creditCost * 10, 1)
  const creditsMeterPercent = Math.min(100, Math.round((creditBalance / creditsMeterMax) * 100))
  const generationsLeft = creditCost > 0 ? Math.floor(creditBalance / creditCost) : 0

  return (
    <div className="min-h-screen bg-gray-950 flex">
      <SeoHead
        titleKey="seo.appAreaTitle"
        descriptionKey="seo.appAreaDescription"
        noindex
      />
      {/* Sidebar */}
      <aside className="w-64 flex-shrink-0 border-r border-white/10 bg-gray-900/50 backdrop-blur-sm flex flex-col h-[100dvh] min-h-0 sticky top-0 self-start">
        <div className="p-6 border-b border-white/10 flex-shrink-0">
          <Link to={`${APP_BASE}/dashboard`} className="flex items-center gap-2">
            <Film className="w-7 h-7 text-brand-400" />
            <span className="text-white font-bold text-xl gradient-text">{siteName}</span>
          </Link>
        </div>

        <nav className="flex-1 min-h-0 overflow-y-auto p-4 space-y-1">
          <NavLink
            to={`${APP_BASE}/dashboard`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <LayoutDashboard className="w-4 h-4" />
            {t('nav.dashboard')}
          </NavLink>

          <NavLink
            to={`${APP_BASE}/templates`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <Layers className="w-4 h-4" />
            {t('nav.templates')}
          </NavLink>

          <NavLink
            to={`${APP_BASE}/projects/new-photo`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <ImagePlus className="w-4 h-4" />
            {t('nav.createFromPhoto')}
          </NavLink>

          <NavLink
            to={`${APP_BASE}/create`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <MessageSquare className="w-4 h-4" />
            {t('nav.create')}
          </NavLink>

          <NavLink
            to={`${APP_BASE}/gallery`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <Images className="w-4 h-4" />
            {t('nav.gallery')}
          </NavLink>

          <NavLink
            to={`${APP_BASE}/profile`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <User className="w-4 h-4" />
            {t('nav.profile')}
          </NavLink>
        </nav>

        {/* Credits usage indicator */}
        {user && (
          <div className="p-4 border-t border-white/10 flex-shrink-0 mt-auto">
            <div className="card p-4 mb-4">
              <div className="flex items-center justify-between gap-2 mb-2">
                <span className="text-xs text-gray-400 font-medium flex items-center gap-1.5">
                  <Coins className="w-3.5 h-3.5 text-amber-400 shrink-0" aria-hidden />
                  {t('layout.creditsUsage')}
                </span>
                <span className="text-xs text-amber-300 font-semibold tabular-nums shrink-0">
                  {creditBalance}
                </span>
              </div>
              <p className="text-[11px] text-gray-500 mb-2">
                {t('layout.creditsPerVideoShort', { count: creditCost })}
                {' · '}
                {t('layout.creditsGenerationsApprox', { count: generationsLeft })}
              </p>
              <div className="w-full bg-gray-800 rounded-full h-1.5">
                <div
                  className="h-1.5 rounded-full bg-gradient-to-r from-amber-500 to-amber-300 transition-all duration-500"
                  style={{ width: `${creditsMeterPercent}%` }}
                />
              </div>
              {creditBalance < creditCost && (
                <p className="text-xs text-amber-400/90 mt-2">{t('layout.lowCredits')}</p>
              )}
              <p className="text-xs text-gray-500 mt-2">
                <Link to="/pricing" className="text-brand-400 hover:underline">
                  {t('layout.getCredits')}
                </Link>
                {user.plan === 'free' && (
                  <>
                    {' · '}
                    <Link to={`${APP_BASE}/profile`} className="text-brand-400 hover:underline">
                      {t('layout.upgrade')}
                    </Link>
                  </>
                )}
              </p>
            </div>

            {/* Language switcher */}
            <div className="mb-3">
              <LanguageSwitcher />
            </div>

            {/* User info */}
            <div className="flex items-center gap-3">
              {user.avatar_url ? (
                <img
                  src={user.avatar_url}
                  alt={user.name}
                  className="w-9 h-9 rounded-full object-cover flex-shrink-0"
                />
              ) : (
                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center text-sm font-bold flex-shrink-0">
                  {user.name.charAt(0).toUpperCase()}
                </div>
              )}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-white truncate">{user.name}</p>
                <p className="text-xs text-gray-500 capitalize">{t(`common.plan.${user.plan}`)}</p>
              </div>
              <button
                onClick={handleLogout}
                className="text-gray-500 hover:text-red-400 transition-colors p-1"
                title={t('nav.logout')}
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-auto">
        <div className={fullBleed ? 'min-h-full p-6 lg:p-8' : 'max-w-6xl mx-auto p-8'}>
          <Outlet />
        </div>
      </main>
    </div>
  )
}
