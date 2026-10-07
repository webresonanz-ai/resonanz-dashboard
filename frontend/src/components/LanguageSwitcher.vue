<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Globe, Check } from 'lucide-vue-next'
import { SUPPORTED_LOCALES, setLocale } from '@/i18n'

const { locale } = useI18n()
const open = ref(false)

const current = computed(
  () => SUPPORTED_LOCALES.find((l) => l.code === locale.value) ?? SUPPORTED_LOCALES[0],
)

function pick(code) {
  setLocale(code)
  open.value = false
}

function onOutside(e) {
  if (!e.target.closest('#lang-switcher')) open.value = false
}

function onKey(e) {
  if (e.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', onOutside)
  document.addEventListener('keydown', onKey)
})
onUnmounted(() => {
  document.removeEventListener('click', onOutside)
  document.removeEventListener('keydown', onKey)
})
</script>

<template>
  <div id="lang-switcher" class="relative">
    <button
      @click="open = !open"
      class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium
             text-gold-400 border border-gold-500/30
             hover:bg-gold-500/10 hover:border-gold-500/60 transition-all duration-300"
      aria-haspopup="listbox"
      :aria-expanded="open"
      :aria-label="$t('nav.language')"
    >
      <Globe class="w-4 h-4" />
      <span>{{ current.label }}</span>
    </button>

    <transition name="dropdown">
      <div
        v-if="open"
        class="absolute right-0 mt-2 w-40 glass-card py-1.5 shadow-gold border-gold-500/30 overflow-hidden z-50"
        role="listbox"
      >
        <button
          v-for="l in SUPPORTED_LOCALES"
          :key="l.code"
          @click="pick(l.code)"
          role="option"
          :aria-selected="l.code === locale"
          class="w-full flex items-center justify-between px-4 py-2.5 text-sm transition-all
                 hover:bg-gold-500/8"
          :class="l.code === locale ? 'text-gold-400' : 'text-gray-300 hover:text-gold-400'"
        >
          <span class="flex items-center gap-2.5">
            <span class="text-xs font-bold w-6">{{ l.label }}</span>
            <span>{{ l.name }}</span>
          </span>
          <Check v-if="l.code === locale" class="w-4 h-4" />
        </button>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.dropdown-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from   { opacity: 0; transform: translateY(-8px) scale(0.97); }
.dropdown-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
