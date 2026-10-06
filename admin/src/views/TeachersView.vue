<script setup>
import { onMounted, ref, reactive, watch } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2 } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/teachers')
onMounted(fetchAll)

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const form  = reactive({ id:null, name:'', role:'', bio:'', initials:'', email:'', is_active:1, sort_order:0 })

// Auto-generate initials from name
watch(() => form.name, (v) => {
  if (!isEdit.value || !form.initials) {
    form.initials = v.split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()||'').join('')
  }
})

function openCreate() { Object.assign(form,{id:null,name:'',role:'',bio:'',initials:'',email:'',is_active:1,sort_order:0}); isEdit.value=false; modal.value=true }
function openEdit(r)  { Object.assign(form,r); isEdit.value=true; modal.value=true }
async function submit() { const {id,...d}=form; if(isEdit.value) await update(id,d); else await create(d); modal.value=false }
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
                  <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-gray-950 font-bold text-xs font-serif shrink-0">{{ row.initials }}</div>
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
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="form-label">Sort Order</label><input v-model.number="form.sort_order" type="number" class="form-input"/></div>
                  <div><label class="form-label">Status</label><select v-model.number="form.is_active" class="form-select"><option :value="1">Active</option><option :value="0">Hidden</option></select></div>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Teacher" message="This will permanently remove the teacher profile."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
