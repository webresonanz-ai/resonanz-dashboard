import { onMounted, onUnmounted, nextTick } from 'vue'

/**
 * useScrollReveal
 *
 * Problem this solves:
 *   Views fetch data asynchronously. The v-for cards only exist in the DOM
 *   AFTER the API response arrives and Vue re-renders. A one-shot onMounted
 *   scan misses all of those elements — they get `.reveal` (opacity:0) but
 *   are never observed, so they stay invisible.
 *
 * Solution:
 *   1. IntersectionObserver — adds `.visible` when an observed element
 *      enters the viewport.
 *   2. MutationObserver on document.body — detects when new child nodes
 *      are added (i.e. Vue renders the v-for items) and immediately
 *      re-scans for unobserved `.reveal*` elements.
 *   3. `scanAndObserve()` is exported so views / composables can call it
 *      explicitly after an async operation (belt-and-suspenders).
 */

// Module-level singleton so multiple views share one observer pair
let io = null   // IntersectionObserver
let mo = null   // MutationObserver
let refCount = 0

const SELECTOR = '.reveal, .reveal-left, .reveal-right, .reveal-scale'

function getIO() {
  if (io) return io
  io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible')
          io.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.06, rootMargin: '0px 0px -24px 0px' }
  )
  return io
}

function getMO() {
  if (mo) return mo
  mo = new MutationObserver((mutations) => {
    let hasAdditions = false
    for (const m of mutations) {
      if (m.addedNodes.length > 0) { hasAdditions = true; break }
    }
    if (hasAdditions) nextTick(scanAndObserve)
  })
  mo.observe(document.body, { childList: true, subtree: true })
  return mo
}

export function scanAndObserve() {
  const observer = getIO()
  document.querySelectorAll(SELECTOR).forEach((el) => {
    if (!el.classList.contains('visible')) {
      observer.observe(el)
    }
  })
}

export function useScrollReveal() {
  onMounted(() => {
    refCount++
    getMO()                      // ensure mutation observer is running
    nextTick(scanAndObserve)     // scan elements already in DOM
  })

  onUnmounted(() => {
    refCount--
    if (refCount <= 0) {
      io?.disconnect(); io = null
      mo?.disconnect(); mo = null
      refCount = 0
    }
  })
}
