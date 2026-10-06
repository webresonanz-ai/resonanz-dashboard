import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ─── Public routes ───────────────────────────────────────────
    { path: '/',            name: 'home',         component: HomeView,                                                              meta: { title: 'Home' } },
    { path: '/schedule',    name: 'schedule',     component: () => import('../views/ScheduleView.vue'),                             meta: { title: 'Schedule' } },
    { path: '/concert',     name: 'concert',      component: () => import('../views/ConcertView.vue'),                              meta: { title: 'Concert' } },
    { path: '/news',        name: 'news',         component: () => import('../views/NewsView.vue'),                                 meta: { title: 'News' } },
    { path: '/courses',     name: 'courses',      component: () => import('../views/CoursesView.vue'),                              meta: { title: 'Courses & Fee' } },
    { path: '/facilitation',name: 'facilitation', component: () => import('../views/FacilitationView.vue'),                        meta: { title: 'Facilitation' } },
    { path: '/teachers',    name: 'teachers',     component: () => import('../views/TeachersView.vue'),                             meta: { title: 'Teachers' } },
    { path: '/contact',     name: 'contact',      component: () => import('../views/ContactView.vue'),                              meta: { title: 'Contact' } },

    // ─── Auth routes (guests only — redirect if already logged in) ─
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { title: 'Sign In', guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      meta: { title: 'Create Account', guestOnly: true },
    },

    // ─── Protected routes (require authentication) ─────────────────
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { title: 'Dashboard', requiresAuth: true },
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to, from) => {
  // Update page title
  document.title = `${to.meta.title} | Resonanz Music Foundation`

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

export default router
