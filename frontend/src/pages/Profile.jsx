import { useState, useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { useAuthContext } from '../context/AuthContext'
import { useLocale } from '../hooks/useLocale'
import { profileApi } from '../services/api'
import FormField from '../components/ui/FormField'
import Spinner from '../components/ui/Spinner'
import toast from 'react-hot-toast'
import {
  User, Mail, Globe, Lock, Camera, Calendar, Zap, Coins,
} from 'lucide-react'

export default function Profile() {
  const { t }                         = useTranslation()
  const { user, refreshUser }         = useAuthContext()
  const { locale, changeLocale }      = useLocale()

  // Profile form
  const [profileForm, setProfileForm] = useState({ name: '', email: '', locale: 'uk' })
  const [profileErrors, setProfileErrors] = useState({})
  const [savingProfile, setSavingProfile] = useState(false)
  const [avatarPreview, setAvatarPreview] = useState(null)
  const avatarFile                    = useRef(null)
  const [avatarBlob, setAvatarBlob]   = useState(null)

  // Password form
  const [pwForm, setPwForm]           = useState({ current_password: '', password: '', password_confirmation: '' })
  const [pwErrors, setPwErrors]       = useState({})
  const [savingPw, setSavingPw]       = useState(false)

  useEffect(() => {
    if (user) {
      setProfileForm({ name: user.name, email: user.email, locale: user.locale ?? 'uk' })
      setAvatarPreview(user.avatar_url ?? null)
    }
  }, [user])

  const handleAvatarChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    setAvatarBlob(file)
    setAvatarPreview(URL.createObjectURL(file))
  }

  const saveProfile = async (e) => {
    e.preventDefault()
    setProfileErrors({})
    setSavingProfile(true)
    try {
      const payload = { ...profileForm }
      if (avatarBlob) payload.avatar = avatarBlob
      await profileApi.update(payload)
      await refreshUser()
      if (profileForm.locale !== locale) {
        await changeLocale(profileForm.locale, true)
      }
      setAvatarBlob(null)
      toast.success(t('profile.saved'))
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setProfileErrors(data.errors)
      toast.error(data?.message ?? t('common.error'))
    } finally {
      setSavingProfile(false)
    }
  }

  const savePassword = async (e) => {
    e.preventDefault()
    setPwErrors({})
    setSavingPw(true)
    try {
      await profileApi.changePassword(pwForm)
      setPwForm({ current_password: '', password: '', password_confirmation: '' })
      toast.success(t('profile.passwordChanged'))
    } catch (err) {
      const data = err.response?.data
      if (data?.errors) setPwErrors(data.errors)
      toast.error(data?.message ?? t('common.error'))
    } finally {
      setSavingPw(false)
    }
  }

  const setPw = (key) => (e) => setPwForm((f) => ({ ...f, [key]: e.target.value }))
  const setPf = (key) => (e) => setProfileForm((f) => ({ ...f, [key]: e.target.value }))

  return (
    <div className="max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-1">{t('profile.title')}</h1>
        <p className="text-gray-400">{t('profile.subtitle')}</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column: avatar + plan */}
        <div className="space-y-5">
          {/* Avatar card */}
          <div className="card flex flex-col items-center text-center">
            <div className="relative mb-4">
              {avatarPreview ? (
                <img
                  src={avatarPreview}
                  alt={user?.name}
                  className="w-24 h-24 rounded-full object-cover ring-4 ring-brand-500/30"
                />
              ) : (
                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center text-3xl font-bold ring-4 ring-brand-500/30">
                  {user?.name?.charAt(0).toUpperCase()}
                </div>
              )}
              <button
                onClick={() => avatarFile.current?.click()}
                className="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-brand-600 hover:bg-brand-500 flex items-center justify-center transition-colors shadow-lg"
                title={t('profile.changeAvatar')}
              >
                <Camera className="w-4 h-4 text-white" />
              </button>
              <input
                ref={avatarFile}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="hidden"
                onChange={handleAvatarChange}
              />
            </div>

            <p className="text-white font-semibold text-lg">{user?.name}</p>
            <p className="text-gray-400 text-sm">{user?.email}</p>
            <span className={`mt-2 status-badge ${
              user?.plan === 'pro'
                ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30'
                : 'bg-gray-500/20 text-gray-400 border border-gray-500/30'
            }`}>
              {user?.plan === 'pro' ? t('profile.pro') : t('profile.free')}
            </span>
          </div>

          {/* Plan info */}
          <div className="card">
            <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
              <Zap className="w-3.5 h-3.5" />
              {t('profile.planInfo')}
            </h3>
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-400">{t('profile.currentPlan')}</span>
                <span className="text-white font-medium capitalize">
                  {user?.plan === 'pro' ? t('profile.pro') : t('profile.free')}
                </span>
              </div>
              <div>
                <div className="flex justify-between text-sm mb-1.5">
                  <span className="text-gray-400">{t('profile.videosUsed')}</span>
                  <span className="text-brand-400 font-semibold">
                    {user?.videos_this_month} {t('profile.outOf')} {user?.video_limit}
                  </span>
                </div>
                <div className="w-full bg-gray-800 rounded-full h-1.5">
                  <div
                    className="h-1.5 rounded-full bg-gradient-to-r from-brand-500 to-purple-500"
                    style={{ width: `${Math.min(((user?.videos_this_month ?? 0) / (user?.video_limit ?? 10)) * 100, 100)}%` }}
                  />
                </div>
              </div>
              <div className="flex justify-between items-center text-sm pt-2 border-t border-gray-800/80">
                <span className="text-gray-400 flex items-center gap-1.5">
                  <Coins className="w-3.5 h-3.5 text-amber-400" aria-hidden />
                  {t('profile.creditsBalance')}
                </span>
                <span className="text-amber-300 font-semibold tabular-nums">
                  {user?.credits?.balance ?? 0}
                </span>
              </div>
              {user?.credits?.video_generation_cost != null && (
                <p className="text-xs text-gray-500">
                  {t('profile.creditsPerVideoHint', { count: user.credits.video_generation_cost })}
                </p>
              )}
              {user?.plan === 'free' && (
                <button className="btn-primary w-full text-sm py-2 mt-2">
                  {t('profile.upgradePro')}
                </button>
              )}
            </div>
          </div>

          {/* Member since */}
          {user?.created_at && (
            <div className="card">
              <div className="flex items-center gap-2 text-xs text-gray-500 uppercase tracking-wider font-semibold mb-3">
                <Calendar className="w-3.5 h-3.5" />
                {t('profile.memberSince')}
              </div>
              <p className="text-white text-sm">
                {new Date(user.created_at).toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' })}
              </p>
            </div>
          )}
        </div>

        {/* Right column: forms */}
        <div className="lg:col-span-2 space-y-6">
          {/* Personal info form */}
          <div className="card">
            <h2 className="text-sm font-semibold text-white mb-5 flex items-center gap-2">
              <User className="w-4 h-4 text-brand-400" />
              {t('profile.personalInfo')}
            </h2>

            <form onSubmit={saveProfile} className="space-y-4">
              {/* Avatar preview if changed */}
              {avatarBlob && (
                <div className="flex items-center gap-3 p-3 bg-brand-900/20 rounded-xl border border-brand-500/20">
                  <img src={avatarPreview} alt="preview" className="w-10 h-10 rounded-full object-cover" />
                  <div className="flex-1 min-w-0">
                    <p className="text-xs text-brand-300">{t('profile.changeAvatar')}</p>
                    <p className="text-xs text-gray-500 truncate">{avatarBlob.name}</p>
                  </div>
                  <button type="button" onClick={() => { setAvatarBlob(null); setAvatarPreview(user?.avatar_url) }}
                    className="text-gray-500 hover:text-red-400 text-xs">✕</button>
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label={t('profile.name')} error={profileErrors.name?.[0]}>
                  <div className="relative">
                    <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                    <input type="text" className="input-field pl-10"
                      value={profileForm.name} onChange={setPf('name')} required />
                  </div>
                </FormField>

                <FormField label={t('profile.email')} error={profileErrors.email?.[0]}>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                    <input type="email" className="input-field pl-10"
                      value={profileForm.email} onChange={setPf('email')} required />
                  </div>
                </FormField>
              </div>

              <FormField label={t('profile.language')} error={profileErrors.locale?.[0]}>
                <div className="relative">
                  <Globe className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                  <select
                    className="input-field pl-10 appearance-none cursor-pointer"
                    value={profileForm.locale}
                    onChange={setPf('locale')}
                  >
                    <option value="uk">🇺🇦 Українська</option>
                    <option value="en">🇬🇧 English</option>
                  </select>
                </div>
              </FormField>

              <div className="flex justify-end">
                <button type="submit" disabled={savingProfile}
                  className="btn-primary flex items-center gap-2 min-w-[160px] justify-center">
                  {savingProfile && <Spinner size="sm" />}
                  {savingProfile ? t('profile.saving') : t('profile.saveChanges')}
                </button>
              </div>
            </form>
          </div>

          {/* Change password */}
          <div className="card">
            <h2 className="text-sm font-semibold text-white mb-5 flex items-center gap-2">
              <Lock className="w-4 h-4 text-brand-400" />
              {t('profile.changePassword')}
            </h2>

            <form onSubmit={savePassword} className="space-y-4">
              <FormField label={t('profile.currentPassword')} error={pwErrors.current_password?.[0]}>
                <input type="password" className="input-field"
                  placeholder="••••••••"
                  value={pwForm.current_password} onChange={setPw('current_password')} required />
              </FormField>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label={t('profile.newPassword')} error={pwErrors.password?.[0]}>
                  <input type="password" className="input-field"
                    placeholder="••••••••"
                    value={pwForm.password} onChange={setPw('password')} required />
                </FormField>

                <FormField label={t('profile.confirmNewPassword')} error={pwErrors.password_confirmation?.[0]}>
                  <input type="password" className="input-field"
                    placeholder="••••••••"
                    value={pwForm.password_confirmation} onChange={setPw('password_confirmation')} required />
                </FormField>
              </div>

              <div className="flex justify-end">
                <button type="submit" disabled={savingPw}
                  className="btn-secondary flex items-center gap-2 min-w-[160px] justify-center">
                  {savingPw && <Spinner size="sm" />}
                  {savingPw ? t('profile.updating') : t('profile.updatePassword')}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}
