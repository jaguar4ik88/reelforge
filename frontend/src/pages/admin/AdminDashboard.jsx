import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../../context/AuthContext'

export default function AdminDashboard() {
  const { t } = useTranslation()
  const { user } = useAuthContext()

  return (
    <div>
      <h1 className="text-3xl font-bold text-white mb-2">{t('admin.dashboard.title')}</h1>
      <p className="text-gray-400 mb-8">{t('admin.dashboard.subtitle')}</p>
      <div className="card max-w-lg">
        <p className="text-sm text-gray-500 mb-1">{t('admin.dashboard.signedInAs')}</p>
        <p className="text-white font-medium">{user?.name}</p>
        <p className="text-xs text-gray-500 mt-2 capitalize">
          {t('admin.dashboard.role')}: {user?.role ?? '—'}
        </p>
      </div>
    </div>
  )
}
