<script setup>
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import {
  LayoutDashboard, Calendar, Music, Newspaper, BookOpen,
  Building2, Users, Mail, LogOut, Menu, X, Music2, ChevronRight, House, Type,
} from 'lucide-vue-next'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()
const sidebarOpen = ref(true)

const nav = [
  { name: 'Dashboard',    to: '/',           icon: LayoutDashboard },
  { name: 'Home Page',    to: '/home',       icon: House },
  { name: 'Schedule',     to: '/schedule',   icon: Calendar },
  { name: 'Events',      to: '/events',    icon: Music },
  { name: 'News',         to: '/news',       icon: Newspaper },
  { name: 'Courses',      to: '/courses',    icon: BookOpen },
  { name: 'Facilities',   to: '/facilities', icon: Building2 },
  { name: 'Teachers',     to: '/teachers',   icon: Users },
  { name: 'Contact',      to: '/contact',    icon: Mail },
  { name: 'Fonts',        to: '/fonts',      icon: Type },
]

const pageTitle = computed(() => nav.find(n => n.to === route.path)?.name ?? 'Admin')

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-gray-950">

    <!-- ─── Sidebar ─── -->
    <aside
      class="flex flex-col bg-gray-900 border-r border-white/8 transition-all duration-300 shrink-0"
      :class="sidebarOpen ? 'w-60' : 'w-16'"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 h-16 border-b border-white/8 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600
                    flex items-center justify-center shrink-0 shadow-lg shadow-gold-500/20">
          <Music2 class="w-5 h-5 text-gray-950" stroke-width="2.5" />
        </div>
        <transition name="fade">
          <div v-if="sidebarOpen" class="overflow-hidden">
            <p class="font-serif font-bold text-gold-400 leading-none text-sm">Resonanz</p>
            <p class="text-[10px] text-gray-500 tracking-wider uppercase">Admin Panel</p>
          </div>
        </transition>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="nav-link"
          :class="{ 'active': route.path === item.to }"
          :title="!sidebarOpen ? item.name : undefined"
        >
          <component :is="item.icon" class="w-4.5 h-4.5 w-[18px] h-[18px] shrink-0" />
          <transition name="fade">
            <span v-if="sidebarOpen" class="truncate">{{ item.name }}</span>
          </transition>
        </RouterLink>
      </nav>

      <!-- User / logout -->
      <div class="px-2 py-3 border-t border-white/8 shrink-0">
        <button
          @click="handleLogout"
          class="nav-link w-full hover:text-red-400 hover:bg-red-500/8"
          :title="!sidebarOpen ? 'Sign Out' : undefined"
        >
          <LogOut class="w-[18px] h-[18px] shrink-0" />
          <transition name="fade">
            <span v-if="sidebarOpen">Sign Out</span>
          </transition>
        </button>
      </div>
    </aside>

    <!-- ─── Main ─── -->
    <div class="flex flex-col flex-1 overflow-hidden">

      <!-- Topbar -->
      <header class="flex items-center justify-between h-16 px-6 border-b border-white/8 bg-gray-900/60 backdrop-blur-sm shrink-0">
        <div class="flex items-center gap-4">
          <button @click="sidebarOpen = !sidebarOpen" class="btn-icon">
            <X    v-if="sidebarOpen" class="w-5 h-5" />
            <Menu v-else             class="w-5 h-5" />
          </button>
          <!-- Breadcrumb -->
          <div class="flex items-center gap-1.5 text-sm text-gray-400">
            <span>Admin</span>
            <ChevronRight class="w-3.5 h-3.5" />
            <span class="text-white font-medium">{{ pageTitle }}</span>
          </div>
        </div>

        <!-- User pill -->
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600
                      flex items-center justify-center text-gray-950 font-bold text-xs font-serif">
            {{ auth.userInitials }}
          </div>
          <div class="hidden sm:block text-right">
            <p class="text-xs font-semibold text-white leading-none">{{ auth.user?.name }}</p>
            <p class="text-[10px] text-gold-400 mt-0.5 capitalize">{{ auth.user?.role }}</p>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView v-slot="{ Component }">
          <transition name="slide-up" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </RouterView>
      </main>
    </div>

  </div>
</template>
