import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import toast from 'react-hot-toast'

export default function Navbar() {
  const { user, logout } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    toast.success('Çıkış yapıldı')
    navigate('/')
  }

  const active = (path) => location.pathname === path

  return (
    <nav style={{
      position: 'sticky', top: 0, zIndex: 100,
      background: 'rgba(10,10,10,0.85)',
      backdropFilter: 'blur(16px)',
      borderBottom: '1px solid var(--border)',
    }}>
      <div className="container" style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        height: 60,
      }}>
        <Link to="/" style={{
          fontFamily: 'var(--font-display)',
          fontWeight: 800, fontSize: 20,
          display: 'flex', alignItems: 'center', gap: 8,
        }}>
          <span style={{ fontSize: 22 }}>🎯</span>
          <span>YKS<span style={{ color: 'var(--accent)' }}>Hunter</span></span>
        </Link>

        <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
          {[
            { to: '/', label: 'Ana Sayfa' },
            { to: '/quizzes', label: 'Testler' },
            ...(user ? [{ to: '/results', label: 'Sonuçlarım' }] : []),
          ].map(({ to, label }) => (
            <Link key={to} to={to} style={{
              padding: '6px 14px',
              borderRadius: 8,
              fontSize: 14,
              fontWeight: 500,
              color: active(to) ? 'var(--accent)' : 'var(--text2)',
              background: active(to) ? 'var(--accent-dim)' : 'transparent',
              transition: 'all var(--transition)',
            }}>{label}</Link>
          ))}

          <div style={{ width: 1, height: 20, background: 'var(--border)', margin: '0 8px' }} />

          {user ? (
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ fontSize: 13, color: 'var(--text2)' }}>👋 {user.name}</span>
              <button onClick={handleLogout} style={{
                padding: '6px 14px', borderRadius: 8, fontSize: 13,
                background: 'var(--bg3)', color: 'var(--text2)',
                border: '1px solid var(--border)',
                transition: 'all var(--transition)',
              }}>Çıkış</button>
            </div>
          ) : (
            <div style={{ display: 'flex', gap: 6 }}>
              <Link to="/login" style={{
                padding: '6px 14px', borderRadius: 8, fontSize: 13,
                background: 'var(--bg3)', color: 'var(--text2)',
                border: '1px solid var(--border)',
              }}>Giriş</Link>
              <Link to="/register" style={{
                padding: '6px 16px', borderRadius: 8, fontSize: 13,
                background: 'var(--accent)', color: '#0a0a0a',
                fontWeight: 700,
              }}>Kayıt Ol</Link>
            </div>
          )}
        </div>
      </div>
    </nav>
  )
}
