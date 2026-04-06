import { ArrowRight, Clock } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SeoHead from '../components/seo/SeoHead'
import { useSite } from '../context/SiteContext'

// Статические мета-данные постов (стили, ключи)
const POST_META = [
  { id: 'p1', tagKey: 'guide',        tagColor: 'text-brand-400 bg-brand-900/40 border-brand-500/30', readTime: 5,  gradient: 'from-brand-900/60 to-purple-900/60' },
  { id: 'p2', tagKey: 'tips',         tagColor: 'text-amber-400 bg-amber-900/30 border-amber-500/30', readTime: 8,  gradient: 'from-amber-900/40 to-orange-900/40' },
  { id: 'p3', tagKey: 'update',       tagColor: 'text-green-400 bg-green-900/30 border-green-500/30', readTime: 4,  gradient: 'from-green-900/40 to-teal-900/40' },
  { id: 'p4', tagKey: 'case',         tagColor: 'text-violet-400 bg-violet-900/30 border-violet-500/30', readTime: 6, gradient: 'from-violet-900/50 to-indigo-900/50' },
  { id: 'p5', tagKey: 'guide',        tagColor: 'text-brand-400 bg-brand-900/40 border-brand-500/30', readTime: 10, gradient: 'from-sky-900/40 to-blue-900/50' },
  { id: 'p6', tagKey: 'productivity', tagColor: 'text-rose-400 bg-rose-900/30 border-rose-500/30',    readTime: 7,  gradient: 'from-rose-900/40 to-pink-900/40' },
]

export default function Blog() {
  const { t } = useTranslation()
  const { siteName } = useSite()

  const posts = POST_META.map(meta => ({
    ...meta,
    tag:     t(`blog.tags.${meta.tagKey}`),
    title:   t(`blog.posts.${meta.id}.title`),
    excerpt: t(`blog.posts.${meta.id}.excerpt`),
    date:    t(`blog.posts.${meta.id}.date`),
    readTime: t('blog.readTime', { count: meta.readTime }),
  }))

  const featured = posts[0]
  const rest     = posts.slice(1)

  return (
    <div className="min-h-screen bg-gray-950 text-white">
      <SeoHead titleKey="seo.blogTitle" descriptionKey="seo.blogDescription" />
      {/* Background glows */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 left-1/3 w-96 h-96 bg-brand-900/20 rounded-full blur-3xl" />
        <div className="absolute top-2/3 -right-20 w-80 h-80 bg-purple-900/15 rounded-full blur-3xl" />
      </div>

      <LandingNav />

      {/* Header */}
      <div className="relative z-10 max-w-5xl mx-auto px-6 pt-14 pb-12">
        <div className="inline-flex items-center gap-2 bg-brand-900/40 border border-brand-500/30 rounded-full px-4 py-1.5 mb-5">
          <span className="text-xs text-brand-300 font-medium">{t('blog.tag', { siteName })}</span>
        </div>
        <h1 className="text-5xl font-extrabold mb-4">{t('blog.title')}</h1>
        <p className="text-gray-400 text-lg max-w-xl">{t('blog.subtitle')}</p>
      </div>

      {/* Featured post */}
      <div className="relative z-10 max-w-5xl mx-auto px-6 pb-14">
        <div className={`relative rounded-3xl overflow-hidden bg-gradient-to-br ${featured.gradient} border border-white/10 p-8 md:p-10 group cursor-pointer hover:border-white/20 transition-all duration-200`}>
          <div className="max-w-lg">
            <span className={`inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full border ${featured.tagColor} mb-4`}>
              {featured.tag}
            </span>
            <h2 className="text-2xl md:text-3xl font-bold text-white mb-4 group-hover:text-brand-300 transition-colors">
              {featured.title}
            </h2>
            <p className="text-gray-300 text-sm leading-relaxed mb-6">{featured.excerpt}</p>
            <div className="flex items-center gap-4 text-xs text-gray-400">
              <span className="flex items-center gap-1.5"><Clock className="w-3.5 h-3.5" />{featured.readTime}</span>
              <span>{featured.date}</span>
            </div>
          </div>
          <div className="absolute bottom-8 right-8 md:right-10">
            <div className="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center group-hover:bg-brand-600 transition-colors">
              <ArrowRight className="w-4 h-4 text-white" />
            </div>
          </div>
        </div>
      </div>

      {/* Posts grid */}
      <div className="relative z-10 max-w-5xl mx-auto px-6 pb-24">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {rest.map((post) => (
            <div
              key={post.id}
              className="group bg-gray-900/60 border border-white/10 hover:border-white/20 rounded-2xl p-6 flex flex-col cursor-pointer transition-all duration-200"
            >
              <div className={`inline-flex items-center self-start text-xs font-semibold px-3 py-1 rounded-full border ${post.tagColor} mb-4`}>
                {post.tag}
              </div>
              <h3 className="font-bold text-white text-base leading-snug mb-3 group-hover:text-brand-300 transition-colors flex-1">
                {post.title}
              </h3>
              <p className="text-gray-400 text-sm leading-relaxed mb-5 line-clamp-3">
                {post.excerpt}
              </p>
              <div className="flex items-center justify-between text-xs text-gray-500 mt-auto">
                <span className="flex items-center gap-1.5"><Clock className="w-3.5 h-3.5" />{post.readTime}</span>
                <span>{post.date}</span>
              </div>
            </div>
          ))}
        </div>

        <div className="text-center mt-12">
          <button className="btn-secondary px-8 py-3 text-sm">
            {t('blog.loadMore')}
          </button>
        </div>
      </div>

      {/* Newsletter */}
      <div className="relative z-10 max-w-2xl mx-auto px-6 pb-24 text-center">
        <div className="bg-gradient-to-r from-brand-900/40 to-purple-900/40 border border-brand-500/20 rounded-3xl p-10">
          <h2 className="text-2xl font-bold mb-2">{t('blog.newsletter.title')}</h2>
          <p className="text-gray-400 text-sm mb-6">{t('blog.newsletter.subtitle')}</p>
          <div className="flex gap-3 max-w-sm mx-auto">
            <input
              type="email"
              placeholder={t('blog.newsletter.email')}
              className="input-field flex-1 text-sm"
            />
            <button className="btn-primary px-5 text-sm whitespace-nowrap">
              {t('blog.newsletter.btn')}
            </button>
          </div>
        </div>
      </div>

      <LandingFooter />
    </div>
  )
}
