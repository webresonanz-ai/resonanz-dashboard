<script setup>
import { onUnmounted, ref } from 'vue'
import { BrowserCodeReader, BrowserQRCodeReader } from '@zxing/browser'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import {
  AlertCircle, Camera, CheckCircle2, Loader2, ScanQrCode, ShieldCheck, UserRound, XCircle,
} from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToastStore()
const video = ref(null)
const manualCode = ref('')
const scanner = new BrowserQRCodeReader()
const scannerControls = ref(null)
const scannerState = ref('idle')
const scannerError = ref('')
const checkingIn = ref(false)
const result = ref(null)
const resultType = ref('')

async function startScanner() {
  scannerError.value = ''
  scannerState.value = 'starting'
  let pendingCode = null

  try {
    const cameras = await BrowserCodeReader.listVideoInputDevices()
    const preferredCamera = cameras.find(camera => /back|rear|environment/i.test(camera.label))
    const cameraId = preferredCamera?.deviceId ?? cameras.at(-1)?.deviceId
    const controls = await scanner.decodeFromVideoDevice(
      cameraId,
      video.value,
      result => {
        if (result && !checkingIn.value) {
          if (!scannerControls.value) {
            pendingCode = result.getText()
            return
          }
          void submitCode(result.getText())
        }
      },
    )
    scannerControls.value = controls
    scannerState.value = 'scanning'
    if (pendingCode) {
      const code = pendingCode
      stopScanner()
      void submitCode(code)
    }
  } catch (error) {
    scannerState.value = 'idle'
    scannerError.value = error.message || 'Unable to access the camera. Allow camera access or enter a ticket code manually.'
  }
}

function stopScanner() {
  scannerControls.value?.stop()
  scannerControls.value = null
  scannerState.value = 'idle'
}

async function submitCode(rawCode) {
  const code = String(rawCode ?? '').trim()
  if (!code || checkingIn.value) return

  stopScanner()
  checkingIn.value = true
  result.value = null
  resultType.value = ''

  try {
    const json = await auth.apiFetch('/api/admin/registrations/check-in', {
      method: 'POST',
      body: JSON.stringify({ code }),
    })
    result.value = json.data
    resultType.value = json.data?.status ?? 'checked_in'
    manualCode.value = ''

    if (resultType.value === 'checked_in') {
      toast.success(json.message ?? 'Check-in successful.')
    } else {
      toast.error(json.message ?? 'This audience member has already checked in.')
    }
  } catch (error) {
    resultType.value = 'invalid'
    result.value = { message: error.message || 'Could not check in this ticket.' }
    toast.error(result.value.message)
  } finally {
    checkingIn.value = false
  }
}

function submitManualCode() {
  void submitCode(manualCode.value)
}

onUnmounted(stopScanner)
</script>

<template>
  <div>
    <PageHeader title="Audience Check-in" subtitle="Scan each concert ticket QR code to verify and check in its audience member" />

    <div class="grid lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)] gap-5 items-start">
      <section class="admin-card overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-white/8">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center">
              <ScanQrCode class="w-5 h-5 text-gold-400" />
            </div>
            <div>
              <h2 class="text-base font-semibold text-white">QR scanner</h2>
              <p class="text-xs text-gray-500">Camera access is required for scanning</p>
            </div>
          </div>
          <span class="badge" :class="scannerState === 'scanning' ? 'badge-green' : 'badge-gray'">
            {{ scannerState === 'scanning' ? 'Camera active' : scannerState === 'starting' ? 'Starting...' : 'Camera off' }}
          </span>
        </div>

        <div class="p-4 sm:p-6">
          <div class="relative overflow-hidden rounded-xl bg-black aspect-video flex items-center justify-center">
            <video
              ref="video"
              class="w-full h-full object-cover"
              :class="{ 'hidden': scannerState !== 'scanning' }"
              autoplay
              muted
              playsinline
              aria-label="Live QR code scanner camera"
            ></video>
            <div v-if="scannerState !== 'scanning'" class="text-center px-6">
              <Camera class="w-10 h-10 text-gray-600 mx-auto mb-3" />
              <p class="text-sm text-gray-400">
                {{ scannerState === 'starting' ? 'Opening camera...' : 'Start the camera and point it at a ticket QR code.' }}
              </p>
            </div>
            <div v-if="scannerState === 'scanning'" class="pointer-events-none absolute inset-[15%] border-2 border-gold-400/80 rounded-2xl">
              <span class="absolute -top-0.5 -left-0.5 w-5 h-5 border-t-4 border-l-4 border-gold-300 rounded-tl-lg"></span>
              <span class="absolute -top-0.5 -right-0.5 w-5 h-5 border-t-4 border-r-4 border-gold-300 rounded-tr-lg"></span>
              <span class="absolute -bottom-0.5 -left-0.5 w-5 h-5 border-b-4 border-l-4 border-gold-300 rounded-bl-lg"></span>
              <span class="absolute -bottom-0.5 -right-0.5 w-5 h-5 border-b-4 border-r-4 border-gold-300 rounded-br-lg"></span>
            </div>
          </div>

          <p v-if="scannerError" class="mt-3 flex items-start gap-2 text-sm text-red-300" role="alert">
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
            {{ scannerError }}
          </p>

          <div class="mt-4 flex flex-wrap gap-2">
            <button
              v-if="scannerState !== 'scanning'"
              type="button"
              class="btn-primary"
              :disabled="checkingIn || scannerState === 'starting'"
              @click="startScanner"
            >
              <Loader2 v-if="scannerState === 'starting' || checkingIn" class="w-4 h-4 animate-spin" />
              <Camera v-else class="w-4 h-4" />
              {{ scannerState === 'starting' ? 'Starting camera...' : 'Start camera' }}
            </button>
            <button v-else type="button" class="btn-secondary" @click="stopScanner">
              Stop camera
            </button>
          </div>
        </div>
      </section>

      <div class="space-y-5">
        <section class="admin-card p-5">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center">
              <ShieldCheck class="w-4 h-4 text-gray-300" />
            </div>
            <div>
              <h2 class="text-sm font-semibold text-white">Manual check-in</h2>
              <p class="text-xs text-gray-500">Use the ticket code if scanning is unavailable</p>
            </div>
          </div>
          <form class="space-y-3" @submit.prevent="submitManualCode">
            <label for="ticket-code" class="form-label">Registration code</label>
            <input
              id="ticket-code"
              v-model="manualCode"
              class="form-input font-mono"
              placeholder="e.g. CONCERT_123_..."
              autocomplete="off"
              maxlength="100"
              required
            />
            <button type="submit" class="btn-secondary w-full justify-center" :disabled="checkingIn || !manualCode.trim()">
              <Loader2 v-if="checkingIn" class="w-4 h-4 animate-spin" />
              <ShieldCheck v-else class="w-4 h-4" />
              Check in audience
            </button>
          </form>
        </section>

        <section
          v-if="result"
          class="rounded-2xl border p-5"
          :class="resultType === 'checked_in' ? 'bg-emerald-500/8 border-emerald-500/25' : resultType === 'already_checked_in' ? 'bg-amber-500/8 border-amber-500/25' : 'bg-red-500/8 border-red-500/25'"
          aria-live="polite"
        >
          <div class="flex items-center gap-2 mb-4">
            <component
              :is="resultType === 'checked_in' ? CheckCircle2 : resultType === 'already_checked_in' ? AlertCircle : XCircle"
              class="w-5 h-5"
              :class="resultType === 'checked_in' ? 'text-emerald-400' : resultType === 'already_checked_in' ? 'text-amber-300' : 'text-red-400'"
            />
            <h2 class="font-semibold" :class="resultType === 'checked_in' ? 'text-emerald-300' : resultType === 'already_checked_in' ? 'text-amber-200' : 'text-red-300'">
              {{ resultType === 'checked_in' ? 'Check-in successful' : resultType === 'already_checked_in' ? 'Already checked in' : 'Ticket not recognized' }}
            </h2>
          </div>
          <p v-if="result.message && resultType === 'invalid'" class="text-sm text-red-200">{{ result.message }}</p>
          <template v-else>
            <p class="text-lg font-semibold text-white">{{ result.audience?.name }}</p>
            <p class="text-sm text-gray-300">{{ result.event?.title }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ result.event?.event_date }} · {{ result.event?.venue }}</p>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center gap-2 text-xs text-gray-300">
              <UserRound class="w-4 h-4" />
              <span>{{ result.audience?.registration_code }}</span>
            </div>
            <p v-if="resultType === 'already_checked_in'" class="text-xs text-amber-200 mt-3">
              Previously checked in {{ result.checked_in_at }}.
            </p>
          </template>
        </section>
      </div>
    </div>
  </div>
</template>
