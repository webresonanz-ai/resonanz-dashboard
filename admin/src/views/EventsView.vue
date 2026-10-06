<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2, ExternalLink } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/events')
onMounted(fetchAll)

const EVENT_TYPES = ['Concert', 'Workshop', 'Masterclass']

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const form  = reactive({ id:null, title:'', event_date:'', event_time:'19:00', venue:'', type:'Concert', use_registration_url:0, registration_url:'', tag:'', is_active:1 })

function emptyForm() {
  return { id:null, title:'', event_date:'', event_time:'19:00', venue:'', type:'Concert', use_registration_url:0, registration_url:'', tag:'', is_active:1 }
}
function openCreate() { Object.assign(form, emptyForm()); isEdit.value=false; modal.value=true }
function openEdit(r)  {
  Object.assign(form, emptyForm(), r)
  // normalize legacy / API shapes (support old external_* aliases)
  if (!EVENT_TYPES.includes(form.type)) form.type = 'Concert'
  const _useUrl = form.use_registration_url ?? r.use_external_url ?? 0
  form.use_registration_url = _useUrl ? 1 : 0
  form.registration_url = form.registration_url ?? r.external_url ?? ''
  form.is_active = form.is_active ? 1 : 0
  isEdit.value=true; modal.value=true
}
function onTypeChange() {
  // Clear URL fields when not a Concert
  if (form.type !== 'Concert') { form.use_registration_url = 0; form.registration_url = '' }
}
async function submit() {
  const { id, ...d } = form
  // normalize payload for backend
  d.use_registration_url = d.use_registration_url ? 1 : 0
  if (d.type !== 'Concert' || !d.use_registration_url) {
    if (d.type !== 'Concert') d.use_registration_url = 0
    d.registration_url = null
  } else {
    d.registration_url = (d.registration_url || '').trim() || null
  }
  try {
    if (isEdit.value) await update(id, d); else await create(d)
    modal.value = false
  } catch {
    // useCrud already shows a toast — keep modal open so input isn't lost
  }
}
async function confirmDelete() { await remove(confirmId.value); confirmId.value=null }
</script>

<template>
  <div>
    <PageHeader title="Events" subtitle="Manage upcoming event listings" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} events</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Event</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Venue</th><th>Type</th><th>Tag</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td class="font-medium text-white max-w-[180px] truncate">{{ row.title }}</td>
              <td>{{ row.event_date }}</td>
              <td>{{ row.event_time?.slice(0,5) }}</td>
              <td class="max-w-[140px] truncate">{{ row.venue }}</td>
              <td>
                <span class="badge badge-gold">{{ row.type || 'Concert' }}</span>
                <ExternalLink v-if="row.type === 'Concert' && (row.use_registration_url == 1 || row.use_registration_url === true) && row.registration_url" class="w-3.5 h-3.5 inline-block ml-1 text-gold-400" />
              </td>
              <td><span v-if="row.tag" class="badge badge-gold">{{ row.tag }}</span></td>
              <td><span class="badge" :class="row.is_active ? 'badge-green' : 'badge-gray'">{{ row.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <button class="btn-icon" @click="openEdit(row)"><Pencil class="w-4 h-4"/></button>
                  <button class="btn-icon hover:text-red-400" @click="confirmId=row.id"><Trash2 class="w-4 h-4"/></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="fade">
      <div v-if="modal" class="modal-overlay" @click.self="modal=false">
        <transition name="scale" appear>
          <div class="modal-box">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Event</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div><label class="form-label">Title</label><input v-model="form.title" required class="form-input" placeholder="Winter Symphony Gala"/></div>
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="form-label">Date</label><input v-model="form.event_date" type="date" required class="form-input"/></div>
                  <div><label class="form-label">Time</label><input v-model="form.event_time" type="time" required class="form-input"/></div>
                </div>
                <div><label class="form-label">Venue</label><input v-model="form.venue" required class="form-input" placeholder="Grand Concert Hall"/></div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Type</label>
                    <select v-model="form.type" required class="form-select" @change="onTypeChange">
                      <option v-for="t in EVENT_TYPES" :key="t" :value="t">{{ t }}</option>
                    </select>
                  </div>
                  <div><label class="form-label">Tag (optional)</label><input v-model="form.tag" class="form-input" placeholder="Featured, New…"/></div>
                </div>
                <div v-if="form.type === 'Concert'" class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
                  <label class="flex items-center gap-2.5 text-sm text-gray-300 cursor-pointer select-none">
                    <input v-model="form.use_registration_url" type="checkbox" :true-value="1" :false-value="0" class="w-4 h-4 accent-yellow-500" />
                    Use external URL for registration
                  </label>
                  <div v-if="form.use_registration_url">
                    <label class="form-label">Registration URL</label>
                    <input v-model="form.registration_url" type="url" required placeholder="https://tickets.example.com/event-123" class="form-input" />
                    <p class="text-xs text-gray-500 mt-1">When enabled, “Book Now” redirects visitors to this URL.</p>
                  </div>
                </div>
                <div>
                  <label class="form-label">Status</label>
                  <select v-model.number="form.is_active" class="form-select">
                    <option :value="1">Active (visible)</option>
                    <option :value="0">Hidden</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-secondary" @click="modal=false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="saving"><Loader2 v-if="saving" class="w-4 h-4 animate-spin"/>{{ saving ? 'Saving…' : 'Save' }}</button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>
    <ConfirmDialog :open="!!confirmId" title="Delete Event" message="This will permanently remove the event."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
