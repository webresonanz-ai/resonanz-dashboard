<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useScrollReveal } from '@/composables/useScrollReveal'
import {
  Music, Calendar, BookOpen, Users, Award, Bell,
  Settings, LogOut, ArrowRight, Sparkles,
} from 'lucide-vue-next'

useScrollReveal()

const auth = useAuthStore()

const quickLinks = [
  { label: 'Schedule',     to: '/schedule',     icon: Calendar,  desc: 'View class timetable' },
  { label: 'Concerts',     to: '/concert',      icon: Music,     desc: 'Upcoming performances' },
  { label: 'Courses',      to: '/courses',      icon: BookOpen,  desc: 'Browse programs & fees' },
  { label: 'Teachers',     to: '/teachers',     icon: Users,     desc: 'Meet the faculty' },
]
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- ─── Welcome header ─── -->
    <div class="reveal mb-10">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">
            Member Dashboard
          </span>
          <h1 class="text-3xl sm:text-4xl font-bold mt-2">
            Welcome back, <span class="gold-text">{{ auth.userName }}</span> 👋
          </h1>
          <p class="text-gray-400 mt-1.5">Here's what's happening at Resonanz.</p>
        </div>

        <!-- Avatar -->
        <div class="flex items-center gap-3">
          <div
            class="w-14 h-14 rounded-xl bg-gold-gradient flex items-center justify-center
                   shadow-gold text-maroon-950 font-serif font-bold text-xl"
          >
            {{ auth.userInitials }}
          </div>
          <div>
            <p class="font-semibold text-white text-sm">{{ auth.userName }}</p>
            <p class="text-xs text-gold-400 capitalize">{{ auth.user?.role }}</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 mt-5">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
    </div>

    <!-- ─── Stats ─── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
      <div
        v-for="(stat, i) in [
          { label: 'Classes', value: '3', sub: 'enrolled', icon: BookOpen },
          { label: 'Concerts', value: '2', sub: 'upcoming', icon: Music },
          { label: 'Awards', value: '1', sub: 'earned', icon: Award },
          { label: 'Notifications', value: '5', sub: 'new', icon: Bell },
        ]"
        :key="stat.label"
        class="reveal glass-card p-5 card-lift animated-border group cursor-default"
        :class="`delay-${(i + 1) * 100}`"
      >
        <div class="flex items-start justify-between mb-3">
          <div
            class="w-10 h-10 rounded-xl bg-gold-500/10 border border-gold-500/20
                   flex items-center justify-center
                   group-hover:bg-gold-gradient group-hover:shadow-gold
                   transition-all duration-300 group-hover:scale-110"
          >
            <component :is="stat.icon"
                       class="w-5 h-5 text-gold-400 group-hover:text-maroon-950 transition-colors" />
          </div>
        </div>
        <p class="text-3xl font-bold gold-text font-serif">{{ stat.value }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ stat.label }} · {{ stat.sub }}</p>
      </div>
    </div>

    <!-- ─── Quick links grid ─── -->
    <div class="mb-10">
      <h2 class="reveal text-xl font-serif font-bold text-white mb-5">
        Quick <span class="gold-text">Access</span>
      </h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <RouterLink
          v-for="(link, i) in quickLinks"
          :key="link.label"
          :to="link.to"
          class="reveal glass-card p-6 card-lift animated-border group text-left"
          :class="`delay-${(i + 1) * 80}`"
        >
          <div
            class="w-12 h-12 rounded-xl bg-gold-500/10 border border-gold-500/20
                   flex items-center justify-center mb-4
                   group-hover:bg-gold-gradient group-hover:shadow-gold
                   transition-all duration-300 group-hover:scale-110 group-hover:rotate-6"
          >
            <component :is="link.icon"
                       class="w-6 h-6 text-gold-400 group-hover:text-maroon-950 transition-colors" />
          </div>
          <h3 class="font-semibold text-white mb-1 group-hover:text-gold-300 transition-colors">
            {{ link.label }}
          </h3>
          <p class="text-xs text-gray-400 mb-3">{{ link.desc }}</p>
          <span class="inline-flex items-center gap-1 text-xs text-gold-400 font-medium
                       group-hover:gap-2 transition-all">
            Go <ArrowRight class="w-3.5 h-3.5" />
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- ─── Account section ─── -->
    <div class="reveal-scale glass-card p-8 animated-border delay-200">
      <h2 class="text-xl font-serif font-bold text-white mb-6">
        Account <span class="gold-text">Settings</span>
      </h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="flex items-center gap-3 p-4 rounded-xl bg-white/3 border border-gold-500/10
                    hover:border-gold-500/25 transition-all group cursor-default">
          <Settings class="w-5 h-5 text-gold-400/70 group-hover:text-gold-400 transition-colors" />
          <div>
            <p class="text-sm font-medium text-white">Profile Settings</p>
            <p class="text-xs text-gray-500">Manage your details</p>
          </div>
        </div>
        <button
          @click="auth.logout().then(() => $router.push('/login'))"
          class="flex items-center gap-3 p-4 rounded-xl bg-white/3 border border-red-500/10
                 hover:border-red-500/30 hover:bg-red-500/5 transition-all group text-left w-full"
        >
          <LogOut class="w-5 h-5 text-red-400/60 group-hover:text-red-400 transition-colors" />
          <div>
            <p class="text-sm font-medium text-white group-hover:text-red-300 transition-colors">
              Sign Out
            </p>
            <p class="text-xs text-gray-500">End your session</p>
          </div>
        </button>
      </div>
    </div>

  </div>
</template>
