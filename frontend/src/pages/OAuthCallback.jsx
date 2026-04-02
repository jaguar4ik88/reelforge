import { useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { useAuthContext } from '../context/AuthContext'

export default function OAuthCallback() {
  const { t } = useTranslation()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { refreshUser } = useAuthContext()

  useEffect(() => {
    const token = searchParams.get('token')
    const err = searchParams.get('error')

    if (err) {
      toast.error(t('common.error'))
      navigate('/login', { replace: true })
      return
    }

    if (!token) {
      toast.error(t('common.error'))
      navigate('/login', { replace: true })
      return
    }

    localStorage.setItem('token', token)
    refreshUser()
      .then(() => {
        toast.success(t('auth.signIn'))
        navigate('/dashboard', { replace: true })
      })
      .catch(() => {
        localStorage.removeItem('token')
        toast.error(t('common.error'))
        navigate('/login', { replace: true })
      })
  }, [searchParams, navigate, refreshUser, t])

  return (
    <div className="min-h-[40vh] flex items-center justify-center">
      <div className="w-12 h-12 rounded-full border-4 border-brand-500/30 border-t-brand-500 animate-spin" />
    </div>
  )
}
