import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost/resonanz-dashboard/backend/public'

/**
 * authStore — manages authentication state throughout the app.
 *
 * The JWT is persisted in localStorage so the session survives page reloads.
 * Sensitive data (password) never reaches this store.
 */
export const useAuthStore = defineStore('auth', () => {
  // ─── State ────────────────────────────────────────────────────
  const token = ref(localStorage.getItem('resonanz_token') ?? null)
  const user  = ref(JSON.parse(localStorage.getItem('resonanz_user') ?? 'null'))

  // ─── Computed ─────────────────────────────────────────────────
  const isAuthenticated = computed(() => token.value !== null && user.value !== null)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const userName        = computed(() => user.value?.name ?? '')
  const userInitials    = computed(() => {
    if (!user.value?.name) return '?'
    return user.value.name
      .split(' ')
      .slice(0, 2)
      .map((w) => w[0]?.toUpperCase() ?? '')
      .join('')
  })

  // ─── Helpers ──────────────────────────────────────────────────
  function persist() {
    if (token.value) {
      localStorage.setItem('resonanz_token', token.value)
    } else {
      localStorage.removeItem('resonanz_token')
    }
    if (user.value) {
      localStorage.setItem('resonanz_user', JSON.stringify(user.value))
    } else {
      localStorage.removeItem('resonanz_user')
    }
  }

  async function apiFetch(path, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
      ...(options.headers ?? {}),
    }

    const res = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers,
    })

    const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response.' }))

    if (!res.ok) {
      const err = new Error(json.message ?? 'Request failed.')
      err.status = res.status
      err.errors = json.errors ?? null
      throw err
    }

    return json
  }

  // ─── Actions ──────────────────────────────────────────────────
  async function register(name, email, password, passwordConfirmation) {
    const json = await apiFetch('/api/auth/register', {
      method: 'POST',
      body: JSON.stringify({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      }),
    })

    token.value = json.data.token
    user.value  = json.data.user
    persist()

    return json
  }

  async function login(email, password) {
    const json = await apiFetch('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })

    token.value = json.data.token
    user.value  = json.data.user
    persist()

    return json
  }

  async function logout() {
    try {
      await apiFetch('/api/auth/logout', { method: 'POST' })
    } catch {
      // Ignore — we clear state regardless
    } finally {
      token.value = null
      user.value  = null
      persist()
    }
  }

  /** Refresh user data from /api/auth/me (e.g. after page reload). */
  async function fetchMe() {
    if (!token.value) return

    try {
      const json  = await apiFetch('/api/auth/me')
      user.value  = json.data
      persist()
    } catch {
      // Token expired or invalid — clear session
      token.value = null
      user.value  = null
      persist()
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    isAdmin,
    userName,
    userInitials,
    register,
    login,
    logout,
    fetchMe,
  }
})
