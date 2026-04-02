import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'
import uk from './uk'
import en from './en'

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
      lookupLocalStorage: 'reelforge_locale',
    },
  })

export default i18n
