import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guestOnly: true } },
    {
      path: '/',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '',          name: 'dashboard',    component: () => import('@/views/DashboardView.vue') },
        { path: 'schedule',  name: 'schedule',     component: () => import('@/views/ScheduleView.vue') },
        { path: 'events',    name: 'events',       component: () => import('@/views/EventsView.vue') },
        { path: 'news',      name: 'news',         component: () => import('@/views/NewsView.vue') },
        { path: 'courses',   name: 'courses',      component: () => import('@/views/CoursesView.vue') },
        { path: 'facilities',name: 'facilities',   component: () => import('@/views/FacilitiesView.vue') },
        { path: 'teachers',  name: 'teachers',     component: () => import('@/views/TeachersView.vue') },
        { path: 'contact',   name: 'contact',      component: () => import('@/views/ContactView.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return { name: 'login' }
  if (to.meta.guestOnly   && auth.isAuthenticated)   return { name: 'dashboard' }
})

export default router
