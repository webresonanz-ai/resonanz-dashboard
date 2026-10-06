<script setup>
import { computed, onMounted } from 'vue'
import { Calendar, MapPin, Ticket, Clock, ArrowRight, AlertCircle, RefreshCw } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/concerts')
onMounted(fetch)

const concerts = computed(() => data.value ?? [])

const tagColors = {
  Featured: 'bg-gold-gradient text-maroon-950',
  Student:  'bg-maroon-700/60 text-gold-300 border border-gold-500/30',
  Special:  'bg-gold-gradient text-maroon-950',
  New:      'bg-maroon-700/60 text-gold-300 border border-gold-500/30',
}

/** Format "YYYY-MM-DD" → "Dec 15, 2025" */
function formatDate(d) {
  if (!d) return ''
  const dt = new Date(d + 'T00:00:00')
  return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

/** Format "HH:MM:SS" → "HH:MM" */
function formatTime(t) {
  return t ? t.slice(0, 5) : ''
}

/** Format price decimal → "$45.00" */
function formatPrice(p) {
  return p !== undefined && p !== null ? `$${parseFloat(p).toFixed(2).replace('.00', '')}` : ''
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Live Performances</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Upcoming <span class="gold-text">Concerts</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Experience the magic of live music performed by our talented students and acclaimed faculty.
      </p>
    </div>

    <!-- ─── Loading ─── -->
    <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="n in 6" :key="n"
           class="glass-card overflow-hidden"
           style="animation: pulse 1.8s ease-in-out infinite;">
        <div class="aspect-video bg-white/5"></div>
        <div class="p-6 space-y-3">
          <div class="h-4 bg-white/8 rounded-full w-3/4"></div>
          <div class="h-3 bg-white/5 rounded-full w-full"></div>
          <div class="h-3 bg-white/5 rounded-full w-2/3"></div>
          <div class="h-10 bg-white/5 rounded-xl mt-5"></div>
        </div>
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
    <div v-else-if="!concerts.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">No upcoming concerts at the moment. Check back soon!</p>
    </div>

    <!-- ─── Grid ─── -->
    <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="(c, i) in concerts"
        :key="c.id"
        class="reveal glass-card overflow-hidden card-lift animated-border group"
        :class="`delay-${(i % 3) * 100 + 100}`"
      >
        <!-- Image area -->
        <div class="aspect-video bg-maroon-gradient relative flex items-center justify-center
                    border-b border-gold-500/20 overflow-hidden">
          <div class="absolute inset-0 bg-gold-gradient opacity-0 group-hover:opacity-10
                      transition-opacity duration-500"></div>
          <div class="absolute w-40 h-40 rounded-full border border-gold-400/10
                      group-hover:scale-110 transition-transform duration-700"></div>
          <div class="absolute w-28 h-28 rounded-full border border-gold-400/15
                      group-hover:scale-125 transition-transform duration-700 delay-75"></div>
          <div v-if="c.tag" class="absolute top-4 right-4 z-10">
            <span class="px-3 py-1 text-xs font-semibold rounded-full shadow-gold"
                  :class="tagColors[c.tag] ?? 'bg-gold-gradient text-maroon-950'">
              {{ c.tag }}
            </span>
          </div>
          <Ticket class="w-16 h-16 text-gold-400/40 group-hover:text-gold-400/70
                         group-hover:scale-110 transition-all duration-500"
                  stroke-width="1" />
        </div>

        <!-- Body -->
        <div class="p-6">
          <h3 class="text-xl font-semibold text-white mb-4 group-hover:text-gold-400
                     transition-colors duration-300 leading-snug">
            {{ c.title }}
          </h3>
          <div class="space-y-2.5 text-sm text-gray-400 mb-6">
            <div class="flex items-center gap-2.5">
              <Calendar class="w-4 h-4 text-gold-400 shrink-0" />
              {{ formatDate(c.event_date) }}
            </div>
            <div class="flex items-center gap-2.5">
              <Clock class="w-4 h-4 text-gold-400 shrink-0" />
              {{ formatTime(c.event_time) }}
            </div>
            <div class="flex items-center gap-2.5">
              <MapPin class="w-4 h-4 text-gold-400 shrink-0" />
              {{ c.venue }}
            </div>
          </div>
          <div class="flex items-center justify-between pt-4 border-t border-gold-500/20">
            <span class="text-2xl font-bold gold-text font-serif">{{ formatPrice(c.price) }}</span>
            <button class="inline-flex items-center gap-1.5 px-4 py-2 bg-gold-gradient text-maroon-950
                           font-semibold text-sm rounded-lg btn-magnetic relative overflow-hidden group/btn">
              <span class="absolute inset-0 -translate-x-full group-hover/btn:translate-x-full
                           bg-gradient-to-r from-transparent via-white/25 to-transparent
                           transition-transform duration-500"></span>
              <span class="relative">Book Now</span>
              <ArrowRight class="w-3.5 h-3.5 relative group-hover/btn:translate-x-0.5 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
