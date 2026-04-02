import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import { Filter, Film, ImageIcon, LayoutGrid } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useProjects } from '../hooks/useProjects'
import Spinner from '../components/ui/Spinner'
import StatusBadge from '../components/ui/StatusBadge'

export default function Gallery() {
  const { t } = useTranslation()
  const { projects, loading } = useProjects()
  const [productId, setProductId] = useState('all')
  const [kind, setKind] = useState('all')

  const filtered = useMemo(() => {
    return projects.filter((p) => {
      if (productId !== 'all' && String(p.id) !== productId) return false
      if (kind === 'video' && p.status !== 'done') return false
      if (kind === 'draft' && p.status !== 'draft') return false
      if (kind === 'processing' && p.status !== 'processing') return false
      return true
    })
  }, [projects, productId, kind])

  return (
    <div className="flex flex-col lg:flex-row gap-8 -mx-2">
      <aside className="w-full lg:w-56 flex-shrink-0 space-y-4">
        <div className="flex items-center gap-2 text-white font-semibold mb-2">
          <Filter className="w-4 h-4 text-brand-400" />
          {t('gallery.filters')}
        </div>

        <div>
          <label className="text-xs text-gray-500 uppercase tracking-wide mb-2 block">{t('gallery.byProduct')}</label>
          <select
            value={productId}
            onChange={(e) => setProductId(e.target.value)}
            className="input-field text-sm py-2"
          >
            <option value="all">{t('gallery.allProducts')}</option>
            {projects.map((p) => (
              <option key={p.id} value={String(p.id)}>
                {p.title}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="text-xs text-gray-500 uppercase tracking-wide mb-2 block">{t('gallery.byType')}</label>
          <div className="flex flex-col gap-1">
            {[
              { id: 'all', icon: LayoutGrid, label: t('gallery.type.all') },
              { id: 'video', icon: Film, label: t('gallery.type.video') },
              { id: 'draft', icon: ImageIcon, label: t('gallery.type.draft') },
              { id: 'processing', icon: Film, label: t('gallery.type.processing') },
            ].map(({ id, icon: Icon, label }) => (
              <button
                key={id}
                type="button"
                onClick={() => setKind(id)}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-left transition-colors ${
                  kind === id ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30' : 'text-gray-400 hover:bg-white/5'
                }`}
              >
                <Icon className="w-4 h-4" />
                {label}
              </button>
            ))}
          </div>
        </div>
      </aside>

      <div className="flex-1 min-w-0">
        <h1 className="text-3xl font-bold text-white mb-1">{t('gallery.title')}</h1>
        <p className="text-gray-400 text-sm mb-6">{t('gallery.subtitle')}</p>

        {loading ? (
          <div className="flex justify-center py-20"><Spinner size="lg" /></div>
        ) : filtered.length === 0 ? (
          <div className="card text-center py-16 text-gray-500">{t('gallery.empty')}</div>
        ) : (
          <div className="columns-2 sm:columns-3 gap-4 space-y-4">
            {filtered.map((p) => {
              const img = p.images?.[0]
              return (
                <Link
                  key={p.id}
                  to={`/projects/${p.id}`}
                  className="break-inside-avoid block rounded-2xl overflow-hidden border border-white/10 bg-gray-900/60 hover:border-brand-500/40 transition-all group"
                >
                  <div className="aspect-[9/16] relative bg-gray-800">
                    {img ? (
                      <img src={img.url} alt="" className="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <Film className="w-12 h-12 text-gray-600" />
                      </div>
                    )}
                    {p.status === 'done' && (
                      <div className="absolute bottom-2 right-2 w-8 h-8 rounded-lg bg-blue-500/90 flex items-center justify-center">
                        <Film className="w-4 h-4 text-white" />
                      </div>
                    )}
                  </div>
                  <div className="p-3">
                    <p className="font-medium text-white text-sm truncate">{p.title}</p>
                    <div className="flex items-center justify-between mt-2">
                      <StatusBadge status={p.status} />
                      <span className="text-xs text-gray-500">${parseFloat(p.price).toFixed(2)}</span>
                    </div>
                  </div>
                </Link>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}
