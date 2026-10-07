<script setup>
import { MapPin, Phone, Mail, Clock, Send, CheckCircle } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { apiPost } from '@/composables/useApi'

const { t } = useI18n()

useScrollReveal()

const form      = ref({ name: '', email: '', subject: '', message: '' })
const submitted = ref(false)
const submitting = ref(false)
const apiError  = ref('')

const contactInfo = computed(() => [
  { icon: MapPin, label: t('contact.labels.address'),     value: 'Jl. Kertanegara No. 28\nJakarta Selatan, Indonesia' },
  { icon: Phone,  label: t('contact.labels.phone'),       value: '+62 21 720 1918\n+62 858 1414 2277' },
  { icon: Mail,   label: t('contact.labels.email'),       value: 'admin@theresonanz.com' },
  { icon: Clock,  label: t('contact.labels.officeHours'), value: 'Mon – Sat: 9:00 – 18:00' },
])

async function handleSubmit() {
  submitting.value = true
  apiError.value   = ''

  try {
    await apiPost('/api/contact', {
      name:    form.value.name,
      email:   form.value.email,
      subject: form.value.subject,
      message: form.value.message,
    })

    submitted.value  = true
    form.value       = { name: '', email: '', subject: '', message: '' }
    setTimeout(() => { submitted.value = false }, 4500)
  } catch (e) {
    // Surface field-level errors or the general message
    if (e.errors) {
      const first = Object.values(e.errors)[0]
      apiError.value = Array.isArray(first) ? first[0] : String(first)
    } else {
      apiError.value = e.message ?? t('contact.sendFailed')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- ─── Header ─── -->
    <div class="mb-14 reveal">
      <span class="text-gold-400 text-sm font-medium tracking-widest uppercase">{{ $t('contact.eyebrow') }}</span>
      <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4">
        {{ $t('contact.titleA') }} <span class="gold-text">{{ $t('contact.titleHighlight') }}</span>
      </h1>
      <div class="flex items-center gap-3 mb-4">
        <div class="h-px w-12 bg-gold-gradient opacity-50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-gold-400/60"></div>
        <div class="h-px w-24 bg-gold-gradient opacity-30"></div>
      </div>
      <p class="text-gray-400 max-w-2xl">
        {{ $t('contact.subtitle') }}
      </p>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">

      <!-- ─── Info panel ─── -->
      <div class="reveal-left space-y-6">
        <div class="glass-card p-8 animated-border">
          <h2 class="text-2xl font-serif font-bold mb-8">
            {{ $t('contact.visitA') }} <span class="gold-text">Resonanz</span>
          </h2>
          <div class="space-y-6">
            <div
              v-for="(info, i) in contactInfo"
              :key="info.label"
              class="flex items-start gap-4 group reveal"
              :class="`delay-${(i + 1) * 100}`"
            >
              <div class="w-11 h-11 rounded-xl bg-gold-500/10 border border-gold-500/30
                          flex items-center justify-center shrink-0
                          group-hover:bg-gold-gradient group-hover:shadow-gold
                          group-hover:scale-110 transition-all duration-300">
                <component :is="info.icon"
                           class="w-5 h-5 text-gold-400 group-hover:text-maroon-950 transition-colors" />
              </div>
              <div>
                <p class="font-semibold text-white mb-1">{{ info.label }}</p>
                <p class="text-sm text-gray-400 whitespace-pre-line">{{ info.value }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Form ─── -->
      <div class="reveal-right glass-card p-8 animated-border relative overflow-hidden">

        <!-- Success overlay -->
        <transition name="success-overlay">
          <div v-if="submitted"
               class="absolute inset-0 z-20 flex flex-col items-center justify-center
                      bg-maroon-950/95 backdrop-blur-sm rounded-2xl">
            <div class="flex flex-col items-center gap-4 text-center">
              <div class="w-16 h-16 rounded-full bg-gold-gradient flex items-center justify-center
                          shadow-gold-lg glow-pulse">
                <CheckCircle class="w-8 h-8 text-maroon-950" />
              </div>
              <h3 class="text-2xl font-serif font-bold text-white">{{ $t('contact.successTitle') }}</h3>
              <p class="text-gray-400 text-sm max-w-xs">
                {{ $t('contact.successText') }}
              </p>
            </div>
          </div>
        </transition>

        <h2 class="text-2xl font-serif font-bold mb-6">
          {{ $t('contact.formTitleA') }} <span class="gold-text">{{ $t('contact.formTitleHighlight') }}</span>
        </h2>

        <!-- API error banner -->
        <transition name="err-banner">
          <div v-if="apiError"
               class="flex items-start gap-2 px-4 py-3 mb-5 rounded-xl
                      bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            {{ apiError }}
          </div>
        </transition>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">{{ $t('contact.name') }}</label>
              <input v-model="form.name" required type="text"
                     class="w-full px-4 py-3 bg-maroon-950/60 border border-gold-500/20 rounded-xl
                            text-white placeholder-gray-500 transition-all duration-300
                            focus:outline-none focus:border-gold-400 focus:bg-maroon-950/80
                            focus:shadow-gold hover:border-gold-500/40"
                     :placeholder="$t('contact.namePlaceholder')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">{{ $t('contact.email') }}</label>
              <input v-model="form.email" required type="email"
                     class="w-full px-4 py-3 bg-maroon-950/60 border border-gold-500/20 rounded-xl
                            text-white placeholder-gray-500 transition-all duration-300
                            focus:outline-none focus:border-gold-400 focus:bg-maroon-950/80
                            focus:shadow-gold hover:border-gold-500/40"
                     placeholder="you@email.com" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">{{ $t('contact.subject') }}</label>
            <input v-model="form.subject" required type="text"
                   class="w-full px-4 py-3 bg-maroon-950/60 border border-gold-500/20 rounded-xl
                          text-white placeholder-gray-500 transition-all duration-300
                          focus:outline-none focus:border-gold-400 focus:bg-maroon-950/80
                          focus:shadow-gold hover:border-gold-500/40"
                   :placeholder="$t('contact.subjectPlaceholder')" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">{{ $t('contact.message') }}</label>
            <textarea v-model="form.message" required rows="5"
                      class="w-full px-4 py-3 bg-maroon-950/60 border border-gold-500/20 rounded-xl
                             text-white placeholder-gray-500 transition-all duration-300 resize-none
                             focus:outline-none focus:border-gold-400 focus:bg-maroon-950/80
                             focus:shadow-gold hover:border-gold-500/40"
                      :placeholder="$t('contact.messagePlaceholder')"></textarea>
          </div>

          <button type="submit"
                  :disabled="submitting"
                  class="w-full inline-flex items-center justify-center gap-2 py-3.5
                         bg-gold-gradient text-maroon-950 font-bold rounded-xl
                         shadow-gold btn-magnetic relative overflow-hidden group
                         disabled:opacity-70 disabled:cursor-not-allowed">
            <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full
                         bg-gradient-to-r from-transparent via-white/30 to-transparent
                         transition-transform duration-500 ease-in-out"></span>
            <transition name="icon-swap" mode="out-in">
              <span v-if="submitting" key="loading" class="flex items-center gap-2 relative">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ $t('contact.sending') }}
              </span>
              <span v-else key="send" class="flex items-center gap-2 relative">
                <Send class="w-4 h-4" /> {{ $t('contact.send') }}
              </span>
            </transition>
          </button>
        </form>
      </div>

    </div>
  </div>
</template>

<style scoped>
.success-overlay-enter-active { transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.16,1,0.3,1); }
.success-overlay-leave-active { transition: opacity 0.3s ease; }
.success-overlay-enter-from   { opacity: 0; transform: scale(0.96); }
.success-overlay-leave-to     { opacity: 0; }

.err-banner-enter-active { transition: all 0.3s cubic-bezier(0.16,1,0.3,1); }
.err-banner-leave-active { transition: all 0.2s ease; }
.err-banner-enter-from   { opacity: 0; transform: translateY(-6px); }
.err-banner-leave-to     { opacity: 0; }

.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.2s ease; }
.icon-swap-enter-from { opacity: 0; transform: translateY(6px); }
.icon-swap-leave-to   { opacity: 0; transform: translateY(-6px); }
</style>
