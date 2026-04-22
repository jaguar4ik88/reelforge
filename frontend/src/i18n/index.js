import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'
import uk from './uk'
import en from './en'

const LOCALE_KEY = 'app_locale'
if (typeof localStorage !== 'undefined') {
  const legacy = localStorage.getItem('reelforge_locale')
  if (legacy && !localStorage.getItem(LOCALE_KEY)) {
    localStorage.setItem(LOCALE_KEY, legacy)
  }
}

i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources: { uk, en },
    supportedLngs:     ['uk', 'en'],
    fallbackLng:       'uk',
    defaultNS:         'translation',
    interpolation:     { escapeValue: false },
    detection: {
      order:  ['localStorage', 'navigator'],
      caches: ['localStorage'],
      lookupLocalStorage: LOCALE_KEY,
    },
  })

export default i18n
