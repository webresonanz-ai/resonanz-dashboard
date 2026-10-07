<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { useScrollReveal } from '@/composables/useScrollReveal'
import {
  Music, Calendar, BookOpen, Users, Award, Bell,
  Settings, LogOut, ArrowRight, Sparkles,
} from 'lucide-vue-next'

const { t } = useI18n()

useScrollReveal()

const auth = useAuthStore()

const quickLinks = computed(() => [
  { label: t('dashboard.links.schedule'), to: '/schedule', icon: Calendar,  desc: t('dashboard.links.scheduleDesc') },
  { label: t('dashboard.links.concerts'), to: '/concert',  icon: Music,     desc: t('dashboard.links.concertsDesc') },
  { label: t('dashboard.links.courses'),  to: '/courses',  icon: BookOpen,  desc: t('dashboard.links.coursesDesc') },
  { label: t('dashboard.links.teachers'), to: '/teachers', icon: Users,     desc: t('dashboard.links.teachersDesc') },
])

const stats = computed(() => [
  { label: t('dashboard.stats.classes'),       value: '3', sub: t('dashboard.stats.enrolled'),     icon: BookOpen },
  { label: t('dashboard.stats.concerts'),      value: '2', sub: t('dashboard.stats.upcoming'),     icon: Music },
  { label: t('dashboard.stats.awards'),        value: '1', sub: t('dashboard.stats.earned'),       icon: Award },
  { label: t('dashboard.stats.notifications'), value: '5', sub: t('dashboard.stats.new'),          icon: Bell },
])
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- ─── Welcome header ─── -->
    <div class="reveal mb-10">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">
            {{ $t('dashboard.eyebrow') }}
          </span>
          <h1 class="text-3xl sm:text-4xl font-bold mt-2">
            {{ $t('dashboard.welcome') }} <span class="gold-text">{{ auth.userName }}</span> 👋
          </h1>
          <p class="text-gray-400 mt-1.5">{{ $t('dashboard.subtitle') }}</p>
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
        v-for="(stat, i) in stats"
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
        {{ $t('dashboard.quickAccess') }} <span class="gold-text">{{ $t('dashboard.quickAccessHighlight') }}</span>
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
            {{ $t('common.go') }} <ArrowRight class="w-3.5 h-3.5" />
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- ─── Account section ─── -->
    <div class="reveal-scale glass-card p-8 animated-border delay-200">
      <h2 class="text-xl font-serif font-bold text-white mb-6">
        {{ $t('dashboard.accountTitle') }} <span class="gold-text">{{ $t('dashboard.accountHighlight') }}</span>
      </h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="flex items-center gap-3 p-4 rounded-xl bg-white/3 border border-gold-500/10
                    hover:border-gold-500/25 transition-all group cursor-default">
          <Settings class="w-5 h-5 text-gold-400/70 group-hover:text-gold-400 transition-colors" />
          <div>
            <p class="text-sm font-medium text-white">{{ $t('dashboard.profile') }}</p>
            <p class="text-xs text-gray-500">{{ $t('dashboard.profileDesc') }}</p>
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
              {{ $t('dashboard.signOut') }}
            </p>
            <p class="text-xs text-gray-500">{{ $t('dashboard.signOutDesc') }}</p>
          </div>
        </button>
      </div>
    </div>

  </div>
</template>
