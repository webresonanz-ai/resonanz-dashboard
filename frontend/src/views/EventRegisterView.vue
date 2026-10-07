<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import QRCode from 'qrcode'
import { Calendar, MapPin, Clock, Users, ArrowLeft, AlertCircle, Loader2, CheckCircle2, Ticket } from 'lucide-vue-next'
import { useApi, apiPost } from '@/composables/useApi'

const route = useRoute()
const router = useRouter()
const { locale } = useI18n()

const eventId = computed(() => route.params.id)
const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const { data: event, loading, error } = useApi(`/api/events/${route.params.id}`)
async function fetch() {
  loading.value = true
  error.value = null
  try {
    const res = await globalThis.fetch(`${API_BASE}/api/events/${eventId.value}`)
    const json = await res.json()
    if (!res.ok) throw new Error(json.message ?? 'Request failed.')
    event.value = json.data ?? json
  } catch (e) {
    error.value = e.message ?? 'Could not load data.'
  } finally {
    loading.value = false
  }
}
onMounted(fetch)
watch(eventId, () => { result.value = null; fetch() })

const form = ref({ name: '', email: '', phone: '' })
const submitting = ref(false)
const submitError = ref('')
const result = ref(null) // { registration, event, remaining }
const qrCanvas = ref(null)

const isFull = computed(() => {
  const cap = event.value?.max_capacity
  const count = event.value?.registered_count ?? 0
  return cap != null && cap > 0 && count >= cap
})

const remaining = computed(() => {
  const cap = event.value?.max_capacity
  if (cap == null || !(cap > 0)) return null
  return Math.max(0, cap - (event.value?.registered_count ?? 0))
})

function formatDate(d) {
  if (!d) return ''
  const tag = locale.value === 'id' ? 'id-ID' : 'en-US'
  return new Date(d + 'T00:00:00').toLocaleDateString(tag, { month: 'short', day: 'numeric', year: 'numeric' })
}

function drawQrCode() {
  const code = result.value?.registration?.registration_code
  if (!code || !qrCanvas.value) return
  QRCode.toCanvas(qrCanvas.value, code, {
    width: 220,
    margin: 2,
    color: { dark: '#1a0505', light: '#ffffff' },
  }).catch(() => {
    // leave empty — code text is still shown
  })
}

watch(result, (v) => {
  if (v) requestAnimationFrame(drawQrCode)
})

async function submit() {
  submitError.value = ''
  if (!form.value.name.trim() || !form.value.email.trim() || !form.value.phone.trim()) {
    submitError.value = 'Please fill in name, email and phone.'
    return
  }
  submitting.value = true
  try {
    const json = await apiPost(`/api/events/${eventId.value}/register`, {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      phone: form.value.phone.trim(),
    })
    result.value = json.data
    // refresh count on the event card
    if (result.value?.registered_count != null && event.value) {
      event.value.registered_count = result.value.registered_count
    }
  } catch (e) {
    submitError.value = e.message ?? 'Registration failed. Please try again.'
  } finally {
    submitting.value = false
  }
}

function registerAnother() {
  result.value = null
  form.value = { name: '', email: '', phone: '' }
  fetch()
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <button
      @click="router.back()"
      class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gold-300 mb-8 transition-colors"
    >
      <ArrowLeft class="w-4 h-4" /> {{ $t('registration.back') }}
    </button>

    <!-- Loading -->
    <div v-if="loading" class="glass-card p-10 text-center">
      <Loader2 class="w-8 h-8 text-gold-400 animate-spin mx-auto mb-3" />
      <p class="text-gray-400 text-sm">Loading event…</p>
    </div>

    <!-- Error -->
    <div v-else-if="error || !event" class="glass-card p-10 text-center border-red-500/20">
      <AlertCircle class="w-10 h-10 text-red-400/60 mx-auto mb-3" />
      <p class="text-gray-400 text-sm mb-4">{{ error || 'Event not found.' }}</p>
      <button @click="fetch" class="px-5 py-2.5 bg-gold-gradient text-maroon-950 font-semibold rounded-xl text-sm">
        {{ $t('common.retry') }}
      </button>
    </div>

    <template v-else>
      <!-- Event summary -->
      <div class="glass-card overflow-hidden mb-6">
        <div class="p-6 sm:p-8">
          <span class="text-gold-400 text-xs font-medium tracking-widest uppercase">{{ $t('registration.eyebrow') }}</span>
          <h1 class="text-2xl sm:text-3xl font-bold text-white mt-2 mb-4">{{ event.title }}</h1>
          <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-400">
            <span class="inline-flex items-center gap-2"><Calendar class="w-4 h-4 text-gold-400" />{{ formatDate(event.event_date) }}</span>
            <span class="inline-flex items-center gap-2"><Clock class="w-4 h-4 text-gold-400" />{{ event.event_time?.slice(0, 5) }}</span>
            <span class="inline-flex items-center gap-2"><MapPin class="w-4 h-4 text-gold-400" />{{ event.venue }}</span>
            <span v-if="event.max_capacity" class="inline-flex items-center gap-2">
              <Users class="w-4 h-4 text-gold-400" />
              {{ event.registered_count ?? 0 }}/{{ event.max_capacity }}
              <template v-if="remaining != null">({{ $t('registration.seatsLeft', { n: remaining }) }})</template>
            </span>
          </div>
        </div>
      </div>

      <!-- Success ticket -->
      <div v-if="result" class="glass-card p-6 sm:p-8 text-center border-gold-500/30">
        <CheckCircle2 class="w-12 h-12 text-green-400 mx-auto mb-3" />
        <h2 class="text-xl font-bold text-white mb-1">{{ $t('registration.successTitle') }}</h2>
        <p class="text-sm text-gray-400 mb-6">{{ $t('registration.successText', { name: result.registration.name }) }}</p>
        <div class="bg-white rounded-2xl p-5 max-w-sm mx-auto">
          <div class="flex items-center justify-center gap-2 text-maroon-950 mb-2">
            <Ticket class="w-5 h-5" />
            <span class="font-bold">{{ event.title }}</span>
          </div>
          <canvas ref="qrCanvas" class="mx-auto rounded-lg"></canvas>
          <p class="font-mono font-bold text-maroon-950 tracking-wider mt-2 break-all">
            {{ result.registration.registration_code }}
          </p>
          <p class="text-xs text-gray-500 mt-1">CODE_ID_TIMESTAMP_RANDOM</p>
        </div>
        <div class="flex flex-wrap justify-center gap-3 mt-6">
          <button @click="registerAnother" class="px-5 py-2.5 rounded-xl text-sm font-semibold border border-gold-500/40 text-gold-300 hover:bg-gold-500/10 transition-colors">
            {{ $t('registration.registerAnother') }}
          </button>
          <button @click="router.push({ name: 'event' })" class="px-5 py-2.5 bg-gold-gradient text-maroon-950 font-semibold rounded-xl text-sm">
            {{ $t('registration.backToEvents') }}
          </button>
        </div>
      </div>

      <!-- Form -->
      <div v-else class="glass-card p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-white mb-5">{{ $t('registration.formTitle') }}</h2>
        <div v-if="isFull" class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300 mb-5">
          {{ $t('registration.full') }}
        </div>
        <form v-else @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $t('registration.name') }}</label>
            <input v-model="form.name" type="text" required minlength="2" maxlength="100" :placeholder="$t('registration.namePlaceholder')" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400/60 focus:ring-1 focus:ring-gold-400/30" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $t('registration.email') }}</label>
            <input v-model="form.email" type="email" required maxlength="255" placeholder="you@example.com" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400/60 focus:ring-1 focus:ring-gold-400/30" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $t('registration.phone') }}</label>
            <input v-model="form.phone" type="tel" required minlength="5" maxlength="30" placeholder="+62 812 3456 7890" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400/60 focus:ring-1 focus:ring-gold-400/30" />
          </div>
          <p v-if="submitError" class="text-sm text-red-300">{{ submitError }}</p>
          <button type="submit" :disabled="submitting" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-gold-gradient text-maroon-950 font-bold rounded-xl disabled:opacity-60">
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ submitting ? $t('registration.submitting') : $t('registration.submit') }}
          </button>
          <p class="text-xs text-gray-500 text-center">{{ $t('registration.codeHint') }}</p>
        </form>
      </div>
    </template>
  </div>
</template>
