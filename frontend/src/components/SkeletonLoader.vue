<script setup>
defineProps({
  /** number of skeleton rows/cards to show */
  count: { type: Number, default: 3 },
  /** 'card' | 'row' | 'text' */
  type:  { type: String, default: 'card' },
})
</script>

<template>
  <!-- Card skeletons (grid layout) -->
  <div v-if="type === 'card'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div
      v-for="n in count" :key="n"
      class="glass-card overflow-hidden"
      style="animation: pulse 1.8s ease-in-out infinite;"
    >
      <div class="aspect-video bg-white/5"></div>
      <div class="p-6 space-y-3">
        <div class="h-4 bg-white/8 rounded-full w-3/4"></div>
        <div class="h-3 bg-white/5 rounded-full w-full"></div>
        <div class="h-3 bg-white/5 rounded-full w-2/3"></div>
        <div class="h-8 bg-white/5 rounded-xl mt-4"></div>
      </div>
    </div>
  </div>

  <!-- Row skeletons (list layout) -->
  <div v-else-if="type === 'row'" class="grid gap-4">
    <div
      v-for="n in count" :key="n"
      class="glass-card p-6 flex items-center gap-6"
      style="animation: pulse 1.8s ease-in-out infinite;"
    >
      <div class="w-20 h-6 bg-white/8 rounded-full shrink-0"></div>
      <div class="flex-1 space-y-2">
        <div class="h-4 bg-white/8 rounded-full w-1/2"></div>
        <div class="h-3 bg-white/5 rounded-full w-1/3"></div>
      </div>
      <div class="w-32 h-8 bg-white/5 rounded-xl shrink-0"></div>
    </div>
  </div>

  <!-- Text/article skeletons -->
  <div v-else-if="type === 'text'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div
      v-for="n in count" :key="n"
      class="glass-card p-6 space-y-3"
      style="animation: pulse 1.8s ease-in-out infinite;"
    >
      <div class="flex justify-between mb-2">
        <div class="h-5 w-24 bg-white/8 rounded-full"></div>
        <div class="h-4 w-20 bg-white/5 rounded-full"></div>
      </div>
      <div class="h-4 bg-white/8 rounded-full w-full"></div>
      <div class="h-4 bg-white/8 rounded-full w-5/6"></div>
      <div class="h-3 bg-white/5 rounded-full w-full"></div>
      <div class="h-3 bg-white/5 rounded-full w-4/5"></div>
      <div class="h-3 bg-white/5 rounded-full w-2/3"></div>
    </div>
  </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.5; }
}
</style>
