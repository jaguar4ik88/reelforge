import { useState, useMemo } from 'react'
import { Search, ImageIcon, Video, LayoutGrid, Sparkles } from 'lucide-react'
import { useTranslation } from 'react-i18next'

const CATEGORY_KEYS = [
  'all',
  'shoes',
  'clothes',
  'tech',
  'beauty',
  'food',
  'sport',
  'home',
  'kids',
  'accessories',
  'jewelry',
  'auto',
  'men',
]

function buildMockItems(t) {
  const bases = [
    { id: 1, cat: 'clothes', type: 'image', h: 'h-64', src: 'https://images.unsplash.com/photo-1523381210438-6e5a059caf88?w=400&q=80' },
    { id: 2, cat: 'shoes', type: 'video', h: 'h-80', src: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80' },
    { id: 3, cat: 'tech', type: 'image', h: 'h-72', src: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&q=80' },
    { id: 4, cat: 'beauty', type: 'image', h: 'h-56', src: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&q=80' },
    { id: 5, cat: 'food', type: 'video', h: 'h-72', src: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80' },
    { id: 6, cat: 'sport', type: 'image', h: 'h-64', src: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&q=80' },
    { id: 7, cat: 'home', type: 'image', h: 'h-80', src: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&q=80' },
    { id: 8, cat: 'kids', type: 'image', h: 'h-60', src: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=400&q=80' },
    { id: 9, cat: 'accessories', type: 'video', h: 'h-72', src: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&q=80' },
    { id: 10, cat: 'jewelry', type: 'image', h: 'h-64', src: 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&q=80' },
    { id: 11, cat: 'auto', type: 'image', h: 'h-56', src: 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=400&q=80' },
    { id: 12, cat: 'men', type: 'image', h: 'h-80', src: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=80' },
    { id: 13, cat: 'clothes', type: 'video', h: 'h-72', src: 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=400&q=80' },
    { id: 14, cat: 'tech', type: 'video', h: 'h-64', src: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&q=80' },
    { id: 15, cat: 'shoes', type: 'image', h: 'h-[17rem]', src: 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=400&q=80' },
  ]
  return bases.map((b) => ({
    ...b,
    title: t(`templatesPage.demoTitle`, { id: b.id }),
  }))
}

export default function TemplatesPage() {
  const { t, i18n } = useTranslation()
  const [search, setSearch] = useState('')
  const [mediaFilter, setMediaFilter] = useState('all')
  const [category, setCategory] = useState('all')

  const items = useMemo(() => buildMockItems(t), [t, i18n.language])

  const filtered = useMemo(() => {
    return items.filter((item) => {
      if (category !== 'all' && item.cat !== category) return false
      if (mediaFilter === 'images' && item.type !== 'image') return false
      if (mediaFilter === 'videos' && item.type !== 'video') return false
      if (search.trim()) {
        const q = search.toLowerCase()
        if (!item.title.toLowerCase().includes(q) && !t(`templatesPage.categories.${item.cat}`).toLowerCase().includes(q)) {
          return false
        }
      }
      return true
    })
  }, [items, category, mediaFilter, search, t])

  return (
    <div className="min-h-full flex flex-col lg:flex-row gap-0 lg:gap-8">
      {/* Sidebar */}
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

        <button
          type="button"
          className="w-full flex items-center justify-center gap-2 bg-white text-gray-900 font-semibold py-2.5 rounded-full text-sm mb-5 hover:bg-gray-100 transition-colors"
        >
          <Sparkles className="w-4 h-4" />
          {t('templatesPage.select')}
        </button>

        <div className="flex gap-1 mb-6 p-1 bg-white/5 rounded-xl">
          {[
            { id: 'all', icon: LayoutGrid, label: t('templatesPage.media.all') },
            { id: 'images', icon: ImageIcon, label: t('templatesPage.media.images') },
            { id: 'videos', icon: Video, label: t('templatesPage.media.videos') },
          ].map(({ id, icon: Icon, label }) => (
            <button
              key={id}
              type="button"
              onClick={() => setMediaFilter(id)}
              className={`flex-1 flex flex-col sm:flex-row items-center justify-center gap-1 py-2 px-1 rounded-lg text-xs font-medium transition-all ${
                mediaFilter === id ? 'bg-white/15 text-white' : 'text-gray-500 hover:text-gray-300'
              }`}
            >
              <Icon className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">{label}</span>
            </button>
          ))}
        </div>

        <div className="flex items-center justify-between mb-3">
          <span className="text-sm font-semibold text-white">{t('templatesPage.filterBy')}</span>
          <button
            type="button"
            onClick={() => { setCategory('all'); setSearch(''); setMediaFilter('all') }}
            className="text-xs text-brand-400 hover:text-brand-300"
          >
            {t('templatesPage.reset')}
          </button>
        </div>
        <ul className="space-y-1 max-h-[40vh] lg:max-h-none overflow-y-auto">
          {CATEGORY_KEYS.map((key) => (
            <li key={key}>
              <button
                type="button"
                onClick={() => setCategory(key)}
                className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                  category === key ? 'bg-brand-600/20 text-brand-300' : 'text-gray-400 hover:bg-white/5 hover:text-white'
                }`}
              >
                {t(`templatesPage.categories.${key}`)}
              </button>
            </li>
          ))}
        </ul>
      </aside>

      {/* Main grid */}
      <div className="flex-1 min-w-0 p-5 lg:p-6 lg:pr-8">
        <h1 className="text-2xl sm:text-3xl font-bold text-white mb-1">
          {t('templatesPage.gridTitle', { count: filtered.length })}
        </h1>
        <p className="text-sm text-gray-500 mb-6">{t('templatesPage.gridHint')}</p>

        <div className="columns-2 sm:columns-3 xl:columns-4 gap-4 space-y-4">
          {filtered.map((item) => (
            <div
              key={item.id}
              className={`break-inside-avoid rounded-2xl overflow-hidden border border-white/10 bg-gray-900/60 hover:border-brand-500/30 transition-all cursor-pointer group ${item.h}`}
            >
              <div className="relative h-full min-h-[180px]">
                <img
                  src={item.src}
                  alt=""
                  className="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                {item.type === 'video' && (
                  <div className="absolute bottom-2 right-2 w-8 h-8 rounded-lg bg-blue-500/90 flex items-center justify-center">
                    <Video className="w-4 h-4 text-white" />
                  </div>
                )}
                <p className="absolute bottom-0 left-0 right-0 p-3 text-xs text-white/90 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                  {item.title}
                </p>
              </div>
            </div>
          ))}
        </div>

        {filtered.length === 0 && (
          <p className="text-center text-gray-500 py-16">{t('templatesPage.noResults')}</p>
        )}
      </div>
    </div>
  )
}
