import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import api from '../services/api'

const fallbackName = (import.meta.env.VITE_SITE_NAME || 'ReelForge').trim() || 'ReelForge'

const SiteContext = createContext({
  siteName: fallbackName,
  loading: true,
  registrationEnabled: true,
  payments: null,
  seller: null,
})

export function SiteProvider({ children }) {
  const [siteName, setSiteName] = useState(fallbackName)
  const [registrationEnabled, setRegistrationEnabled] = useState(true)
  const [payments, setPayments] = useState(null)
  const [seller, setSeller] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    api
      .get('/site')
      .then((res) => {
        const d = res.data?.data
        const name = d?.site_name
        if (!cancelled && typeof name === 'string' && name.trim()) {
          setSiteName(name.trim())
        }
        if (!cancelled && typeof d?.registration_enabled === 'boolean') {
          setRegistrationEnabled(d.registration_enabled)
        }
        if (!cancelled && d?.payments) {
          setPayments(d.payments)
        }
        if (!cancelled && d?.seller && typeof d.seller === 'object') {
          setSeller(d.seller)
        }
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [])

  const value = useMemo(
    () => ({ siteName, loading, registrationEnabled, payments, seller }),
    [siteName, loading, registrationEnabled, payments, seller],
  )

  return <SiteContext.Provider value={value}>{children}</SiteContext.Provider>
}

export function useSite() {
  return useContext(SiteContext)
}
