import { useState, useMemo, useEffect, useCallback } from 'react'
import { Link } from 'react-router-dom'

/** Maps catalog template category → API product category for photo flow */
function productCategoryForTemplate(cat) {
  if (!cat) return 'other'
  const m = {
    sneakers: 'sports',
    shoes: 'sports',
    sport: 'sports',
    apparel: 'apparel',
    clothes: 'apparel',
    men: 'apparel',
    accessories: 'apparel',
    tech: 'electronics',
    beauty: 'beauty',
    food: 'food',
    home: 'home',
    kids: 'other',
    jewelry: 'beauty',
    auto: 'other',
  }
  return m[cat] || 'other'
}
import { Search, Sparkles } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { templatesApi } from '../services/api'
import { APP_BASE } from '../constants/routes'
import Spinner from '../components/ui/Spinner'

function categoryLabel(t, key) {
  if (!key) return '—'
  const k = `templatesPage.categories.${key}`
  const translated = t(k)
  return translated !== k ? translated : key
}

export default function TemplatesPage() {
  const { t, i18n } = useTranslation()
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('all')
  const [templates, setTemplates] = useState([])
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data } = await templatesApi.list()
      const list = data?.data ?? []
      setTemplates(Array.isArray(list) ? list : [])
    } catch {
      setTemplates([])
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load, i18n.language])

  const categoryKeys = useMemo(() => {
    const fromApi = [...new Set(templates.map((x) => x.category).filter(Boolean))].sort()
    return ['all', ...fromApi]
  }, [templates])

  const filtered = useMemo(() => {
    return templates.filter((item) => {
      if (category !== 'all' && (item.category || '') !== category) return false
      if (search.trim()) {
        const q = search.toLowerCase()
        const name = (item.name || '').toLowerCase()
        const slug = (item.slug || '').toLowerCase()
        const catLabel = categoryLabel(t, item.category).toLowerCase()
        if (!name.includes(q) && !slug.includes(q) && !catLabel.includes(q)) return false
      }
      return true
    })
  }, [templates, category, search, t])

  return (
    <div className="min-h-full flex flex-col lg:flex-row gap-0 lg:gap-8">
      <aside className="w-full lg:w-64 flex-shrink-0 border-b lg:border-b-0 lg:border-r border-white/10 bg-gray-900/40 p-5 lg:min-h-[calc(100vh-4rem)]">
        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t('templatesPage.searchPlaceholder')}
            className="input-field pl-10 text-sm py-2.5"
          />
        </div>

        <Link
          to={`${APP_BASE}/projects/new-photo`}
          className="w-full flex items-center justify-center gap-2 bg-white text-gray-900 font-semibold py-2.5 rounded-full text-sm mb-6 hover:bg-gray-100 transition-colors"
        >
          <Sparkles className="w-4 h-4" />
          {t('templatesPage.fromPhoto')}
        </Link>

        <div className="flex items-center justify-between mb-3">
          <span className="text-sm font-semibold text-white">{t('templatesPage.filterBy')}</span>
          <button
            type="button"
            onClick={() => {
              setCategory('all')
              setSearch('')
            }}
            className="text-xs text-brand-400 hover:text-brand-300"
          >
            {t('templatesPage.reset')}
          </button>
        </div>
        <ul className="space-y-1 max-h-[40vh] lg:max-h-none overflow-y-auto">
          {categoryKeys.map((key) => (
            <li key={key}>
              <button
                type="button"
                onClick={() => setCategory(key)}
                className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                  category === key ? 'bg-brand-600/20 text-brand-300' : 'text-gray-400 hover:bg-white/5 hover:text-white'
                }`}
              >
                {key === 'all' ? t('templatesPage.categories.all') : categoryLabel(t, key)}
              </button>
            </li>
          ))}
        </ul>
      </aside>

      <div className="flex-1 min-w-0 p-5 lg:p-6 lg:pr-8">
        <h1 className="text-2xl sm:text-3xl font-bold text-white mb-1">
          {t('templatesPage.gridTitle', { count: filtered.length })}
        </h1>
        <p className="text-sm text-gray-500 mb-6">{t('templatesPage.gridHint')}</p>

        {loading ? (
          <div className="flex justify-center py-24">
            <Spinner size="lg" />
          </div>
        ) : filtered.length === 0 ? (
          <p className="text-center text-gray-500 py-16">{t('templatesPage.noResults')}</p>
        ) : (
          <div className="columns-2 sm:columns-3 xl:columns-4 gap-4 space-y-4">
            {filtered.map((item) => (
              <Link
                key={item.id}
                to={`${APP_BASE}/projects/new-photo`}
                state={{
                  templateId: item.id,
                  templateSlug: item.slug,
                  templateName: item.name,
                  previewUrl: item.preview_url,
                  productCategory: productCategoryForTemplate(item.category),
                }}
                className="break-inside-avoid block rounded-2xl overflow-hidden border border-white/10 bg-gray-900/60 hover:border-brand-500/40 transition-all group"
              >
                <div className="relative aspect-[3/4] bg-gray-800">
                  {item.preview_url ? (
                    <img
                      src={item.preview_url}
                      alt=""
                      className="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-600 text-sm px-4 text-center">
                      {item.name}
                    </div>
                  )}
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-90" />
                  <div className="absolute bottom-0 left-0 right-0 p-3">
                    <p className="text-sm text-white font-semibold leading-snug line-clamp-2">{item.name}</p>
                    {item.category && (
                      <p className="text-xs text-brand-300/90 mt-1">{categoryLabel(t, item.category)}</p>
                    )}
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
