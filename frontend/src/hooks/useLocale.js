import { useCallback } from 'react'
import { useTranslation } from 'react-i18next'
import { profileApi } from '../services/api'

export function useLocale() {
  const { i18n } = useTranslation()

  const changeLocale = useCallback(async (locale, isAuthenticated = false) => {
    await i18n.changeLanguage(locale)
    localStorage.setItem('reelforge_locale', locale)

    if (isAuthenticated) {
      try {
        await profileApi.update({ locale })
      } catch {
        // locale saved locally even if API fails
      }
    }
  }, [i18n])

  return { locale: i18n.language, changeLocale }
}
