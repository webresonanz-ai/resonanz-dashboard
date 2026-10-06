<script setup>
import { AlertTriangle, X } from 'lucide-vue-next'
const props = defineProps({ open: Boolean, title: String, message: String, loading: Boolean })
const emit  = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="modal-overlay" @click.self="emit('cancel')">
      <transition name="scale">
        <div v-if="open" class="admin-card border-red-500/20 w-full max-w-sm p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-500/15 flex items-center justify-center shrink-0">
              <AlertTriangle class="w-5 h-5 text-red-400" />
            </div>
            <h3 class="font-semibold text-white">{{ title }}</h3>
          </div>
          <p class="text-sm text-gray-400 mb-6">{{ message }}</p>
          <div class="flex justify-end gap-3">
            <button class="btn-secondary" @click="emit('cancel')" :disabled="loading">Cancel</button>
            <button class="btn-danger bg-red-500/20 hover:bg-red-500/35 px-4 py-2.5 text-sm font-semibold"
                    @click="emit('confirm')" :disabled="loading">
              {{ loading ? 'Deleting…' : 'Delete' }}
            </button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>
