import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import toast from 'react-hot-toast'

export default function Login() {
  const [email, setEmail]     = useState('')
  const [password, setPass]   = useState('')
  const [loading, setLoading] = useState(false)
  const { login }             = useAuth()
  const navigate              = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    try {
      await login(email, password)
      toast.success('Hoş geldin!')
      navigate('/')
    } catch (err) {
      toast.error(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <main style={{ display: 'flex', justifyContent: 'center', padding: '60px 20px' }}>
      <div style={{ width: '100%', maxWidth: 400 }}>
        <h1 style={{ fontSize: 28, fontWeight: 800, marginBottom: 8, textAlign: 'center' }}>Giriş Yap</h1>
        <p style={{ color: 'var(--text2)', textAlign: 'center', marginBottom: 32 }}>
          Hesabın yok mu? <Link to="/register" style={{ color: 'var(--accent)' }}>Kayıt Ol</Link>
        </p>

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          <FormField label="E-posta" type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="ornek@mail.com" />
          <FormField label="Şifre" type="password" value={password} onChange={e => setPass(e.target.value)} placeholder="••••••" />

          <button type="submit" disabled={loading} style={{
            marginTop: 4, padding: '13px', borderRadius: 10,
            background: 'var(--accent)', color: '#0a0a0a',
            fontWeight: 700, fontSize: 15, fontFamily: 'var(--font-display)',
          }}>
            {loading ? 'Giriş yapılıyor...' : 'Giriş Yap'}
          </button>
        </form>
      </div>
    </main>
  )
}

function FormField({ label, ...props }) {
  return (
    <div>
      <label style={{ display: 'block', fontSize: 13, color: 'var(--text2)', marginBottom: 6 }}>{label}</label>
      <input {...props} style={{
        width: '100%', padding: '11px 14px', borderRadius: 8,
        background: 'var(--bg2)', border: '1px solid var(--border2)',
        color: 'var(--text)',
        outline: 'none',
      }} />
    </div>
  )
}
