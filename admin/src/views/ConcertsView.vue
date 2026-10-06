<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2 } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/concerts')
onMounted(fetchAll)

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const form  = reactive({ id:null, title:'', event_date:'', event_time:'19:00', venue:'', price:0, tag:'', is_active:1 })

function fmt(v) { return v ? `$${parseFloat(v).toFixed(2)}` : '$0.00' }
function openCreate() { Object.assign(form, {id:null,title:'',event_date:'',event_time:'19:00',venue:'',price:0,tag:'',is_active:1}); isEdit.value=false; modal.value=true }
function openEdit(r)  { Object.assign(form, r); isEdit.value=true; modal.value=true }
async function submit() { const {id,...d}=form; if(isEdit.value) await update(id,d); else await create(d); modal.value=false }
async function confirmDelete() { await remove(confirmId.value); confirmId.value=null }
</script>

<template>
  <div>
    <PageHeader title="Concerts" subtitle="Manage upcoming concert listings" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} concerts</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Concert</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Venue</th><th>Price</th><th>Tag</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td class="font-medium text-white max-w-[180px] truncate">{{ row.title }}</td>
              <td>{{ row.event_date }}</td>
              <td>{{ row.event_time?.slice(0,5) }}</td>
              <td class="max-w-[140px] truncate">{{ row.venue }}</td>
              <td class="text-gold-400 font-semibold">{{ fmt(row.price) }}</td>
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
        <transition name="scale">
          <div v-if="modal" class="modal-box">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Concert</h3>
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
                  <div><label class="form-label">Price ($)</label><input v-model.number="form.price" type="number" step="0.01" min="0" required class="form-input"/></div>
                  <div><label class="form-label">Tag (optional)</label><input v-model="form.tag" class="form-input" placeholder="Featured, New…"/></div>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Concert" message="This will permanently remove the concert."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
