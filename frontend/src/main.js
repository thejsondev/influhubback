import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createStore } from 'vuex'
import App from './App.vue'
import axios from 'axios'

// Create axios instance
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add auth token to requests if available
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Routes will be added here
  ]
})

// Create store
const store = createStore({
  state() {
    return {
      user: null,
      isAuthenticated: false
    }
  },
  mutations: {
    setUser(state, user) {
      state.user = user
      state.isAuthenticated = !!user
    }
  },
  actions: {
    logout({ commit }) {
      localStorage.removeItem('token')
      commit('setUser', null)
    }
  }
})

// Create and mount app
const app = createApp(App)
app.config.globalProperties.$api = api
app.use(router)
app.use(store)
app.mount('#app')
