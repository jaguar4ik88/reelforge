import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

/**
 * Pushes a virtual page view on client-side navigation so GTM/GA4 can record SPA routes.
 * In GTM, add a trigger: Custom Event = virtualPageView (or map this event in your container).
 */
export default function GtmRouteListener() {
  const location = useLocation()

  useEffect(() => {
    const dl = window.dataLayer
    if (!Array.isArray(dl)) {
      return
    }
    dl.push({
      event: 'virtualPageView',
      virtualPageURL: `${location.pathname}${location.search}`,
      virtualPageTitle: typeof document !== 'undefined' ? document.title : '',
    })
  }, [location.pathname, location.search])

  return null
}
