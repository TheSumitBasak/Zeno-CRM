import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import router from '../router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      router.push('/login')
    } else if (error.response) {
      const toastStore = useToastStore()
      const message = error.response.data?.message
        || error.response.data?.error
        || `Server error (${error.response.status})`
      toastStore.error(message)
    }
    return Promise.reject(error)
  }
)

export function useApi() {
  return api
}

export default api
