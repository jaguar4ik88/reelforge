import { useLocale } from '../../hooks/useLocale'
import { useAuthContext } from '../../context/AuthContext'

const LANGS = [
  { code: 'uk', flag: '🇺🇦', label: 'UA' },
  { code: 'en', flag: '🇬🇧', label: 'EN' },
]

export default function LanguageSwitcher({ compact = false }) {
  const { locale, changeLocale } = useLocale()
  const { user } = useAuthContext()

  return (
    <div className="flex items-center gap-1 bg-white/5 rounded-lg p-1">
      {LANGS.map(({ code, flag, label }) => (
        <button
          key={code}
          onClick={() => changeLocale(code, !!user)}
          className={`flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold transition-all duration-150
            ${locale === code
              ? 'bg-brand-600 text-white shadow-sm'
              : 'text-gray-400 hover:text-white hover:bg-white/10'
            }`}
          title={code === 'uk' ? 'Українська' : 'English'}
        >
          <span>{flag}</span>
          {!compact && <span>{label}</span>}
        </button>
      ))}
    </div>
  )
}
