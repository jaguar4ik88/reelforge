import { Moon, Sun } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useTheme } from '../../context/ThemeContext'

export default function ThemeToggle({ className = '' }) {
  const { t } = useTranslation()
  const { resolvedTheme, toggleTheme } = useTheme()
  const isDark = resolvedTheme === 'dark'

  return (
    <button
      type="button"
      onClick={() => toggleTheme()}
      className={
        `inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rf-border bg-rf-muted text-rf-text ` +
        `transition-colors hover:bg-rf-elevated hover:text-rf-text focus:outline-none focus-visible:ring-2 ` +
        `focus-visible:ring-brand-500/40 ${className}`.trim()
      }
      aria-label={isDark ? t('theme.switchToLight') : t('theme.switchToDark')}
      title={isDark ? t('theme.switchToLight') : t('theme.switchToDark')}
    >
      {isDark ? <Sun className="h-4 w-4 shrink-0" aria-hidden /> : <Moon className="h-4 w-4 shrink-0" aria-hidden />}
    </button>
  )
}
