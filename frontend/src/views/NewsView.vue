<script setup>
import { Calendar, ArrowRight } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

useScrollReveal()

const news = [
  { title: 'Resonanz Students Win National Competition', date: 'Nov 28, 2025', category: 'Achievement', excerpt: 'Three of our talented students took home top honors at the National Young Musicians Competition in the capital.' },
  { title: 'New Partnership with Vienna Conservatory', date: 'Nov 15, 2025', category: 'Announcement', excerpt: 'We are proud to announce an exchange program that will allow our students to study abroad in Austria.' },
  { title: 'Winter Recital Series Announced', date: 'Nov 8, 2025', category: 'Event', excerpt: 'Our annual winter recital series will feature over 40 performances across three weekends this December.' },
  { title: 'Masterclass with Renowned Pianist', date: 'Oct 30, 2025', category: 'Event', excerpt: 'Grammy-winning pianist Maria Santos will host an exclusive masterclass for our advanced students.' },
  { title: 'New Scholarship Fund Established', date: 'Oct 22, 2025', category: 'Announcement', excerpt: 'A generous donation from the Hartwell Foundation will provide full scholarships for 10 students annually.' },
  { title: 'Alumni Spotlight: From Resonanz to Carnegie Hall', date: 'Oct 10, 2025', category: 'Story', excerpt: 'Read the inspiring journey of our alumna Sarah Kim, who recently made her Carnegie Hall debut.' },
]

const categoryColor = {
  Achievement: 'text-gold-300 bg-gold-500/15 border-gold-500/40',
  Announcement: 'text-blue-300 bg-blue-500/10 border-blue-500/30',
  Event: 'text-purple-300 bg-purple-500/10 border-purple-500/30',
  Story: 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30',
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Header -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">Stay Updated</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        Latest <span class="gold-text">News</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        Discover stories, announcements, and highlights from the Resonanz community.
      </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article
        v-for="(item, i) in news"
        :key="item.title"
        class="reveal glass-card p-6 card-lift animated-border group flex flex-col cursor-pointer"
        :class="`delay-${(i % 3) * 100 + 100}`"
      >
        <!-- Meta row -->
        <div class="flex items-center justify-between mb-5">
          <span
            class="px-3 py-1 text-xs font-semibold rounded-full border"
            :class="categoryColor[item.category] || 'text-gold-400 bg-gold-500/10 border-gold-500/30'"
          >
            {{ item.category }}
          </span>
          <span class="text-xs text-gray-500 flex items-center gap-1.5">
            <Calendar class="w-3 h-3" /> {{ item.date }}
          </span>
        </div>

        <!-- Title -->
        <h3
          class="text-lg font-semibold text-white mb-3 group-hover:text-gold-400
                 transition-colors duration-300 leading-snug"
        >
          {{ item.title }}
        </h3>

        <!-- Excerpt -->
        <p class="text-sm text-gray-400 leading-relaxed flex-1">{{ item.excerpt }}</p>

        <!-- Divider -->
        <div class="mt-5 pt-5 border-t border-gold-500/10 group-hover:border-gold-500/25 transition-colors">
          <button
            class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium
                   group-hover:gap-3 transition-all duration-300 group/btn"
          >
            Read More
            <ArrowRight
              class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform duration-200"
            />
          </button>
        </div>
      </article>
    </div>

  </div>
</template>
