import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL
    ? `${import.meta.env.VITE_API_URL}/api`
    : '/api',
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
  list:     (page = 1) => api.get(`/projects?page=${page}`),
  get:      (id)       => api.get(`/projects/${id}`),
  create:   (data)     => api.post('/projects', data),
  delete:   (id)       => api.delete(`/projects/${id}`),
}

// ── Images ────────────────────────────────────────────────────────────────────
export const imagesApi = {
  upload: (projectId, files) => {
    const form = new FormData()
    files.forEach((f) => form.append('images[]', f))
    return api.post(`/projects/${projectId}/images`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  delete: (projectId, imageId) => api.delete(`/projects/${projectId}/images/${imageId}`),
}

// ── Video ─────────────────────────────────────────────────────────────────────
export const videoApi = {
  generate: (projectId) => api.post(`/projects/${projectId}/generate`),
}

// ── Photo-guided project (product reference → generation job) ────────────────
export const photoFlowApi = {
  /** @param {File} imageFile */
  createFromPhoto: (imageFile, title) => {
    const form = new FormData()
    form.append('image', imageFile)
    if (title) form.append('title', title)
    return api.post('/projects/from-photo', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  startGeneration: (projectId, payload) =>
    api.post(`/projects/${projectId}/photo-generations`, payload),
}

// ── Templates ─────────────────────────────────────────────────────────────────
export const templatesApi = {
  list: () => api.get('/templates'),
  get:  (id) => api.get(`/templates/${id}`),
}

// ── Profile ───────────────────────────────────────────────────────────────────
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

export default api
