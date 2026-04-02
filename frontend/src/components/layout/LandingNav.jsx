import { useState } from 'react'
import { Link, NavLink } from 'react-router-dom'
import { Film, Menu, X } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import LanguageSwitcher from '../ui/LanguageSwitcher'

export default function LandingNav() {
  const { t } = useTranslation()
  const [mobileOpen, setMobileOpen] = useState(false)

  const navLinks = [
    { to: '/pricing', label: t('navLinks.pricing') },
    { to: '/blog',    label: t('navLinks.blog') },
  ]

  return (
    <nav className="relative z-10 max-w-6xl mx-auto px-6 py-5">
      <div className="flex items-center justify-between">
        {/* Logo */}
        <Link to="/" className="flex items-center gap-2">
          <Film className="w-8 h-8 text-brand-400" />
          <span className="text-white font-bold text-2xl gradient-text">ReelForge</span>
        </Link>

        {/* Desktop nav links */}
        <div className="hidden md:flex items-center gap-1">
          {navLinks.map(({ to, label }) => (
            <NavLink
              key={to}
              to={to}
              className={({ isActive }) =>
                `px-4 py-2 rounded-xl text-sm font-medium transition-colors duration-150 ${
                  isActive
                    ? 'text-white bg-white/10'
                    : 'text-gray-400 hover:text-white hover:bg-white/5'
                }`
              }
            >
              {label}
            </NavLink>
          ))}
        </div>

        {/* Right side */}
        <div className="hidden md:flex items-center gap-3">
          <Link to="/login"    className="btn-secondary text-sm">{t('landing.ctaLogin')}</Link>
          <LanguageSwitcher />
        </div>

        {/* Mobile burger */}
        <button
          className="md:hidden text-gray-400 hover:text-white"
          onClick={() => setMobileOpen(o => !o)}
        >
          {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Mobile menu */}
      {mobileOpen && (
        <div className="md:hidden mt-4 p-4 rounded-2xl bg-gray-900/80 backdrop-blur-sm border border-white/10 flex flex-col gap-2">
          {navLinks.map(({ to, label }) => (
            <NavLink
              key={to}
              to={to}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) =>
                `px-4 py-2.5 rounded-xl text-sm font-medium transition-colors ${
                  isActive ? 'text-white bg-white/10' : 'text-gray-400 hover:text-white hover:bg-white/5'
                }`
              }
            >
              {label}
            </NavLink>
          ))}
          <div className="border-t border-white/10 pt-3 mt-1 flex flex-col gap-2">
            <Link to="/login"    className="btn-secondary text-sm text-center" onClick={() => setMobileOpen(false)}>
              {t('landing.ctaLogin')}
            </Link>
            <Link to="/register" className="btn-primary  text-sm text-center" onClick={() => setMobileOpen(false)}>
              {t('landing.ctaFree')}
            </Link>
          </div>
        </div>
      )}
    </nav>
  )
}
