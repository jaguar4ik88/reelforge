import { useMemo, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import FormField from '../components/ui/FormField'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { authApi } from '../services/api'
import SeoHead from '../components/seo/SeoHead'

export default function ResetPassword() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const canSubmit = useMemo(() => token.length > 0 && email.length > 0, [token, email])

  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))

  const submit = async (e) => {
    e.preventDefault()
    setErrors({})
    setLoading(true)
    try {
      await authApi.resetPassword({
        token,
        email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
      toast.success(t('auth.passwordResetDone'))
      navigate('/login', { replace: true })
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setErrors(data.errors)
      toast.error(data?.message ?? t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  if (!canSubmit) {
    return (
      <div className="w-full max-w-md">
        <SeoHead titleKey="seo.resetPasswordTitle" descriptionKey="seo.resetPasswordDescription" />
        <div className="card">
          <h1 className="text-2xl font-bold text-white mb-1">{t('auth.resetPasswordTitle')}</h1>
          <p className="text-gray-400 text-sm mb-6">{t('auth.resetLinkInvalid')}</p>
          <Link to="/forgot-password" className="text-brand-400 hover:text-brand-300 text-sm font-medium">
            {t('auth.requestNewResetLink')}
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="w-full max-w-md">
      <SeoHead titleKey="seo.resetPasswordTitle" descriptionKey="seo.resetPasswordDescription" />
      <div className="card">
        <h1 className="text-2xl font-bold text-white mb-1">{t('auth.resetPasswordTitle')}</h1>
        <p className="text-gray-400 text-sm mb-8">{t('auth.resetPasswordSub')}</p>

        <form onSubmit={submit} className="space-y-5">
          <FormField label={t('auth.newPassword')} error={errors.password?.[0]}>
            <input
              type="password"
              className="input-field"
              placeholder={t('auth.passwordMin')}
              value={form.password}
              onChange={set('password')}
              required
              minLength={8}
            />
          </FormField>

          <FormField label={t('auth.confirmPassword')} error={errors.password_confirmation?.[0]}>
            <input
              type="password"
              className="input-field"
              placeholder={t('auth.repeatPassword')}
              value={form.password_confirmation}
              onChange={set('password_confirmation')}
              required
              minLength={8}
            />
          </FormField>

          <button
            type="submit"
            className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            disabled={loading}
          >
            {loading && <Spinner size="sm" />}
            {loading ? t('auth.savingPassword') : t('auth.saveNewPassword')}
          </button>
        </form>

        <p className="text-center text-sm text-gray-500 mt-6">
          <Link to="/login" className="text-brand-400 hover:text-brand-300 font-medium">
            {t('auth.backToLogin')}
          </Link>
        </p>
      </div>
    </div>
  )
}
