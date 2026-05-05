import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useEffect } from 'react'
import { Toaster } from 'react-hot-toast'
import { useAuth } from './hooks/useAuth'
import Navbar from './components/Navbar'
import Home from './pages/Home'
import Quizzes from './pages/Quizzes'
import QuizPlay from './pages/QuizPlay'
import Results from './pages/Results'
import ResultDetail from './pages/ResultDetail'
import Login from './pages/Login'
import Register from './pages/Register'

export default function App() {
  const { init, loading } = useAuth()
  useEffect(() => { init() }, [])

  if (loading) return (
    <div style={{ display:'flex', alignItems:'center', justifyContent:'center', height:'100vh' }}>
      <div className="spinner" />
    </div>
  )

  return (
    <BrowserRouter>
      <Toaster
        position="top-right"
        toastOptions={{
          style: { background: '#1a1a1a', color: '#f0f0f0', border: '1px solid #2e2e2e', fontFamily: 'DM Sans, sans-serif' },
          success: { iconTheme: { primary: '#e8ff47', secondary: '#0a0a0a' } },
        }}
      />
      <Navbar />
      <Routes>
        <Route path="/"             element={<Home />} />
        <Route path="/quizzes"      element={<Quizzes />} />
        <Route path="/quiz/:id"     element={<QuizPlay />} />
        <Route path="/results"      element={<Results />} />
        <Route path="/results/:id"  element={<ResultDetail />} />
        <Route path="/login"        element={<Login />} />
        <Route path="/register"     element={<Register />} />
        <Route path="*"             element={<Navigate to="/" />} />
      </Routes>
    </BrowserRouter>
  )
}
