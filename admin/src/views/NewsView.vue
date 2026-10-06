<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useCrud } from '@/composables/useCrud'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Plus, Pencil, Trash2, X, Loader2 } from 'lucide-vue-next'

const { items, loading, saving, deleting, fetchAll, create, update, remove } = useCrud('/api/admin/news')
onMounted(fetchAll)

const CATS = ['Achievement','Announcement','Event','Story','Other']
const modal = ref(false); const isEdit = ref(false); const confirmId = ref(null)
const form  = reactive({ id:null, title:'', category:'Event', excerpt:'', content:'', is_published:1, published_at:'' })

function openCreate() { Object.assign(form,{id:null,title:'',category:'Event',excerpt:'',content:'',is_published:1,published_at:new Date().toISOString().slice(0,10)}); isEdit.value=false; modal.value=true }
function openEdit(r)  { Object.assign(form, { ...r, published_at: r.published_at?.slice(0,10) }); isEdit.value=true; modal.value=true }
async function submit() { const {id,...d}=form; if(isEdit.value) await update(id,d); else await create(d); modal.value=false }
async function confirmDelete() { await remove(confirmId.value); confirmId.value=null }
</script>

<template>
  <div>
    <PageHeader title="News" subtitle="Manage news articles and announcements" />
    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} articles</p>
        <button class="btn-primary" @click="openCreate"><Plus class="w-4 h-4"/>Add Article</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" />
        <table v-else class="data-table">
          <thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id">
              <td class="font-medium text-white max-w-[240px]"><p class="truncate">{{ row.title }}</p><p class="text-xs text-gray-500 mt-0.5 truncate">{{ row.excerpt }}</p></td>
              <td><span class="badge badge-gold">{{ row.category }}</span></td>
              <td class="text-xs text-gray-400">{{ row.published_at?.slice(0,10) }}</td>
              <td><span class="badge" :class="row.is_published ? 'badge-green' : 'badge-gray'">{{ row.is_published ? 'Published' : 'Draft' }}</span></td>
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
          <div v-if="modal" class="modal-box max-w-2xl">
            <div class="modal-header">
              <h3 class="font-semibold text-white">{{ isEdit ? 'Edit' : 'Add' }} Article</h3>
              <button class="btn-icon" @click="modal=false"><X class="w-4 h-4"/></button>
            </div>
            <form @submit.prevent="submit">
              <div class="modal-body">
                <div><label class="form-label">Title</label><input v-model="form.title" required class="form-input" placeholder="Article title"/></div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Category</label>
                    <select v-model="form.category" class="form-select"><option v-for="c in CATS" :key="c" :value="c">{{ c }}</option></select>
                  </div>
                  <div><label class="form-label">Published Date</label><input v-model="form.published_at" type="date" class="form-input"/></div>
                </div>
                <div><label class="form-label">Excerpt</label><textarea v-model="form.excerpt" required rows="2" class="form-input resize-none" placeholder="Short summary…"></textarea></div>
                <div><label class="form-label">Full Content (optional)</label><textarea v-model="form.content" rows="4" class="form-input resize-none" placeholder="Full article body…"></textarea></div>
                <div>
                  <label class="form-label">Status</label>
                  <select v-model.number="form.is_published" class="form-select">
                    <option :value="1">Published</option><option :value="0">Draft</option>
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
    <ConfirmDialog :open="!!confirmId" title="Delete Article" message="This will permanently remove the article."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
