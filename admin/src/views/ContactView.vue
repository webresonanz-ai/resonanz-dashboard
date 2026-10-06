<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Mail, Trash2, Eye, X, Loader2, RefreshCw } from 'lucide-vue-next'

const auth    = useAuthStore()
const toast   = useToastStore()
const items   = ref([])
const loading = ref(false)
const deleting = ref(false)
const confirmId = ref(null)
const selected  = ref(null)
const viewModal = ref(false)

async function loadAll() {
  loading.value = true
  try {
    const json = await auth.apiFetch('/api/admin/contact')
    items.value = json.data ?? []
  } catch(e) { toast.error(e.message) }
  finally { loading.value = false }
}

async function view(id) {
  const json = await auth.apiFetch(`/api/admin/contact/${id}`)
  selected.value = json.data
  // Mark as read locally
  const row = items.value.find(i => i.id === id)
  if (row) row.is_read = 1
  viewModal.value = true
}

async function confirmDelete() {
  deleting.value = true
  try {
    await auth.apiFetch(`/api/admin/contact/${confirmId.value}`, { method: 'DELETE' })
    items.value = items.value.filter(i => i.id !== confirmId.value)
    toast.success('Message deleted.')
  } catch(e) { toast.error(e.message) }
  finally { deleting.value = false; confirmId.value = null }
}

const unread = () => items.value.filter(i => !i.is_read).length
onMounted(loadAll)
</script>

<template>
  <div>
    <PageHeader title="Contact Messages" subtitle="View and manage contact form submissions" />

    <!-- Stats bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
      <div class="admin-card px-4 py-3 flex items-center gap-3">
        <Mail class="w-5 h-5 text-gold-400 shrink-0"/>
        <div><p class="text-xl font-bold text-white">{{ items.length }}</p><p class="text-xs text-gray-500">Total Messages</p></div>
      </div>
      <div class="admin-card px-4 py-3 flex items-center gap-3">
        <div class="w-2 h-2 rounded-full bg-gold-400 animate-pulse shrink-0"></div>
        <div><p class="text-xl font-bold text-white">{{ unread() }}</p><p class="text-xs text-gray-500">Unread</p></div>
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <p class="text-sm text-gray-400">{{ items.length }} messages</p>
        <button class="btn-secondary" @click="loadAll"><RefreshCw class="w-4 h-4" :class="loading && 'animate-spin'"/>Refresh</button>
      </div>
      <div class="overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center py-16"><Loader2 class="w-6 h-6 text-gold-400 animate-spin"/></div>
        <EmptyState v-else-if="!items.length" message="No messages yet." />
        <table v-else class="data-table">
          <thead><tr><th></th><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="row in items" :key="row.id" :class="!row.is_read && 'bg-gold-500/3'">
              <td class="w-2 pl-4 pr-0">
                <span v-if="!row.is_read" class="block w-2 h-2 rounded-full bg-gold-400" title="Unread"></span>
              </td>
              <td class="font-medium" :class="!row.is_read ? 'text-white' : 'text-gray-300'">{{ row.name }}</td>
              <td class="text-xs text-gray-400">{{ row.email }}</td>
              <td class="max-w-[200px] truncate text-sm" :class="!row.is_read ? 'text-gray-200' : 'text-gray-400'">{{ row.subject }}</td>
              <td class="text-xs text-gray-500">{{ row.created_at?.slice(0,10) }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <button class="btn-icon" @click="view(row.id)" title="Read message"><Eye class="w-4 h-4"/></button>
                  <button class="btn-icon hover:text-red-400" @click="confirmId=row.id"><Trash2 class="w-4 h-4"/></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- View message modal -->
    <transition name="fade">
      <div v-if="viewModal" class="modal-overlay" @click.self="viewModal=false">
        <transition name="scale">
          <div v-if="viewModal && selected" class="modal-box max-w-lg">
            <div class="modal-header">
              <h3 class="font-semibold text-white">Message from {{ selected.name }}</h3>
              <button class="btn-icon" @click="viewModal=false"><X class="w-4 h-4"/></button>
            </div>
            <div class="modal-body">
              <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div><p class="text-gray-500 text-xs mb-1">From</p><p class="text-white font-medium">{{ selected.name }}</p></div>
                <div><p class="text-gray-500 text-xs mb-1">Email</p><a :href="`mailto:${selected.email}`" class="text-gold-400 hover:underline">{{ selected.email }}</a></div>
                <div class="col-span-2"><p class="text-gray-500 text-xs mb-1">Subject</p><p class="text-white">{{ selected.subject }}</p></div>
                <div class="col-span-2"><p class="text-gray-500 text-xs mb-1">Date</p><p class="text-gray-400 text-xs">{{ selected.created_at }}</p></div>
              </div>
              <div class="bg-white/5 rounded-xl p-4">
                <p class="text-sm text-gray-300 whitespace-pre-wrap leading-relaxed">{{ selected.message }}</p>
              </div>
            </div>
            <div class="modal-footer">
              <a :href="`mailto:${selected.email}?subject=Re: ${selected.subject}`"
                 class="btn-primary">Reply via Email</a>
              <button class="btn-secondary" @click="viewModal=false">Close</button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

    <ConfirmDialog :open="!!confirmId" title="Delete Message" message="This will permanently delete the message."
      :loading="deleting" @confirm="confirmDelete" @cancel="confirmId=null" />
  </div>
</template>
