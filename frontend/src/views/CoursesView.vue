<script setup>
import { Clock, Users, Award, Check, Sparkles } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

useScrollReveal()

const courses = [
  {
    name: 'Beginner Piano',
    price: '$120',
    period: '/month',
    duration: '45 min / session',
    size: 'Private',
    level: 'Beginner',
    features: ['Weekly 1-on-1 lessons', 'Practice materials included', 'Monthly progress report', 'Recital participation'],
    featured: false,
  },
  {
    name: 'Advanced Performance',
    price: '$280',
    period: '/month',
    duration: '90 min / session',
    size: 'Private',
    level: 'Advanced',
    features: ['Bi-weekly lessons', 'Masterclass access', 'Concert opportunities', 'Competition coaching', 'Recording sessions'],
    featured: true,
  },
  {
    name: 'Ensemble Program',
    price: '$180',
    period: '/month',
    duration: '120 min / session',
    size: 'Group (8)',
    level: 'Intermediate',
    features: ['Weekly group rehearsals', 'Performance opportunities', 'Music theory class', 'Sheet music provided'],
    featured: false,
  },
]
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Header -->
    <div class="mb-14 reveal text-center">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Programs</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Courses & <span class="gold-text">Fees</span>
      </h1>
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
      </div>
      <p class="text-gray-400 max-w-2xl mx-auto">
        Choose from flexible programs designed for every level of musical ambition.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 items-start">
      <div
        v-for="(course, i) in courses"
        :key="course.name"
        class="reveal glass-card p-8 relative transition-all duration-500 group"
        :class="[
          course.featured
            ? 'border-gold-500/60 shadow-gold-lg md:-translate-y-6 hover:shadow-gold-lg animated-border'
            : 'hover:border-gold-500/40 card-lift',
          `delay-${(i + 1) * 100}`
        ]"
      >
        <!-- Most popular badge -->
        <div
          v-if="course.featured"
          class="absolute -top-3.5 left-1/2 -translate-x-1/2 flex items-center gap-1.5
                 px-4 py-1 bg-gold-gradient text-maroon-950 text-xs font-bold rounded-full
                 shadow-gold glow-pulse"
        >
          <Sparkles class="w-3 h-3" />
          MOST POPULAR
        </div>

        <!-- Decorative corner for featured -->
        <div
          v-if="course.featured"
          class="absolute top-0 right-0 w-20 h-20 pointer-events-none"
          aria-hidden="true"
        >
          <div class="absolute top-4 right-4 w-12 h-12 bg-gold-500/5 rounded-full blur-xl"></div>
        </div>

        <!-- Course name + level -->
        <h3 class="text-2xl font-serif font-bold text-white mb-1 group-hover:text-gold-200 transition-colors">
          {{ course.name }}
        </h3>
        <p class="text-sm text-gray-400 mb-6">Level: {{ course.level }}</p>

        <!-- Price -->
        <div class="flex items-baseline gap-2 mb-6">
          <span class="text-4xl font-bold gold-text font-serif">{{ course.price }}</span>
          <span class="text-gray-400 text-sm">{{ course.period }}</span>
        </div>

        <!-- Meta info -->
        <div class="space-y-3 mb-6 pb-6 border-b border-gold-500/20">
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Clock class="w-4 h-4 text-gold-400" /> {{ course.duration }}
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Users class="w-4 h-4 text-gold-400" /> {{ course.size }}
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-300">
            <Award class="w-4 h-4 text-gold-400" /> Certificate on completion
          </div>
        </div>

        <!-- Feature list -->
        <ul class="space-y-3 mb-8">
          <li
            v-for="(f, fi) in course.features"
            :key="f"
            class="flex items-start gap-3 text-sm text-gray-300"
          >
            <div
              class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5
                     bg-gold-500/10 border border-gold-500/30 group-hover:bg-gold-gradient
                     group-hover:border-transparent transition-all duration-300"
              :style="`transition-delay: ${fi * 50}ms`"
            >
              <Check class="w-3 h-3 text-gold-400 group-hover:text-maroon-950 transition-colors" />
            </div>
            {{ f }}
          </li>
        </ul>

        <!-- CTA button -->
        <button
          class="w-full py-3 rounded-xl font-semibold transition-all duration-300
                 relative overflow-hidden group/btn"
          :class="course.featured
            ? 'bg-gold-gradient text-maroon-950 shadow-gold hover:shadow-gold-lg btn-magnetic'
            : 'border border-gold-500/40 text-gold-400 hover:bg-gold-500/10 hover:border-gold-500/70'"
        >
          <span
            v-if="course.featured"
            class="absolute inset-0 -translate-x-full group-hover/btn:translate-x-full
                   bg-gradient-to-r from-transparent via-white/25 to-transparent
                   transition-transform duration-500"
          ></span>
          <span class="relative">Enroll Now</span>
        </button>
      </div>
    </div>

    <!-- Financial aid notice -->
    <div class="mt-14 reveal-scale glass-card p-6 text-center delay-300">
      <p class="text-gray-300">
        <span class="gold-text font-semibold">Financial aid available.</span>
        We believe music education should be accessible to all.
        <RouterLink to="/contact" class="text-gold-400 hover:text-gold-300 underline underline-offset-2 ml-1 transition-colors">
          Contact us
        </RouterLink>
        to learn about scholarships.
      </p>
    </div>

  </div>
</template>
