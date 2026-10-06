<script setup>
import { RouterLink } from 'vue-router'
import { Music, Award, Users, Calendar, ArrowRight, Sparkles } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useApi } from '@/composables/useApi'
import { ref, computed, onMounted } from 'vue'

useScrollReveal()

// ─── Live counts from API ────────────────────────────────────
const { data: concertsData, fetch: fetchConcerts } = useApi('/api/concerts')
const { data: teachersData, fetch: fetchTeachers } = useApi('/api/teachers')
onMounted(() => { fetchConcerts(); fetchTeachers() })

// Stats: first two values are driven by API counts; last two are fixed brand values
const stats = computed(() => [
  { label: 'Students',   value: 500,                                       suffix: '+', icon: Users    },
  { label: 'Concerts',   value: concertsData.value?.length ?? 120,         suffix: '+', icon: Music    },
  { label: 'Awards',     value: 45,                                        suffix: '',  icon: Award    },
  { label: 'Faculty',    value: teachersData.value?.length   ?? 25,        suffix: '',  icon: Calendar },
])

const features = [
  { title: 'World-Class Faculty',         desc: 'Learn from internationally acclaimed musicians and educators.',    icon: Award },
  { title: 'Performance Opportunities',   desc: 'Regular concerts and recitals in prestigious venues.',             icon: Music },
  { title: 'Personalized Learning',       desc: 'One-on-one lessons tailored to your musical journey.',             icon: Users },
]

// ─── Animated counter ────────────────────────────────────────
const displayStats = ref([0, 0, 0, 0])
const statsVisible = ref(false)
let hasAnimated = false

function runCounters() {
  if (hasAnimated) return
  hasAnimated      = true
  statsVisible.value = true

  stats.value.forEach((stat, i) => {
    const duration  = 1600
    const steps     = 60
    const increment = stat.value / steps
    let current = 0
    let step    = 0
    const timer = setInterval(() => {
      step++
      current = Math.min(Math.round(increment * step), stat.value)
      displayStats.value[i] = current
      if (step >= steps) clearInterval(timer)
    }, duration / steps)
  })
}

onMounted(() => {
  const el = document.querySelector('#stats-section')
  if (!el) return
  const obs = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) { runCounters(); obs.disconnect() }
  }, { threshold: 0.3 })
  obs.observe(el)
})
</script>

<template>
  <div>

    <!-- ═══════════════════════════════════════════ HERO ═══ -->
    <section class="relative py-24 lg:py-36 overflow-hidden">

      <!-- Decorative rings -->
      <div class="absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true">
        <div class="w-[600px] h-[600px] rounded-full border border-gold-500/5 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
        <div class="w-[480px] h-[480px] rounded-full border border-gold-500/8 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
        <div class="w-[340px] h-[340px] rounded-full border border-gold-500/10 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-14 items-center">

          <!-- Left: text -->
          <div>
            <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full
                        bg-gold-500/10 border border-gold-500/30 text-gold-400
                        text-xs font-medium tracking-wider uppercase mb-6">
              <span class="w-1.5 h-1.5 rounded-full bg-gold-400 animate-pulse"></span>
              Est. 2000 · Excellence in Music
            </div>

            <h1 class="reveal delay-100 text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6">
              Where <span class="gold-shimmer">Harmony</span><br />
              Meets Excellence
            </h1>

            <p class="reveal delay-200 text-lg text-gray-300 leading-relaxed mb-8 max-w-xl">
              Resonanz Music Foundation nurtures the next generation of musicians through
              world-class education, immersive performance experiences, and a community
              bound by the love of music.
            </p>

            <div class="reveal delay-300 flex flex-wrap gap-4">
              <RouterLink to="/courses"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gold-gradient text-maroon-950
                       font-semibold rounded-xl shadow-gold btn-magnetic relative overflow-hidden group">
                <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full
                             bg-gradient-to-r from-transparent via-white/30 to-transparent
                             transition-transform duration-500 ease-in-out"></span>
                <span class="relative">Explore Courses</span>
                <ArrowRight class="w-4 h-4 relative group-hover:translate-x-1 transition-transform" />
              </RouterLink>

              <RouterLink to="/concert"
                class="inline-flex items-center gap-2 px-6 py-3 border border-gold-500/40
                       text-gold-400 font-semibold rounded-xl transition-all duration-300
                       hover:bg-gold-500/10 hover:border-gold-500/70 hover:scale-105 hover:shadow-gold group">
                <Sparkles class="w-4 h-4 group-hover:text-gold-300 transition-colors" />
                Upcoming Concerts
              </RouterLink>
            </div>
          </div>

          <!-- Right: visual card -->
          <div class="reveal-right relative perspective">

            <!-- Floating award badge -->
            <div class="float-delay absolute -bottom-6 -left-6 glass-card p-5 z-20 hidden sm:block shadow-gold">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gold-gradient flex items-center justify-center glow-pulse">
                  <Award class="w-5 h-5 text-maroon-950" />
                </div>
                <div>
                  <p class="text-2xl font-bold gold-text font-serif">45+</p>
                  <p class="text-xs text-gray-400">Awards Won</p>
                </div>
              </div>
            </div>

            <!-- Students badge -->
            <div class="float absolute -top-4 -right-4 z-20 hidden sm:flex items-center gap-2
                        px-4 py-2 glass-card border-gold-500/30 shadow-gold"
                 style="animation: float 7s ease-in-out 1.5s infinite;">
              <div class="flex -space-x-1.5">
                <div v-for="n in 3" :key="n"
                     class="w-7 h-7 rounded-full bg-gold-gradient border-2 border-maroon-950
                            flex items-center justify-center text-[10px] font-bold text-maroon-950 font-serif">
                  {{ ['ER','HT','AO'][n-1] }}
                </div>
              </div>
              <span class="text-xs text-gray-300 font-medium">500+ Students</span>
            </div>

            <!-- Main card -->
            <div class="glass-card p-8 relative overflow-hidden tilt-card">
              <div class="absolute -top-20 -right-20 w-64 h-64 bg-gold-500/10 rounded-full blur-3xl"></div>
              <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-maroon-700/30 rounded-full blur-2xl"></div>
              <div class="relative">
                <div class="aspect-square rounded-2xl bg-maroon-gradient border border-gold-500/30
                            flex items-center justify-center overflow-hidden group cursor-default">
                  <div class="relative flex items-center justify-center w-full h-full">
                    <div class="absolute w-48 h-48 rounded-full border border-gold-400/20"
                         style="animation: ripple 3s ease-out infinite;"></div>
                    <div class="absolute w-36 h-36 rounded-full border border-gold-400/15"
                         style="animation: ripple 3s ease-out 1s infinite;"></div>
                    <Music class="w-28 h-28 text-gold-400/60 float transition-all duration-500
                                  group-hover:text-gold-400/90 group-hover:scale-110" stroke-width="1" />
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════ STATS ═══ -->
    <section id="stats-section"
             class="py-14 border-y border-gold-500/10 bg-maroon-900/30 relative overflow-hidden">
      <div class="absolute top-0 left-0 h-px w-full"
           style="background: linear-gradient(90deg, transparent 0%, rgba(255,197,32,0.4) 50%, transparent 100%);
                  animation: shimmer 4s linear infinite;"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="(stat, i) in stats"
            :key="stat.label"
            class="reveal text-center group cursor-default"
            :class="`delay-${(i + 1) * 100}`"
          >
            <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-gold-500/10 border border-gold-500/20
                        flex items-center justify-center group-hover:bg-gold-gradient
                        transition-all duration-300 group-hover:scale-110 group-hover:shadow-gold">
              <component :is="stat.icon"
                         class="w-5 h-5 text-gold-400 group-hover:text-maroon-950 transition-colors" />
            </div>
            <p class="text-3xl sm:text-4xl font-bold gold-text font-serif stat-number">
              {{ statsVisible ? displayStats[i] : 0 }}{{ stat.suffix }}
            </p>
            <p class="text-sm text-gray-400 mt-1">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════ FEATURES ═══ -->
    <section class="py-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-16">
          <span class="text-gold-400 text-sm font-medium tracking-widest uppercase block mb-3">
            Why Choose Us
          </span>
          <h2 class="text-3xl sm:text-4xl font-bold mb-4">
            Why Choose <span class="gold-text">Resonanz</span>
          </h2>
          <div class="flex items-center justify-center gap-3 mt-5">
            <div class="h-px w-16 bg-gold-gradient opacity-60"></div>
            <div class="w-2 h-2 rounded-full bg-gold-400 opacity-70"></div>
            <div class="h-px w-16 bg-gold-gradient opacity-60"></div>
          </div>
          <p class="text-gray-400 mt-5">
            A holistic musical education designed to develop both technical mastery and artistic expression.
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
          <div
            v-for="(feature, i) in features"
            :key="feature.title"
            class="reveal glass-card p-8 card-lift animated-border group cursor-default"
            :class="`delay-${(i + 1) * 150}`"
          >
            <div class="w-14 h-14 rounded-xl bg-gold-500/10 border border-gold-500/30
                        flex items-center justify-center mb-6
                        group-hover:bg-gold-gradient group-hover:shadow-gold
                        transition-all duration-400 group-hover:scale-110 group-hover:rotate-6">
              <component :is="feature.icon"
                         class="w-7 h-7 text-gold-400 group-hover:text-maroon-950 transition-colors duration-300" />
            </div>
            <div class="flex items-center gap-3 mb-4">
              <span class="text-xs font-bold text-gold-500/50 font-mono tracking-widest">0{{ i + 1 }}</span>
              <div class="h-px flex-1 bg-gold-500/15 group-hover:bg-gold-500/40 transition-colors"></div>
            </div>
            <h3 class="text-xl font-semibold text-white mb-3 group-hover:text-gold-300 transition-colors">
              {{ feature.title }}
            </h3>
            <p class="text-gray-400 leading-relaxed">{{ feature.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════ CTA ═══ -->
    <section class="py-24">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal-scale glass-card p-12 sm:p-16 text-center relative overflow-hidden animated-border group">
          <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700"
               style="background: radial-gradient(ellipse at 50% 120%, rgba(255,197,32,0.07) 0%, transparent 60%);"></div>
          <div class="absolute inset-0 bg-gold-gradient opacity-[0.04]"></div>

          <!-- Corner decorations -->
          <div class="absolute top-0 left-0 w-24 h-24 border-t border-l border-gold-500/30 rounded-tl-2xl pointer-events-none"></div>
          <div class="absolute bottom-0 right-0 w-24 h-24 border-b border-r border-gold-500/30 rounded-br-2xl pointer-events-none"></div>

          <div class="relative">
            <div class="flex items-center justify-center gap-2 mb-4">
              <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
              <Sparkles class="w-4 h-4 text-gold-400" />
              <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">
              Begin Your <span class="gold-text">Musical Journey</span>
            </h2>
            <p class="text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
              Join a community of passionate musicians and unlock your full potential
              with personalized mentorship from world-class faculty.
            </p>
            <RouterLink to="/contact"
              class="inline-flex items-center gap-2 px-10 py-4 bg-gold-gradient text-maroon-950
                     font-bold rounded-xl shadow-gold btn-magnetic relative overflow-hidden group/btn">
              <span class="absolute inset-0 -translate-x-full group-hover/btn:translate-x-full
                           bg-gradient-to-r from-transparent via-white/30 to-transparent
                           transition-transform duration-500"></span>
              <span class="relative">Get Started Today</span>
              <ArrowRight class="w-5 h-5 relative group-hover/btn:translate-x-1 transition-transform" />
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script>
export default { name: 'HomeView' }
</script>
