import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import FormField from '../components/ui/FormField'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { getApiOrigin, getOAuthRedirectUrl, redirectToApp } from '../utils/apiBase'
import { postLoginPath } from '../constants/routes'
import SeoHead from '../components/seo/SeoHead'
import { useSite } from '../context/SiteContext'

export default function Register() {
  const { t } = useTranslation()
  const { registrationEnabled } = useSite()
  const { register } = useAuthContext()
  const navigate     = useNavigate()

  const [form, setForm]       = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [errors, setErrors]   = useState({})
  const [loading, setLoading] = useState(false)

  const apiOrigin = getApiOrigin()

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))

  const submit = async (e) => {
    e.preventDefault()
    setErrors({})
    setLoading(true)
    try {
      const u = await register(form)
      toast.success(t('auth.createAccount'))
      redirectToApp(postLoginPath(u.role), navigate)
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
      <SeoHead titleKey="seo.registerTitle" descriptionKey="seo.registerDescription" />
      <div className="card">
        <h1 className="text-2xl font-bold text-white mb-1">
          {registrationEnabled ? t('auth.registerTitle') : t('auth.registrationClosedTitle')}
        </h1>
        <p className="text-gray-400 text-sm mb-8">
          {registrationEnabled ? t('auth.registerSub') : t('auth.registrationClosedSub')}
        </p>

        {!registrationEnabled ? (
          <p className="text-center text-sm text-gray-400">
            <Link to="/login" className="text-brand-400 hover:text-brand-300 font-medium">
              {t('auth.signIn')}
            </Link>
          </p>
        ) : (
          <>
        <form onSubmit={submit} className="space-y-5">
          <FormField label={t('auth.fullName')} error={errors.name?.[0]}>
            <input
              type="text"
              className="input-field"
              placeholder={t('auth.namePlaceholder')}
              value={form.name}
              onChange={set('name')}
              required
            />
          </FormField>

          <FormField label={t('auth.email')} error={errors.email?.[0]}>
            <input
              type="email"
              className="input-field"
              placeholder="you@example.com"
              value={form.email}
              onChange={set('email')}
              required
            />
          </FormField>

          <FormField label={t('auth.password')} error={errors.password?.[0]}>
            <input
              type="password"
              className="input-field"
              placeholder={t('auth.passwordMin')}
              value={form.password}
              onChange={set('password')}
              required
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
            />
          </FormField>

          <button
            type="submit"
            className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            disabled={loading}
          >
            {loading && <Spinner size="sm" />}
            {loading ? t('auth.creatingAccount') : t('auth.createAccount')}
          </button>
        </form>

        {apiOrigin ? (
          <>
            <div className="relative my-8">
              <div className="absolute inset-0 flex items-center" aria-hidden>
                <div className="w-full border-t border-gray-700/80" />
              </div>
              <div className="relative flex justify-center text-xs uppercase tracking-wide">
                <span className="bg-gray-950/90 px-3 text-gray-500">{t('auth.orContinueWith')}</span>
              </div>
            </div>

            <div className="grid gap-3">
              <a
                href={getOAuthRedirectUrl('google')}
                className="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-gray-700 bg-gray-900/50
                  text-white text-sm font-medium hover:bg-gray-800/80 transition-colors"
              >
                <span className="text-sm">{t('auth.continueGoogle')}</span>
              </a>
              <a
                href={getOAuthRedirectUrl('apple')}
                className="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-gray-700 bg-gray-900/50
                  text-white text-sm font-medium hover:bg-gray-800/80 transition-colors"
              >
                <span className="text-sm">{t('auth.continueApple')}</span>
              </a>
            </div>
          </>
        ) : (
          <p className="mt-6 text-xs text-amber-500/90 text-center">{t('auth.oauthMissingApiUrl')}</p>
        )}

        <p className="text-center text-sm text-gray-500 mt-6">
          {t('auth.haveAccount')}{' '}
          <Link to="/login" className="text-brand-400 hover:text-brand-300 font-medium">
            {t('auth.signIn')}
          </Link>
        </p>
          </>
        )}
      </div>
    </div>
  )
}
