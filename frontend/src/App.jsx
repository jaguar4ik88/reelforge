import { Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuthContext } from './context/AuthContext'
import AppLayout from './layouts/AppLayout'
import AuthLayout from './layouts/AuthLayout'
import Landing       from './pages/Landing'
import Pricing       from './pages/Pricing'
import Blog          from './pages/Blog'
import Login         from './pages/Login'
import Register      from './pages/Register'
import OAuthCallback   from './pages/OAuthCallback'
import ForgotPassword  from './pages/ForgotPassword'
import ResetPassword   from './pages/ResetPassword'
import Dashboard     from './pages/Dashboard'
import Products      from './pages/Products'
import TemplatesPage from './pages/TemplatesPage'
import CreateStudio  from './pages/CreateStudio'
import Gallery       from './pages/Gallery'
import CreateProject from './pages/CreateProject'
import CreateProductPhotoFlow from './pages/CreateProductPhotoFlow'
import ProjectView   from './pages/ProjectView'
import Profile       from './pages/Profile'

function PrivateRoute({ children }) {
  const { user, loading } = useAuthContext()
  if (loading) return <FullScreenSpinner />
  return user ? children : <Navigate to="/login" replace />
}

function GuestRoute({ children }) {
  const { user, loading } = useAuthContext()
  if (loading) return <FullScreenSpinner />
  return !user ? children : <Navigate to="/dashboard" replace />
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

        <Route element={<GuestRoute><AuthLayout /></GuestRoute>}>
          <Route path="/login"    element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/auth/oauth-callback" element={<OAuthCallback />} />
        </Route>

        <Route element={<PrivateRoute><AppLayout /></PrivateRoute>}>
          <Route path="/dashboard"        element={<Dashboard />} />
          <Route path="/products"         element={<Products />} />
          <Route path="/templates"        element={<TemplatesPage />} />
          <Route path="/create"           element={<CreateStudio />} />
          <Route path="/gallery"          element={<Gallery />} />
          <Route path="/projects/new"       element={<CreateProject />} />
          <Route path="/projects/new-photo" element={<CreateProductPhotoFlow />} />
          <Route path="/projects/:id"     element={<ProjectView />} />
          <Route path="/profile"          element={<Profile />} />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  )
}
