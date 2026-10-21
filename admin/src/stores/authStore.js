import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const MAX_TIMEOUT = 2_147_483_647

function getTokenExpiry(token) {
  try {
    const payload = token.split('.')[1]
    const base64 = payload.replace(/-/g, '+').replace(/_/g, '/')
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
    const bytes = Uint8Array.from(atob(padded), character => character.charCodeAt(0))
    const { exp } = JSON.parse(new TextDecoder().decode(bytes))
    return Number.isFinite(exp) ? exp * 1000 : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('rz_admin_token') ?? null)
  const user  = ref(JSON.parse(localStorage.getItem('rz_admin_user') ?? 'null'))
  const tokenExpiresAt = ref(token.value ? getTokenExpiry(token.value) : null)
  let expiryTimer

  function persist() {
    token.value ? localStorage.setItem('rz_admin_token', token.value) : localStorage.removeItem('rz_admin_token')
    user.value  ? localStorage.setItem('rz_admin_user', JSON.stringify(user.value)) : localStorage.removeItem('rz_admin_user')
  }

  function clearSession() {
    clearTimeout(expiryTimer)
    token.value = null
    user.value = null
    tokenExpiresAt.value = null
    persist()
  }

  async function expireSession() {
    clearSession()
    const { default: router } = await import('@/router')
    if (router.currentRoute.value.name !== 'login') {
      await router.replace({ name: 'login' })
    }
  }

  function scheduleSessionExpiry() {
    clearTimeout(expiryTimer)
    if (!token.value) return

    const expiresAt = tokenExpiresAt.value
    if (!expiresAt || expiresAt <= Date.now()) {
      void expireSession().catch(error => console.error('Failed to redirect after session expiry.', error))
      return
    }

    expiryTimer = setTimeout(() => {
      if (Date.now() >= expiresAt) {
        void expireSession().catch(error => console.error('Failed to redirect after session expiry.', error))
      } else {
        scheduleSessionExpiry()
      }
    }, Math.min(expiresAt - Date.now(), MAX_TIMEOUT))
  }

  const isAuthenticated = computed(() =>
    !!token.value && !!user.value && !!tokenExpiresAt.value && tokenExpiresAt.value > Date.now()
  )
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const userInitials    = computed(() =>
    user.value?.name?.split(' ').slice(0,2).map(w => w[0]?.toUpperCase()).join('') ?? '?'
  )

  scheduleSessionExpiry()

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
    if (res.status === 401 && token.value) {
      await expireSession()
    }
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
    tokenExpiresAt.value = getTokenExpiry(token.value)
    persist()
    scheduleSessionExpiry()
    return json
  }

  async function logout() {
    try { await apiFetch('/api/auth/logout', { method: 'POST' }) } catch {}
    clearSession()
  }

  return { token, user, isAuthenticated, isAdmin, userInitials, login, logout, apiFetch }
})
