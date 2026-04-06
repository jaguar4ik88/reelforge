import { useState } from 'react'
import { ChevronDown } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { LANDING_FAQ_KEYS } from '../../constants/landingFaqKeys'

function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false)
  return (
    <div className="border border-white/10 rounded-2xl overflow-hidden">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-white/5 transition-colors"
      >
        <span className="font-medium text-white">{q}</span>
        <ChevronDown
          className={`w-5 h-5 text-gray-400 flex-shrink-0 ml-4 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
        />
      </button>
      {open && (
        <div className="px-6 pb-5 text-gray-400 text-sm leading-relaxed border-t border-white/10 pt-4">
          {a}
        </div>
      )}
    </div>
  )
}

export default function FaqSection({ className = '' }) {
  const { t } = useTranslation()
  return (
    <section className={`max-w-3xl mx-auto px-6 ${className}`} aria-labelledby="landing-faq-heading">
      <div className="text-center mb-12">
        <h2 id="landing-faq-heading" className="text-3xl font-bold mb-3 text-white">
          {t('landing.faq.title')}
        </h2>
        <p className="text-gray-400">{t('landing.faq.subtitle')}</p>
      </div>
      <div className="flex flex-col gap-3">
        {LANDING_FAQ_KEYS.map((key) => (
          <FaqItem
            key={key}
            q={t(`landing.faq.items.${key}.q`)}
            a={t(`landing.faq.items.${key}.a`)}
          />
        ))}
      </div>
    </section>
  )
}
