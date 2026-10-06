<script setup>
defineProps({ title: String, subtitle: String })
</script>
<template>
  <div class="mb-7">
    <h1 class="text-2xl font-serif font-bold text-white">{{ title }}</h1>
    <p v-if="subtitle" class="text-sm text-gray-400 mt-1">{{ subtitle }}</p>
    <div class="h-px bg-gradient-to-r from-gold-500/40 via-gold-500/10 to-transparent mt-4"></div>
  </div>
</template>
