import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import api from '../services/api'

const fallbackName = (import.meta.env.VITE_SITE_NAME || 'ReelForge').trim() || 'ReelForge'

const SiteContext = createContext({
  siteName: fallbackName,
  loading: true,
})

export function SiteProvider({ children }) {
  const [siteName, setSiteName] = useState(fallbackName)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    api
      .get('/site')
      .then((res) => {
        const name = res.data?.data?.site_name
        if (!cancelled && typeof name === 'string' && name.trim()) {
          setSiteName(name.trim())
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

  const value = useMemo(() => ({ siteName, loading }), [siteName, loading])

  return <SiteContext.Provider value={value}>{children}</SiteContext.Provider>
}

export function useSite() {
  return useContext(SiteContext)
}
