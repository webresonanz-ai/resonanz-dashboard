<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import EmptyState from '@/components/EmptyState.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Check, Loader2, Mail, Pencil, RefreshCw, Search, Trash2, Users, X } from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToastStore()
const audiences = ref([])
const loading = ref(false)
const search = ref('')
const selectedEvent = ref('')
const editingAudience = ref(null)
const editForm = ref({ name: '', email: '', phone: '' })
const saving = ref(false)
const deletingAudience = ref(null)
const deleting = ref(false)
const sendingId = ref(null)
const sentIds = ref(new Set())

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

function openEdit(audience) {
  editingAudience.value = audience
  editForm.value = {
    name: audience.name,
    email: audience.email,
    phone: audience.phone,
  }
}

function closeEdit() {
  if (saving.value) return
  editingAudience.value = null
}

async function saveAudience() {
  if (!editingAudience.value || saving.value) return
  saving.value = true
  try {
    const json = await auth.apiFetch(`/api/admin/registrations/${editingAudience.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(editForm.value),
    })
    Object.assign(editingAudience.value, json.data)
    toast.success(json.message ?? 'Audience details updated.')
    editingAudience.value = null
  } catch (error) {
    toast.error(error.message ?? 'Could not update audience.')
  } finally {
    saving.value = false
  }
}

async function deleteAudience() {
  if (!deletingAudience.value || deleting.value) return
  deleting.value = true
  try {
    await auth.apiFetch(`/api/admin/registrations/${deletingAudience.value.id}`, { method: 'DELETE' })
    audiences.value = audiences.value.filter(audience => audience.id !== deletingAudience.value.id)
    toast.success('Audience registration deleted.')
  } catch (error) {
    toast.error(error.message ?? 'Could not delete audience registration.')
  } finally {
    deleting.value = false
    deletingAudience.value = null
  }
}

async function sendTicket(audience) {
  if (sendingId.value || !audience.registration_code) return
  sendingId.value = audience.id
  try {
    const json = await auth.apiFetch(`/api/admin/registrations/${audience.id}/send-ticket`, { method: 'POST' })
    sentIds.value = new Set([...sentIds.value, audience.id])
    toast.success(json.message ?? `Ticket sent to ${audience.email}.`)
  } catch (error) {
    toast.error(error.message ?? 'Could not send ticket email.')
  } finally {
    sendingId.value = null
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
              <th>Check-in</th>
              <th>Registered</th>
              <th class="text-right">Actions</th>
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
              <td>
                <span v-if="audience.checked_in_at" class="badge badge-green">Checked in</span>
                <span v-else class="badge badge-gray">Not checked in</span>
              </td>
              <td class="text-xs text-gray-500">{{ formatDate(audience.created_at) }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    class="btn-icon"
                    :disabled="sendingId !== null || !audience.registration_code"
                    :title="sentIds.has(audience.id) ? 'Ticket sent — send again' : 'Send ticket email'"
                    :aria-label="`Send ticket to ${audience.name}`"
                    @click="sendTicket(audience)"
                  >
                    <Loader2 v-if="sendingId === audience.id" class="w-4 h-4 animate-spin" />
                    <Check v-else-if="sentIds.has(audience.id)" class="w-4 h-4 text-green-400" />
                    <Mail v-else class="w-4 h-4" />
                  </button>
                  <button
                    class="btn-icon"
                    :aria-label="`Edit ${audience.name}`"
                    title="Edit audience"
                    @click="openEdit(audience)"
                  >
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button
                    class="btn-icon hover:text-red-400"
                    :aria-label="`Delete ${audience.name}`"
                    title="Delete audience"
                    @click="deletingAudience = audience"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="fade">
      <div v-if="editingAudience" class="modal-overlay" @click.self="closeEdit">
        <div class="modal-box max-w-lg">
          <div class="modal-header">
            <div>
              <h2 class="text-lg font-semibold text-white">Edit Audience</h2>
              <p class="text-xs text-gray-500 mt-1">{{ editingAudience.event_title }}</p>
            </div>
            <button class="btn-icon" :disabled="saving" aria-label="Close edit dialog" @click="closeEdit">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="saveAudience">
            <div class="modal-body">
              <div>
                <label for="audience-name" class="form-label">Name</label>
                <input id="audience-name" v-model.trim="editForm.name" class="form-input" required minlength="2" maxlength="100" />
              </div>
              <div>
                <label for="audience-email" class="form-label">Email</label>
                <input id="audience-email" v-model.trim="editForm.email" type="email" class="form-input" required maxlength="255" />
              </div>
              <div>
                <label for="audience-phone" class="form-label">Phone</label>
                <input id="audience-phone" v-model.trim="editForm.phone" type="tel" class="form-input" required minlength="5" maxlength="30" />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" :disabled="saving" @click="closeEdit">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                <Check v-else class="w-4 h-4" />
                Save changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
    <ConfirmDialog
      :open="!!deletingAudience"
      title="Delete Audience"
      :message="deletingAudience ? `Delete ${deletingAudience.name}'s registration for ${deletingAudience.event_title}? This cannot be undone.` : ''"
      :loading="deleting"
      @confirm="deleteAudience"
      @cancel="!deleting && (deletingAudience = null)"
    />
  </div>
</template>
