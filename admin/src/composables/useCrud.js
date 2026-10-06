import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'

/**
 * useCrud — generic composable for CRUD operations on a resource endpoint.
 * @param {string} endpoint  e.g. '/api/admin/teachers'
 */
export function useCrud(endpoint) {
  const auth  = useAuthStore()
  const toast = useToastStore()

  const items   = ref([])
  const loading = ref(false)
  const saving  = ref(false)
  const deleting = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const json = await auth.apiFetch(endpoint)
      items.value = json.data ?? []
    } catch (e) {
      toast.error(e.message)
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    saving.value = true
    try {
      const json = await auth.apiFetch(endpoint, { method: 'POST', body: JSON.stringify(data) })
      items.value.push(json.data)
      toast.success('Created successfully.')
      return json.data
    } catch (e) {
      toast.error(e.message)
      throw e
    } finally {
      saving.value = false
    }
  }

  async function update(id, data) {
    saving.value = true
    try {
      const json = await auth.apiFetch(`${endpoint}/${id}`, { method: 'PUT', body: JSON.stringify(data) })
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = json.data
      toast.success('Updated successfully.')
      return json.data
    } catch (e) {
      toast.error(e.message)
      throw e
    } finally {
      saving.value = false
    }
  }

  async function remove(id) {
    deleting.value = true
    try {
      await auth.apiFetch(`${endpoint}/${id}`, { method: 'DELETE' })
      items.value = items.value.filter(i => i.id !== id)
      toast.success('Deleted successfully.')
    } catch (e) {
      toast.error(e.message)
    } finally {
      deleting.value = false
    }
  }

  return { items, loading, saving, deleting, fetchAll, create, update, remove }
}
