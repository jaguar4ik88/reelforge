import { useEffect, useRef } from 'react'
import { useLocation } from 'react-router-dom'
import { GA_MEASUREMENT_ID } from './googleAnalyticsId'

/**
 * SPA navigation: dataLayer push for GTM + gtag config for GA4 (`gtag.js` in index.html).
 * Skips the first effect run to avoid duplicating the initial page_view from inline gtag in index.html.
 * If GA4 is also fired from inside GTM, disable one path to avoid duplicate page_view.
 */
export default function GtmRouteListener() {
  const location = useLocation()
  const skipFirst = useRef(true)

  useEffect(() => {
    if (skipFirst.current) {
      skipFirst.current = false
      return
    }

    const pagePath = `${location.pathname}${location.search}`
    const pageTitle = typeof document !== 'undefined' ? document.title : ''

    const dl = window.dataLayer
    if (Array.isArray(dl)) {
      dl.push({
        event: 'virtualPageView',
        virtualPageURL: pagePath,
        virtualPageTitle: pageTitle,
      })
    }

    if (typeof window.gtag === 'function') {
      window.gtag('config', GA_MEASUREMENT_ID, {
        page_path: pagePath,
        page_title: pageTitle,
      })
    }
  }, [location.pathname, location.search])

  return null
}
