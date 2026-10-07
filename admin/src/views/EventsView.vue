<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import { useAuthStore } from '@/stores/authStore'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2, ExternalLink, ImagePlus, Link2, Upload, Users } from 'lucide-vue-next'

const auth = useAuthStore()
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/events')
onMounted(fetchAll)

const regsModal = ref(false)
const regsLoading = ref(false)
const regsEvent = ref(null)
const regs = ref([])

async function openRegistrations(row) {
  regsEvent.value = row
  regs.value = []
  regsModal.value = true
  regsLoading.value = true
  try {
    const json = await auth.apiFetch(`/api/admin/events/${row.id}/registrations`)
    regs.value = json.data?.registrations ?? []
  } catch {
    // toast handled in apiFetch caller? show empty
  } finally {
    regsLoading.value = false
  }
}

const EVENT_TYPES = ['Concert', 'Workshop', 'Masterclass']

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const uploading = ref(false)
const coverMode = ref('upload') // 'upload' | 'url'
const coverFile = ref(null)
const coverPreview = ref('')
const coverFileInput = ref(null)
const form  = reactive({ id:null, title:'', event_date:'', event_time:'19:00', venue:'', type:'Concert', event_code:'', max_capacity:'', use_registration_url:0, registration_url:'', cover_image:'', tag:'', is_active:1 })

function resolveCover(path) {
  if (!path) return ''
  const s = String(path).trim()
  if (!s) return ''
  return /^https?:\/\//i.test(s) ? s : `${API}${s.startsWith('/') ? s : `/${s}`}`
}

function emptyForm() {
  return { id:null, title:'', event_date:'', event_time:'19:00', venue:'', type:'Concert', event_code:'', max_capacity:'', use_registration_url:0, registration_url:'', cover_image:'', tag:'', is_active:1 }
}
function resetCover() { coverMode.value = 'upload'; coverFile.value = null; coverPreview.value = ''; if (coverFileInput.value) coverFileInput.value.value = '' }
function openCreate() { Object.assign(form, emptyForm()); resetCover(); isEdit.value=false; modal.value=true }
function openEdit(r)  {
  Object.assign(form, emptyForm(), r)
  // normalize legacy / API shapes (support old external_* aliases)
  if (!EVENT_TYPES.includes(form.type)) form.type = 'Concert'
  const _useUrl = form.use_registration_url ?? r.use_external_url ?? 0
  form.use_registration_url = _useUrl ? 1 : 0
  form.registration_url = form.registration_url ?? r.external_url ?? ''
  form.event_code = (form.event_code ?? '').toString().toUpperCase()
  form.max_capacity = form.max_capacity ?? ''
  form.cover_image = form.cover_image ?? ''
  form.is_active = form.is_active ? 1 : 0
  // cover preview state
  coverFile.value = null
  if (coverFileInput.value) coverFileInput.value.value = ''
  const c = String(form.cover_image || '').trim()
  coverPreview.value = resolveCover(c)
  coverMode.value = /^https?:\/\//i.test(c) ? 'url' : 'upload'
  isEdit.value=true; modal.value=true
}
function onTypeChange() {
  // Clear URL fields when not a Concert
  if (form.type !== 'Concert') { form.use_registration_url = 0; form.registration_url = ''; form.event_code = ''; form.max_capacity = '' }
}
function onEventCodeInput() {
  form.event_code = (form.event_code ?? '').toString().toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20)
}
function onCoverFile(e) {
  const f = e.target.files?.[0]
  if (!f) return
  if (!f.type.startsWith('image/')) { alert('Please choose an image file.'); return }
  if (f.size > 5 * 1024 * 1024) { alert('Image too large. Max 5 MB.'); return }
  coverFile.value = f
  coverMode.value = 'upload'
  if (coverPreview.value.startsWith('blob:')) URL.revokeObjectURL(coverPreview.value)
  coverPreview.value = URL.createObjectURL(f)
}
function clearCover() {
  coverFile.value = null
  coverPreview.value = ''
  form.cover_image = ''
  if (coverFileInput.value) coverFileInput.value.value = ''
}
async function uploadCoverFile() {
  const fd = new FormData()
  fd.append('image', coverFile.value)
  const json = await auth.apiFetch('/api/admin/uploads', { method: 'POST', body: fd })
  return json.data?.url ?? null
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
  // internal registration fields: only Concert without external URL
  const isInternal = d.type === 'Concert' && !d.use_registration_url
  if (isInternal) {
    d.event_code = (d.event_code || '').toString().toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20) || null
    const cap = parseInt(d.max_capacity, 10)
    d.max_capacity = Number.isFinite(cap) && cap > 0 ? cap : null
  } else {
    d.event_code = null
    d.max_capacity = null
  }
  // cover image: upload first when a new file was picked
  try {
    if (coverMode.value === 'upload') {
      if (coverFile.value) {
        uploading.value = true
        const url = await uploadCoverFile()
        uploading.value = false
        if (!url) return
        d.cover_image = url
      } else {
        d.cover_image = (d.cover_image || '').trim() || null
      }
    } else {
      d.cover_image = (d.cover_image || '').trim() || null
    }
    if (isEdit.value) await update(id, d); else await create(d)
    modal.value = false
  } catch {
    uploading.value = false
    // useCrud / apiFetch already shows a toast — keep modal open so input isn't lost
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
          <thead><tr><th>Cover</th><th>Title</th><th>Date</th><th>Time</th><th>Venue</th><th>Type</th><th>Code / Capacity</th><th>Tag</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td>
                <img v-if="row.cover_image" :src="resolveCover(row.cover_image)" alt="" class="w-16 h-10 rounded-lg object-cover border border-white/10" loading="lazy" />
                <span v-else class="flex w-16 h-10 rounded-lg bg-white/5 border border-white/10 items-center justify-center text-gray-600"><ImagePlus class="w-4 h-4"/></span>
              </td>
              <td class="font-medium text-white max-w-[180px] truncate">{{ row.title }}</td>
              <td>{{ row.event_date }}</td>
              <td>{{ row.event_time?.slice(0,5) }}</td>
              <td class="max-w-[140px] truncate">{{ row.venue }}</td>
              <td>
                <span class="badge badge-gold">{{ row.type || 'Concert' }}</span>
                <ExternalLink v-if="row.type === 'Concert' && (row.use_registration_url == 1 || row.use_registration_url === true) && row.registration_url" class="w-3.5 h-3.5 inline-block ml-1 text-gold-400" />
              </td>
              <td>
                <span v-if="row.type === 'Concert' && !(row.use_registration_url == 1 || row.use_registration_url === true)" class="text-xs text-gray-300">
                  <span class="font-mono font-semibold text-gold-300">{{ row.event_code || '—' }}</span>
                  <span class="text-gray-500"> · </span>{{ row.registered_count ?? 0 }}{{ row.max_capacity ? `/${row.max_capacity}` : '' }}
                </span>
                <span v-else class="text-gray-600 text-xs">—</span>
              </td>
              <td><span v-if="row.tag" class="badge badge-gold">{{ row.tag }}</span></td>
              <td><span class="badge" :class="row.is_active ? 'badge-green' : 'badge-gray'">{{ row.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <button v-if="row.type === 'Concert' && !(row.use_registration_url == 1 || row.use_registration_url === true)" class="btn-icon" title="View registrations" @click="openRegistrations(row)"><Users class="w-4 h-4"/></button>
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
                  <div v-else class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="form-label">Event code</label>
                      <input v-model="form.event_code" class="form-input font-mono uppercase" maxlength="20" placeholder="RSNVCFEST" @input="onEventCodeInput" />
                      <p class="text-xs text-gray-500 mt-1">Used in QR code: CODE_ID_TIMESTAMP_RANDOM.</p>
                    </div>
                    <div>
                      <label class="form-label">Max capacity</label>
                      <input v-model="form.max_capacity" type="number" min="1" step="1" class="form-input" placeholder="e.g. 500" />
                      <p class="text-xs text-gray-500 mt-1">Empty = unlimited seats.</p>
                    </div>
                  </div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
                  <label class="form-label">Cover image (optional)</label>
                  <div class="flex gap-2">
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': coverMode === 'upload' }" @click="coverMode = 'upload'"><Upload class="w-4 h-4"/>Upload</button>
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': coverMode === 'url' }" @click="coverMode = 'url'"><Link2 class="w-4 h-4"/>Image URL</button>
                  </div>
                  <div v-if="coverMode === 'upload'">
                    <input ref="coverFileInput" type="file" accept="image/*" class="form-input" @change="onCoverFile" />
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP or GIF — max 5 MB.</p>
                  </div>
                  <div v-else>
                    <input v-model="form.cover_image" type="url" placeholder="https://example.com/cover.jpg" class="form-input" @input="coverPreview = resolveCover(form.cover_image)" />
                  </div>
                  <div v-if="coverPreview" class="relative">
                    <img :src="coverPreview" alt="Cover preview" class="w-full h-36 rounded-xl object-cover border border-white/10" />
                    <button type="button" class="btn-icon absolute top-2 right-2 !bg-black/60" @click="clearCover"><Trash2 class="w-4 h-4"/></button>
                  </div>
                  <div v-else class="flex items-center gap-2 text-xs text-gray-500"><ImagePlus class="w-4 h-4"/>No cover — guest page shows the default icon.</div>
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
                <button type="submit" class="btn-primary" :disabled="saving || uploading"><Loader2 v-if="saving || uploading" class="w-4 h-4 animate-spin"/>{{ uploading ? 'Uploading…' : saving ? 'Saving…' : 'Save' }}</button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>
    <ConfirmDialog :open="!!confirmId" title="Delete Event" message="This will permanently remove the event."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />

    <transition name="fade">
      <div v-if="regsModal" class="modal-overlay" @click.self="regsModal=false">
        <div class="modal-box">
          <div class="modal-header">
            <h3 class="font-semibold text-white">Registrations — {{ regsEvent?.title }}</h3>
            <button class="btn-icon" @click="regsModal=false"><X class="w-4 h-4"/></button>
          </div>
          <div class="modal-body">
            <p class="text-sm text-gray-400 mb-3">
              Code <span class="font-mono text-gold-300">{{ regsEvent?.event_code || '—' }}</span>
              · {{ regs.length }}{{ regsEvent?.max_capacity ? `/${regsEvent.max_capacity}` : '' }} registered
            </p>
            <div v-if="regsLoading" class="flex items-center justify-center py-10"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
            <p v-else-if="!regs.length" class="text-sm text-gray-500 py-6 text-center">No registrations yet.</p>
            <div v-else class="overflow-x-auto">
              <table class="data-table">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>QR code</th></tr></thead>
                <tbody>
                  <tr v-for="g in regs" :key="g.id">
                    <td>{{ g.id }}</td>
                    <td class="font-medium text-white">{{ g.name }}</td>
                    <td>{{ g.email }}</td>
                    <td>{{ g.phone }}</td>
                    <td class="font-mono text-xs text-gold-300">{{ g.registration_code }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="regsModal=false">Close</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>
