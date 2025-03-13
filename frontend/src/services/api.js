import axios from 'axios'

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

// Handle response errors
api.interceptors.response.use(
  response => response,
  error => {
    // Handle 401 Unauthorized errors (expired token)
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default {
  // Auth endpoints
  auth: {
    login(credentials) {
      return api.post('/users/login', credentials)
    },
    register(userData) {
      return api.post('/users/register', userData)
    }
  },
  
  // User endpoints
  users: {
    getProfile(id) {
      return api.get(`/users/${id}`)
    },
    updateProfile(id, data) {
      return api.put(`/users/${id}`, data)
    }
  },
  
  // Component endpoints
  components: {
    getAll(params = {}) {
      return api.get('/components', { params })
    },
    get(id) {
      return api.get(`/components/${id}`)
    },
    create(data) {
      return api.post('/components', data)
    },
    update(id, data) {
      return api.put(`/components/${id}`, data)
    },
    delete(id) {
      return api.delete(`/components/${id}`)
    },
    search(query, filters = {}) {
      return api.get('/components', { 
        params: { 
          search: query,
          ...filters
        } 
      })
    },
    getUserComponents() {
      return api.get('/components/user')
    }
  },
  
  // Category endpoints
  categories: {
    getAll() {
      return api.get('/categories')
    },
    get(id) {
      return api.get(`/categories/${id}`)
    },
    create(data) {
      return api.post('/categories', data)
    },
    update(id, data) {
      return api.put(`/categories/${id}`, data)
    },
    delete(id) {
      return api.delete(`/categories/${id}`)
    }
  },
  
  // Purchase endpoints
  purchases: {
    getAll() {
      return api.get('/purchases')
    },
    get(id) {
      return api.get(`/purchases/${id}`)
    },
    create(data) {
      return api.post('/purchases', data)
    }
  },
  
  // Review endpoints
  reviews: {
    getComponentReviews(componentId) {
      return api.get(`/reviews/component/${componentId}`)
    },
    create(data) {
      return api.post('/reviews', data)
    },
    update(id, data) {
      return api.put(`/reviews/${id}`, data)
    },
    delete(id) {
      return api.delete(`/reviews/${id}`)
    }
  }
}
