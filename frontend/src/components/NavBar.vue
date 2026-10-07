<script setup>
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'
import { useAppStore } from '@/stores/appStore'
import { useAuthStore } from '@/stores/authStore'
import LanguageSwitcher from './LanguageSwitcher.vue'
import { Menu, X, Music2, LayoutDashboard, LogOut, ChevronDown } from 'lucide-vue-next'
import { watch, ref, onMounted, onUnmounted } from 'vue'

const { t } = useI18n()
const store    = useAppStore()
const auth     = useAuthStore()
const route    = useRoute()
const router   = useRouter()
const scrolled = ref(false)
const userMenuOpen = ref(false)

const navLinks = computed(() => [
  { name: t('nav.home'), path: '/' },
  { name: t('nav.schedule'), path: '/schedule' },
  { name: t('nav.event'), path: '/event' },
  { name: t('nav.news'), path: '/news' },
  { name: t('nav.courses'), path: '/courses' },
  { name: t('nav.facilitation'), path: '/facilitation' },
  { name: t('nav.teachers'), path: '/teachers' },
  { name: t('nav.contact'), path: '/contact' },
])

watch(() => route.path, () => {
  store.closeMobileMenu()
  userMenuOpen.value = false
})

const handleScroll = () => { scrolled.value = window.scrollY > 24 }

// Close user menu on outside click
const handleClickOutside = (e) => {
  if (!e.target.closest('#user-menu-wrapper')) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  document.addEventListener('click', handleClickOutside)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
})

async function handleLogout() {
  userMenuOpen.value = false
  await auth.logout()
  router.push('/login')
}
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
            class="relative w-11 h-11 rounded-xl bg-gold-gradient flex items-center justify-center
                   shadow-gold group-hover:shadow-gold-lg transition-all duration-300
                   group-hover:scale-110 group-hover:rotate-6"
          >
            <img src="/logo_resonanz_square.webp" alt="Logo" class="w-7 h-7 relative z-10" />
          </div>
          <div class="leading-tight">
            <span class="block font-serif text-xl font-bold gold-text group-hover:gold-shimmer transition-all">
              The Resonanz
            </span>
            <span class="block text-[10px] tracking-[0.2em] uppercase text-gold-500/70
                         transition-colors group-hover:text-gold-500/100">
              Music Studio
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
            <span
              class="absolute inset-0 bg-gold-500/0 group-hover:bg-gold-500/8 rounded-lg
                     transition-all duration-300 ease-out"
            ></span>
            <span class="relative z-10">{{ link.name }}</span>
            <span
              class="absolute bottom-0 left-1/2 -translate-x-1/2 h-0.5 bg-gold-gradient
                     transition-all duration-350 ease-out rounded-full"
              :class="route.path === link.path ? 'w-8' : 'w-0 group-hover:w-8'"
            ></span>
          </RouterLink>
        </div>

        <!-- ─── Right side: auth CTA or user menu ─── -->
        <div class="flex items-center gap-3">
          <LanguageSwitcher class="hidden sm:block" />

          <!-- Guest: Join Us -->
          <template v-if="!auth.isAuthenticated">
            <RouterLink
              to="/login"
              class="hidden lg:inline-flex items-center px-4 py-2 text-sm font-medium
                     text-gold-400 border border-gold-500/30 rounded-lg
                     hover:bg-gold-500/10 hover:border-gold-500/60 transition-all duration-300"
            >
              {{ $t('nav.signIn') }}
            </RouterLink>
            <RouterLink
              to="/register"
              class="hidden lg:inline-flex items-center px-5 py-2.5 bg-gold-gradient
                     text-maroon-950 font-semibold text-sm rounded-lg shadow-gold
                     btn-magnetic relative overflow-hidden group"
            >
              <span
                class="absolute inset-0 -translate-x-full group-hover:translate-x-full
                       bg-gradient-to-r from-transparent via-white/25 to-transparent
                       transition-transform duration-600 ease-in-out"
              ></span>
              <span class="relative">{{ $t('nav.joinUs') }}</span>
            </RouterLink>
          </template>

          <!-- Authenticated: avatar + dropdown -->
          <template v-else>
            <div id="user-menu-wrapper" class="hidden lg:block relative">
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl
                       border border-gold-500/25 hover:border-gold-500/50
                       bg-white/3 hover:bg-white/6 transition-all duration-300 group"
                aria-haspopup="true"
                :aria-expanded="userMenuOpen"
              >
                <!-- Initials avatar -->
                <div
                  class="w-8 h-8 rounded-lg bg-gold-gradient flex items-center justify-center
                         text-maroon-950 font-serif font-bold text-sm shadow-gold
                         group-hover:shadow-gold-lg group-hover:scale-105 transition-all"
                >
                  {{ auth.userInitials }}
                </div>
                <span class="text-sm font-medium text-gray-200 max-w-[100px] truncate">
                  {{ auth.userName }}
                </span>
                <ChevronDown
                  class="w-4 h-4 text-gold-400/70 transition-transform duration-300"
                  :class="userMenuOpen ? 'rotate-180' : ''"
                />
              </button>

              <!-- Dropdown -->
              <transition name="dropdown">
                <div
                  v-if="userMenuOpen"
                  class="absolute right-0 mt-2 w-52 glass-card py-1.5 shadow-gold
                         border-gold-500/30 overflow-hidden"
                  role="menu"
                >
                  <!-- User info -->
                  <div class="px-4 py-3 border-b border-gold-500/15">
                    <p class="text-sm font-semibold text-white truncate">{{ auth.userName }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
                  </div>

                  <!-- Links -->
                  <RouterLink
                    to="/dashboard"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-300
                           hover:text-gold-400 hover:bg-gold-500/8 transition-all group"
                    role="menuitem"
                  >
                    <LayoutDashboard
                      class="w-4 h-4 text-gold-400/60 group-hover:text-gold-400 transition-colors"
                    />
                    {{ $t('nav.dashboard') }}
                  </RouterLink>

                  <button
                    @click="handleLogout"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-300
                           hover:text-red-400 hover:bg-red-500/8 transition-all group"
                    role="menuitem"
                  >
                    <LogOut
                      class="w-4 h-4 text-gray-500 group-hover:text-red-400 transition-colors"
                    />
                    {{ $t('nav.signOut') }}
                  </button>
                </div>
              </transition>
            </div>
          </template>

          <!-- Mobile menu toggle -->
          <button
            @click="store.toggleMobileMenu"
            class="lg:hidden p-2 rounded-lg text-gold-400 hover:bg-gold-500/10
                   transition-all active:scale-90"
            :aria-label="$t('nav.toggleMenu')"
          >
            <transition name="icon-swap" mode="out-in">
              <X    v-if="store.isMobileMenuOpen" class="w-6 h-6" key="x" />
              <Menu v-else                         class="w-6 h-6" key="menu" />
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
            class="block px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200"
            :class="route.path === link.path
              ? 'bg-gold-500/10 text-gold-400 border-l-2 border-gold-400'
              : 'text-gray-300 hover:bg-gold-500/5 hover:text-gold-400 hover:translate-x-1'"
            :style="`animation-delay: ${i * 50}ms`"
          >
            {{ link.name }}
          </RouterLink>

          <!-- Mobile auth section -->
          <div class="pt-3 border-t border-gold-500/15 mt-2 space-y-1">
            <div class="px-4 py-2 sm:hidden">
              <LanguageSwitcher />
            </div>
            <template v-if="!auth.isAuthenticated">
              <RouterLink to="/login"
                class="block px-4 py-3 rounded-lg text-sm font-medium text-gray-300
                       hover:bg-gold-500/5 hover:text-gold-400 transition-all">
                {{ $t('nav.signIn') }}
              </RouterLink>
              <RouterLink to="/register"
                class="block px-4 py-3 bg-gold-gradient text-maroon-950 font-semibold
                       text-sm rounded-lg text-center shadow-gold">
                {{ $t('nav.createAccount') }}
              </RouterLink>
            </template>
            <template v-else>
              <div class="px-4 py-2 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-gold-gradient flex items-center justify-center
                            text-maroon-950 font-serif font-bold text-sm">
                  {{ auth.userInitials }}
                </div>
                <div>
                  <p class="text-sm font-medium text-white">{{ auth.userName }}</p>
                  <p class="text-xs text-gold-400 capitalize">{{ auth.user?.role }}</p>
                </div>
              </div>
              <RouterLink to="/dashboard"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-300
                       hover:bg-gold-500/5 hover:text-gold-400 transition-all">
                <LayoutDashboard class="w-4 h-4 text-gold-400/60" /> {{ $t('nav.dashboard') }}
              </RouterLink>
              <button
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm
                       text-red-400/80 hover:bg-red-500/8 hover:text-red-400 transition-all text-left">
                <LogOut class="w-4 h-4" /> {{ $t('nav.signOut') }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<style scoped>
.mobile-menu-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); max-height: 700px; }
.mobile-menu-leave-active { transition: all 0.25s cubic-bezier(0.7, 0, 1, 1); max-height: 700px; }
.mobile-menu-enter-from,
.mobile-menu-leave-to     { max-height: 0; opacity: 0; overflow: hidden; }

.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.2s ease; }
.icon-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.icon-swap-leave-to   { opacity: 0; transform: rotate(90deg) scale(0.7); }

.dropdown-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from   { opacity: 0; transform: translateY(-8px) scale(0.97); }
.dropdown-leave-to     { opacity: 0; transform: translateY(-4px); }

.duration-600 { transition-duration: 600ms; }
</style>
