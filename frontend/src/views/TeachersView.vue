<script setup>
import { computed, onMounted } from 'vue'
import { Mail, AlertCircle, RefreshCw } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/teachers')
onMounted(fetch)

const teachers = computed(() => data.value ?? [])
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Our People</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Meet the <span class="gold-text">Teachers</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Learn from internationally acclaimed artists and dedicated educators who bring passion to every lesson.
      </p>
    </div>

    <!-- ─── Loading ─── -->
    <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="n in 8" :key="n"
           class="glass-card p-6 text-center space-y-3"
           style="animation: pulse 1.8s ease-in-out infinite;">
        <div class="w-24 h-24 rounded-full bg-white/8 mx-auto mb-5"></div>
        <div class="h-4 bg-white/8 rounded-full w-2/3 mx-auto"></div>
        <div class="h-3 bg-white/5 rounded-full w-1/2 mx-auto"></div>
        <div class="h-3 bg-white/5 rounded-full w-full"></div>
        <div class="h-3 bg-white/5 rounded-full w-4/5 mx-auto"></div>
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
    <div v-else-if="!teachers.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">No faculty profiles listed yet.</p>
    </div>

    <!-- ─── Grid ─── -->
    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(t, i) in teachers"
        :key="t.id"
        class="reveal glass-card p-6 text-center card-lift animated-border group cursor-default"
        :class="`delay-${(i % 4) * 100 + 100}`"
      >
        <!-- Avatar with animated ring -->
        <div class="relative w-24 h-24 mx-auto mb-5">
          <div class="absolute inset-0 rounded-full bg-gold-gradient opacity-0 blur-md
                      group-hover:opacity-40 transition-opacity duration-500 scale-110"></div>
          <div class="absolute inset-0 rounded-full border-2 border-dashed border-gold-500/20
                      group-hover:border-gold-500/50 transition-all duration-500"
               style="animation: spin-slow 12s linear infinite;"></div>
          <div class="relative w-full h-full rounded-full bg-gold-gradient flex items-center justify-center
                      shadow-gold group-hover:shadow-gold-lg transition-all duration-300 group-hover:scale-105">
            <span class="text-2xl font-serif font-bold text-maroon-950">{{ t.initials }}</span>
          </div>
        </div>

        <h3 class="text-lg font-serif font-bold text-white mb-1 group-hover:text-gold-300 transition-colors">
          {{ t.name }}
        </h3>
        <p class="text-xs text-gold-400 font-medium mb-4 tracking-wide">{{ t.role }}</p>
        <p class="text-sm text-gray-400 leading-relaxed mb-5">{{ t.bio }}</p>

        <!-- Contact link — mailto if email available, else router link -->
        <a v-if="t.email"
           :href="`mailto:${t.email}`"
           class="inline-flex items-center gap-2 text-sm text-gold-400 font-medium
                  hover:text-gold-300 group/btn transition-all">
          <Mail class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
          <span class="group-hover/btn:translate-x-0.5 transition-transform">Contact</span>
        </a>
        <button v-else
                class="inline-flex items-center gap-2 text-sm text-gold-400 font-medium
                       hover:text-gold-300 group/btn transition-all">
          <Mail class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
          <span class="group-hover/btn:translate-x-0.5 transition-transform">Contact</span>
        </button>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
