import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { api } from '../api'

const OPT_LABEL = { a: 'A', b: 'B', c: 'C', d: 'D', e: 'E' }

export default function ResultDetail() {
  const { id } = useParams()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.resultDetail(id).then(d => { setData(d); setLoading(false) }).catch(() => setLoading(false))
  }, [id])

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: '80px 0' }}><div className="spinner" /></div>
  if (!data) return <div style={{ textAlign: 'center', padding: '80px 0', color: 'var(--text3)' }}>Sonuç bulunamadı.</div>

  const { result, questions } = data
  const scoreColor = result.score >= 80 ? '#4ade80' : result.score >= 50 ? '#f59e0b' : '#f87171'

  return (
    <main style={{ padding: '40px 0 80px' }}>
      <div className="container" style={{ maxWidth: 740 }}>
        <Link to="/results" style={{ fontSize: 13, color: 'var(--text3)', display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 24 }}>
          ← Geri
        </Link>

        {/* Score card */}
        <div style={{
          padding: '32px', borderRadius: 'var(--radius)',
          background: 'var(--bg2)', border: '1px solid var(--border)',
          textAlign: 'center', marginBottom: 32,
        }}>
          <div style={{ fontSize: 18, fontWeight: 700, marginBottom: 20 }}>{result.quiz_title}</div>
          <div style={{
            fontSize: 72, fontWeight: 800, fontFamily: 'var(--font-display)',
            color: scoreColor, lineHeight: 1, marginBottom: 16,
          }}>{result.score}%</div>

          <div style={{ display: 'flex', gap: 24, justifyContent: 'center', flexWrap: 'wrap' }}>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 24, fontWeight: 700, color: '#4ade80' }}>{result.correct_count}</div>
              <div style={{ fontSize: 12, color: 'var(--text3)' }}>Doğru</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 24, fontWeight: 700, color: '#f87171' }}>{result.total - result.correct_count}</div>
              <div style={{ fontSize: 12, color: 'var(--text3)' }}>Yanlış</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 24, fontWeight: 700, color: 'var(--text2)' }}>{result.total}</div>
              <div style={{ fontSize: 12, color: 'var(--text3)' }}>Toplam</div>
            </div>
          </div>

          <Link to="/quizzes" style={{
            display: 'inline-flex', marginTop: 24, padding: '10px 28px',
            borderRadius: 8, background: 'var(--accent)', color: '#0a0a0a',
            fontWeight: 700, fontSize: 14,
          }}>Yeni Test Çöz</Link>
        </div>

        {/* Question breakdown */}
        <h2 style={{ fontSize: 18, fontWeight: 700, marginBottom: 16 }}>Soru Analizi</h2>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          {questions.map((q, i) => (
            <div key={q.id} style={{
              borderRadius: 'var(--radius-sm)',
              background: 'var(--bg2)',
              border: `1px solid ${q.is_correct ? 'rgba(74,222,128,0.25)' : 'rgba(248,113,113,0.25)'}`,
              overflow: 'hidden',
            }}>
              <div style={{
                padding: '12px 16px', display: 'flex', gap: 12, alignItems: 'flex-start',
                borderBottom: '1px solid var(--border)',
              }}>
                <span style={{
                  minWidth: 24, height: 24, borderRadius: 6,
                  background: q.is_correct ? 'rgba(74,222,128,0.2)' : 'rgba(248,113,113,0.2)',
                  color: q.is_correct ? '#4ade80' : '#f87171',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontSize: 12, fontWeight: 700, flexShrink: 0,
                }}>{i + 1}</span>
                <p style={{ fontSize: 14, lineHeight: 1.6 }}>{q.body}</p>
              </div>
              <div style={{ padding: '10px 16px', display: 'flex', gap: 16, fontSize: 13 }}>
                <span>
                  <span style={{ color: 'var(--text3)' }}>Cevabın: </span>
                  <span style={{ fontWeight: 700, color: q.is_correct ? '#4ade80' : '#f87171' }}>
                    {q.user_answer ? OPT_LABEL[q.user_answer] : '—'}
                  </span>
                </span>
                {!q.is_correct && (
                  <span>
                    <span style={{ color: 'var(--text3)' }}>Doğru: </span>
                    <span style={{ fontWeight: 700, color: '#4ade80' }}>{OPT_LABEL[q.correct]}</span>
                  </span>
                )}
              </div>
              {q.explanation && (
                <div style={{ padding: '8px 16px 12px', borderTop: '1px solid var(--border)' }}>
                  <p style={{ fontSize: 12, color: 'var(--text2)', lineHeight: 1.6 }}>💡 {q.explanation}</p>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </main>
  )
}
