import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { templatesApi } from '../services/api'
import Spinner from './ui/Spinner'
import { CheckCircle2 } from 'lucide-react'

const STYLE_PREVIEWS = {
  dark:  { bg: 'from-gray-900 to-gray-800',       accent: '#FFD700',  text: 'white' },
  light: { bg: 'from-gray-100 to-white',           accent: '#E53E3E',  text: '#111' },
  neon:  { bg: 'from-[#0A0A2E] to-[#1a0040]',     accent: '#FF00FF',  text: '#00FFCC' },
  warm:  { bg: 'from-orange-600 to-rose-600',      accent: '#FFF200',  text: 'white' },
}

function TemplatePreview({ template, selected, onSelect, productLabel, descLabel }) {
  const style = STYLE_PREVIEWS[template.config?.style] ?? STYLE_PREVIEWS.dark

  return (
    <button
      onClick={() => onSelect(template.id)}
      className={`relative rounded-2xl overflow-hidden transition-all duration-200 aspect-[9/16] w-full
        ${selected ? 'ring-2 ring-brand-400 ring-offset-2 ring-offset-gray-950 scale-105' : 'hover:ring-1 hover:ring-white/20'}`}
    >
      <div className={`absolute inset-0 bg-gradient-to-b ${style.bg}`} />
      <div className="absolute inset-0 opacity-20">
        <div className="w-full h-full bg-[radial-gradient(ellipse_at_center,_rgba(255,255,255,0.15)_0%,_transparent_60%)]" />
      </div>
      <div className="absolute bottom-0 left-0 right-0 p-3 bg-black/50">
        <p className="text-xs font-bold truncate" style={{ color: style.text }}>{productLabel}</p>
        <p className="text-sm font-extrabold" style={{ color: style.accent }}>$99.99</p>
        <p className="text-xs opacity-70 truncate" style={{ color: style.text }}>{descLabel}</p>
      </div>
      {selected && (
        <div className="absolute top-2 right-2">
          <CheckCircle2 className="w-6 h-6 text-brand-400 drop-shadow-lg" />
        </div>
      )}
      <div className="absolute bottom-0 left-0 right-0 top-[60%] flex items-end">
        <p className="w-full text-center pb-24 text-xs font-semibold text-white/80 drop-shadow">
          {template.name}
        </p>
      </div>
    </button>
  )
}

export default function TemplateSelector({ value, onChange }) {
  const { t } = useTranslation()
  const [templates, setTemplates] = useState([])
  const [loading, setLoading]     = useState(true)

  useEffect(() => {
    templatesApi.list()
      .then(({ data }) => setTemplates(data.data))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return (
    <div className="flex justify-center py-8"><Spinner size="lg" /></div>
  )

  return (
    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
      {templates.map((tmpl) => (
        <TemplatePreview
          key={tmpl.id}
          template={tmpl}
          selected={value === tmpl.id}
          onSelect={onChange}
          productLabel={t('templates.product')}
          descLabel={t('templates.desc')}
        />
      ))}
    </div>
  )
}
