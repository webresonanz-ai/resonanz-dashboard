<script setup>
import { useToastStore } from '@/stores/toastStore'
import { CheckCircle, XCircle, X } from 'lucide-vue-next'
const toast = useToastStore()
</script>

<template>
  <teleport to="body">
    <div class="fixed bottom-4 left-4 right-4 z-[100] space-y-2 pointer-events-none sm:bottom-6 sm:left-auto sm:right-6 sm:w-96">
      <transition-group name="slide-up" tag="div" class="space-y-2">
        <div
          v-for="t in toast.toasts"
          :key="t.id"
          class="toast pointer-events-auto"
          :class="t.type === 'success' ? 'toast-success' : 'toast-error'"
          :role="t.type === 'error' ? 'alert' : 'status'"
          aria-live="polite"
        >
          <CheckCircle v-if="t.type === 'success'" class="w-4 h-4 shrink-0" />
          <XCircle     v-else                       class="w-4 h-4 shrink-0" />
          <span class="flex-1">{{ t.message }}</span>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>
