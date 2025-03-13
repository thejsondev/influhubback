import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Home.vue'),
    meta: { title: 'Home' }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { title: 'Login' }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Register.vue'),
    meta: { title: 'Register' }
  },
  {
    path: '/components',
    name: 'ComponentsList',
    component: () => import('../views/ComponentsList.vue'),
    meta: { title: 'Browse Components' }
  },
  {
    path: '/components/:slug',
    name: 'ComponentDetail',
    component: () => import('../views/ComponentDetail.vue'),
    meta: { title: 'Component Details' }
  },
  {
    path: '/categories',
    name: 'Categories',
    component: () => import('../views/Categories.vue'),
    meta: { title: 'Categories' }
  },
  {
    path: '/categories/:slug',
    name: 'CategoryComponents',
    component: () => import('../views/CategoryComponents.vue'),
    meta: { title: 'Category Components' }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { title: 'Dashboard', requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('../views/Profile.vue'),
    meta: { title: 'Profile', requiresAuth: true }
  },
  {
    path: '/purchases',
    name: 'Purchases',
    component: () => import('../views/Purchases.vue'),
    meta: { title: 'My Purchases', requiresAuth: true }
  },
  {
    path: '/my-components',
    name: 'MyComponents',
    component: () => import('../views/MyComponents.vue'),
    meta: { title: 'My Components', requiresAuth: true }
  },
  {
    path: '/upload',
    name: 'UploadComponent',
    component: () => import('../views/UploadComponent.vue'),
    meta: { title: 'Upload Component', requiresAuth: true }
  },
  {
    path: '/edit-component/:id',
    name: 'EditComponent',
    component: () => import('../views/EditComponent.vue'),
    meta: { title: 'Edit Component', requiresAuth: true }
  },
  {
    path: '/checkout/:id',
    name: 'Checkout',
    component: () => import('../views/Checkout.vue'),
    meta: { title: 'Checkout', requiresAuth: true }
  },
  {
    path: '/search',
    name: 'Search',
    component: () => import('../views/Search.vue'),
    meta: { title: 'Search Results' }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFound.vue'),
    meta: { title: 'Page Not Found' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for auth routes
router.beforeEach((to, from, next) => {
  // Update document title
  document.title = to.meta.title ? `${to.meta.title} | Component Marketplace` : 'Component Marketplace'
  
  // Check if route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Check if user is logged in
    const token = localStorage.getItem('token')
    if (!token) {
      // Redirect to login page if not logged in
      next({ name: 'Login', query: { redirect: to.fullPath } })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
