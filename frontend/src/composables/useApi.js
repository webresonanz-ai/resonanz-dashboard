import { ref, nextTick } from 'vue'
import { scanAndObserve } from '@/composables/useScrollReveal'

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

/**
 * useApi — lightweight composable for public (no-auth) GET requests.
 *
 * After data loads it calls nextTick → scanAndObserve so any newly
 * rendered .reveal elements are immediately picked up by the
 * IntersectionObserver, regardless of whether MutationObserver fired.
 */
export function useApi(endpoint) {
  const data    = ref(null)
  const loading = ref(false)
  const error   = ref(null)

  async function fetch() {
    loading.value = true
    error.value   = null
    try {
      const res  = await globalThis.fetch(`${API_BASE}${endpoint}`)
      const json = await res.json()
      if (!res.ok) throw new Error(json.message ?? 'Request failed.')
      data.value = json.data ?? json
    } catch (e) {
      error.value = e.message ?? 'Could not load data. Please try again.'
    } finally {
      loading.value = false
      // After Vue re-renders the new list, make sure all .reveal elements
      // that are already in the viewport get .visible immediately.
      nextTick(scanAndObserve)
    }
  }

  return { data, loading, error, fetch }
}

/**
 * apiPost — fire a POST to a public endpoint (e.g. contact form).
 */
export async function apiPost(endpoint, body) {
  const res  = await globalThis.fetch(`${API_BASE}${endpoint}`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body),
  })
  const json = await res.json().catch(() => ({ success: false, message: 'Server error.' }))
  if (!res.ok) {
    const err  = new Error(json.message ?? 'Request failed.')
    err.errors = json.errors ?? null
    err.status = res.status
    throw err
  }
  return json
}
