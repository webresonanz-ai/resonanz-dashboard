<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Mail, AlertCircle, RefreshCw, X } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'

useScrollReveal()

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const { data, loading, error, fetch } = useApi('/api/teachers')
onMounted(fetch)

const teachers = computed(() => data.value ?? [])

function resolvePhoto(t) {
  const raw = t?.photo ?? ''
  const s = String(raw).trim()
  if (!s) return ''
  return /^https?:\/\//i.test(s) ? s : `${API_BASE}${s.startsWith('/') ? s : `/${s}`}`
}

// ─── Detail modal ───
const selected = ref(null)
function openDetail(t) {
  selected.value = t
  document.body.style.overflow = 'hidden'
}
function closeDetail() {
  selected.value = null
  document.body.style.overflow = ''
}
function onKey(e) {
  if (e.key === 'Escape') closeDetail()
}
onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">{{ $t('teachers.eyebrow') }}</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        {{ $t('teachers.titleA') }} <span class="gold-text">{{ $t('teachers.titleHighlight') }}</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        {{ $t('teachers.subtitle') }}
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
        <RefreshCw class="w-4 h-4" /> {{ $t('common.retry') }}
      </button>
    </div>

    <!-- ─── Empty ─── -->
    <div v-else-if="!teachers.length" class="glass-card p-16 text-center">
      <p class="text-gray-500">{{ $t('teachers.empty') }}</p>
    </div>

    <!-- ─── Grid ─── -->
    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(t, i) in teachers"
        :key="t.id"
        role="button"
        tabindex="0"
        @click="openDetail(t)"
        @keydown.enter="openDetail(t)"
        @keydown.space.prevent="openDetail(t)"
        class="reveal glass-card p-6 text-center card-lift animated-border group cursor-pointer"
        :class="`delay-${(i % 4) * 100 + 100}`"
      >
        <!-- Avatar with animated ring -->
        <div class="relative w-24 h-24 mx-auto mb-5">
          <div class="absolute inset-0 rounded-full bg-gold-gradient opacity-0 blur-md
                      group-hover:opacity-40 transition-opacity duration-500 scale-110"></div>
          <div class="absolute inset-0 rounded-full border-2 border-dashed border-gold-500/20
                      group-hover:border-gold-500/50 transition-all duration-500"
               style="animation: spin-slow 12s linear infinite;"></div>
          <div class="relative w-full h-full rounded-full overflow-hidden bg-gold-gradient flex items-center justify-center
                      shadow-gold group-hover:shadow-gold-lg transition-all duration-300 group-hover:scale-105">
            <img v-if="resolvePhoto(t)" :src="resolvePhoto(t)" :alt="t.name" loading="lazy"
                 class="absolute inset-0 w-full h-full object-cover"
                 @error="$event.target.style.display = 'none'" />
            <span v-else class="text-2xl font-serif font-bold text-maroon-950">{{ t.initials }}</span>
          </div>
        </div>

        <h3 class="text-lg font-serif font-bold text-white mb-1 group-hover:text-gold-300 transition-colors">
          {{ t.name }}
        </h3>
        <p class="text-xs text-gold-400 font-medium mb-3 tracking-wide">{{ t.role }}</p>
        <p class="text-sm text-gray-400 leading-relaxed mb-4 line-clamp-2">{{ t.bio }}</p>
        <span class="inline-flex items-center gap-1 text-xs font-medium text-gold-400/80 group-hover:text-gold-300 transition-colors">
          {{ $t('common.readMore') }} →
        </span>

        <!-- Contact link — mailto if email available -->
        <div class="mt-3" @click.stop>
          <a v-if="t.email"
             :href="`mailto:${t.email}`"
             @click.stop
             class="inline-flex items-center gap-2 text-sm text-gold-400 font-medium
                    hover:text-gold-300 group/btn transition-all">
            <Mail class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
            <span class="group-hover/btn:translate-x-0.5 transition-transform">{{ $t('common.contact') }}</span>
          </a>
        </div>
      </div>
    </div>

    <!-- ─── Detail modal ─── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeDetail"></div>
          <div role="dialog" aria-modal="true"
               class="relative glass-card max-w-md w-full p-8 text-center border-gold-500/30 max-h-[90vh] overflow-y-auto">
            <button @click="closeDetail" aria-label="Close"
                    class="absolute top-4 right-4 w-8 h-8 rounded-lg flex items-center justify-center
                           text-gray-400 hover:text-gold-300 hover:bg-white/5 transition-colors">
              <X class="w-5 h-5" />
            </button>

            <div class="relative w-32 h-32 mx-auto mb-5">
              <div class="absolute inset-0 rounded-full border-2 border-dashed border-gold-500/30"></div>
              <div class="relative w-full h-full rounded-full overflow-hidden bg-gold-gradient flex items-center justify-center shadow-gold">
                <img v-if="resolvePhoto(selected)" :src="resolvePhoto(selected)" :alt="selected.name"
                     class="absolute inset-0 w-full h-full object-cover" />
                <span v-else class="text-4xl font-serif font-bold text-maroon-950">{{ selected.initials }}</span>
              </div>
            </div>

            <h3 class="text-2xl font-serif font-bold text-white mb-1">{{ selected.name }}</h3>
            <p class="text-sm text-gold-400 font-medium tracking-wide mb-5">{{ selected.role }}</p>
            <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-line text-left mb-6">{{ selected.bio }}</p>

            <a v-if="selected.email" :href="`mailto:${selected.email}`"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold-gradient text-maroon-950
                      font-semibold rounded-xl text-sm btn-magnetic">
              <Mail class="w-4 h-4" /> {{ selected.email }}
            </a>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
