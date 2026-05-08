import LandingNav from '../components/layout/LandingNav'
import LandingFooter from '../components/layout/LandingFooter'
import SellerLegalBlock, { hasSellerProfile } from '../components/layout/SellerLegalBlock'
import SeoHead from '../components/seo/SeoHead'
import { useTranslation } from 'react-i18next'
import { useSite } from '../context/SiteContext'

export default function SellerContacts() {
  const { t } = useTranslation()
  const { siteName, seller } = useSite()

  return (
    <div className="min-h-screen bg-rf-page text-rf-text">
      <SeoHead titleKey="seo.sellerContactsTitle" descriptionKey="seo.sellerContactsDescription" />

      <LandingNav />

      <main className="relative z-10 max-w-3xl w-full mx-auto px-6 pt-16 pb-16">
        <h1 className="text-3xl sm:text-4xl font-bold text-rf-text mb-8">{t('landing.sellerPageTitle')}</h1>
        {hasSellerProfile(seller) ? (
          <SellerLegalBlock showTitle={false} />
        ) : (
          <p className="text-rf-mutedFg text-sm leading-relaxed">{t('landing.sellerNotConfigured', { siteName })}</p>
        )}
      </main>

      <LandingFooter />
    </div>
  )
}
