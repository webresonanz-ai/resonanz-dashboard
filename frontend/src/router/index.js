import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView, meta: { title: 'Home' } },
    { path: '/schedule', name: 'schedule', component: () => import('../views/ScheduleView.vue'), meta: { title: 'Schedule' } },
    { path: '/concert', name: 'concert', component: () => import('../views/ConcertView.vue'), meta: { title: 'Concert' } },
    { path: '/news', name: 'news', component: () => import('../views/NewsView.vue'), meta: { title: 'News' } },
    { path: '/courses', name: 'courses', component: () => import('../views/CoursesView.vue'), meta: { title: 'Courses & Fee' } },
    { path: '/facilitation', name: 'facilitation', component: () => import('../views/FacilitationView.vue'), meta: { title: 'Facilitation' } },
    { path: '/teachers', name: 'teachers', component: () => import('../views/TeachersView.vue'), meta: { title: 'Teachers' } },
    { path: '/contact', name: 'contact', component: () => import('../views/ContactView.vue'), meta: { title: 'Contact' } },
  ],
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to) => {
  document.title = `${to.meta.title} | Resonanz Music Foundation`
})

export default router