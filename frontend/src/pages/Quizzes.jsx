import { useEffect, useState } from 'react'
import { Link, useSearchParams, useNavigate } from 'react-router-dom'
import { api } from '../api'
import toast from 'react-hot-toast'

const DIFF_COLOR = { kolay: '#4ade80', orta: '#f59e0b', zor: '#f87171' }
const DIFF_BG    = { kolay: 'rgba(74,222,128,0.12)', orta: 'rgba(245,158,11,0.12)', zor: 'rgba(248,113,113,0.12)' }

export default function Quizzes() {
  const [quizzes, setQuizzes] = useState([])
  const [cats, setCats]       = useState([])
  const [loading, setLoading] = useState(true)
  const [starting, setStarting] = useState(null)
  const [searchParams]        = useSearchParams()
  const cat                   = searchParams.get('cat') || ''
  const navigate              = useNavigate()

  useEffect(() => {
    api.categories().then(d => setCats(d.categories)).catch(() => {})
  }, [])

  useEffect(() => {
    setLoading(true)
    api.quizzes(cat)
      .then(d => { setQuizzes(d.quizzes); setLoading(false) })
      .catch(() => setLoading(false))
  }, [cat])

  const handleStart = async (quizId) => {
    setStarting(quizId)
    try {
      const { session_id } = await api.startQuiz(quizId)
      navigate(`/quiz/${session_id}`)
    } catch (e) {
      toast.error(e.message)
      setStarting(null)
    }
  }

  const activeCat = cats.find(c => c.slug === cat)

  return (
    <main style={{ padding: '40px 0 80px' }}>
      <div className="container">
        <div style={{ marginBottom: 32 }}>
          <h1 style={{ fontSize: 28, fontWeight: 800, marginBottom: 6 }}>
            {activeCat ? `${activeCat.icon} ${activeCat.name} Testleri` : 'Tüm Testler'}
          </h1>
          <p style={{ color: 'var(--text2)', fontSize: 14 }}>
            {quizzes.length} test bulundu
          </p>
        </div>

        {/* Category filter */}
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 32, paddingBottom: 24, borderBottom: '1px solid var(--border)' }}>
          <Link to="/quizzes" style={{
            padding: '7px 18px', borderRadius: 20, fontSize: 13, fontWeight: 600,
            background: !cat ? 'var(--accent)' : 'var(--bg3)',
            color: !cat ? '#0a0a0a' : 'var(--text2)',
            border: `1px solid ${!cat ? 'var(--accent)' : 'var(--border)'}`,
            fontFamily: !cat ? 'var(--font-display)' : 'inherit',
          }}>Tümü</Link>
          {cats.map(c => (
            <Link key={c.id} to={`/quizzes?cat=${c.slug}`} style={{
              padding: '7px 16px', borderRadius: 20, fontSize: 13, fontWeight: 500,
              background: cat === c.slug ? c.color + '22' : 'var(--bg3)',
              color: cat === c.slug ? c.color : 'var(--text2)',
              border: `1px solid ${cat === c.slug ? c.color + '55' : 'var(--border)'}`,
              display: 'inline-flex', alignItems: 'center', gap: 5,
            }}>
              <span>{c.icon}</span> {c.name}
              {c.quiz_count > 0 && (
                <span style={{
                  background: cat === c.slug ? c.color + '33' : 'var(--bg)',
                  borderRadius: 10, padding: '1px 6px', fontSize: 10, fontWeight: 700,
                  color: cat === c.slug ? c.color : 'var(--text3)',
                }}>{c.quiz_count}</span>
              )}
            </Link>
          ))}
        </div>

        {loading ? (
          <div style={{ display: 'flex', justifyContent: 'center', padding: '80px 0' }}>
            <div className="spinner" />
          </div>
        ) : quizzes.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '80px 0', color: 'var(--text3)' }}>
            <div style={{ fontSize: 48, marginBottom: 16 }}>📭</div>
            <p style={{ marginBottom: 20 }}>Bu kategoride henüz test yok.</p>
            <Link to="/quizzes" style={{
              padding: '10px 24px', borderRadius: 8,
              background: 'var(--accent)', color: '#0a0a0a', fontWeight: 700,
            }}>Tüm testlere bak</Link>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(310px, 1fr))', gap: 16 }}>
            {quizzes.map((quiz) => (
              <div key={quiz.id} style={{
                padding: 0, borderRadius: 14,
                background: 'var(--bg2)',
                border: '1px solid var(--border)',
                display: 'flex', flexDirection: 'column',
                overflow: 'hidden',
                transition: 'border-color 0.2s',
              }}
                onMouseEnter={e => e.currentTarget.style.borderColor = quiz.color || '#444'}
                onMouseLeave={e => e.currentTarget.style.borderColor = 'var(--border)'}
              >
                {/* Top color strip */}
                <div style={{ height: 4, background: quiz.color || '#6366f1' }} />

                <div style={{ padding: '20px 22px 22px', display: 'flex', flexDirection: 'column', flex: 1 }}>
                  {/* Header row */}
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 14 }}>
                    <span style={{ fontSize: 20 }}>{quiz.icon || '📚'}</span>
                    <span style={{ fontSize: 11, fontWeight: 700, color: quiz.color, fontFamily: 'var(--font-display)' }}>
                      {quiz.category_name}
                    </span>
                    <span style={{ marginLeft: 'auto', fontSize: 11, fontWeight: 700,
                      padding: '3px 10px', borderRadius: 20,
                      background: DIFF_BG[quiz.difficulty],
                      color: DIFF_COLOR[quiz.difficulty],
                    }}>
                      {quiz.difficulty === 'kolay' ? '🟢' : quiz.difficulty === 'orta' ? '🟡' : '🔴'} {quiz.difficulty}
                    </span>
                  </div>

                  <h3 style={{ fontSize: 16, fontWeight: 700, marginBottom: 8, lineHeight: 1.3 }}>{quiz.title}</h3>
                  <p style={{ fontSize: 13, color: 'var(--text2)', marginBottom: 18, flex: 1, lineHeight: 1.6 }}>{quiz.description}</p>

                  {/* Meta */}
                  <div style={{ display: 'flex', gap: 16, marginBottom: 18 }}>
                    <div style={{
                      flex: 1, padding: '10px', borderRadius: 8,
                      background: 'var(--bg3)', border: '1px solid var(--border)',
                      textAlign: 'center',
                    }}>
                      <div style={{ fontSize: 18, fontWeight: 800, fontFamily: 'var(--font-display)' }}>{quiz.question_count}</div>
                      <div style={{ fontSize: 11, color: 'var(--text3)', marginTop: 2 }}>Soru</div>
                    </div>
                    <div style={{
                      flex: 1, padding: '10px', borderRadius: 8,
                      background: 'var(--bg3)', border: '1px solid var(--border)',
                      textAlign: 'center',
                    }}>
                      <div style={{ fontSize: 18, fontWeight: 800, fontFamily: 'var(--font-display)' }}>{Math.floor(quiz.time_limit / 60)}</div>
                      <div style={{ fontSize: 11, color: 'var(--text3)', marginTop: 2 }}>Dakika</div>
                    </div>
                  </div>

                  <button onClick={() => handleStart(quiz.id)}
                    disabled={starting === quiz.id}
                    style={{
                      width: '100%', padding: '11px', borderRadius: 8,
                      background: starting === quiz.id ? 'var(--bg3)' : 'var(--accent)',
                      color: starting === quiz.id ? 'var(--text2)' : '#0a0a0a',
                      fontWeight: 800, fontSize: 14, fontFamily: 'var(--font-display)',
                    }}>
                    {starting === quiz.id ? '⏳ Başlatılıyor...' : 'Testi Başlat →'}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </main>
  )
}
