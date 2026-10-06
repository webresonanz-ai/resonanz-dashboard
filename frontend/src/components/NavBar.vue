<script setup>
import { RouterLink, useRoute } from 'vue-router'
import { useAppStore } from '@/stores/appStore'
import { Menu, X, Music2 } from 'lucide-vue-next'
import { watch, ref, onMounted, onUnmounted } from 'vue'

const store = useAppStore()
const route = useRoute()
const scrolled = ref(false)

const navLinks = [
  { name: 'Home', path: '/' },
  { name: 'Schedule', path: '/schedule' },
  { name: 'Concert', path: '/concert' },
  { name: 'News', path: '/news' },
  { name: 'Courses & Fee', path: '/courses' },
  { name: 'Facilitation', path: '/facilitation' },
  { name: 'Teachers', path: '/teachers' },
  { name: 'Contact', path: '/contact' },
]

watch(() => route.path, () => store.closeMobileMenu())

const handleScroll = () => {
  scrolled.value = window.scrollY > 24
}
onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<template>
  <nav
    class="sticky top-0 z-50 backdrop-blur-xl transition-all duration-500"
    :class="scrolled
      ? 'bg-maroon-950/95 border-b border-gold-500/30 shadow-maroon'
      : 'bg-maroon-950/75 border-b border-gold-500/15'"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">

        <!-- ─── Logo ─── -->
        <RouterLink to="/" class="flex items-center gap-3 group">
          <div
            class="relative w-11 h-11 rounded-xl bg-gold-gradient flex items-center justify-center shadow-gold
                   group-hover:shadow-gold-lg transition-all duration-300 group-hover:scale-110 group-hover:rotate-6"
          >
            <!-- Ripple ring on hover -->
            <span
              class="absolute inset-0 rounded-xl bg-gold-400 opacity-0 group-hover:opacity-30
                     group-hover:scale-125 transition-all duration-500"
            ></span>
            <Music2 class="w-6 h-6 text-maroon-950 relative z-10" stroke-width="2.5" />
          </div>
          <div class="leading-tight">
            <span class="block font-serif text-xl font-bold gold-text group-hover:gold-shimmer transition-all">
              Resonanz
            </span>
            <span class="block text-[10px] tracking-[0.2em] uppercase text-gold-500/70 transition-colors group-hover:text-gold-500/100">
              Music Foundation
            </span>
          </div>
        </RouterLink>

        <!-- ─── Desktop nav ─── -->
        <div class="hidden lg:flex items-center gap-1">
          <RouterLink
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="relative px-4 py-2 text-sm font-medium rounded-lg group overflow-hidden
                   transition-colors duration-300"
            :class="route.path === link.path
              ? 'text-gold-400'
              : 'text-gray-300 hover:text-gold-400'"
          >
            <!-- Hover background fill -->
            <span
              class="absolute inset-0 bg-gold-500/0 group-hover:bg-gold-500/8 rounded-lg
                     transition-all duration-300 ease-out"
            ></span>

            <span class="relative z-10">{{ link.name }}</span>

            <!-- Active / hover underline -->
            <span
              class="absolute bottom-0 left-1/2 -translate-x-1/2 h-0.5 bg-gold-gradient
                     transition-all duration-350 ease-out rounded-full"
              :class="route.path === link.path ? 'w-8' : 'w-0 group-hover:w-8'"
            ></span>
          </RouterLink>
        </div>

        <!-- ─── CTA + Mobile toggle ─── -->
        <div class="flex items-center gap-3">
          <RouterLink
            to="/contact"
            class="hidden lg:inline-flex items-center px-5 py-2.5 bg-gold-gradient text-maroon-950
                   font-semibold text-sm rounded-lg shadow-gold btn-magnetic relative overflow-hidden group"
          >
            <!-- Shine sweep -->
            <span
              class="absolute inset-0 -translate-x-full group-hover:translate-x-full
                     bg-gradient-to-r from-transparent via-white/25 to-transparent
                     transition-transform duration-600 ease-in-out"
            ></span>
            <span class="relative">Join Us</span>
          </RouterLink>

          <button
            @click="store.toggleMobileMenu"
            class="lg:hidden p-2 rounded-lg text-gold-400 hover:bg-gold-500/10 transition-all
                   active:scale-90"
            aria-label="Toggle menu"
          >
            <transition name="icon-swap" mode="out-in">
              <X v-if="store.isMobileMenuOpen" class="w-6 h-6" key="x" />
              <Menu v-else class="w-6 h-6" key="menu" />
            </transition>
          </button>
        </div>

      </div>
    </div>

    <!-- ─── Mobile menu ─── -->
    <transition name="mobile-menu">
      <div
        v-if="store.isMobileMenuOpen"
        class="lg:hidden border-t border-gold-500/20 bg-maroon-950/98 backdrop-blur-2xl"
      >
        <div class="px-4 py-4 space-y-1">
          <RouterLink
            v-for="(link, i) in navLinks"
            :key="link.path"
            :to="link.path"
            class="block px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 group"
            :class="route.path === link.path
              ? 'bg-gold-500/10 text-gold-400 border-l-2 border-gold-400'
              : 'text-gray-300 hover:bg-gold-500/5 hover:text-gold-400 hover:translate-x-1'"
            :style="`animation-delay: ${i * 50}ms`"
          >
            {{ link.name }}
          </RouterLink>
          <RouterLink
            to="/contact"
            class="block mt-3 px-4 py-3 bg-gold-gradient text-maroon-950 font-semibold
                   text-sm rounded-lg text-center shadow-gold btn-magnetic"
          >
            Join Us
          </RouterLink>
        </div>
      </div>
    </transition>
  </nav>
</template>

<style scoped>
/* Mobile menu slide */
.mobile-menu-enter-active {
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  max-height: 600px;
}
.mobile-menu-leave-active {
  transition: all 0.25s cubic-bezier(0.7, 0, 1, 1);
  max-height: 600px;
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
}

/* Icon swap animation */
.icon-swap-enter-active,
.icon-swap-leave-active {
  transition: all 0.2s ease;
}
.icon-swap-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.7);
}
.icon-swap-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.7);
}

/* Shine sweep duration */
.duration-600 {
  transition-duration: 600ms;
}
</style>
