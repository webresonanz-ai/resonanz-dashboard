<script setup>
import { Inbox } from 'lucide-vue-next'
defineProps({ message: { type: String, default: 'No records found.' } })
</script>
<template>
  <div class="flex flex-col items-center justify-center py-16 text-center">
    <Inbox class="w-12 h-12 text-gray-600 mb-3" />
    <p class="text-gray-500 text-sm">{{ message }}</p>
  </div>
</template>
