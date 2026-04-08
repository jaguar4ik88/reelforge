import { Routes, Route, Navigate, useParams } from 'react-router-dom'
import { AuthProvider, useAuthContext } from './context/AuthContext'
import { getAppUrl } from './utils/apiBase'
import { postLoginPath, isStaffRole, APP_BASE, ADMIN_BASE } from './constants/routes'
import AppLayout from './layouts/AppLayout'
import AdminLayout from './layouts/AdminLayout'
import AuthLayout from './layouts/AuthLayout'
import Landing       from './pages/Landing'
import Pricing       from './pages/Pricing'
import Blog          from './pages/Blog'
import Privacy       from './pages/Privacy'
import Terms         from './pages/Terms'
import Refund        from './pages/Refund'
import Login         from './pages/Login'
import Register      from './pages/Register'
import OAuthCallback   from './pages/OAuthCallback'
import ForgotPassword  from './pages/ForgotPassword'
import ResetPassword   from './pages/ResetPassword'
import Dashboard     from './pages/Dashboard'
import TemplatesPage from './pages/TemplatesPage'
import CreateStudio  from './pages/CreateStudio'
import Gallery       from './pages/Gallery'
import CreateProductPhotoFlow from './pages/CreateProductPhotoFlow'
import ProjectView   from './pages/ProjectView'
import Profile       from './pages/Profile'
import AdminDashboard from './pages/admin/AdminDashboard'
import AdminTemplates from './pages/admin/AdminTemplates'
import AdminTemplateEdit from './pages/admin/AdminTemplateEdit'

/** Authenticated clients only — staff is redirected to /admin. */
function ClientAppRoute({ children }) {
  const { user, loading } = useAuthContext()
  if (loading) return <FullScreenSpinner />
  if (!user) return <Navigate to="/login" replace />
  if (isStaffRole(user.role)) {
    return <Navigate to={`${ADMIN_BASE}/dashboard`} replace />
  }
  return children
}

/** Admin + manager only. */
function StaffRoute({ children }) {
  const { user, loading } = useAuthContext()
  if (loading) return <FullScreenSpinner />
  if (!user) return <Navigate to="/login" replace />
  if (!isStaffRole(user.role)) {
    return <Navigate to={`${APP_BASE}/dashboard`} replace />
  }
  return children
}

function GuestRoute({ children }) {
  const { user, loading } = useAuthContext()
  if (loading) return <FullScreenSpinner />
  if (!user) return children

  const dest = postLoginPath(user.role)
  const appUrl = getAppUrl()
  if (appUrl !== window.location.origin) {
    window.location.href = appUrl + dest
    return <FullScreenSpinner />
  }
  return <Navigate to={dest} replace />
}

function LegacyProjectRedirect() {
  const { id } = useParams()
  return <Navigate to={`${APP_BASE}/projects/${id}`} replace />
}

function FullScreenSpinner() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-950">
      <div className="w-12 h-12 rounded-full border-4 border-brand-500/30 border-t-brand-500 animate-spin" />
    </div>
  )
}

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/"        element={<Landing />} />
        <Route path="/pricing" element={<Pricing />} />
        <Route path="/blog"    element={<Blog />} />
        <Route path="/privacy" element={<Privacy />} />
        <Route path="/terms"   element={<Terms />} />
        <Route path="/refund"  element={<Refund />} />

        <Route element={<GuestRoute><AuthLayout /></GuestRoute>}>
          <Route path="/login"    element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/auth/oauth-callback" element={<OAuthCallback />} />
        </Route>

        <Route path="/app" element={<ClientAppRoute><AppLayout /></ClientAppRoute>}>
          <Route index element={<Navigate to="dashboard" replace />} />
          <Route path="dashboard"   element={<Dashboard />} />
          <Route path="products"    element={<Navigate to={`${APP_BASE}/gallery`} replace />} />
          <Route path="templates"   element={<TemplatesPage />} />
          <Route path="create"      element={<CreateStudio />} />
          <Route path="gallery"     element={<Gallery />} />
          <Route path="projects/new-photo" element={<CreateProductPhotoFlow />} />
          <Route path="projects/:id"     element={<ProjectView />} />
          <Route path="profile"     element={<Profile />} />
        </Route>

        <Route path="/admin" element={<StaffRoute><AdminLayout /></StaffRoute>}>
          <Route index element={<Navigate to="dashboard" replace />} />
          <Route path="dashboard" element={<AdminDashboard />} />
          <Route path="templates" element={<AdminTemplates />} />
          <Route path="templates/new" element={<AdminTemplateEdit />} />
          <Route path="templates/:id/edit" element={<AdminTemplateEdit />} />
        </Route>

        {/* Old URLs → /app/... */}
        <Route path="/dashboard" element={<Navigate to={`${APP_BASE}/dashboard`} replace />} />
        <Route path="/products" element={<Navigate to={`${APP_BASE}/gallery`} replace />} />
        <Route path="/templates" element={<Navigate to={`${APP_BASE}/templates`} replace />} />
        <Route path="/create" element={<Navigate to={`${APP_BASE}/create`} replace />} />
        <Route path="/gallery" element={<Navigate to={`${APP_BASE}/gallery`} replace />} />
        <Route path="/profile" element={<Navigate to={`${APP_BASE}/profile`} replace />} />
        <Route path="/projects/new-photo" element={<Navigate to={`${APP_BASE}/projects/new-photo`} replace />} />
        <Route path="/projects/:id" element={<LegacyProjectRedirect />} />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  )
}
