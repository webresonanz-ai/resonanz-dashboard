import { onMounted, onUnmounted } from 'vue'

/**
 * useScrollReveal — attaches an IntersectionObserver to add/remove
 * the `.visible` class on elements that carry a `.reveal*` class.
 *
 * Call once per page-level component.
 */
export function useScrollReveal() {
  let observer = null

  onMounted(() => {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible')
            // Keep visible once revealed
            observer.unobserve(entry.target)
          }
        })
      },
      { threshold: 0.12, rootMargin: '0px 0px -48px 0px' }
    )

    const targets = document.querySelectorAll(
      '.reveal, .reveal-left, .reveal-right, .reveal-scale'
    )
    targets.forEach((el) => observer.observe(el))
  })

  onUnmounted(() => {
    observer?.disconnect()
  })
}
