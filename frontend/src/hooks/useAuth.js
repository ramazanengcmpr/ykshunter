import { create } from 'zustand'
import { api } from '../api'

export const useAuth = create((set) => ({
  user: null,
  loading: true,

  init: async () => {
    const token = localStorage.getItem('yks_token')
    if (!token) { set({ loading: false }); return }
    try {
      const { user } = await api.me()
      set({ user, loading: false })
    } catch {
      localStorage.removeItem('yks_token')
      set({ loading: false })
    }
  },

  login: async (email, password) => {
    const { user, token } = await api.login({ email, password })
    localStorage.setItem('yks_token', token)
    set({ user })
    return user
  },

  register: async (name, email, password) => {
    const { user, token } = await api.register({ name, email, password })
    localStorage.setItem('yks_token', token)
    set({ user })
    return user
  },

  logout: () => {
    localStorage.removeItem('yks_token')
    set({ user: null })
  },
}))
