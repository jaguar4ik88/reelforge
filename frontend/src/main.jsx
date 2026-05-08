import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { HelmetProvider } from 'react-helmet-async'
import { Toaster } from 'react-hot-toast'
import App from './App'
import { SiteProvider } from './context/SiteContext'
import { ThemeProvider, hydrateThemeFromStorage } from './context/ThemeContext'
import GtmRouteListener from './analytics/GtmRouteListener'
import './i18n'
import './index.css'

hydrateThemeFromStorage()

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <GtmRouteListener />
      <HelmetProvider>
        <SiteProvider>
          <ThemeProvider>
            <App />
          </ThemeProvider>
        </SiteProvider>
        <Toaster
          position="top-right"
          toastOptions={{
            style: {
              background: 'var(--rf-sidebar)',
              color: 'var(--rf-text)',
              border: '1px solid var(--rf-border)',
            },
            success: { iconTheme: { primary: '#a855f7', secondary: 'var(--rf-text)' } },
            error: { iconTheme: { primary: '#ef4444', secondary: 'var(--rf-text)' } },
          }}
        />
      </HelmetProvider>
    </BrowserRouter>
  </React.StrictMode>,
)
