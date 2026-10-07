import { createRouter, createWebHistory } from 'vue-router'
import { watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import i18n from '@/i18n'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ─── Public routes ───────────────────────────────────────────
    { path: '/',            name: 'home',         component: HomeView,                                                              meta: { titleKey: 'meta.home' } },
    { path: '/schedule',    name: 'schedule',     component: () => import('../views/ScheduleView.vue'),                             meta: { titleKey: 'meta.schedule' } },
    { path: '/event',       name: 'event',        component: () => import('../views/EventView.vue'),                                meta: { titleKey: 'meta.events' } },
    { path: '/event/:id/register', name: 'event-register', component: () => import('../views/EventRegisterView.vue'),            meta: { titleKey: 'meta.eventRegister' } },
    { path: '/news',        name: 'news',         component: () => import('../views/NewsView.vue'),                                 meta: { titleKey: 'meta.news' } },
    { path: '/courses',     name: 'courses',      component: () => import('../views/CoursesView.vue'),                              meta: { titleKey: 'meta.courses' } },
    { path: '/facilitation',name: 'facilitation', component: () => import('../views/FacilitationView.vue'),                        meta: { titleKey: 'meta.facilitation' } },
    { path: '/teachers',    name: 'teachers',     component: () => import('../views/TeachersView.vue'),                             meta: { titleKey: 'meta.teachers' } },
    { path: '/contact',     name: 'contact',      component: () => import('../views/ContactView.vue'),                              meta: { titleKey: 'meta.contact' } },

    // ─── Auth routes (guests only — redirect if already logged in) ─
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { titleKey: 'meta.login', guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      meta: { titleKey: 'meta.register', guestOnly: true },
    },

    // ─── Protected routes (require authentication) ─────────────────
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { titleKey: 'meta.dashboard', requiresAuth: true },
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

export function updatePageTitle(route) {
  const key = route?.meta?.titleKey
  const title = key ? i18n.global.t(key) : route?.meta?.title ?? ''
  document.title = title ? `${title} | Resonanz Music Foundation` : 'Resonanz Music Foundation'
}

router.beforeEach((to, from) => {
  // Update page title (locale-aware)
  updatePageTitle(to)

  // Lazy-load the auth store inside the guard (avoids Pinia "not activated" error)
  const authStore = useAuthStore()

  // Protect routes that need authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Redirect already-authenticated users away from login/register
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Allow navigation
})

// Re-translate the page title whenever the language changes
watch(
  () => i18n.global.locale.value,
  () => updatePageTitle(router.currentRoute.value),
)

export default router
