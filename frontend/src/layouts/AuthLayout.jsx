import { Outlet, Link } from 'react-router-dom'
import { Film } from 'lucide-react'
import { useSite } from '../context/SiteContext'
import LanguageSwitcher from '../components/ui/LanguageSwitcher'

export default function AuthLayout() {
  const { siteName } = useSite()
  return (
    <div className="min-h-screen bg-gray-950 flex flex-col">
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 -left-40 w-96 h-96 bg-brand-900/30 rounded-full blur-3xl" />
        <div className="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-900/20 rounded-full blur-3xl" />
      </div>

      <header className="relative z-10 p-6 flex items-center justify-between">
        <Link to="/" className="inline-flex items-center gap-2 text-white font-bold text-xl">
          <Film className="w-7 h-7 text-brand-400" />
          <span className="gradient-text">{siteName}</span>
        </Link>
        <LanguageSwitcher />
      </header>

      <main className="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <Outlet />
      </main>
    </div>
  )
}
