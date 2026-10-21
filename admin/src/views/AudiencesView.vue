<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Loader2, RefreshCw, Search, Users } from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToastStore()
const audiences = ref([])
const loading = ref(false)
const search = ref('')
const selectedEvent = ref('')

const events = computed(() => {
  const uniqueEvents = new Map()
  for (const audience of audiences.value) {
    if (!uniqueEvents.has(String(audience.event_id))) {
      uniqueEvents.set(String(audience.event_id), {
        id: String(audience.event_id),
        title: audience.event_title,
      })
    }
  }
  return [...uniqueEvents.values()]
})

const filteredAudiences = computed(() => {
  const query = search.value.trim().toLowerCase()
  return audiences.value.filter(audience => {
    const matchesEvent = !selectedEvent.value || String(audience.event_id) === selectedEvent.value
    const matchesSearch = !query || [
      audience.name,
      audience.email,
      audience.phone,
      audience.event_title,
      audience.registration_code,
    ].some(value => String(value ?? '').toLowerCase().includes(query))
    return matchesEvent && matchesSearch
  })
})

async function loadAudiences() {
  loading.value = true
  try {
    const json = await auth.apiFetch('/api/admin/audiences')
    audiences.value = json.data ?? []
  } catch (error) {
    toast.error(error.message ?? 'Could not load audiences.')
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(`${String(value).replace(' ', 'T')}`).toLocaleString()
}

onMounted(loadAudiences)
</script>

<template>
  <div>
    <PageHeader title="Audiences" subtitle="View guests registered for each concert" />

    <div class="grid grid-cols-2 gap-3 mb-6">
      <div class="admin-card px-4 py-3 flex items-center gap-3">
        <Users class="w-5 h-5 text-gold-400 shrink-0" />
        <div>
          <p class="text-xl font-bold text-white">{{ audiences.length }}</p>
          <p class="text-xs text-gray-500">Total Audiences</p>
        </div>
      </div>
      <div class="admin-card px-4 py-3 flex items-center gap-3">
        <div class="w-2 h-2 rounded-full bg-gold-400 shrink-0"></div>
        <div>
          <p class="text-xl font-bold text-white">{{ events.length }}</p>
          <p class="text-xs text-gray-500">Concerts With Registrations</p>
        </div>
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ filteredAudiences.length }} audiences</p>
        <div class="flex flex-col sm:flex-row gap-2">
          <label class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
            <input
              v-model="search"
              type="search"
              placeholder="Search audiences..."
              class="form-input pl-9"
              aria-label="Search audiences"
            />
          </label>
          <select v-model="selectedEvent" class="form-input sm:max-w-56" aria-label="Filter by concert">
            <option value="">All concerts</option>
            <option v-for="event in events" :key="event.id" :value="event.id">{{ event.title }}</option>
          </select>
          <button class="btn-secondary" :disabled="loading" @click="loadAudiences">
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            Refresh
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16">
          <Loader2 class="w-6 h-6 text-gold-400 animate-spin" />
        </div>
        <EmptyState v-else-if="!filteredAudiences.length" :message="audiences.length ? 'No audiences match your filters.' : 'No concert registrations yet.'" />
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Concert</th>
              <th>Audience</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Registration Code</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="audience in filteredAudiences" :key="audience.id">
              <td>
                <p class="font-medium text-white">{{ audience.event_title }}</p>
                <p class="text-xs text-gray-500">{{ audience.event_date }} · {{ audience.venue }}</p>
              </td>
              <td class="font-medium text-gray-200">{{ audience.name }}</td>
              <td class="text-xs text-gray-400">{{ audience.email }}</td>
              <td class="text-xs text-gray-400">{{ audience.phone }}</td>
              <td class="font-mono text-xs text-gold-300">{{ audience.registration_code || '—' }}</td>
              <td class="text-xs text-gray-500">{{ formatDate(audience.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
