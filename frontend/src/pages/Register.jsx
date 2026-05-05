import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import toast from 'react-hot-toast'

export default function Register() {
  const [name, setName]       = useState('')
  const [email, setEmail]     = useState('')
  const [password, setPass]   = useState('')
  const [loading, setLoading] = useState(false)
  const { register }          = useAuth()
  const navigate              = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    try {
      await register(name, email, password)
      toast.success('Hesap oluşturuldu! 🎉')
      navigate('/')
    } catch (err) {
      toast.error(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <main style={{ display: 'flex', justifyContent: 'center', padding: '60px 20px' }}>
      <div style={{ width: '100%', maxWidth: 420 }}>
        <h1 style={{ fontSize: 28, fontWeight: 800, marginBottom: 8, textAlign: 'center' }}>Hesap Oluştur</h1>
        <p style={{ color: 'var(--text2)', textAlign: 'center', marginBottom: 32 }}>
          Zaten hesabın var mı? <Link to="/login" style={{ color: 'var(--accent)' }}>Giriş Yap</Link>
        </p>

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          {[
            { label: 'Ad Soyad', type: 'text',     value: name,     setter: setName,  ph: 'Ahmet Yılmaz' },
            { label: 'E-posta',  type: 'email',    value: email,    setter: setEmail, ph: 'ornek@mail.com' },
            { label: 'Şifre',    type: 'password', value: password, setter: setPass,  ph: 'En az 6 karakter' },
          ].map(({ label, type, value, setter, ph }) => (
            <div key={label}>
              <label style={{ display: 'block', fontSize: 13, color: 'var(--text2)', marginBottom: 6 }}>{label}</label>
              <input type={type} value={value} onChange={e => setter(e.target.value)} placeholder={ph}
                style={{
                  width: '100%', padding: '11px 14px', borderRadius: 8,
                  background: 'var(--bg2)', border: '1px solid var(--border2)',
                  color: 'var(--text)', outline: 'none',
                }} />
            </div>
          ))}

          <button type="submit" disabled={loading} style={{
            marginTop: 4, padding: '13px', borderRadius: 10,
            background: 'var(--accent)', color: '#0a0a0a',
            fontWeight: 700, fontSize: 15, fontFamily: 'var(--font-display)',
          }}>
            {loading ? 'Oluşturuluyor...' : 'Kayıt Ol →'}
          </button>
        </form>
      </div>
    </main>
  )
}
