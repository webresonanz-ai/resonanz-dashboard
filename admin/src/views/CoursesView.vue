<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2, PlusCircle, MinusCircle } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/courses')
onMounted(fetchAll)

const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const form  = reactive({ id:null, name:'', level:'Beginner', price:0, period:'/month', duration:'', class_size:'Private', features:[], is_featured:0, is_active:1, sort_order:0 })
const newFeature = ref('')

function resetForm() { Object.assign(form,{id:null,name:'',level:'Beginner',price:0,period:'/month',duration:'45 min / session',class_size:'Private',features:[],is_featured:0,is_active:1,sort_order:0}); newFeature.value='' }
function openCreate() { resetForm(); isEdit.value=false; modal.value=true }
function openEdit(r)  {
  const features = typeof r.features === 'string' ? JSON.parse(r.features) : (r.features ?? [])
  Object.assign(form, { ...r, features: [...features] }); isEdit.value=true; modal.value=true
}
function addFeature()    { if(newFeature.value.trim()) { form.features.push(newFeature.value.trim()); newFeature.value='' } }
function removeFeature(i){ form.features.splice(i,1) }
async function submit()  { const {id,...d}=form; if(isEdit.value) await update(id,d); else await create(d); modal.value=false }
async function confirmDelete() { await remove(confirmId.value); confirmId.value=null }
</script>

<template>
  <div>
    <PageHeader title="Courses" subtitle="Manage programs and pricing" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} courses</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Course</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Name</th><th>Level</th><th>Price</th><th>Class Size</th><th>Featured</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td class="font-medium text-white">{{ row.name }}</td>
              <td>{{ row.level }}</td>
              <td class="text-gold-400 font-semibold">Rp{{ Number(row.price).toLocaleString('id-ID') }}{{ row.period }}</td>
              <td>{{ row.class_size }}</td>
              <td><span class="badge" :class="row.is_featured ? 'badge-gold' : 'badge-gray'">{{ row.is_featured ? 'Yes' : 'No' }}</span></td>
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
          <div v-if="modal" class="modal-box max-w-2xl">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Course</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div><label class="form-label">Course Name</label><input v-model="form.name" required class="form-input" placeholder="Advanced Performance"/></div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Level</label>
                    <select v-model="form.level" class="form-select">
                      <option v-for="l in ['Beginner','Intermediate','Advanced']" :key="l">{{ l }}</option>
                    </select>
                  </div>
                  <div><label class="form-label">Sort Order</label><input v-model.number="form.sort_order" type="number" class="form-input"/></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                  <div><label class="form-label">Price (IDR)</label><input v-model.number="form.price" type="number" step="1000" min="0" required class="form-input"/></div>
                  <div><label class="form-label">Period</label><input v-model="form.period" class="form-input" placeholder="/month"/></div>
                  <div><label class="form-label">Duration</label><input v-model="form.duration" class="form-input" placeholder="45 min / session"/></div>
                </div>
                <div><label class="form-label">Class Size</label><input v-model="form.class_size" class="form-input" placeholder="Private or Group (8)"/></div>

                <!-- Features -->
                <div>
                  <label class="form-label">Features</label>
                  <div class="space-y-1.5 mb-2">
                    <div v-for="(f, i) in form.features" :key="i"
                         class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-1.5 text-sm">
                      <span class="flex-1">{{ f }}</span>
                      <button type="button" @click="removeFeature(i)" class="text-gray-500 hover:text-red-400 transition-colors"><MinusCircle class="w-4 h-4"/></button>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <input v-model="newFeature" class="form-input flex-1" placeholder="Add a feature…" @keydown.enter.prevent="addFeature"/>
                    <button type="button" class="btn-secondary px-3" @click="addFeature"><PlusCircle class="w-4 h-4"/></button>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Featured</label>
                    <select v-model.number="form.is_featured" class="form-select">
                      <option :value="0">No</option><option :value="1">Yes (Most Popular)</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Status</label>
                    <select v-model.number="form.is_active" class="form-select">
                      <option :value="1">Active</option><option :value="0">Hidden</option>
                    </select>
                  </div>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Course" message="This will permanently remove the course."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
