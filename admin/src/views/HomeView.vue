<script setup>
import { onMounted, ref, reactive } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'
import PageHeader from '@/components/PageHeader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LangField from '@/components/LangField.vue'
import {
  Save, RotateCcw, Loader2, Upload, Link2, ImagePlus, Trash2,
} from 'lucide-vue-next'

const auth  = useAuthStore()
const toast = useToastStore()
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

// ─── Field definitions (must match backend HomeController) ───
const TEXT_KEYS = [
  'hero_badge',
  'hero_title_a',
  'hero_title_highlight',
  'hero_title_b',
  'hero_subtitle',
  'hero_primary_label',
  'hero_secondary_label',
  'hero_award_label',
  'hero_students_label',
  'stat_students_label',
  'stat_events_label',
  'stat_awards_label',
  'stat_faculty_label',
  'why_eyebrow',
  'why_title_a',
  'why_subtitle',
  'cta_title_a',
  'cta_title_highlight',
  'cta_subtitle',
  'cta_button_label',
]

const str = (v) => (typeof v === 'string' ? v : '')

// ─── Form state ───
const form = reactive({})
TEXT_KEYS.forEach((k) => { form[k] = { en: '', id: '' } })

const neutral = reactive({
  hero_award_value: '',
  stat_students_value: '',
  stat_awards_value: '',
  hero_background_image: '',
  hero_side_image: '',
})

const blankFeature = () => ({ title_en: '', title_id: '', desc_en: '', desc_id: '' })
const features = ref([blankFeature(), blankFeature(), blankFeature()])

const loading    = ref(false)
const saving     = ref(false)
const uploading  = ref(false)
const resetting  = ref(false)
const confirmReset = ref(false)

// ─── Image fields (background + side card) ───
function makeImageField() {
  return reactive({ mode: 'upload', file: null, preview: '', inputEl: null })
}
const bgImage   = makeImageField()
const sideImage = makeImageField()

function resolveImg(path) {
  const s = String(path ?? '').trim()
  if (!s) return ''
  return /^https?:\/\//i.test(s) ? s : `${API}${s.startsWith('/') ? s : `/${s}`}`
}
const isRemote = (s) => /^https?:\/\//i.test(String(s ?? '').trim())

function onFile(field, e) {
  const f = e.target.files?.[0]
  if (!f) return
  if (!f.type.startsWith('image/')) { toast.error('Please choose an image file.'); return }
  if (f.size > 5 * 1024 * 1024) { toast.error('Image too large. Max 5 MB.'); return }
  field.file = f
  field.mode = 'upload'
  if (field.preview.startsWith('blob:')) URL.revokeObjectURL(field.preview)
  field.preview = URL.createObjectURL(f)
}

function clearImage(field, neutralKey) {
  field.file = null
  field.preview = ''
  neutral[neutralKey] = ''
  if (field.inputEl) field.inputEl.value = ''
}

async function uploadImage(file) {
  const fd = new FormData()
  fd.append('image', file)
  fd.append('folder', 'home')
  const json = await auth.apiFetch('/api/admin/uploads', { method: 'POST', body: fd })
  return json.data?.url ?? null
}

// ─── Load ───
async function load() {
  loading.value = true
  try {
    const json = await auth.apiFetch('/api/admin/home')
    const data = json.data ?? {}
    TEXT_KEYS.forEach((k) => {
      const v = data[k]
      form[k] = typeof v === 'string'
        ? { en: v, id: '' }
        : { en: str(v?.en), id: str(v?.id) }
    })
    Object.keys(neutral).forEach((k) => {
      const v = data[k]
      neutral[k] = typeof v === 'string' ? v : str(v?.en)
    })
    const raw = str(data.features?.en ?? data.features)
    if (raw) {
      try {
        const arr = JSON.parse(raw)
        if (Array.isArray(arr) && arr.length) {
          features.value = [0, 1, 2].map((i) => ({
            title_en: str(arr[i]?.title_en),
            title_id: str(arr[i]?.title_id),
            desc_en:  str(arr[i]?.desc_en),
            desc_id:  str(arr[i]?.desc_id),
          }))
        }
      } catch { /* keep blanks on invalid JSON */ }
    }
    syncImageField(bgImage, neutral.hero_background_image)
    syncImageField(sideImage, neutral.hero_side_image)
  } catch (e) {
    toast.error(e.message)
  } finally {
    loading.value = false
  }
}

function syncImageField(field, value) {
  field.file = null
  field.preview = resolveImg(value)
  field.mode = isRemote(value) && !String(value).startsWith('/uploads/') ? 'url' : 'upload'
  if (field.inputEl) field.inputEl.value = ''
}

onMounted(load)

// ─── Save ───
async function save() {
  saving.value = true
  try {
    // Upload pending files first
    for (const [field, key] of [[bgImage, 'hero_background_image'], [sideImage, 'hero_side_image']]) {
      if (field.mode === 'upload' && field.file) {
        uploading.value = true
        const url = await uploadImage(field.file)
        uploading.value = false
        if (!url) { saving.value = false; return }
        neutral[key] = url
        field.file = null
        field.preview = resolveImg(url)
      }
    }
    const payload = {}
    TEXT_KEYS.forEach((k) => { payload[k] = { en: form[k].en, id: form[k].id } })
    Object.keys(neutral).forEach((k) => { payload[k] = neutral[k] })
    payload.features = JSON.stringify(features.value)
    await auth.apiFetch('/api/admin/home', { method: 'PUT', body: JSON.stringify(payload) })
    toast.success('Home page saved.')
  } catch (e) {
    uploading.value = false
    toast.error(e.message)
  } finally {
    saving.value = false
  }
}

// ─── Reset to defaults ───
async function doReset() {
  resetting.value = true
  try {
    await auth.apiFetch('/api/admin/home', { method: 'DELETE' })
    confirmReset.value = false
    TEXT_KEYS.forEach((k) => { form[k] = { en: '', id: '' } })
    Object.keys(neutral).forEach((k) => { neutral[k] = '' })
    features.value = [blankFeature(), blankFeature(), blankFeature()]
    syncImageField(bgImage, '')
    syncImageField(sideImage, '')
    toast.success('Home page reset to defaults.')
  } catch (e) {
    toast.error(e.message)
  } finally {
    resetting.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Home Page" subtitle="Manage guest home content in English & Indonesian. Empty fields fall back to the built-in defaults." />

    <!-- Action bar -->
    <div class="flex items-center justify-end gap-3 mb-6">
      <button class="btn-secondary" :disabled="saving || loading" @click="confirmReset = true">
        <RotateCcw class="w-4 h-4" />Reset to defaults
      </button>
      <button class="btn-primary" :disabled="saving || uploading || loading" @click="save">
        <Loader2 v-if="saving || uploading" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        {{ uploading ? 'Uploading…' : saving ? 'Saving…' : 'Save changes' }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-24">
      <Loader2 class="w-6 h-6 text-gold-400 animate-spin" />
    </div>

    <div v-else class="space-y-6 max-w-4xl">

      <!-- ─── HERO ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">Hero section</h3>
          <p class="text-xs text-gray-500 mt-0.5">Top banner: badge, headline, subtitle and buttons.</p>
        </div>
        <LangField v-model="form.hero_badge" label="Badge pill" />
        <div class="grid sm:grid-cols-3 gap-4">
          <LangField v-model="form.hero_title_a" label="Title — first line" />
          <LangField v-model="form.hero_title_highlight" label="Title — highlighted word" />
          <LangField v-model="form.hero_title_b" label="Title — second line" />
        </div>
        <LangField v-model="form.hero_subtitle" label="Subtitle" :multiline="true" />
        <div class="grid sm:grid-cols-2 gap-4">
          <LangField v-model="form.hero_primary_label" label="Primary button label" />
          <LangField v-model="form.hero_secondary_label" label="Secondary button label" />
        </div>

        <!-- Background image -->
        <div>
          <label class="form-label">Background image (optional)</label>
          <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
            <div class="flex gap-2">
              <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': bgImage.mode === 'upload' }" @click="bgImage.mode = 'upload'"><Upload class="w-4 h-4" />Upload</button>
              <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': bgImage.mode === 'url' }" @click="bgImage.mode = 'url'"><Link2 class="w-4 h-4" />Image URL</button>
            </div>
            <div v-if="bgImage.mode === 'upload'">
              <input :ref="(el) => (bgImage.inputEl = el)" type="file" accept="image/*" class="form-input" @change="onFile(bgImage, $event)" />
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP or GIF — max 5 MB. Shown dimmed behind the hero text.</p>
            </div>
            <div v-else>
              <input v-model="neutral.hero_background_image" type="url" placeholder="https://example.com/hero-bg.jpg" class="form-input" @input="bgImage.preview = resolveImg(neutral.hero_background_image)" />
            </div>
            <div v-if="bgImage.preview" class="relative">
              <img :src="bgImage.preview" alt="Background preview" class="w-full h-40 rounded-xl object-cover border border-white/10" />
              <button type="button" class="btn-icon absolute top-2 right-2 !bg-black/60" @click="clearImage(bgImage, 'hero_background_image')"><Trash2 class="w-4 h-4" /></button>
            </div>
            <div v-else class="flex items-center gap-2 text-xs text-gray-500"><ImagePlus class="w-4 h-4" />No background — decorative rings are shown instead.</div>
          </div>
        </div>

        <!-- Side card image -->
        <div>
          <label class="form-label">Hero side image (optional)</label>
          <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
            <div class="flex gap-2">
              <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': sideImage.mode === 'upload' }" @click="sideImage.mode = 'upload'"><Upload class="w-4 h-4" />Upload</button>
              <button type="button" class="btn-secondary flex-1" :class="{ '!border-gold-400 !text-gold-300': sideImage.mode === 'url' }" @click="sideImage.mode = 'url'"><Link2 class="w-4 h-4" />Image URL</button>
            </div>
            <div v-if="sideImage.mode === 'upload'">
              <input :ref="(el) => (sideImage.inputEl = el)" type="file" accept="image/*" class="form-input" @change="onFile(sideImage, $event)" />
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP or GIF — max 5 MB. Replaces the music-note visual card.</p>
            </div>
            <div v-else>
              <input v-model="neutral.hero_side_image" type="url" placeholder="https://example.com/hero.jpg" class="form-input" @input="sideImage.preview = resolveImg(neutral.hero_side_image)" />
            </div>
            <div v-if="sideImage.preview" class="relative">
              <img :src="sideImage.preview" alt="Side image preview" class="w-full h-40 rounded-xl object-cover border border-white/10" />
              <button type="button" class="btn-icon absolute top-2 right-2 !bg-black/60" @click="clearImage(sideImage, 'hero_side_image')"><Trash2 class="w-4 h-4" /></button>
            </div>
            <div v-else class="flex items-center gap-2 text-xs text-gray-500"><ImagePlus class="w-4 h-4" />No image — default music visual is shown.</div>
          </div>
        </div>
      </section>

      <!-- ─── HERO BADGES ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">Hero floating badges</h3>
          <p class="text-xs text-gray-500 mt-0.5">Small cards overlapping the hero visual.</p>
        </div>
        <div>
          <label class="form-label">Award count</label>
          <input v-model="neutral.hero_award_value" type="number" min="0" class="form-input max-w-[160px]" placeholder="45" />
        </div>
        <LangField v-model="form.hero_award_label" label="Award badge label" />
        <LangField v-model="form.hero_students_label" label="Students badge label" />
      </section>

      <!-- ─── STATS ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">Stats band</h3>
          <p class="text-xs text-gray-500 mt-0.5">Events & Faculty counts stay live from the database; Students & Awards numbers are editable here.</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Students count</label>
            <input v-model="neutral.stat_students_value" type="number" min="0" class="form-input" placeholder="500" />
          </div>
          <div>
            <label class="form-label">Awards count</label>
            <input v-model="neutral.stat_awards_value" type="number" min="0" class="form-input" placeholder="45" />
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <LangField v-model="form.stat_students_label" label="Label: Students" />
          <LangField v-model="form.stat_events_label" label="Label: Events" />
          <LangField v-model="form.stat_awards_label" label="Label: Awards" />
          <LangField v-model="form.stat_faculty_label" label="Label: Faculty" />
        </div>
      </section>

      <!-- ─── FEATURES ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">“Why Choose Resonanz” cards</h3>
          <p class="text-xs text-gray-500 mt-0.5">Three feature cards below the stats band.</p>
        </div>
        <LangField v-model="form.why_eyebrow" label="Section eyebrow" />
        <LangField v-model="form.why_title_a" label="Section title (before “Resonanz”)" />
        <LangField v-model="form.why_subtitle" label="Section subtitle" :multiline="true" />
        <div
          v-for="(f, i) in features"
          :key="i"
          class="rounded-xl border border-white/10 bg-white/[0.02] p-4 space-y-4"
        >
          <p class="text-xs font-semibold tracking-widest text-gold-400 uppercase">Card {{ i + 1 }}</p>
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="form-label">Title (EN)</label>
              <input v-model="f.title_en" class="form-input" :placeholder="`Card ${i + 1} title (EN)`" />
            </div>
            <div>
              <label class="form-label">Title (ID)</label>
              <input v-model="f.title_id" class="form-input" :placeholder="`Card ${i + 1} title (ID)`" />
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="form-label">Description (EN)</label>
              <textarea v-model="f.desc_en" rows="2" class="form-input resize-none" :placeholder="`Card ${i + 1} description (EN)`" />
            </div>
            <div>
              <label class="form-label">Description (ID)</label>
              <textarea v-model="f.desc_id" rows="2" class="form-input resize-none" :placeholder="`Card ${i + 1} description (ID)`" />
            </div>
          </div>
        </div>
      </section>

      <!-- ─── CTA ─── -->
      <section class="admin-card p-6 space-y-5">
        <div>
          <h3 class="font-semibold text-white">Call-to-action banner</h3>
          <p class="text-xs text-gray-500 mt-0.5">Bottom banner linking to the contact page.</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <LangField v-model="form.cta_title_a" label="Title (first part)" />
          <LangField v-model="form.cta_title_highlight" label="Title (highlighted part)" />
        </div>
        <LangField v-model="form.cta_subtitle" label="Subtitle" :multiline="true" />
        <LangField v-model="form.cta_button_label" label="Button label" />
      </section>

      <!-- Bottom action bar -->
      <div class="flex items-center justify-end gap-3 pb-4">
        <button class="btn-secondary" :disabled="saving" @click="confirmReset = true">
          <RotateCcw class="w-4 h-4" />Reset to defaults
        </button>
        <button class="btn-primary" :disabled="saving || uploading" @click="save">
          <Loader2 v-if="saving || uploading" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          {{ uploading ? 'Uploading…' : saving ? 'Saving…' : 'Save changes' }}
        </button>
      </div>
    </div>

    <ConfirmDialog
      :open="confirmReset"
      title="Reset Home Page"
      message="This clears all custom home content (both languages) and restores the built-in defaults. Continue?"
      :loading="resetting"
      @confirm="doReset"
      @cancel="confirmReset = false"
    />
  </div>
</template>
