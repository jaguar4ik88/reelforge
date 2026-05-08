import { Outlet, Link } from 'react-router-dom'
import { Film } from 'lucide-react'
import { useSite } from '../context/SiteContext'
import LanguageSwitcher from '../components/ui/LanguageSwitcher'
import ThemeToggle from '../components/ui/ThemeToggle'

export default function AuthLayout() {
  const { siteName } = useSite()
  return (
    <div className="min-h-screen bg-rf-page flex flex-col">
      <header className="relative z-10 p-6 flex items-center justify-between">
        <Link to="/" className="inline-flex items-center gap-2 text-rf-text font-bold text-xl">
          <Film className="w-7 h-7 text-brand-400" />
          <span className="gradient-text">{siteName}</span>
        </Link>
        <div className="flex items-center gap-3">
          <ThemeToggle />
          <LanguageSwitcher />
        </div>
      </header>

      <main className="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <Outlet />
      </main>
    </div>
  )
}
