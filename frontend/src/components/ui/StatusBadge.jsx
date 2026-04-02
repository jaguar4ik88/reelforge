import { useTranslation } from 'react-i18next'

const styles = {
  draft:      'bg-gray-500/20 text-gray-400 border border-gray-500/30',
  processing: 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
  done:       'bg-green-500/20 text-green-400 border border-green-500/30',
  failed:     'bg-red-500/20 text-red-400 border border-red-500/30',
}

export default function StatusBadge({ status }) {
  const { t } = useTranslation()
  const cls = styles[status] ?? styles.draft

  return (
    <span className={`status-badge ${cls} ${status === 'processing' ? 'animate-pulse' : ''}`}>
      <span className="w-1.5 h-1.5 rounded-full bg-current" />
      {t(`status.${status}`)}
    </span>
  )
}
