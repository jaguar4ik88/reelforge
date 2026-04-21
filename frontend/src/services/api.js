import axios from 'axios'

/**
 * Resolves API prefix. VITE_API_URL = origin only (no /api). Trailing slash is OK.
 * Uses URL() so https://host/ + /api never becomes https://host//api (breaks one deploy).
 */
function apiBaseUrl() {
  const raw = import.meta.env.VITE_API_URL
  if (raw == null || String(raw).trim() === '') return '/api'
  const base = String(raw).trim().replace(/\/+$/, '')
  if (base === '') return '/api'
  try {
    return new URL('/api', base).href.replace(/\/$/, '')
  } catch {
    return `${base}/api`.replace(/([^:]\/)\/+/g, '$1')
  }
}

const api = axios.create({
  baseURL: apiBaseUrl(),
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

api.interceptors.request.use((config) => {
  const token  = localStorage.getItem('token')
  const locale = localStorage.getItem('reelforge_locale') || 'uk'
  if (token)  config.headers.Authorization = `Bearer ${token}`
  config.headers['X-Locale'] = locale
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status
    const url = err.config?.url ?? ''
    const isCredentialRoute =
      url.includes('/auth/login') ||
      url.includes('/auth/register') ||
      url.includes('/auth/forgot-password') ||
      url.includes('/auth/reset-password')
    if (status === 401 && !isCredentialRoute) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

// ── Auth ──────────────────────────────────────────────────────────────────────
export const authApi = {
  register:       (data) => api.post('/auth/register', data),
  login:          (data) => api.post('/auth/login', data),
  forgotPassword: (data) => api.post('/auth/forgot-password', data),
  resetPassword:  (data) => api.post('/auth/reset-password', data),
  logout:         ()     => api.post('/auth/logout'),
  me:             ()     => api.get('/auth/me'),
}

// ── Projects ──────────────────────────────────────────────────────────────────
export const projectsApi = {
  list:   (page = 1) => api.get(`/projects?page=${page}`),
  get:    (id)       => api.get(`/projects/${id}`),
  update: (id, body) => api.patch(`/projects/${id}`, body),
  delete: (id)       => api.delete(`/projects/${id}`),
}

// ── Images ────────────────────────────────────────────────────────────────────
export const imagesApi = {
  delete: (projectId, imageId) => api.delete(`/projects/${projectId}/images/${imageId}`),
}

// ── Photo-guided project (product reference → generation job) ────────────────
export const photoFlowApi = {
  /**
   * @param {File|File[]} files
   * @param {{ productName: string, category: string }} meta — required by API (MVP: manual name + category)
   */
  createFromPhoto: (files, { productName, category, templateId }) => {
    const form = new FormData()
    const list = Array.isArray(files) ? files : [files]
    list.forEach((f) => form.append('images[]', f))
    form.append('product_name', productName)
    form.append('category', category)
    if (templateId != null && templateId !== '') {
      form.append('template_id', String(templateId))
    }
    return api.post('/projects/from-photo', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  /** Future: AI vision analysis (not used in MVP). */
  analyzeProduct: (projectId) => api.post(`/projects/${projectId}/product-analysis`),
  startGeneration: (projectId, payload) =>
    api.post(`/projects/${projectId}/photo-generations`, payload),
}

// ── Templates ─────────────────────────────────────────────────────────────────
export const templatesApi = {
  list: () => api.get('/templates'),
  get:  (id) => api.get(`/templates/${id}`),
}

export const adminTemplatesApi = {
  list: (page = 1) => api.get(`/admin/templates?page=${page}`),
  get: (id) => api.get(`/admin/templates/${id}`),
  create: (formData) =>
    api.post('/admin/templates', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
  update: (id, formData) =>
    api.post(`/admin/templates/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
  remove: (id) => api.delete(`/admin/templates/${id}`),
}

export const adminSubscriptionPlansApi = {
  list: () => api.get('/admin/subscription-plans'),
  get: (id) => api.get(`/admin/subscription-plans/${id}`),
  create: (body) => api.post('/admin/subscription-plans', body),
  update: (id, body) => api.put(`/admin/subscription-plans/${id}`, body),
  remove: (id) => api.delete(`/admin/subscription-plans/${id}`),
}

export const adminStatsApi = {
  overview: () => api.get('/admin/stats/overview'),
}

export const adminUsersApi = {
  list: (params) => api.get('/admin/users', { params }),
  get: (id) => api.get(`/admin/users/${id}`),
  updateCredits: (id, body) => api.put(`/admin/users/${id}/credits`, body),
  purchases: (id, params) => api.get(`/admin/users/${id}/purchases`, { params }),
  remove: (id) => api.delete(`/admin/users/${id}`),
}

// ── Profile ───────────────────────────────────────────────────────────────────
export const homeApi = {
  /** Authenticated: plan, credits, image/video counts, last 4 projects */
  dashboard: () => api.get('/home'),
}

export const profileApi = {
  get:            ()       => api.get('/profile'),
  update:         (data)   => {
    const form = new FormData()
    Object.entries(data).forEach(([k, v]) => v !== undefined && form.append(k, v))
    return api.post('/profile', form, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  changePassword: (data)   => api.post('/profile/password', data),
  stats:          ()       => api.get('/profile/stats'),
}

// ── AI Generation (Replicate) ─────────────────────────────────────────────────
export const generationApi = {
  start:  (data)         => api.post('/generate', data),
  status: (predictionId) => api.get(`/generate/${predictionId}`),
}

// ── Payments (WayForPay / FastSpring) ───────────────────────────────────────────
export const paymentsApi = {
  checkoutContext: () => api.get('/payments/checkout-context'),
  wayforpayInvoice: (slug) => api.post('/payments/wayforpay/invoice', { slug }),
  wayforpaySubscriptionInvoice: (slug) =>
    api.post('/payments/wayforpay/subscription-invoice', { slug }),
  wayforpayOrderStatus: (orderReference) =>
    api.get('/payments/wayforpay/order-status', { params: { order_reference: orderReference } }),
  fastspringSession: (slug) => api.post('/payments/fastspring/session', { slug }),
}

export default api
