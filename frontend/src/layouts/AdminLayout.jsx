import { Outlet, NavLink, Link, useNavigate } from 'react-router-dom'
import { Film, LayoutDashboard, LayoutTemplate, LogOut } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import toast from 'react-hot-toast'
import { ADMIN_BASE } from '../constants/routes'

export default function AdminLayout() {
  const { t } = useTranslation()
  const { user, logout } = useAuthContext()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
    toast.success(t('nav.logout'))
  }

  return (
    <div className="min-h-screen bg-gray-950 flex">
      <aside className="w-64 flex-shrink-0 border-r border-white/10 bg-gray-900/50 backdrop-blur-sm flex flex-col">
        <div className="p-6 border-b border-white/10">
          <Link to={`${ADMIN_BASE}/dashboard`} className="flex items-center gap-2">
            <Film className="w-7 h-7 text-amber-400" />
            <span className="text-white font-bold text-xl">ReelForge Admin</span>
          </Link>
        </div>
        <nav className="flex-1 p-4 space-y-1">
          <NavLink
            to={`${ADMIN_BASE}/dashboard`}
            end
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-amber-600/20 text-amber-300 border border-amber-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <LayoutDashboard className="w-4 h-4" />
            {t('admin.nav.dashboard')}
          </NavLink>
          <NavLink
            to={`${ADMIN_BASE}/templates`}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 ${
                isActive
                  ? 'bg-amber-600/20 text-amber-300 border border-amber-500/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`
            }
          >
            <LayoutTemplate className="w-4 h-4" />
            {t('admin.nav.templates')}
          </NavLink>
        </nav>
        <div className="p-4 border-t border-white/10">
          <p className="text-xs text-gray-500 truncate mb-3">{user?.email}</p>
          <button
            type="button"
            onClick={handleLogout}
            className="flex items-center gap-2 text-sm text-gray-400 hover:text-red-400 transition-colors"
          >
            <LogOut className="w-4 h-4" />
            {t('nav.logout')}
          </button>
        </div>
      </aside>
      <main className="flex-1 overflow-auto">
        <div className="max-w-6xl mx-auto p-8">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
