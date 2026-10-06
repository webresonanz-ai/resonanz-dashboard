<script setup>
import { Calendar, Clock, MapPin, User } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

useScrollReveal()

const schedule = [
  { day: 'Monday', time: '09:00 - 18:00', course: 'Classical Piano', room: 'Studio A', teacher: 'Ms. Elena Rossi' },
  { day: 'Tuesday', time: '10:00 - 19:00', course: 'Violin Masterclass', room: 'Hall B', teacher: 'Mr. Hiroshi Tanaka' },
  { day: 'Wednesday', time: '09:00 - 17:00', course: 'Vocal Training', room: 'Studio C', teacher: 'Ms. Amara Okafor' },
  { day: 'Thursday', time: '11:00 - 20:00', course: 'Music Theory', room: 'Room 204', teacher: 'Dr. James Whitmore' },
  { day: 'Friday', time: '09:00 - 18:00', course: 'String Ensemble', room: 'Hall A', teacher: 'Mr. Lucas Müller' },
  { day: 'Saturday', time: '08:00 - 16:00', course: 'Youth Orchestra', room: 'Main Hall', teacher: 'Ms. Sophia Chen' },
]

const dayColors = ['#ffd843', '#ffc520', '#e8a400', '#c07d00', '#ffd843', '#ffc520']
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Header -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Weekly Timetable</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Class <span class="gold-text">Schedule</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Plan your week with our comprehensive schedule of classes, masterclasses, and ensemble rehearsals.
      </p>
    </div>

    <div class="grid gap-4">
      <div
        v-for="(item, i) in schedule"
        :key="item.day"
        class="reveal glass-card overflow-hidden group transition-all duration-400"
        :class="`delay-${(i + 1) * 80}`"
        style="--card-hover: translateY(-3px);"
      >
        <div
          class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8 p-6
                 group-hover:-translate-y-0.5 group-hover:border-gold-500/40
                 transition-all duration-300"
        >

          <!-- Day name with accent bar -->
          <div class="md:w-36 shrink-0 flex items-center gap-3">
            <div
              class="w-1 h-10 rounded-full shrink-0 transition-all duration-300
                     group-hover:h-14"
              :style="`background: linear-gradient(180deg, ${dayColors[i]}, ${dayColors[i]}88)`"
            ></div>
            <div>
              <p class="text-gold-400 font-serif text-xl font-bold group-hover:text-gold-300 transition-colors">
                {{ item.day }}
              </p>
              <p class="text-xs text-gold-500/50 font-medium uppercase tracking-widest">Day {{ i + 1 }}</p>
            </div>
          </div>

          <!-- Course + teacher -->
          <div class="flex-1 min-w-0">
            <h3
              class="text-lg font-semibold text-white mb-1.5 group-hover:text-gold-400
                     transition-colors duration-300"
            >
              {{ item.course }}
            </h3>
            <p class="text-sm text-gray-400 flex items-center gap-1.5">
              <User class="w-3.5 h-3.5 text-gold-500/60 shrink-0" />
              {{ item.teacher }}
            </p>
          </div>

          <!-- Time + room pills -->
          <div class="flex flex-wrap sm:flex-nowrap gap-3 text-sm">
            <div
              class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg
                     bg-gold-500/8 border border-gold-500/15 text-gray-300
                     group-hover:border-gold-500/35 group-hover:bg-gold-500/12
                     transition-all duration-300"
            >
              <Clock class="w-4 h-4 text-gold-400 shrink-0" />
              <span class="whitespace-nowrap">{{ item.time }}</span>
            </div>
            <div
              class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg
                     bg-gold-500/8 border border-gold-500/15 text-gray-300
                     group-hover:border-gold-500/35 group-hover:bg-gold-500/12
                     transition-all duration-300"
            >
              <MapPin class="w-4 h-4 text-gold-400 shrink-0" />
              <span>{{ item.room }}</span>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</template>
