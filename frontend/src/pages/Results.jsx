import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'

export default function Results() {
  const [results, setResults] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.results().then(d => { setResults(d.results); setLoading(false) }).catch(() => setLoading(false))
  }, [])

  const scoreColor = (s) => s >= 80 ? '#4ade80' : s >= 50 ? '#f59e0b' : '#f87171'

  return (
    <main style={{ padding: '40px 0 80px' }}>
      <div className="container">
        <h1 style={{ fontSize: 28, fontWeight: 800, marginBottom: 8 }}>Sonuçlarım</h1>
        <p style={{ color: 'var(--text2)', marginBottom: 28 }}>Tamamladığın testlerin sonuçları</p>

        {loading ? (
          <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}>
            <div className="spinner" />
          </div>
        ) : results.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '60px 0', color: 'var(--text3)' }}>
            <div style={{ fontSize: 48, marginBottom: 16 }}>📋</div>
            <p style={{ marginBottom: 20 }}>Henüz tamamlanmış test yok.</p>
            <Link to="/quizzes" style={{
              padding: '10px 24px', borderRadius: 8,
              background: 'var(--accent)', color: '#0a0a0a',
              fontWeight: 700,
            }}>Test Çöz</Link>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {results.map(r => (
              <Link key={r.id} to={`/results/${r.id}`} style={{
                display: 'flex', alignItems: 'center', gap: 16,
                padding: '18px 22px',
                borderRadius: 'var(--radius)',
                background: 'var(--bg2)',
                border: '1px solid var(--border)',
                transition: 'border-color var(--transition)',
              }}>
                <span style={{ fontSize: 22 }}>{r.icon || '📝'}</span>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600, marginBottom: 2 }}>{r.quiz_title}</div>
                  <div style={{ fontSize: 12, color: 'var(--text3)' }}>{r.category_name} • {new Date(r.finished_at).toLocaleDateString('tr-TR')}</div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontSize: 22, fontWeight: 800, fontFamily: 'var(--font-display)', color: scoreColor(r.score) }}>
                    {r.score}%
                  </div>
                  <div style={{ fontSize: 12, color: 'var(--text3)' }}>{r.correct_count}/{r.total}</div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </main>
  )
}
