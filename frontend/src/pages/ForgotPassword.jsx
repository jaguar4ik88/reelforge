import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import FormField from '../components/ui/FormField'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { authApi } from '../services/api'
import SeoHead from '../components/seo/SeoHead'

export default function ForgotPassword() {
  const { t } = useTranslation()
  const [email, setEmail] = useState('')
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)
  const [sent, setSent] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setErrors({})
    setLoading(true)
    try {
      await authApi.forgotPassword({ email })
      setSent(true)
      toast.success(t('auth.resetLinkSentToast'))
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setErrors(data.errors)
      toast.error(data?.message ?? t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="w-full max-w-md">
      <SeoHead titleKey="seo.forgotPasswordTitle" descriptionKey="seo.forgotPasswordDescription" />
      <div className="card">
        <h1 className="text-2xl font-bold text-white mb-1">{t('auth.forgotPasswordTitle')}</h1>
        <p className="text-gray-400 text-sm mb-8">{t('auth.forgotPasswordSub')}</p>

        {sent ? (
          <p className="text-gray-300 text-sm leading-relaxed">{t('auth.resetLinkSentDetail')}</p>
        ) : (
          <form onSubmit={submit} className="space-y-5">
            <FormField label={t('auth.email')} error={errors.email?.[0]}>
              <input
                type="email"
                className="input-field"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </FormField>

            <button
              type="submit"
              className="btn-primary w-full py-3 flex items-center justify-center gap-2"
              disabled={loading}
            >
              {loading && <Spinner size="sm" />}
              {loading ? t('auth.sendingReset') : t('auth.sendResetLink')}
            </button>
          </form>
        )}

        <p className="text-center text-sm text-gray-500 mt-6">
          <Link to="/login" className="text-brand-400 hover:text-brand-300 font-medium">
            {t('auth.backToLogin')}
          </Link>
        </p>
      </div>
    </div>
  )
}
