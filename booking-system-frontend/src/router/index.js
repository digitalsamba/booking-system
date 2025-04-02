import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Lazy load components for better performance
const HomeView = () => import('../views/HomeView.vue')
const LoginView = () => import('../views/LoginView.vue')
const RegisterView = () => import('../views/RegisterView.vue')
const BookingsView = () => import('../views/BookingsView.vue')
const ProfileView = () => import('../views/ProfileView.vue')
const AvailabilityView = () => import('../views/AvailabilityView.vue')
const PublicBookingView = () => import('../views/PublicBookingView.vue')

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { requiresGuest: true }
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingsView,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true }
  },
  {
    path: '/availability',
    name: 'availability',
    component: AvailabilityView,
    meta: { requiresAuth: true }
  },
  {
    path: '/booking/:username',
    name: 'public-booking',
    component: PublicBookingView,
    meta: { requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory('/'),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isLoggedIn = authStore.isLoggedIn

  // Routes that require authentication
  if (to.meta.requiresAuth && !isLoggedIn) {
    next({ name: 'login' })
  } 
  // Routes for guests only (login, register)
  else if (to.meta.requiresGuest && isLoggedIn) {
    next({ name: 'home' })
  }
  else {
    next()
  }
})

export default router