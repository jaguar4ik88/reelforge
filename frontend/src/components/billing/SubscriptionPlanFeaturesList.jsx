import { Check } from 'lucide-react'
import { useTranslation } from 'react-i18next'

/**
 * Renders feature rows using i18n keys under `pricing.features.*` (params optional).
 *
 * @param {{ features?: Array<{ key: string, params?: Record<string, unknown> }>, className?: string, compact?: boolean }} props
 */
export default function SubscriptionPlanFeaturesList({ features, className = '', compact = false }) {
  const { t } = useTranslation()
  const list = Array.isArray(features) ? features : []

  if (list.length === 0) return null

  return (
    <ul className={`${compact ? 'space-y-2' : 'space-y-3'} ${className}`}>
      {list.map((f, i) => {
        const key = typeof f?.key === 'string' ? f.key : ''
        const params = f?.params && typeof f.params === 'object' ? f.params : {}
        if (!key) return null
        const label = t(`pricing.features.${key}`, params)
        return (
          <li key={`${key}-${i}`} className={`flex items-start gap-2.5 ${compact ? 'text-sm' : 'text-sm'} text-gray-300`}>
            <Check className="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5" />
            <span>{label}</span>
          </li>
        )
      })}
    </ul>
  )
}
