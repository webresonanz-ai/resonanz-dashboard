import { createI18n } from 'vue-i18n'
import en from './en.json'
import id from './id.json'

const STORAGE_KEY = 'resonanz-locale'

function detectLocale() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === 'en' || saved === 'id') return saved
  } catch { /* ignore */ }
  const nav = (navigator.language || 'en').toLowerCase()
  return nav.startsWith('id') ? 'id' : 'en'
}

export const SUPPORTED_LOCALES = [
  { code: 'en', label: 'EN', name: 'English' },
  { code: 'id', label: 'ID', name: 'Indonesia' },
]

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: detectLocale(),
  fallbackLocale: 'en',
  messages: { en, id },
})

export function setLocale(code) {
  if (code !== 'en' && code !== 'id') return
  i18n.global.locale.value = code
  try {
    localStorage.setItem(STORAGE_KEY, code)
  } catch { /* ignore */ }
  document.documentElement.setAttribute('lang', code === 'id' ? 'id' : 'en')
}

export function getLocale() {
  return i18n.global.locale.value
}

// Set initial <html lang>
if (typeof document !== 'undefined') {
  document.documentElement.setAttribute('lang', i18n.global.locale.value === 'id' ? 'id' : 'en')
}

export default i18n
