<script setup>
import { computed, onMounted } from 'vue'
import { Calendar, ArrowRight, AlertCircle, RefreshCw } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/news')
onMounted(fetch)

const news = computed(() => data.value ?? [])

const categoryColor = {
  Achievement:  'text-gold-300 bg-gold-500/15 border-gold-500/40',
  Announcement: 'text-blue-300 bg-blue-500/10 border-blue-500/30',
  Event:        'text-purple-300 bg-purple-500/10 border-purple-500/30',
  Story:        'text-emerald-300 bg-emerald-500/10 border-emerald-500/30',
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Stay Updated</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Latest <span class="gold-text">News</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Discover stories, announcements, and highlights from the Resonanz community.
      </p>
    </div>

    <!-- ─── Loading ─── -->
    <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="n in 6" :key="n"
           class="glass-card p-6 space-y-3"
           style="animation: pulse 1.8s ease-in-out infinite;">
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
    <div v-else-if="!news.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">No news articles published yet.</p>
    </div>

    <!-- ─── Grid ─── -->
    <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article
        v-for="(item, i) in news"
        :key="item.id"
        class="reveal glass-card p-6 card-lift animated-border group flex flex-col cursor-pointer"
        :class="`delay-${(i % 3) * 100 + 100}`"
      >
        <!-- Meta row -->
        <div class="flex items-center justify-between mb-5">
          <span class="px-3 py-1 text-xs font-semibold rounded-full border"
                :class="categoryColor[item.category] ?? 'text-gold-400 bg-gold-500/10 border-gold-500/30'">
            {{ item.category }}
          </span>
          <span class="text-xs text-gray-500 flex items-center gap-1.5">
            <Calendar class="w-3 h-3" />
            {{ formatDate(item.published_at) }}
          </span>
        </div>

        <!-- Title -->
        <h3 class="text-lg font-semibold text-white mb-3 group-hover:text-gold-400
                   transition-colors duration-300 leading-snug">
          {{ item.title }}
        </h3>

        <!-- Excerpt -->
        <p class="text-sm text-gray-400 leading-relaxed flex-1">{{ item.excerpt }}</p>

        <!-- Footer -->
        <div class="mt-5 pt-5 border-t border-gold-500/10 group-hover:border-gold-500/25 transition-colors">
          <button class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium
                         group-hover:gap-3 transition-all duration-300 group/btn">
            Read More
            <ArrowRight class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform duration-200" />
          </button>
        </div>
      </article>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
