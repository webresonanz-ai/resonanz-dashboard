const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

/**
 * useSiteFont — applies the admin-chosen custom font (if any) site-wide.
 *
 * Reads `site_font_url` from the public /api/home snapshot and injects an
 * @font-face + overrides for body and headings. No custom font configured
 * means "do nothing" and the built-in Inter / Playfair Display stay.
 */
export function applySiteFont() {
  if (typeof document === 'undefined') return
  if (document.getElementById('site-custom-font')) return

  globalThis
    .fetch(`${API_BASE}/api/home`)
    .then((res) => (res.ok ? res.json() : null))
    .then((json) => {
      const data = json?.data ?? {}
      const raw = typeof data.site_font_url === 'string' ? data.site_font_url : data.site_font_url?.en
      const url = String(raw ?? '').trim()
      if (!url) return
      const abs = /^https?:\/\//i.test(url) ? url : `${API_BASE}${url.startsWith('/') ? url : `/${url}`}`
      const style = document.createElement('style')
      style.id = 'site-custom-font'
      style.textContent =
        `@font-face{font-family:'SiteCustomFont';src:url('${abs}');font-display:swap;}` +
        `body{font-family:'SiteCustomFont',ui-sans-serif,system-ui,sans-serif !important;}` +
        `h1,h2,h3,h4,h5,h6,.font-serif{font-family:'SiteCustomFont',Georgia,serif !important;}`
      document.head.appendChild(style)
    })
    .catch(() => {
      // Font is decorative — never break the page over it
    })
}
