<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import { Save, RotateCcw, Loader2, Upload, Trash2, Type, Check } from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToastStore()
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const FONT_EXTS = ['ttf', 'otf', 'woff', 'woff2']
const MAX_BYTES = 10 * 1024 * 1024 // 10 MB
const PREVIEW_FACE = 'AdminFontPreview'

const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const resetting = ref(false)

const activeFamily = ref('')
const activeUrl = ref('')

const family = ref('')
const file = ref(null)
const fileInput = ref(null)
const localPreview = ref('') // blob: url for instant preview

function resolveFont(path) {
  const s = String(path ?? '').trim()
  if (!s || s.startsWith('blob:')) return s
  return /^https?:\/\//i.test(s) ? s : `${API}${s.startsWith('/') ? s : `/${s}`}`
}

// Previewed font = newly picked file, else the active site font
const previewSrc = computed(() => localPreview.value || resolveFont(activeUrl.value))
const hasCustom = computed(() => !!activeUrl.value)

function refreshPreviewFace() {
  document.getElementById('admin-font-preview')?.remove()
  if (!previewSrc.value) return
  const style = document.createElement('style')
  style.id = 'admin-font-preview'
  style.textContent = `@font-face{font-family:'${PREVIEW_FACE}';src:url('${previewSrc.value}');font-display:swap;}`
  document.head.appendChild(style)
}

function prettifyFilename(name) {
  return String(name ?? '')
    .replace(/\.[^.]+$/, '')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

function onFile(e) {
  const f = e.target.files?.[0]
  if (!f) return
  const ext = String(f.name ?? '').split('.').pop()?.toLowerCase() ?? ''
  if (!FONT_EXTS.includes(ext)) { toast.error('Invalid font type. Allowed: ttf, otf, woff, woff2.'); return }
  if (f.size > MAX_BYTES) { toast.error('Font too large. Max 10 MB.'); return }
  file.value = f
  if (localPreview.value.startsWith('blob:')) URL.revokeObjectURL(localPreview.value)
  localPreview.value = URL.createObjectURL(f)
  if (!family.value.trim()) family.value = prettifyFilename(f.name)
  refreshPreviewFace()
}

function clearFile() {
  file.value = null
  if (localPreview.value.startsWith('blob:')) URL.revokeObjectURL(localPreview.value)
  localPreview.value = ''
  if (fileInput.value) fileInput.value.value = ''
  refreshPreviewFace()
}

async function load() {
  loading.value = true
  try {
    const json = await auth.apiFetch('/api/admin/home')
    const data = json.data ?? {}
    const pick = (v) => (typeof v === 'string' ? v : String(v?.en ?? ''))
    activeFamily.value = pick(data.site_font_family)
    activeUrl.value = pick(data.site_font_url)
    refreshPreviewFace()
  } catch (e) {
    toast.error(e.message)
  } finally {
    loading.value = false
  }
}

onMounted(load)
onUnmounted(() => {
  document.getElementById('admin-font-preview')?.remove()
  if (localPreview.value.startsWith('blob:')) URL.revokeObjectURL(localPreview.value)
})

async function save() {
  if (!file.value && !activeUrl.value) { toast.error('Choose a .ttf/.otf/.woff/.woff2 file first.'); return }
  const name = family.value.trim() || activeFamily.value || 'Custom Font'
  saving.value = true
  try {
    let url = activeUrl.value
    if (file.value) {
      uploading.value = true
      const fd = new FormData()
      fd.append('font', file.value)
      fd.append('folder', 'fonts')
      const json = await auth.apiFetch('/api/admin/uploads', { method: 'POST', body: fd })
      uploading.value = false
      if (!json.data?.url) return
      url = json.data.url
    }
    await auth.apiFetch('/api/admin/home', {
      method: 'PUT',
      body: JSON.stringify({ site_font_family: name, site_font_url: url }),
    })
    activeFamily.value = name
    activeUrl.value = url
    clearFile()
    refreshPreviewFace()
    toast.success(`Site font set to “${name}”.`)
  } catch (e) {
    uploading.value = false
    toast.error(e.message)
  } finally {
    saving.value = false
  }
}

async function resetToDefault() {
  resetting.value = true
  try {
    await auth.apiFetch('/api/admin/home', {
      method: 'PUT',
      body: JSON.stringify({ site_font_family: '', site_font_url: '' }),
    })
    activeFamily.value = ''
    activeUrl.value = ''
    clearFile()
    toast.success('Site font reset to default.')
  } catch (e) {
    toast.error(e.message)
  } finally {
    resetting.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Fonts" subtitle="Upload a custom font (.ttf) for the guest website" />

    <div v-if="loading" class="flex items-center justify-center py-24">
      <Loader2 class="w-6 h-6 text-gold-400 animate-spin" />
    </div>

    <div v-else class="space-y-6 max-w-4xl">
      <!-- ─── Active font ─── -->
      <section class="admin-card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-semibold text-white">Active site font</h3>
            <p class="text-xs text-gray-500 mt-0.5">Applied to the whole guest website.</p>
          </div>
          <button v-if="hasCustom" class="btn-secondary" :disabled="resetting" @click="resetToDefault">
            <Loader2 v-if="resetting" class="w-4 h-4 animate-spin" />
            <RotateCcw v-else class="w-4 h-4" />Default font
          </button>
        </div>
        <div v-if="hasCustom" class="rounded-xl border border-gold-500/30 bg-white/[0.03] p-5">
          <p class="flex items-center gap-2 text-sm text-gold-300 font-medium mb-2">
            <Check class="w-4 h-4" />{{ activeFamily || 'Custom Font' }}
          </p>
          <p class="text-3xl text-white" :style="{ fontFamily: `'${PREVIEW_FACE}', sans-serif` }">
            Resonanz Music Foundation
          </p>
          <p class="text-gray-400 mt-1" :style="{ fontFamily: `'${PREVIEW_FACE}', sans-serif` }">
            AaBbCcDdEeFfGg 0123456789 — Where harmony meets excellence.
          </p>
        </div>
        <p v-else class="text-sm text-gray-500 py-4">No custom font set — the site uses Inter / Playfair Display.</p>
      </section>

      <!-- ─── Upload ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">Upload new font</h3>
          <p class="text-xs text-gray-500 mt-0.5">TTF, OTF, WOFF or WOFF2 — max 10 MB.</p>
        </div>
        <div>
          <label class="form-label">Font family name</label>
          <div class="flex items-center gap-2">
            <Type class="w-4 h-4 text-gold-400 shrink-0" />
            <input v-model="family" class="form-input" placeholder="e.g. Poppins" />
          </div>
        </div>
        <div>
          <label class="form-label">Font file</label>
          <input ref="fileInput" type="file" accept=".ttf,.otf,.woff,.woff2" class="form-input" @change="onFile" />
        </div>

        <!-- Live preview of the picked file -->
        <div v-if="localPreview" class="rounded-xl border border-white/10 bg-white/[0.03] p-5 relative">
          <p class="text-xs text-gray-500 mb-2">Preview — {{ family || 'Custom Font' }}</p>
          <p class="text-4xl text-white" :style="{ fontFamily: `'${PREVIEW_FACE}', sans-serif` }">
            AaBbCcDdEeFfGg
          </p>
          <p class="text-xl text-gold-300 mt-2" :style="{ fontFamily: `'${PREVIEW_FACE}', sans-serif` }">
            Where harmony meets excellence 0123456789
          </p>
          <button type="button" class="btn-icon absolute top-3 right-3" @click="clearFile"><Trash2 class="w-4 h-4" /></button>
        </div>

        <div class="flex items-center justify-end">
          <button class="btn-primary" :disabled="saving || uploading || (!file && !hasCustom)" @click="save">
            <Loader2 v-if="saving || uploading" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            {{ uploading ? 'Uploading…' : saving ? 'Saving…' : file ? 'Upload & set as site font' : 'Rename active font' }}
          </button>
        </div>
        <p class="flex items-center gap-2 text-xs text-gray-500">
          <Upload class="w-3.5 h-3.5" />Without picking a file, saving only renames the active font.
        </p>
      </section>
    </div>
  </div>
</template>
