const BASE = import.meta.env.VITE_API_URL || '/api'

async function request(path, options = {}) {
  const token = localStorage.getItem('yks_token')
  const res = await fetch(`${BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
    ...options,
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.error || 'Sunucu hatası')
  return data
}

export const api = {
  get: (path) => request(path),
  post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body) }),

  // Auth
  register: (d) => request('/auth/register', { method: 'POST', body: JSON.stringify(d) }),
  login: (d) => request('/auth/login', { method: 'POST', body: JSON.stringify(d) }),
  me: () => request('/auth/me'),

  // Quiz
  categories: () => request('/quiz/categories'),
  quizzes: (cat) => request('/quiz/list' + (cat ? `?category=${cat}` : '')),
  startQuiz: (quizId) => request('/quiz/start', { method: 'POST', body: JSON.stringify({ quiz_id: quizId }) }),
  question: (sessionId, index) => request(`/quiz/question/${sessionId}:${index}`),
  submitAnswer: (d) => request('/quiz/submit', { method: 'POST', body: JSON.stringify(d) }),
  finishQuiz: (sessionId) => request('/quiz/finish', { method: 'POST', body: JSON.stringify({ session_id: sessionId }) }),

  // Results
  results: () => request('/results/list'),
  resultDetail: (id) => request(`/results/detail/${id}`),
}
