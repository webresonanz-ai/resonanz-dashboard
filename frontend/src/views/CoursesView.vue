<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Clock, Users, Award, Check, Sparkles, AlertCircle, RefreshCw } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const { data, loading, error, fetch } = useApi('/api/courses')
onMounted(fetch)

const courses = computed(() => data.value ?? [])

function formatPrice(p) {
  if (p === undefined || p === null) return '$0'
  const n = parseFloat(p)
  return Number.isInteger(n) ? `$${n}` : `$${n.toFixed(2)}`
}

function getFeatures(raw) {
  if (Array.isArray(raw)) return raw
  if (typeof raw === 'string') {
    try { return JSON.parse(raw) } catch { return [] }
  }
  return []
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal text-center">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">{{ $t('courses.eyebrow') }}</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        {{ $t('courses.titleA') }} <span class="gold-text">{{ $t('courses.titleHighlight') }}</span>
      </h1>
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
      </div>
      <p class="text-gray-400 max-w-2xl mx-auto">
        {{ $t('courses.subtitle') }}
      </p>
    </div>

    <!-- ─── Loading ─── -->
    <div v-if="loading" class="grid md:grid-cols-3 gap-6">
      <div v-for="n in 3" :key="n"
           class="glass-card p-8 space-y-4"
           style="animation: pulse 1.8s ease-in-out infinite;">
        <div class="h-5 bg-white/8 rounded-full w-2/3"></div>
        <div class="h-3 bg-white/5 rounded-full w-1/3"></div>
        <div class="h-10 bg-white/8 rounded-full w-1/2 mt-4"></div>
        <div class="space-y-2 mt-4">
          <div v-for="m in 4" :key="m" class="h-3 bg-white/5 rounded-full"></div>
        </div>
        <div class="h-11 bg-white/8 rounded-xl mt-6"></div>
      </div>
    </div>

    <!-- ─── Error ─── -->
    <div v-else-if="error" class="glass-card p-10 text-center border-red-500/20">
      <AlertCircle class="w-10 h-10 text-red-400/60 mx-auto mb-3" />
      <p class="text-gray-400 text-sm mb-4">{{ error }}</p>
      <button @click="fetch"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold-gradient text-maroon-950
                     font-semibold rounded-xl text-sm btn-magnetic">
        <RefreshCw class="w-4 h-4" /> {{ $t('common.retry') }}
      </button>
    </div>

    <!-- ─── Empty ─── -->
    <div v-else-if="!courses.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">{{ $t('courses.empty') }}</p>
    </div>

    <!-- ─── Cards ─── -->
    <div v-else class="grid md:grid-cols-3 gap-6 items-start">
      <div
        v-for="(course, i) in courses"
        :key="course.id"
        class="reveal glass-card p-8 relative transition-all duration-500 group"
        :class="[
          course.is_featured
            ? 'border-gold-500/60 shadow-gold-lg md:-translate-y-6 hover:shadow-gold-lg animated-border'
            : 'hover:border-gold-500/40 card-lift',
          `delay-${(i + 1) * 100}`
        ]"
      >
        <!-- Most popular badge -->
        <div v-if="course.is_featured"
             class="absolute -top-3.5 left-1/2 -translate-x-1/2 flex items-center gap-1.5
                    px-4 py-1 bg-gold-gradient text-maroon-950 text-xs font-bold rounded-full
                    shadow-gold glow-pulse">
          <Sparkles class="w-3 h-3" />
          {{ $t('courses.mostPopular') }}
        </div>

        <!-- Decorative corner for featured -->
        <div v-if="course.is_featured"
             class="absolute top-0 right-0 w-20 h-20 pointer-events-none" aria-hidden="true">
          <div class="absolute top-4 right-4 w-12 h-12 bg-gold-500/5 rounded-full blur-xl"></div>
        </div>

        <h3 class="text-2xl font-serif font-bold text-white mb-1 group-hover:text-gold-200 transition-colors">
          {{ course.name }}
        </h3>
        <p class="text-sm text-gray-400 mb-6">{{ $t('courses.level') }}: {{ course.level }}</p>

        <!-- Price -->
        <div class="flex items-baseline gap-2 mb-6">
          <span class="text-4xl font-bold gold-text font-serif">{{ formatPrice(course.price) }}</span>
          <span class="text-gray-400 text-sm">{{ course.period }}</span>
        </div>

        <!-- Meta -->
        <div class="space-y-3 mb-6 pb-6 border-b border-gold-500/20">
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Clock class="w-4 h-4 text-gold-400" /> {{ course.duration }}
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Users class="w-4 h-4 text-gold-400" /> {{ course.class_size }}
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Award class="w-4 h-4 text-gold-400" /> {{ $t('courses.certificate') }}
          </div>
        </div>

        <!-- Feature list -->
        <ul class="space-y-3 mb-8">
          <li
            v-for="(f, fi) in getFeatures(course.features)"
            :key="fi"
            class="flex items-start gap-3 text-sm text-gray-300"
          >
            <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5
                        bg-gold-500/10 border border-gold-500/30 group-hover:bg-gold-gradient
                        group-hover:border-transparent transition-all duration-300"
                 :style="`transition-delay: ${fi * 50}ms`">
              <Check class="w-3 h-3 text-gold-400 group-hover:text-maroon-950 transition-colors" />
            </div>
            {{ f }}
          </li>
        </ul>

        <!-- CTA -->
        <RouterLink to="/contact"
          class="block w-full py-3 rounded-xl font-semibold transition-all duration-300 text-center
                 relative overflow-hidden group/btn"
          :class="course.is_featured
            ? 'bg-gold-gradient text-maroon-950 shadow-gold hover:shadow-gold-lg btn-magnetic'
            : 'border border-gold-500/40 text-gold-400 hover:bg-gold-500/10 hover:border-gold-500/70'"
        >
          <span v-if="course.is_featured"
                class="absolute inset-0 -translate-x-full group-hover/btn:translate-x-full
                       bg-gradient-to-r from-transparent via-white/25 to-transparent
                       transition-transform duration-500"></span>
          <span class="relative">{{ $t('courses.enrollNow') }}</span>
        </RouterLink>
      </div>
    </div>

    <!-- Financial aid notice -->
    <div class="mt-14 reveal-scale glass-card p-6 text-center delay-300">
      <p class="text-gray-300">
        <span class="gold-text font-semibold">{{ $t('courses.aidHighlight') }}</span>
        {{ $t('courses.aidText') }}
        <RouterLink to="/contact"
                    class="text-gold-400 hover:text-gold-300 underline underline-offset-2 ml-1 transition-colors">
          {{ $t('common.contactUs') }}
        </RouterLink>
        {{ $t('courses.aidCta') }}
      </p>
    </div>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
