/** @param {string | undefined} lang i18n language (e.g. 'uk', 'en') */
export function pickLocalizedDescription(plan, lang) {
  if (!plan || typeof plan !== 'object') return ''
  const useUk = typeof lang === 'string' && lang.toLowerCase().startsWith('uk')
  const en = typeof plan.description_en === 'string' ? plan.description_en.trim() : ''
  const uk = typeof plan.description_uk === 'string' ? plan.description_uk.trim() : ''
  if (useUk && uk) return uk
  if (en) return en
  return uk
}
