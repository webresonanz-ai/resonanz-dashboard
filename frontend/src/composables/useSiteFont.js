const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

function pickText(v) {
  if (typeof v === 'string') return v;
  if (v && typeof v === 'object') return v.en ?? v.id ?? '';
  return '';
}

function sanitizeFamily(name) {
  // Keep it a valid CSS family name: letters, digits, spaces, - _
  const s = String(name ?? '').trim().replace(/['";\\]/g, '').slice(0, 80);
  return s || 'SiteCustomFont';
}

function formatHint(url) {
  const ext = String(url ?? '').split('?')[0].split('.').pop()?.toLowerCase() ?? '';
  if (ext === 'woff2') return ` format('woff2')`;
  if (ext === 'woff') return ` format('woff')`;
  if (ext === 'otf') return ` format('opentype')`;
  return ` format('truetype')`;
}

function toFontSrc(url) {
  // Route uploaded fonts through /api/fonts/:name so the response always
  // carries CORS headers. Static /uploads/* files may be served by the web
  // server directly (php -S without router, Apache) with no CORS headers,
  // which makes browsers refuse the font. External (http…) URLs pass through.
  const s = String(url ?? '');
  const m = s.match(/\/uploads\/fonts\/([^/?#]+)/i);
  if (!m) return /^https?:\/\//i.test(s) ? s : `${API_BASE}${s.startsWith('/') ? s : `/${s}`}`;
  if (/^https?:\/\//i.test(s)) {
    try {
      if (new URL(s).origin !== new URL(API_BASE).origin) return s;
    } catch { return s; }
  }
  return `${API_BASE}/api/fonts/${m[1]}`;
}

/**
 * useSiteFont — applies the admin-chosen custom font (if any) site-wide.
 *
 * Reads `site_font_family` + `site_font_url` from the public /api/home
 * snapshot and injects an @font-face + overrides for body and headings.
 * No custom font configured means "do nothing" and the built-in
 * Inter / Playfair Display stay.
 */
export function applySiteFont() {
  if (typeof document === 'undefined') return
  if (document.getElementById('site-custom-font')) return

  globalThis
    .fetch(`${API_BASE}/api/home`)
    .then((res) => (res.ok ? res.json() : null))
    .then((json) => {
      const data = json?.data ?? {}
      const url = String(pickText(data.site_font_url) ?? '').trim()
      if (!url) return
      const family = sanitizeFamily(pickText(data.site_font_family))
      const abs = toFontSrc(url)
      const style = document.createElement('style')
      style.id = 'site-custom-font'
      style.textContent =
        `@font-face{font-family:'${family}';src:url('${abs}')${formatHint(abs)};font-weight:400;font-style:normal;font-display:swap;}` +
        `body{font-family:'${family}',ui-sans-serif,system-ui,sans-serif !important;}` +
        `h1,h2,h3,h4,h5,h6,.font-serif{font-family:'${family}',Georgia,serif !important;}`
      document.head.appendChild(style)
    })
    .catch(() => {
      // Font is decorative — never break the page over it
    })
}
