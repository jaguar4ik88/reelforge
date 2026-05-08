import { createContext, useCallback, useContext, useMemo, useSyncExternalStore } from 'react'

const STORAGE_KEY = 'reelforge-theme'

const listeners = new Set()

function emit() {
  listeners.forEach((l) => l())
}

function subscribe(listener) {
  listeners.add(listener)
  return () => {
    listeners.delete(listener)
  }
}

/** @returns {'dark'|'light'} */
function readStoredTheme() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    if (v === 'light' || v === 'dark') {
      return v
    }
  } catch {
    // ignore
  }
  return 'dark'
}

function getSnapshot() {
  return readStoredTheme()
}

function getServerSnapshot() {
  return 'dark'
}

function applyDomTheme(theme) {
  document.documentElement.dataset.theme = theme
  document.documentElement.classList.toggle('dark', theme === 'dark')
  /** @type {HTMLMetaElement | null} */
  const meta = document.querySelector('meta[name="theme-color"]')
  if (meta) {
    meta.setAttribute('content', theme === 'dark' ? '#030712' : '#f8fafc')
  }
}

export function hydrateThemeFromStorage() {
  applyDomTheme(readStoredTheme())
}

function persistAndApply(theme) {
  try {
    localStorage.setItem(STORAGE_KEY, theme)
  } catch {
    // ignore
  }
  applyDomTheme(theme)
  emit()
}

const ThemeContext = createContext(null)

/** @typedef {{ resolvedTheme: 'dark'|'light', setTheme: (t:'dark'|'light')=>void, toggleTheme: ()=>void }} ThemeValue */

export function ThemeProvider({ children }) {
  const resolvedTheme = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot)

  const setTheme = useCallback((t) => {
    persistAndApply(t === 'light' ? 'light' : 'dark')
  }, [])

  const toggleTheme = useCallback(() => {
    persistAndApply(resolvedTheme === 'dark' ? 'light' : 'dark')
  }, [resolvedTheme])

  const value = useMemo(
    () => ({
      resolvedTheme,
      setTheme,
      toggleTheme,
    }),
    [resolvedTheme, setTheme, toggleTheme],
  )

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}

/** @returns {ThemeValue} */
export function useTheme() {
  const ctx = useContext(ThemeContext)
  if (!ctx) {
    throw new Error('useTheme must be used within ThemeProvider')
  }
  return ctx
}
