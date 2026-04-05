import { useState, useEffect, useCallback } from 'react'
import i18n from '../i18n'
import { authApi } from '../services/api'

function syncLocale(user) {
  if (user?.locale && user.locale !== i18n.language) {
    i18n.changeLanguage(user.locale)
    localStorage.setItem('reelforge_locale', user.locale)
  }
}

export function useAuth() {
  const [user, setUser]       = useState(null)
  const [loading, setLoading] = useState(true)

  const fetchMe = useCallback(async () => {
    const token = localStorage.getItem('token')
    if (!token) {
      setLoading(false)
      return null
    }
    try {
      const { data } = await authApi.me()
      setUser(data.data)
      syncLocale(data.data)
      return data.data
    } catch {
      localStorage.removeItem('token')
      return null
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { fetchMe() }, [fetchMe])

  const login = async (credentials) => {
    const { data } = await authApi.login(credentials)
    localStorage.setItem('token', data.data.token)
    setUser(data.data.user)
    syncLocale(data.data.user)
    return data.data.user
  }

  const register = async (payload) => {
    const { data } = await authApi.register(payload)
    localStorage.setItem('token', data.data.token)
    setUser(data.data.user)
    syncLocale(data.data.user)
    return data.data.user
  }

  const logout = async () => {
    try { await authApi.logout() } catch { /* ignore */ }
    localStorage.removeItem('token')
    setUser(null)
  }

  return { user, loading, login, register, logout, refreshUser: fetchMe }
}
