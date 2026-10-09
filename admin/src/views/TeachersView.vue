<script setup>
import { onMounted, ref, reactive, watch } from 'vue'
import { useCrud } from '@/composables/useCrud'
import { useAuthStore } from '@/stores/authStore'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2, ImagePlus, Link2, Upload } from 'lucide-vue-next'

const auth = useAuthStore()
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/teachers')
onMounted(fetchAll)

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const uploading = ref(false)
const photoMode = ref('upload') // 'upload' | 'url'
const photoFile = ref(null)
const photoPreview = ref('')
const photoFileInput = ref(null)
const form  = reactive({ id:null, name:'', role:'', bio:'', initials:'', email:'', photo:'', is_active:1, sort_order:0 })

function resolvePhoto(path) {
  if (!path) return ''
  const s = String(path).trim()
  if (!s) return ''
  return /^https?:\/\//i.test(s) ? s : `${API}${s.startsWith('/') ? s : `/${s}`}`
}

function emptyForm() {
  return { id:null, name:'', role:'', bio:'', initials:'', email:'', photo:'', is_active:1, sort_order:0 }
}
function resetPhoto() { photoMode.value = 'upload'; photoFile.value = null; photoPreview.value = ''; if (photoFileInput.value) photoFileInput.value.value = '' }

// Auto-generate initials from name
watch(() => form.name, (v) => {
  if (!isEdit.value || !form.initials) {
    form.initials = v.split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()||'').join('')
  }
})

function openCreate() { Object.assign(form, emptyForm()); resetPhoto(); isEdit.value=false; modal.value=true }
function openEdit(r)  {
  Object.assign(form, emptyForm(), r)
  form.photo = form.photo ?? ''
  photoFile.value = null
  if (photoFileInput.value) photoFileInput.value.value = ''
  const p = String(form.photo || '').trim()
  photoPreview.value = resolvePhoto(p)
  photoMode.value = /^https?:\/\//i.test(p) ? 'url' : 'upload'
  isEdit.value=true; modal.value=true
}
function onPhotoFile(e) {
  const f = e.target.files?.[0]
  if (!f) return
  if (!f.type.startsWith('image/')) { alert('Please choose an image file.'); return }
  if (f.size > 5 * 1024 * 1024) { alert('Image too large. Max 5 MB.'); return }
  photoFile.value = f
  photoMode.value = 'upload'
  if (photoPreview.value.startsWith('blob:')) URL.revokeObjectURL(photoPreview.value)
  photoPreview.value = URL.createObjectURL(f)
}
function clearPhoto() {
  photoFile.value = null
  photoPreview.value = ''
  form.photo = ''
  if (photoFileInput.value) photoFileInput.value.value = ''
}
async function uploadPhotoFile() {
  const fd = new FormData()
  fd.append('image', photoFile.value)
  fd.append('folder', 'teachers')
  const json = await auth.apiFetch('/api/admin/uploads', { method: 'POST', body: fd })
  return json.data?.url ?? null
}
async function submit() {
  const {id,...d}=form
  try {
    if (photoMode.value === 'upload') {
      if (photoFile.value) {
        uploading.value = true
        const url = await uploadPhotoFile()
        uploading.value = false
        if (!url) return
        d.photo = url
      } else {
        d.photo = (d.photo || '').trim() || null
      }
    } else {
      d.photo = (d.photo || '').trim() || null
    }
    if(isEdit.value) await update(id,d); else await create(d)
    modal.value=false
  } catch {
    uploading.value = false
    // useCrud / apiFetch already shows a toast — keep modal open so input isn't lost
  }
}
async function confirmDelete() { await remove(confirmId.value); confirmId.value=null }
</script>

<template>
  <div>
    <PageHeader title="Teachers" subtitle="Manage faculty profiles" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} teachers</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Teacher</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Teacher</th><th>Role</th><th>Email</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td>
                <div class="flex items-center gap-3">
                  <img v-if="row.photo" :src="resolvePhoto(row.photo)" alt="" class="w-9 h-9 rounded-lg object-cover border border-white/10 shrink-0" loading="lazy" />
                  <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-gray-950 font-bold text-xs font-serif shrink-0">{{ row.initials }}</div>
                  <span class="font-medium text-white">{{ row.name }}</span>
                </div>
              </td>
              <td class="text-gray-400 text-xs">{{ row.role }}</td>
              <td class="text-xs text-gray-500">{{ row.email }}</td>
              <td><span class="badge" :class="row.is_active ? 'badge-green' : 'badge-gray'">{{ row.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td class="text-right"><div class="flex items-center justify-end gap-1">
                <button class="btn-icon" @click="openEdit(row)"><Pencil class="w-4 h-4"/></button>
                <button class="btn-icon hover:text-red-400" @click="confirmId=row.id"><Trash2 class="w-4 h-4"/></button>
              </div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="fade">
      <div v-if="modal" class="modal-overlay" @click.self="modal=false">
        <transition name="scale">
          <div v-if="modal" class="modal-box">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Teacher</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div class="grid grid-cols-3 gap-4">
                  <div class="col-span-2"><label class="form-label">Full Name</label><input v-model="form.name" required class="form-input" placeholder="Elena Rossi"/></div>
                  <div><label class="form-label">Initials</label><input v-model="form.initials" required maxlength="4" class="form-input" placeholder="ER"/></div>
                </div>
                <div><label class="form-label">Role / Instrument</label><input v-model="form.role" required class="form-input" placeholder="Piano · Department Head"/></div>
                <div><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-input" placeholder="teacher@resonanz.org"/></div>
                <div><label class="form-label">Bio</label><textarea v-model="form.bio" required rows="3" class="form-input resize-none" placeholder="Short biography…"></textarea></div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
                  <label class="form-label">Photo (optional)</label>
                  <div class="flex gap-2">
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': photoMode === 'upload' }" @click="photoMode = 'upload'"><Upload class="w-4 h-4"/>Upload</button>
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': photoMode === 'url' }" @click="photoMode = 'url'"><Link2 class="w-4 h-4"/>Image URL</button>
                  </div>
                  <div v-if="photoMode === 'upload'">
                    <input ref="photoFileInput" type="file" accept="image/*" class="form-input" @change="onPhotoFile" />
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP or GIF — max 5 MB. Square photos look best.</p>
                  </div>
                  <div v-else>
                    <input v-model="form.photo" type="url" placeholder="https://example.com/teacher.jpg" class="form-input" @input="photoPreview = resolvePhoto(form.photo)" />
                  </div>
                  <div v-if="photoPreview" class="relative">
                    <img :src="photoPreview" alt="Photo preview" class="w-24 h-24 rounded-full object-cover border border-white/10 mx-auto" />
                    <button type="button" class="btn-icon absolute top-0 right-1/3 !bg-black/60" @click="clearPhoto"><Trash2 class="w-4 h-4"/></button>
                  </div>
                  <div v-else class="flex items-center gap-2 text-xs text-gray-500"><ImagePlus class="w-4 h-4"/>No photo — guest page shows initials.</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="form-label">Sort Order</label><input v-model.number="form.sort_order" type="number" class="form-input"/></div>
                  <div><label class="form-label">Status</label><select v-model.number="form.is_active" class="form-select"><option :value="1">Active</option><option :value="0">Hidden</option></select></div>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Teacher" message="This will permanently remove the teacher profile."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
