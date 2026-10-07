<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { Music2, Facebook, Instagram, Youtube, Mail, Phone, MapPin, ArrowUpRight } from 'lucide-vue-next'
import { useAppStore } from '@/stores/appStore'
import LanguageSwitcher from './LanguageSwitcher.vue'

const { t } = useI18n()
const store = useAppStore()

const socials = [
  { icon: Facebook, href: 'https://www.facebook.com/TheResonanzMusicStudio', label: 'Facebook' },
  { icon: Instagram, href: 'https://www.instagram.com/theresonanz/?hl=en', label: 'Instagram' },
  { icon: Youtube, href: 'https://www.youtube.com/@TheResonanzMusic', label: 'YouTube' },
]

const quickLinks = computed(() => [
  { n: t('nav.schedule'), p: '/schedule' },
  { n: t('nav.event'), p: '/event' },
  { n: t('nav.courses'), p: '/courses' },
  { n: t('nav.teachers'), p: '/teachers' },
])

const programs = ['Classical Piano', 'String Ensemble', 'Vocal Performance', 'Music Theory']
</script>

<template>
  <footer class="relative z-10 border-t border-gold-500/20 bg-maroon-950/90 backdrop-blur-xl mt-20 overflow-hidden">

    <!-- Top shimmer line -->
    <div
      class="absolute top-0 left-0 h-px w-full"
      style="background: linear-gradient(90deg, transparent 0%, rgba(255,197,32,0.5) 50%, transparent 100%);
             animation: shimmer 5s linear infinite;"
    ></div>

    <!-- Ambient glow -->
    <div
      class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[200px] pointer-events-none"
      style="background: radial-gradient(ellipse at center, rgba(255,197,32,0.04) 0%, transparent 70%);"
    ></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

        <!-- ─── Brand ─── -->
        <div>
          <RouterLink to="/" class="inline-flex items-center gap-3 mb-5 group">
            <div
              class="w-10 h-10 rounded-lg bg-gold-gradient flex items-center justify-center
                     shadow-gold group-hover:shadow-gold-lg group-hover:scale-110
                     group-hover:rotate-6 transition-all duration-300"
            >
              <Music2 class="w-5 h-5 text-maroon-950" stroke-width="2.5" />
            </div>
            <span class="font-serif text-lg font-bold gold-text">Resonanz</span>
          </RouterLink>

          <p class="text-sm text-gray-400 leading-relaxed mb-6 max-w-[220px]">
            {{ $t('footer.tagline') }}
          </p>

          <div class="flex gap-2.5">
            <a
              v-for="social in socials"
              :key="social.label"
              :href="social.href"
              :aria-label="social.label"
              class="w-9 h-9 rounded-lg bg-gold-500/10 border border-gold-500/20
                     flex items-center justify-center text-gold-400
                     hover:bg-gold-gradient hover:text-maroon-950 hover:border-transparent
                     hover:shadow-gold hover:scale-110 transition-all duration-300"
            >
              <component :is="social.icon" class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- ─── Quick Links ─── -->
        <div>
          <h4 class="text-gold-400 font-serif text-lg mb-5 relative inline-block">
            {{ $t('footer.quickLinks') }}
            <span class="absolute -bottom-1 left-0 h-px w-full bg-gold-gradient opacity-40"></span>
          </h4>
          <ul class="space-y-2.5 text-sm">
            <li v-for="link in quickLinks" :key="link.p">
              <RouterLink
                :to="link.p"
                class="flex items-center gap-1.5 text-gray-400 hover:text-gold-400
                       transition-all duration-200 group hover:translate-x-1"
              >
                <span
                  class="w-1 h-1 rounded-full bg-gold-500/40 group-hover:bg-gold-400
                         group-hover:scale-150 transition-all duration-200"
                ></span>
                {{ link.n }}
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- ─── Programs ─── -->
        <div>
          <h4 class="text-gold-400 font-serif text-lg mb-5 relative inline-block">
            {{ $t('footer.programs') }}
            <span class="absolute -bottom-1 left-0 h-px w-full bg-gold-gradient opacity-40"></span>
          </h4>
          <ul class="space-y-2.5 text-sm text-gray-400">
            <li
              v-for="program in programs"
              :key="program"
              class="flex items-center gap-1.5 hover:text-gold-400 transition-colors cursor-default"
            >
              <span class="w-1 h-1 rounded-full bg-gold-500/40 shrink-0"></span>
              {{ program }}
            </li>
          </ul>
        </div>

        <!-- ─── Contact ─── -->
        <div>
          <h4 class="text-gold-400 font-serif text-lg mb-5 relative inline-block">
            {{ $t('footer.contact') }}
            <span class="absolute -bottom-1 left-0 h-px w-full bg-gold-gradient opacity-40"></span>
          </h4>
          <ul class="space-y-3.5 text-sm text-gray-400">
            <li class="flex items-start gap-3 group hover:text-gray-300 transition-colors cursor-default">
              <MapPin class="w-4 h-4 text-gold-400/70 mt-0.5 shrink-0 group-hover:text-gold-400 transition-colors" />
              <span>Jl. Kertanegara No. 28<br>Jakarta Selatan, Indonesia</span>
            </li>
            <li class="flex items-center gap-3 group hover:text-gray-300 transition-colors cursor-default">
              <Phone class="w-4 h-4 text-gold-400/70 shrink-0 group-hover:text-gold-400 transition-colors" />
              <span>+62 21 720 1918<br>+62 858 1414 2277</span>
            </li>
            <li class="flex items-center gap-3 group hover:text-gold-400 transition-colors cursor-pointer">
              <Mail class="w-4 h-4 text-gold-400/70 shrink-0 group-hover:text-gold-400 transition-colors" />
              <span>admin@theresonanz.com</span>
            </li>
          </ul>
        </div>

      </div>

      <!-- ─── Bottom bar ─── -->
      <div class="mt-12 pt-6 border-t border-gold-500/10 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-gray-500">
        <p>&copy; {{ store.currentYear }} The Resonanz Music Studio. {{ $t('footer.rights') }}</p>
        <div class="flex items-center gap-4">
          <LanguageSwitcher />
          <p class="flex items-center gap-2">
            {{ $t('footer.crafted') }}
          </p>
        </div>
      </div>
    </div>

  </footer>
</template>
