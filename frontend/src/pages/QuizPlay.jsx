import { useEffect, useState, useCallback } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { api } from '../api'
import toast from 'react-hot-toast'

const OPTS = ['a', 'b', 'c', 'd', 'e']
const OPT_LABEL = { a: 'A', b: 'B', c: 'C', d: 'D', e: 'E' }

export default function QuizPlay() {
  const { id: sessionId } = useParams()
  const navigate = useNavigate()

  const [qData, setQData]           = useState(null)
  const [loading, setLoading]       = useState(true)
  const [selected, setSelected]     = useState(null)
  const [feedback, setFeedback]     = useState(null)
  const [submitting, setSub]        = useState(false)
  const [finishing, setFinishing]   = useState(false)
  const [timeLeft, setTimeLeft]     = useState(null)
  const [answeredMap, setAnsweredMap] = useState({}) // { questionIndex: answer }
  const [showConfirm, setShowConfirm] = useState(false)

  const loadQuestion = useCallback(async (index) => {
    setLoading(true)
    setSelected(null)
    setFeedback(null)
    try {
      const data = await api.question(sessionId, index)
      setQData(data)
      if (data.answered) setSelected(data.answered)
      if (timeLeft === null && data.quiz) setTimeLeft(data.quiz.time_limit || 1800)
    } catch (e) {
      toast.error(e.message)
    } finally {
      setLoading(false)
    }
  }, [sessionId])

  useEffect(() => { loadQuestion(0) }, [loadQuestion])

  // Timer countdown
  useEffect(() => {
    if (timeLeft === null || timeLeft <= 0) return
    const t = setInterval(() => setTimeLeft(p => Math.max(0, p - 1)), 1000)
    return () => clearInterval(t)
  }, [timeLeft !== null])

  useEffect(() => {
    if (timeLeft === 0) handleFinish()
  }, [timeLeft])

  const handleAnswer = async (opt) => {
    if (submitting || feedback) return
    setSelected(opt)
    setSub(true)
    try {
      const res = await api.submitAnswer({
        session_id: parseInt(sessionId),
        question_id: qData.question.id,
        answer: opt,
      })
      setFeedback(res)
      setAnsweredMap(prev => ({ ...prev, [qData.index]: opt }))
      if (res.correct) toast.success('Doğru! 🎉', { duration: 1500 })
      else toast.error('Yanlış!', { duration: 1500 })
    } catch (e) {
      toast.error(e.message)
    } finally {
      setSub(false)
    }
  }

  const handleNext = () => {
    const next = qData.index + 1
    if (next >= qData.total) {
      setShowConfirm(true)
    } else {
      loadQuestion(next)
    }
  }

  const handleFinish = async () => {
    if (finishing) return
    setFinishing(true)
    setShowConfirm(false)
    try {
      await api.finishQuiz(parseInt(sessionId))
      navigate(`/results/${sessionId}`)
    } catch (e) {
      toast.error(e.message)
      setFinishing(false)
    }
  }

  if (finishing) return (
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '60vh', gap: 16 }}>
      <div className="spinner" />
      <p style={{ color: 'var(--text2)' }}>Sonuçlar hesaplanıyor...</p>
    </div>
  )

  if (loading && !qData) return (
    <div style={{ display: 'flex', justifyContent: 'center', padding: '80px 0' }}>
      <div className="spinner" />
    </div>
  )

  if (!qData) return null

  const { question, index, total } = qData
  const progress = ((index) / total) * 100
  const mins = String(Math.floor((timeLeft || 0) / 60)).padStart(2, '0')
  const secs = String((timeLeft || 0) % 60).padStart(2, '0')
  const timeWarning = timeLeft !== null && timeLeft < 120
  const answeredCount = Object.keys(answeredMap).length

  return (
    <main style={{ padding: '28px 0 80px' }}>
      {/* Finish confirm modal */}
      {showConfirm && (
        <div style={{
          position: 'fixed', inset: 0, zIndex: 200,
          background: 'rgba(0,0,0,0.7)', backdropFilter: 'blur(6px)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>
          <div style={{
            background: 'var(--bg2)', border: '1px solid var(--border2)',
            borderRadius: 16, padding: '32px', maxWidth: 380, textAlign: 'center',
          }}>
            <div style={{ fontSize: 40, marginBottom: 16 }}>🏁</div>
            <h3 style={{ fontSize: 20, fontWeight: 800, marginBottom: 8 }}>Testi Bitir?</h3>
            <p style={{ color: 'var(--text2)', fontSize: 14, marginBottom: 24, lineHeight: 1.6 }}>
              {answeredCount} / {total} soruyu yanıtladın. Sonuçları görmek için bitir.
            </p>
            <div style={{ display: 'flex', gap: 10 }}>
              <button onClick={() => setShowConfirm(false)} style={{
                flex: 1, padding: '11px', borderRadius: 8,
                background: 'var(--bg3)', color: 'var(--text)',
                border: '1px solid var(--border)',
              }}>Geri Dön</button>
              <button onClick={handleFinish} style={{
                flex: 1, padding: '11px', borderRadius: 8,
                background: 'var(--accent)', color: '#0a0a0a',
                fontWeight: 800, fontFamily: 'var(--font-display)',
              }}>Bitir!</button>
            </div>
          </div>
        </div>
      )}

      <div className="container" style={{ maxWidth: 740 }}>
        {/* ── Header ── */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
          <div style={{ flex: 1 }}>
            <div style={{ fontSize: 13, color: 'var(--text2)', marginBottom: 4 }}>
              <strong style={{ color: 'var(--text)', fontFamily: 'var(--font-display)', fontSize: 16 }}>
                {index + 1}
              </strong>
              <span style={{ color: 'var(--text3)' }}> / {total}</span>
              <span style={{ color: 'var(--text3)', marginLeft: 12 }}>✅ {answeredCount} yanıtlandı</span>
            </div>
            {/* Progress bar */}
            <div style={{ height: 6, background: 'var(--bg3)', borderRadius: 3, overflow: 'hidden' }}>
              <div style={{
                height: '100%', width: `${progress}%`,
                background: 'linear-gradient(90deg, var(--accent) 0%, #b8ff00 100%)',
                borderRadius: 3, transition: 'width 0.4s ease',
              }} />
            </div>
          </div>

          {timeLeft !== null && (
            <div style={{
              padding: '7px 16px', borderRadius: 10, minWidth: 80, textAlign: 'center',
              background: timeWarning ? 'rgba(248,113,113,0.15)' : 'var(--bg3)',
              border: `1px solid ${timeWarning ? 'rgba(248,113,113,0.5)' : 'var(--border)'}`,
              color: timeWarning ? '#f87171' : 'var(--text)',
              fontFamily: 'var(--font-display)', fontWeight: 800, fontSize: 18,
            }}>
              {mins}:{secs}
            </div>
          )}

          <button onClick={() => setShowConfirm(true)} style={{
            padding: '7px 14px', borderRadius: 8, fontSize: 13,
            background: 'var(--bg3)', color: 'var(--text2)',
            border: '1px solid var(--border)',
          }}>Bitir</button>
        </div>

        {/* ── Question card ── */}
        <div style={{
          background: 'var(--bg2)', border: '1px solid var(--border)',
          borderRadius: 14, padding: '26px 28px 24px', marginBottom: 14,
        }}>
          {loading ? (
            <div style={{ display: 'flex', justifyContent: 'center', padding: '30px 0' }}>
              <div className="spinner" />
            </div>
          ) : (
            <>
              <p style={{ fontSize: 16, lineHeight: 1.75, fontWeight: 500, marginBottom: 26, color: 'var(--text)' }}>
                <span style={{
                  display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                  width: 26, height: 26, borderRadius: 6, background: 'var(--accent-dim)',
                  color: 'var(--accent)', fontSize: 12, fontWeight: 800,
                  fontFamily: 'var(--font-display)', marginRight: 10, flexShrink: 0,
                }}>S{index + 1}</span>
                {question.body}
              </p>

              <div style={{ display: 'flex', flexDirection: 'column', gap: 9 }}>
                {OPTS.map((opt) => {
                  const optText = question[`option_${opt}`]
                  const isSelected = selected === opt
                  const isCorrect  = feedback?.correct_ans === opt
                  const isWrong    = feedback && isSelected && !feedback.correct

                  let bg = 'var(--bg3)'
                  let border = 'var(--border)'
                  let color  = 'var(--text)'
                  let labelBg = 'var(--bg)'
                  let labelColor = 'var(--text3)'

                  if (feedback) {
                    if (isCorrect) {
                      bg = 'rgba(74,222,128,0.1)'; border = '#4ade80'
                      color = '#4ade80'; labelBg = '#4ade80'; labelColor = '#0a0a0a'
                    } else if (isWrong) {
                      bg = 'rgba(248,113,113,0.1)'; border = '#f87171'
                      color = '#f87171'; labelBg = '#f87171'; labelColor = '#0a0a0a'
                    }
                  } else if (isSelected) {
                    bg = 'var(--accent-dim)'; border = 'var(--accent)'
                    color = 'var(--accent)'; labelBg = 'var(--accent)'; labelColor = '#0a0a0a'
                  }

                  return (
                    <button key={opt} onClick={() => handleAnswer(opt)}
                      disabled={!!feedback || submitting}
                      style={{
                        display: 'flex', alignItems: 'flex-start', gap: 12,
                        padding: '12px 16px', borderRadius: 10, textAlign: 'left',
                        background: bg, border: `1.5px solid ${border}`, color,
                        transition: 'all 0.15s',
                        cursor: feedback ? 'default' : 'pointer',
                      }}>
                      <span style={{
                        minWidth: 26, height: 26, borderRadius: 7,
                        background: labelBg, color: labelColor,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 12, fontWeight: 800, fontFamily: 'var(--font-display)',
                        flexShrink: 0, transition: 'all 0.15s',
                        border: `1px solid ${isSelected && !feedback ? 'var(--accent)' : 'transparent'}`,
                      }}>{OPT_LABEL[opt]}</span>
                      <span style={{ fontSize: 14, lineHeight: 1.55, paddingTop: 4 }}>{optText}</span>
                    </button>
                  )
                })}
              </div>
            </>
          )}
        </div>

        {/* ── Explanation ── */}
        {feedback?.explanation && (
          <div style={{
            padding: '14px 18px', borderRadius: 10, marginBottom: 14,
            background: feedback.correct ? 'rgba(74,222,128,0.07)' : 'rgba(248,113,113,0.07)',
            border: `1px solid ${feedback.correct ? 'rgba(74,222,128,0.25)' : 'rgba(248,113,113,0.25)'}`,
          }}>
            <p style={{ fontSize: 13, lineHeight: 1.75, color: 'var(--text2)' }}>
              <strong style={{ color: feedback.correct ? '#4ade80' : '#f87171' }}>
                {feedback.correct ? '✅ Doğru! ' : '❌ Yanlış! '}
              </strong>
              {feedback.explanation}
            </p>
          </div>
        )}

        {/* ── Navigation ── */}
        <div style={{ display: 'flex', gap: 10 }}>
          {index > 0 && (
            <button onClick={() => loadQuestion(index - 1)}
              disabled={loading}
              style={{
                padding: '12px 20px', borderRadius: 10, fontSize: 14,
                background: 'var(--bg3)', color: 'var(--text2)',
                border: '1px solid var(--border)',
              }}>← Önceki</button>
          )}
          {feedback && (
            <button onClick={handleNext} style={{
              flex: 1, padding: '13px', borderRadius: 10, fontSize: 15,
              background: 'var(--accent)', color: '#0a0a0a',
              fontWeight: 800, fontFamily: 'var(--font-display)',
            }}>
              {index + 1 >= total ? '🏁 Sonuçları Gör' : 'Sonraki Soru →'}
            </button>
          )}
          {!feedback && index < total - 1 && (
            <button onClick={() => loadQuestion(index + 1)} disabled={loading}
              style={{
                marginLeft: 'auto', padding: '12px 20px', borderRadius: 10, fontSize: 14,
                background: 'var(--bg3)', color: 'var(--text2)',
                border: '1px solid var(--border)',
              }}>Atla →</button>
          )}
        </div>

        {/* ── Question mini-map ── */}
        {total > 1 && (
          <div style={{ marginTop: 24, padding: '16px 20px', borderRadius: 10, background: 'var(--bg2)', border: '1px solid var(--border)' }}>
            <p style={{ fontSize: 11, color: 'var(--text3)', marginBottom: 10, fontWeight: 600 }}>SORU HARİTASI</p>
            <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap' }}>
              {Array.from({ length: total }, (_, i) => {
                const isActive  = i === index
                const answered  = answeredMap[i]
                let bg = 'var(--bg3)'
                let border = 'var(--border)'
                if (isActive) { bg = 'var(--accent)'; border = 'var(--accent)' }
                else if (answered) { bg = 'rgba(74,222,128,0.2)'; border = 'rgba(74,222,128,0.5)' }

                return (
                  <button key={i} onClick={() => { if (!loading) loadQuestion(i) }}
                    style={{
                      width: 30, height: 30, borderRadius: 6, fontSize: 11,
                      fontWeight: 700, fontFamily: 'var(--font-display)',
                      background: bg, border: `1.5px solid ${border}`,
                      color: isActive ? '#0a0a0a' : answered ? '#4ade80' : 'var(--text3)',
                      cursor: loading ? 'default' : 'pointer',
                    }}>{i + 1}</button>
                )
              })}
            </div>
          </div>
        )}
      </div>
    </main>
  )
}
