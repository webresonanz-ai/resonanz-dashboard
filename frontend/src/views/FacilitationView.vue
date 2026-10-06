<script setup>
import { computed, onMounted } from 'vue'
import {
  Piano, Mic2, Drum, Guitar, Music, Speaker, Lightbulb, Snowflake,
  Building2, Star, AlertCircle, RefreshCw,
} from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/facilities')
onMounted(fetch)

const facilities = computed(() => data.value ?? [])

/** Map icon name string (stored in DB) → lucide component */
const iconMap = { Music, Piano, Mic2, Drum, Guitar, Speaker, Lightbulb, Snowflake, Building2, Star }
function resolveIcon(name) {
  return iconMap[name] ?? Music
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Our Spaces</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        World-Class <span class="gold-text">Facilities</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Every space at Resonanz is designed to inspire, from intimate practice rooms to our iconic concert hall.
      </p>
    </div>

    <!-- ─── Loading ─── -->
    <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="n in 8" :key="n"
           class="glass-card p-6 space-y-3"
           style="animation: pulse 1.8s ease-in-out infinite;">
        <div class="w-14 h-14 bg-white/8 rounded-xl mb-5"></div>
        <div class="h-4 bg-white/8 rounded-full w-3/4"></div>
        <div class="h-5 bg-white/5 rounded-full w-1/3"></div>
        <div class="h-3 bg-white/5 rounded-full w-full"></div>
        <div class="h-3 bg-white/5 rounded-full w-4/5"></div>
      </div>
    </div>

    <!-- ─── Error ─── -->
    <div v-else-if="error" class="glass-card p-10 text-center border-red-500/20">
      <AlertCircle class="w-10 h-10 text-red-400/60 mx-auto mb-3" />
      <p class="text-gray-400 text-sm mb-4">{{ error }}</p>
      <button @click="fetch"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold-gradient text-maroon-950
                     font-semibold rounded-xl text-sm btn-magnetic">
        <RefreshCw class="w-4 h-4" /> Retry
      </button>
    </div>

    <!-- ─── Empty ─── -->
    <div v-else-if="!facilities.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">No facilities listed yet.</p>
    </div>

    <!-- ─── Grid ─── -->
    <div v-else class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(f, i) in facilities"
        :key="f.id"
        class="reveal glass-card p-6 card-lift animated-border group cursor-default"
        :class="`delay-${(i % 4) * 100 + 100}`"
      >
        <!-- Icon -->
        <div class="w-14 h-14 rounded-xl bg-gold-500/10 border border-gold-500/30
                    flex items-center justify-center mb-5
                    group-hover:bg-gold-gradient group-hover:shadow-gold group-hover:scale-110
                    group-hover:rotate-6 transition-all duration-350 ease-out">
          <component :is="resolveIcon(f.icon)"
                     class="w-6 h-6 text-gold-400 group-hover:text-maroon-950 transition-colors duration-300" />
        </div>

        <h3 class="text-lg font-semibold text-white mb-1 group-hover:text-gold-300 transition-colors">
          {{ f.name }}
        </h3>

        <!-- Capacity pill -->
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                     bg-gold-500/10 text-gold-400 border border-gold-500/20 mb-3
                     group-hover:bg-gold-500/20 transition-colors">
          {{ f.capacity }}
        </span>

        <p class="text-sm text-gray-400 leading-relaxed">{{ f.description }}</p>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
