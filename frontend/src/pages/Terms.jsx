import LegalDocument from './LegalDocument'

export default function Terms() {
  return (
    <LegalDocument
      titleKey="legal.termsTitle"
      bodyKey="legal.termsBody"
      seoTitleKey="seo.termsTitle"
      seoDescKey="seo.termsDescription"
    />
  )
}
