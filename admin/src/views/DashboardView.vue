<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import PageHeader from '@/components/PageHeader.vue'
import { Calendar, Music, Newspaper, BookOpen, Building2, Users, Mail, TrendingUp } from 'lucide-vue-next'

const auth  = useAuthStore()
const stats = ref([])
const unread = ref(0)
const loading = ref(true)

const modules = [
  { name: 'Schedule',   to: '/schedule',   icon: Calendar,   color: 'from-blue-500/20 to-blue-600/10',   border: 'border-blue-500/20' },
  { name: 'Events',     to: '/events',    icon: Music,      color: 'from-purple-500/20 to-purple-600/10', border: 'border-purple-500/20' },
  { name: 'News',       to: '/news',       icon: Newspaper,  color: 'from-emerald-500/20 to-emerald-600/10', border: 'border-emerald-500/20' },
  { name: 'Courses',    to: '/courses',    icon: BookOpen,   color: 'from-gold-500/20 to-gold-600/10',    border: 'border-gold-500/20' },
  { name: 'Facilities', to: '/facilities', icon: Building2,  color: 'from-cyan-500/20 to-cyan-600/10',    border: 'border-cyan-500/20' },
  { name: 'Teachers',   to: '/teachers',   icon: Users,      color: 'from-pink-500/20 to-pink-600/10',    border: 'border-pink-500/20' },
  { name: 'Contact',    to: '/contact',    icon: Mail,       color: 'from-orange-500/20 to-orange-600/10', border: 'border-orange-500/20' },
]

onMounted(async () => {
  try {
    const endpoints = ['/api/admin/schedule','/api/admin/events','/api/admin/news',
                       '/api/admin/courses','/api/admin/facilities','/api/admin/teachers']
    const labels = ['Classes','Events','News Articles','Courses','Facilities','Teachers']

    const results = await Promise.all(endpoints.map(e => auth.apiFetch(e).catch(() => ({ data: [] }))))
    stats.value = results.map((r, i) => ({ label: labels[i], value: r.data?.length ?? 0 }))

    const contactStats = await auth.apiFetch('/api/admin/contact/stats').catch(() => ({ data: { unread: 0 } }))
    unread.value = contactStats.data?.unread ?? 0
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHeader :title="`Welcome, ${auth.user?.name}`" subtitle="Here's your content overview" />

    <!-- Stats row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
      <div v-for="(s, i) in stats" :key="i"
           class="admin-card p-4 text-center">
        <p class="text-2xl font-bold text-white font-serif">
          <span v-if="loading" class="inline-block w-6 h-6 rounded bg-white/10 animate-pulse"></span>
          <span v-else>{{ s.value }}</span>
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ s.label }}</p>
      </div>
    </div>

    <!-- Module grid -->
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Manage Content</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <RouterLink
        v-for="m in modules"
        :key="m.to"
        :to="m.to"
        class="admin-card p-5 flex items-center gap-4 group hover:border-white/15 transition-all duration-200"
        :class="m.border"
      >
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center shrink-0"
             :class="m.color">
          <component :is="m.icon" class="w-5 h-5 text-white/80 group-hover:text-white transition-colors" />
        </div>
        <div>
          <p class="font-semibold text-white text-sm">{{ m.name }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Manage records</p>
        </div>
        <span v-if="m.name === 'Contact' && unread > 0"
              class="ml-auto badge badge-gold">{{ unread }}</span>
      </RouterLink>
    </div>
  </div>
</template>
