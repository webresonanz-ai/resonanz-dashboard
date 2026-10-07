<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import { useAuthStore } from '@/stores/authStore'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2, ImagePlus, Link2, Upload } from 'lucide-vue-next'

const auth = useAuthStore()
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/facilities')
onMounted(fetchAll)

const ICONS = ['Music','Piano','Mic2','Drum','Guitar','Speaker','Snowflake','Lightbulb','Building2','Star']
const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const uploading = ref(false)
const imageMode = ref('upload') // 'upload' | 'url'
const imageFile = ref(null)
const imagePreview = ref('')
const imageFileInput = ref(null)
const form  = reactive({ id:null, name:'', capacity:'', description:'', icon:'Music', image:'', is_active:1, sort_order:0 })

function resolveImage(path) {
  if (!path) return ''
  const s = String(path).trim()
  if (!s) return ''
  return /^https?:\/\//i.test(s) ? s : `${API}${s.startsWith('/') ? s : `/${s}`}`
}

function emptyForm() {
  return { id:null, name:'', capacity:'', description:'', icon:'Music', image:'', is_active:1, sort_order:0 }
}
function resetImage() { imageMode.value = 'upload'; imageFile.value = null; imagePreview.value = ''; if (imageFileInput.value) imageFileInput.value.value = '' }
function openCreate() { Object.assign(form, emptyForm()); resetImage(); isEdit.value=false; modal.value=true }
function openEdit(r)  {
  Object.assign(form, emptyForm(), r)
  form.image = form.image ?? ''
  imageFile.value = null
  if (imageFileInput.value) imageFileInput.value.value = ''
  const img = String(form.image || '').trim()
  imagePreview.value = resolveImage(img)
  imageMode.value = /^https?:\/\//i.test(img) ? 'url' : 'upload'
  isEdit.value=true; modal.value=true
}
function onImageFile(e) {
  const f = e.target.files?.[0]
  if (!f) return
  if (!f.type.startsWith('image/')) { alert('Please choose an image file.'); return }
  if (f.size > 5 * 1024 * 1024) { alert('Image too large. Max 5 MB.'); return }
  imageFile.value = f
  imageMode.value = 'upload'
  if (imagePreview.value.startsWith('blob:')) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = URL.createObjectURL(f)
}
function clearImage() {
  imageFile.value = null
  imagePreview.value = ''
  form.image = ''
  if (imageFileInput.value) imageFileInput.value.value = ''
}
async function uploadImageFile() {
  const fd = new FormData()
  fd.append('image', imageFile.value)
  fd.append('folder', 'facilities')
  const json = await auth.apiFetch('/api/admin/uploads', { method: 'POST', body: fd })
  return json.data?.url ?? null
}
async function submit() {
  const {id,...d}=form
  try {
    if (imageMode.value === 'upload') {
      if (imageFile.value) {
        uploading.value = true
        const url = await uploadImageFile()
        uploading.value = false
        if (!url) return
        d.image = url
      } else {
        d.image = (d.image || '').trim() || null
      }
    } else {
      d.image = (d.image || '').trim() || null
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
    <PageHeader title="Facilities" subtitle="Manage rooms and spaces" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} facilities</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Facility</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Image</th><th>Name</th><th>Capacity</th><th>Icon</th><th>Sort</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td>
                <img v-if="row.image" :src="resolveImage(row.image)" alt="" class="w-16 h-10 rounded-lg object-cover border border-white/10" loading="lazy" />
                <span v-else class="flex w-16 h-10 rounded-lg bg-white/5 border border-white/10 items-center justify-center text-gray-600"><ImagePlus class="w-4 h-4"/></span>
              </td>
              <td><p class="font-medium text-white">{{ row.name }}</p><p class="text-xs text-gray-500 max-w-[200px] truncate mt-0.5">{{ row.description }}</p></td>
              <td><span class="badge badge-gold">{{ row.capacity }}</span></td>
              <td class="text-gray-400 font-mono text-xs">{{ row.icon }}</td>
              <td>{{ row.sort_order }}</td>
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
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Facility</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div><label class="form-label">Name</label><input v-model="form.name" required class="form-input" placeholder="Grand Concert Hall"/></div>
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="form-label">Capacity</label><input v-model="form.capacity" required class="form-input" placeholder="500 seats"/></div>
                  <div>
                    <label class="form-label">Icon</label>
                    <select v-model="form.icon" class="form-select"><option v-for="i in ICONS" :key="i" :value="i">{{ i }}</option></select>
                  </div>
                </div>
                <div><label class="form-label">Description</label><textarea v-model="form.description" required rows="3" class="form-input resize-none" placeholder="Describe this facility…"></textarea></div>
                <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
                  <label class="form-label">Preview image (optional)</label>
                  <div class="flex gap-2">
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': imageMode === 'upload' }" @click="imageMode = 'upload'"><Upload class="w-4 h-4"/>Upload</button>
                    <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': imageMode === 'url' }" @click="imageMode = 'url'"><Link2 class="w-4 h-4"/>Image URL</button>
                  </div>
                  <div v-if="imageMode === 'upload'">
                    <input ref="imageFileInput" type="file" accept="image/*" class="form-input" @change="onImageFile" />
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP or GIF — max 5 MB.</p>
                  </div>
                  <div v-else>
                    <input v-model="form.image" type="url" placeholder="https://example.com/facility.jpg" class="form-input" @input="imagePreview = resolveImage(form.image)" />
                  </div>
                  <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" alt="Preview" class="w-full h-36 rounded-xl object-cover border border-white/10" />
                    <button type="button" class="btn-icon absolute top-2 right-2 !bg-black/60" @click="clearImage"><Trash2 class="w-4 h-4"/></button>
                  </div>
                  <div v-else class="flex items-center gap-2 text-xs text-gray-500"><ImagePlus class="w-4 h-4"/>No image — guest page shows the icon.</div>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Facility" message="This will permanently remove the facility."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
