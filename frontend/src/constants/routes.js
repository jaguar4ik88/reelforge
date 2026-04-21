/** Client app (dashboard, projects, etc.) — prefix for all logged-in customer routes. */
export const APP_BASE = '/app'

/** Staff (admin / manager) — separate area. */
export const ADMIN_BASE = '/admin'

/**
 * Where to send the user right after login / OAuth.
 * @param {string | undefined} role — from API `user.role`
 */
export function postLoginPath(role) {
  if (role === 'admin' || role === 'manager') {
    return `${ADMIN_BASE}/dashboard`
  }
  return `${APP_BASE}/dashboard`
}

export function isStaffRole(role) {
  return role === 'admin' || role === 'manager'
}

export function isAdminRole(role) {
  return role === 'admin'
}
