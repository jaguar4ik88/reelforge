import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import FormField from '../components/ui/FormField'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import { getApiOrigin, getOAuthRedirectUrl, redirectToApp } from '../utils/apiBase'
import { postLoginPath } from '../constants/routes'
import SeoHead from '../components/seo/SeoHead'

export default function Login() {
  const { t } = useTranslation()
  const { login } = useAuthContext()
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()

  const [form, setForm] = useState({ email: '', password: '' })
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)

  const apiOrigin = getApiOrigin()

  useEffect(() => {
    const oauthError = searchParams.get('oauth_error')
    if (!oauthError) return
    const msg = t(`auth.oauthError.${oauthError}`, {
      defaultValue: t('auth.oauthError.generic'),
    })
    toast.error(msg)
    setSearchParams({}, { replace: true })
  }, [searchParams, setSearchParams, t])

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))

  const submit = async (e) => {
    e.preventDefault()
    setErrors({})
    setLoading(true)
    try {
      const u = await login(form)
      toast.success(t('auth.signIn'))
      redirectToApp(postLoginPath(u.role), navigate)
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setErrors(data.errors)
      const message = data?.message ?? t('common.error')
      toast.error(message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="w-full max-w-md">
      <SeoHead titleKey="seo.loginTitle" descriptionKey="seo.loginDescription" />
      <div className="card">
        <h1 className="text-2xl font-bold text-white mb-1">{t('auth.loginTitle')}</h1>
        <p className="text-gray-400 text-sm mb-8">{t('auth.loginSub')}</p>

        <form onSubmit={submit} className="space-y-5">
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
              placeholder="••••••••"
              value={form.password}
              onChange={set('password')}
              required
            />
          </FormField>

          <div className="flex justify-end -mt-2">
            <Link
              to="/forgot-password"
              className="text-sm text-brand-400 hover:text-brand-300 font-medium"
            >
              {t('auth.forgotPasswordLink')}
            </Link>
          </div>

          <button
            type="submit"
            className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            disabled={loading}
          >
            {loading && <Spinner size="sm" />}
            {loading ? t('auth.signingIn') : t('auth.signIn')}
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
                <GoogleGlyph />
                {t('auth.continueGoogle')}
              </a>
              <a
                href={getOAuthRedirectUrl('apple')}
                className="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-gray-700 bg-gray-900/50
                  text-white text-sm font-medium hover:bg-gray-800/80 transition-colors"
              >
                <AppleGlyph />
                {t('auth.continueApple')}
              </a>
            </div>
          </>
        ) : (
          <p className="mt-6 text-xs text-amber-500/90 text-center">{t('auth.oauthMissingApiUrl')}</p>
        )}

        <p className="text-center text-sm text-gray-500 mt-6">
          {t('auth.noAccount')}{' '}
          <Link to="/register" className="text-brand-400 hover:text-brand-300 font-medium">
            {t('auth.createFree')}
          </Link>
        </p>
      </div>
    </div>
  )
}

function GoogleGlyph() {
  return (
    <svg className="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden>
      <path
        fill="#4285F4"
        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
      />
      <path
        fill="#34A853"
        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
      />
      <path
        fill="#FBBC05"
        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
      />
      <path
        fill="#EA4335"
        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
      />
    </svg>
  )
}

function AppleGlyph() {
  return (
    <svg className="w-5 h-5 shrink-0 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" />
    </svg>
  )
}
