import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {
  const isMobileMenuOpen = ref(false)
  const currentYear = new Date().getFullYear()

  const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
  }

  const closeMobileMenu = () => {
    isMobileMenuOpen.value = false
  }

  return { isMobileMenuOpen, currentYear, toggleMobileMenu, closeMobileMenu }
})