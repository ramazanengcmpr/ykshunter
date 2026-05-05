import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'

const FEATURES = [
  { icon: '⚡', title: 'Anlık Geri Bildirim', desc: 'Her sorudan sonra doğru cevabı ve açıklamasını hemen gör.' },
  { icon: '📊', title: 'Detaylı Sonuç Analizi', desc: 'Test bitince soru bazlı doğru/yanlış analizi ve puanın.' },
  { icon: '🎯', title: 'TYT & AYT Kapsamı', desc: '8 ders, 16 test, 170\'den fazla özgün soru.' },
  { icon: '🔄', title: 'Sınırsız Tekrar', desc: 'Aynı testi istediğin kadar çöz, gelişimini izle.' },
]

export default function Home() {
  const [cats, setCats] = useState([])
  const [quizCount, setQuizCount] = useState(0)

  useEffect(() => {
    api.categories().then(d => {
      setCats(d.categories)
      const total = d.categories.reduce((s, c) => s + (c.quiz_count || 0), 0)
      setQuizCount(total)
    }).catch(() => {})
  }, [])

  return (
    <main>
      {/* ── Hero ── */}
      <section style={{ padding: '80px 0 56px', textAlign: 'center', position: 'relative', overflow: 'hidden' }}>
        <div style={{
          position: 'absolute', top: 0, left: '50%', transform: 'translateX(-50%)',
          width: 700, height: 400,
          background: 'radial-gradient(ellipse at 50% 0%, rgba(232,255,71,0.09) 0%, transparent 65%)',
          pointerEvents: 'none',
        }} />

        <div className="container">
          <div className="fade-up" style={{
            display: 'inline-flex', alignItems: 'center', gap: 8,
            padding: '5px 16px', borderRadius: 20,
            border: '1px solid rgba(232,255,71,0.35)',
            background: 'rgba(232,255,71,0.07)',
            fontSize: 13, color: 'var(--accent)',
            fontFamily: 'var(--font-display)', fontWeight: 600,
            marginBottom: 28,
          }}>
            🚀 YKS 2025'e hazır mısın?
          </div>

          <h1 className="fade-up" style={{
            fontSize: 'clamp(38px, 7vw, 76px)',
            fontWeight: 800, lineHeight: 1.08, marginBottom: 22,
            animationDelay: '0.05s',
          }}>
            Online Test Çöz,<br />
            <span style={{ color: 'var(--accent)' }}>Sıralamana Fırlat</span>
          </h1>

          <p className="fade-up" style={{
            fontSize: 18, color: 'var(--text2)', lineHeight: 1.7,
            maxWidth: 540, margin: '0 auto 40px',
            animationDelay: '0.1s',
          }}>
            TYT ve AYT konularına özel hazırlanmış denemeler. Cevapla, öğren, tekrar et.
          </p>

          <div className="fade-up" style={{
            display: 'flex', gap: 12, justifyContent: 'center', flexWrap: 'wrap',
            animationDelay: '0.15s',
          }}>
            <Link to="/quizzes" style={{
              padding: '14px 36px', borderRadius: 10,
              background: 'var(--accent)', color: '#0a0a0a',
              fontWeight: 800, fontSize: 16, fontFamily: 'var(--font-display)',
              boxShadow: '0 0 32px rgba(232,255,71,0.25)',
            }}>Teste Başla →</Link>
            <Link to="/register" style={{
              padding: '14px 28px', borderRadius: 10,
              background: 'var(--bg3)', color: 'var(--text)',
              fontWeight: 500, fontSize: 15,
              border: '1px solid var(--border2)',
            }}>Ücretsiz Kayıt</Link>
          </div>

          {/* Stats */}
          <div className="fade-up" style={{
            display: 'flex', gap: 48, justifyContent: 'center',
            marginTop: 56, animationDelay: '0.2s', flexWrap: 'wrap',
          }}>
            {[
              ['8', 'Ders'],
              [quizCount || '16', 'Test'],
              ['170+', 'Soru'],
              ['Ücretsiz', '100%'],
            ].map(([v, l]) => (
              <div key={l} style={{ textAlign: 'center' }}>
                <div style={{ fontSize: 30, fontWeight: 800, fontFamily: 'var(--font-display)', color: 'var(--accent)' }}>{v}</div>
                <div style={{ fontSize: 12, color: 'var(--text3)', marginTop: 2, fontWeight: 500 }}>{l}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Kategoriler ── */}
      {cats.length > 0 && (
        <section style={{ padding: '16px 0 48px' }}>
          <div className="container">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 20 }}>
              <h2 style={{ fontSize: 20, fontWeight: 700 }}>Dersler</h2>
              <Link to="/quizzes" style={{ fontSize: 13, color: 'var(--text3)' }}>Tüm testleri gör →</Link>
            </div>
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))',
              gap: 10,
            }}>
              {cats.map((cat) => (
                <Link key={cat.id} to={`/quizzes?cat=${cat.slug}`} style={{
                  padding: '18px 12px', borderRadius: 12,
                  background: 'var(--bg2)',
                  border: '1px solid var(--border)',
                  textAlign: 'center', display: 'block',
                  transition: 'border-color 0.2s, transform 0.15s',
                }}
                  onMouseEnter={e => { e.currentTarget.style.borderColor = cat.color; e.currentTarget.style.transform = 'translateY(-2px)' }}
                  onMouseLeave={e => { e.currentTarget.style.borderColor = 'var(--border)'; e.currentTarget.style.transform = 'none' }}
                >
                  <div style={{ fontSize: 28, marginBottom: 8 }}>{cat.icon}</div>
                  <div style={{ fontSize: 13, fontWeight: 700, color: cat.color, marginBottom: 4 }}>{cat.name}</div>
                  <div style={{ fontSize: 11, color: 'var(--text3)' }}>{cat.quiz_count} test</div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* ── Özellikler ── */}
      <section style={{ padding: '0 0 80px' }}>
        <div className="container">
          <h2 style={{ fontSize: 20, fontWeight: 700, marginBottom: 20 }}>Neden YKS Hunter?</h2>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(230px, 1fr))', gap: 14 }}>
            {FEATURES.map((f) => (
              <div key={f.title} style={{
                padding: '24px 22px', borderRadius: 12,
                background: 'var(--bg2)', border: '1px solid var(--border)',
              }}>
                <div style={{ fontSize: 30, marginBottom: 14 }}>{f.icon}</div>
                <h3 style={{ fontSize: 15, fontWeight: 700, marginBottom: 8 }}>{f.title}</h3>
                <p style={{ fontSize: 13, color: 'var(--text2)', lineHeight: 1.6 }}>{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── CTA Banner ── */}
      <section style={{ padding: '0 0 80px' }}>
        <div className="container">
          <div style={{
            padding: '40px 40px', borderRadius: 16,
            background: 'linear-gradient(135deg, rgba(232,255,71,0.1) 0%, rgba(232,255,71,0.03) 100%)',
            border: '1px solid rgba(232,255,71,0.2)',
            display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 24,
          }}>
            <div>
              <h2 style={{ fontSize: 22, fontWeight: 800, marginBottom: 8 }}>Hazır mısın?</h2>
              <p style={{ color: 'var(--text2)', fontSize: 14 }}>Hemen kayıt ol, sonuçlarını kaydet, gelişimini takip et.</p>
            </div>
            <Link to="/register" style={{
              padding: '13px 32px', borderRadius: 10,
              background: 'var(--accent)', color: '#0a0a0a',
              fontWeight: 800, fontSize: 15, fontFamily: 'var(--font-display)',
              whiteSpace: 'nowrap',
            }}>Ücretsiz Başla →</Link>
          </div>
        </div>
      </section>
    </main>
  )
}
