<script setup>
import { computed, onMounted } from 'vue'
import { Clock, MapPin, User, RefreshCw, AlertCircle } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/schedule')
onMounted(fetch)

// The API returns time_start / time_end as "HH:MM:SS" — format to "HH:MM"
function formatTime(t) {
  return t ? t.slice(0, 5) : ''
}

// Assign a gold shade per row index (cycles through 6 tones)
const dayColors = ['#ffd843', '#ffc520', '#e8a400', '#c07d00', '#ffd843', '#ffc520']
function dayColor(i) {
  return dayColors[i % dayColors.length]
}

const schedule = computed(() => data.value ?? [])
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Weekly Timetable</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Class <span class="gold-text">Schedule</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Plan your week with our comprehensive schedule of classes, masterclasses, and ensemble rehearsals.
      </p>
    </div>

    <!-- ─── Loading skeletons ─── -->
    <div v-if="loading" class="grid gap-4">
      <div
        v-for="n in 6" :key="n"
        class="glass-card p-6 flex items-center gap-6"
        style="animation: pulse 1.8s ease-in-out infinite;"
      >
        <div class="w-28 h-6 bg-white/8 rounded-full shrink-0"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-white/8 rounded-full w-1/2"></div>
          <div class="h-3 bg-white/5 rounded-full w-1/3"></div>
        </div>
        <div class="hidden sm:flex gap-3">
          <div class="w-32 h-8 bg-white/5 rounded-xl"></div>
          <div class="w-24 h-8 bg-white/5 rounded-xl"></div>
        </div>
      </div>
    </div>

    <!-- ─── Error state ─── -->
    <div v-else-if="error"
         class="glass-card p-10 text-center border-red-500/20">
      <AlertCircle class="w-10 h-10 text-red-400/60 mx-auto mb-3" />
      <p class="text-gray-400 text-sm mb-4">{{ error }}</p>
      <button @click="fetch"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold-gradient text-maroon-950
                     font-semibold rounded-xl text-sm btn-magnetic">
        <RefreshCw class="w-4 h-4" /> Retry
      </button>
    </div>

    <!-- ─── Empty state ─── -->
    <div v-else-if="!schedule.length"
         class="glass-card p-16 text-center">
      <p class="text-gray-500">No schedule available yet. Check back soon!</p>
    </div>

    <!-- ─── Data ─── -->
    <div v-else class="grid gap-4">
      <div
        v-for="(item, i) in schedule"
        :key="item.id"
        class="reveal glass-card overflow-hidden group transition-all duration-400"
        :class="`delay-${Math.min((i + 1) * 80, 700)}`"
      >
        <div
          class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8 p-6
                 group-hover:-translate-y-0.5 group-hover:border-gold-500/40
                 transition-all duration-300"
        >
          <!-- Day name with accent bar -->
          <div class="md:w-36 shrink-0 flex items-center gap-3">
            <div
              class="w-1 h-10 rounded-full shrink-0 transition-all duration-300 group-hover:h-14"
              :style="`background: linear-gradient(180deg, ${dayColor(i)}, ${dayColor(i)}88)`"
            ></div>
            <div>
              <p class="text-gold-400 font-serif text-xl font-bold group-hover:text-gold-300 transition-colors">
                {{ item.day }}
              </p>
              <p class="text-xs text-gold-500/50 font-medium uppercase tracking-widest">Day {{ i + 1 }}</p>
            </div>
          </div>

          <!-- Course + teacher -->
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-white mb-1.5 group-hover:text-gold-400 transition-colors duration-300">
              {{ item.course }}
            </h3>
            <p class="text-sm text-gray-400 flex items-center gap-1.5">
              <User class="w-3.5 h-3.5 text-gold-500/60 shrink-0" />
              {{ item.teacher }}
            </p>
          </div>

          <!-- Time + room pills -->
          <div class="flex flex-wrap sm:flex-nowrap gap-3 text-sm">
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg
                        bg-gold-500/8 border border-gold-500/15 text-gray-300
                        group-hover:border-gold-500/35 group-hover:bg-gold-500/12 transition-all duration-300">
              <Clock class="w-4 h-4 text-gold-400 shrink-0" />
              <span class="whitespace-nowrap">{{ formatTime(item.time_start) }} – {{ formatTime(item.time_end) }}</span>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg
                        bg-gold-500/8 border border-gold-500/15 text-gray-300
                        group-hover:border-gold-500/35 group-hover:bg-gold-500/12 transition-all duration-300">
              <MapPin class="w-4 h-4 text-gold-400 shrink-0" />
              <span>{{ item.room }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.5; }
}
</style>
