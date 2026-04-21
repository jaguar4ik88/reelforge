/**
 * Legacy `user.plan` (free|pro) vs active SubscriptionPlan (WayForPay).
 *
 * @param {{ plan?: string, subscription?: { name?: string } | null } | null | undefined} user
 * @param {(key: string) => string} t i18n `t`
 */
export function billingPlanLabel(user, t) {
  const name = user?.subscription?.name
  if (typeof name === 'string' && name.trim() !== '') {
    return name.trim()
  }
  const legacy = user?.plan === 'pro' ? 'pro' : 'free'
  const key = `common.plan.${legacy}`
  const label = t(key)
  return label !== key ? label : legacy
}

/**
 * Highlight style: paid subscription (catalog plan) or legacy Pro flag.
 *
 * @param {{ plan?: string, has_active_subscription?: boolean } | null | undefined} user
 */
export function userHasPaidPlanHighlight(user) {
  return user?.has_active_subscription === true || user?.plan === 'pro'
}
