<script setup>
import { Mail, ExternalLink } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

useScrollReveal()

const teachers = [
  { name: 'Elena Rossi', role: 'Piano · Department Head', bio: 'Juilliard graduate with 20 years of concert experience across Europe and Asia.', initials: 'ER' },
  { name: 'Hiroshi Tanaka', role: 'Violin · Principal', bio: 'Former concertmaster of the Tokyo Symphony, specializing in Romantic repertoire.', initials: 'HT' },
  { name: 'Amara Okafor', role: 'Voice · Vocal Coach', bio: 'Internationally touring soprano and vocal pedagogue with a passion for operatic training.', initials: 'AO' },
  { name: 'James Whitmore', role: 'Music Theory & Composition', bio: 'PhD in Musicology and award-winning composer of contemporary chamber works.', initials: 'JW' },
  { name: 'Lucas Müller', role: 'Cello · Chamber Music', bio: 'Founding member of the Müller Quartet with 15+ international recordings.', initials: 'LM' },
  { name: 'Sophia Chen', role: 'Orchestra Conductor', bio: 'Conductor of the National Youth Orchestra with degrees from Yale and Vienna.', initials: 'SC' },
  { name: 'Marcus Rivera', role: 'Jazz & Improvisation', bio: 'Grammy-nominated jazz pianist and arranger with a career spanning three decades.', initials: 'MR' },
  { name: 'Ingrid Larsson', role: 'Flute · Woodwinds', bio: 'Principal flutist of the Nordic Philharmonic and dedicated educator.', initials: 'IL' },
]
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Header -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Our People</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Meet the <span class="gold-text">Teachers</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Learn from internationally acclaimed artists and dedicated educators who bring passion to every lesson.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(t, i) in teachers"
        :key="t.name"
        class="reveal glass-card p-6 text-center card-lift animated-border group cursor-default"
        :class="`delay-${(i % 4) * 100 + 100}`"
      >

        <!-- Avatar with animated ring -->
        <div class="relative w-24 h-24 mx-auto mb-5">
          <!-- Outer glow ring -->
          <div
            class="absolute inset-0 rounded-full bg-gold-gradient opacity-0 blur-md
                   group-hover:opacity-40 transition-opacity duration-500 scale-110"
          ></div>
          <!-- Rotating dashed ring -->
          <div
            class="absolute inset-0 rounded-full border-2 border-dashed border-gold-500/20
                   group-hover:border-gold-500/50 transition-all duration-500"
            style="animation: spin-slow 12s linear infinite;"
          ></div>
          <!-- Avatar circle -->
          <div
            class="relative w-full h-full rounded-full bg-gold-gradient flex items-center justify-center
                   shadow-gold group-hover:shadow-gold-lg transition-all duration-300
                   group-hover:scale-105"
          >
            <span class="text-2xl font-serif font-bold text-maroon-950">{{ t.initials }}</span>
          </div>
        </div>

        <h3 class="text-lg font-serif font-bold text-white mb-1 group-hover:text-gold-300 transition-colors">
          {{ t.name }}
        </h3>
        <p class="text-xs text-gold-400 font-medium mb-4 tracking-wide">{{ t.role }}</p>
        <p class="text-sm text-gray-400 leading-relaxed mb-5">{{ t.bio }}</p>

        <button
          class="inline-flex items-center gap-2 text-sm text-gold-400 font-medium
                 hover:text-gold-300 group/btn transition-all"
        >
          <Mail class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
          <span class="group-hover/btn:translate-x-0.5 transition-transform">Contact</span>
        </button>
      </div>
    </div>

  </div>
</template>
