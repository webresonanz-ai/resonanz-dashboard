<script setup>
import { Piano, Mic2, Drum, Guitar, Music, Speaker, Lightbulb, Snowflake } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

useScrollReveal()

const facilities = [
  { name: 'Grand Concert Hall', capacity: '500 seats', desc: 'Acoustically perfected performance venue with a Steinway D-274 concert grand.', icon: Music },
  { name: 'Piano Studios', capacity: '12 rooms', desc: 'Individual practice rooms equipped with Yamaha and Kawai grand pianos.', icon: Piano },
  { name: 'Recording Studio', capacity: 'Professional', desc: 'State-of-the-art recording facility with Pro Tools HD and vintage microphones.', icon: Mic2 },
  { name: 'Percussion Lab', capacity: '30 students', desc: 'Complete orchestral and world percussion collection with soundproofing.', icon: Drum },
  { name: 'String Workshop', capacity: '20 students', desc: 'Dedicated space for strings with instrument storage and humidity control.', icon: Guitar },
  { name: 'Lecture Theatre', capacity: '120 seats', desc: 'Multimedia lecture hall for theory classes, seminars, and workshops.', icon: Speaker },
  { name: 'Climate-Controlled Storage', capacity: '200+ instruments', desc: 'Secure, humidity-controlled storage for student and faculty instruments.', icon: Snowflake },
  { name: 'Innovation Lab', capacity: '15 students', desc: 'Digital music production, composition, and electronic music workspace.', icon: Lightbulb },
]
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Header -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Our Spaces</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        World-Class <span class="gold-text">Facilities</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Every space at Resonanz is designed to inspire, from intimate practice rooms to our iconic concert hall.
      </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(f, i) in facilities"
        :key="f.name"
        class="reveal glass-card p-6 card-lift animated-border group cursor-default"
        :class="`delay-${(i % 4) * 100 + 100}`"
      >
        <!-- Icon container -->
        <div
          class="w-14 h-14 rounded-xl bg-gold-500/10 border border-gold-500/30
                 flex items-center justify-center mb-5
                 group-hover:bg-gold-gradient group-hover:shadow-gold group-hover:scale-110
                 group-hover:rotate-6 transition-all duration-350 ease-out"
        >
          <component
            :is="f.icon"
            class="w-6 h-6 text-gold-400 group-hover:text-maroon-950 transition-colors duration-300"
          />
        </div>

        <h3 class="text-lg font-semibold text-white mb-1 group-hover:text-gold-300 transition-colors">
          {{ f.name }}
        </h3>

        <!-- Capacity pill -->
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                 bg-gold-500/10 text-gold-400 border border-gold-500/20 mb-3
                 group-hover:bg-gold-500/20 transition-colors"
        >
          {{ f.capacity }}
        </span>

        <p class="text-sm text-gray-400 leading-relaxed">{{ f.desc }}</p>
      </div>
    </div>

  </div>
</template>
