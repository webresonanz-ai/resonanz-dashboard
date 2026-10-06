import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('rz_admin_token') ?? null)
  const user  = ref(JSON.parse(localStorage.getItem('rz_admin_user') ?? 'null'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const userInitials    = computed(() =>
    user.value?.name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'
  )

  function persist() {
    token.value ? localStorage.setItem('rz_admin_token', token.value) : localStorage.removeItem('rz_admin_token')
    user.value  ? localStorage.setItem('rz_admin_user', JSON.stringify(user.value)) : localStorage.removeItem('rz_admin_user')
  }

  async function apiFetch(path, options = {}) {
    const isForm = options.body instanceof FormData
    const res = await fetch(`${API}${path}`, {
      ...options,
      headers: {
        ...(isForm ? {} : { 'Content-Type': 'application/json' }),
        ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
        ...(options.headers ?? {}),
      },
    })
    const json = await res.json().catch(() => ({ success: false, message: 'Server error' }))
    if (!res.ok) {
      const err = new Error(json.message ?? 'Request failed')
      err.status = res.status; err.errors = json.errors ?? null
      throw err
    }
    return json
  }

  async function login(email, password) {
    const json = await apiFetch('/api/auth/login', {
      method: 'POST', body: JSON.stringify({ email, password }),
    })
    if (json.data?.user?.role !== 'admin') {
      throw new Error('Access denied. Admin account required.')
    }
    token.value = json.data.token
    user.value  = json.data.user
    persist()
    return json
  }

  async function logout() {
    try { await apiFetch('/api/auth/logout', { method: 'POST' }) } catch {}
    token.value = null; user.value = null; persist()
  }

  return { token, user, isAuthenticated, isAdmin, userInitials, login, logout, apiFetch }
})
