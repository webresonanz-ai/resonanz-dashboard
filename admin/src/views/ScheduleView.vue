<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2 } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/schedule')
onMounted(fetchAll)

const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']

const modal  = ref(false)
const isEdit = ref(false)
const form   = reactive({ id:null, day:'Monday', time_start:'09:00', time_end:'18:00', course:'', room:'', teacher:'', sort_order:0 })
const confirmId = ref(null)

function openCreate() {
  Object.assign(form, { id:null, day:'Monday', time_start:'09:00', time_end:'18:00', course:'', room:'', teacher:'', sort_order:0 })
  isEdit.value = false; modal.value = true
}
function openEdit(row) {
  Object.assign(form, { ...row, time_start: row.time_start?.slice(0,5), time_end: row.time_end?.slice(0,5) })
  isEdit.value = true; modal.value = true
}
async function submit() {
  const { id, ...data } = form
  if (isEdit.value) await update(id, data); else await create(data)
  modal.value = false
}
async function confirmDelete() {
  await remove(confirmId.value)
  confirmId.value = null
}
</script>

<template>
  <div>
    <PageHeader title="Schedule" subtitle="Manage weekly class timetable" />

    <div class="admin-card overflow-hidden">
      <!-- Toolbar -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} records</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Class</button>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16">
          <Loader2 class="w-6 h-6 text-gold-400 animate-spin" />
        </div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Day</th><th>Course</th><th>Time</th><th>Room</th><th>Teacher</th><th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td><span class="badge badge-gold">{{ row.day }}</span></td>
              <td class="font-medium text-white">{{ row.course }}</td>
              <td class="text-gray-400 text-xs">{{ row.time_start?.slice(0,5) }} – {{ row.time_end?.slice(0,5) }}</td>
              <td>{{ row.room }}</td>
              <td>{{ row.teacher }}</td>
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

    <!-- Modal -->
    <transition name="fade">
      <div v-if="modal" class="modal-overlay" @click.self="modal=false">
        <transition name="scale">
          <div v-if="modal" class="modal-box">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Class</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Day</label>
                    <select v-model="form.day" class="form-select">
                      <option v-for="d in DAYS" :key="d" :value="d">{{ d }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Sort Order</label>
                    <input v-model.number="form.sort_order" type="number" class="form-input" />
                  </div>
                </div>
                <div>
                  <label class="form-label">Course Name</label>
                  <input v-model="form.course" required class="form-input" placeholder="Classical Piano" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div><label class="form-label">Start Time</label><input v-model="form.time_start" type="time" required class="form-input" /></div>
                  <div><label class="form-label">End Time</label><input v-model="form.time_end" type="time" required class="form-input" /></div>
                </div>
                <div>
                  <label class="form-label">Room</label>
                  <input v-model="form.room" required class="form-input" placeholder="Studio A" />
                </div>
                <div>
                  <label class="form-label">Teacher</label>
                  <input v-model="form.teacher" required class="form-input" placeholder="Ms. Elena Rossi" />
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-secondary" @click="modal=false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="saving">
                  <Loader2 v-if="saving" class="w-4 h-4 animate-spin"/>
                  {{ saving ? 'Saving…' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>

    <ConfirmDialog :open="!!confirmId" title="Delete Class" message="This action cannot be undone."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
