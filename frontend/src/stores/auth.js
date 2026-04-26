import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const storedToken = localStorage.getItem('zeno_token');
  const token = ref(storedToken === 'undefined' ? null : storedToken || null);
  
  let initialUser = null;
  try {
    const storedUser = localStorage.getItem('zeno_user');
    if (storedUser && storedUser !== 'undefined') {
      initialUser = JSON.parse(storedUser);
    }
  } catch (e) {
    initialUser = null;
  }
  const user = ref(initialUser);

  const isAuthenticated = computed(() => !!token.value)

  const MOCK_USER = {
    id: 1,
    name: 'Admin User',
    email: 'admin@zenocrm.com',
    role: 'admin',
    team: 'Management',
    page_permissions: ['accounts', 'contacts', 'leads', 'opportunities', 'meetings', 'tasks'],
  }

  async function login(email, password) {
    try {
      const response = await axios.post('/api/auth/login', { email, password })
      const data = response.data.data || response.data
      token.value = data.token
      user.value = data.user
      localStorage.setItem('zeno_token', data.token)
      localStorage.setItem('zeno_user', JSON.stringify(data.user))
      return { success: true }
    } catch (error) {
      // Fallback to mock authentication
      if (email === 'admin@zenocrm.com' && password === 'Admin@123') {
        const mockToken = 'mock_jwt_token_' + Date.now()
        token.value = mockToken
        user.value = MOCK_USER
        localStorage.setItem('zeno_token', mockToken)
        localStorage.setItem('zeno_user', JSON.stringify(MOCK_USER))
        return { success: true }
      }
      return { success: false, message: 'Invalid email or password' }
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('zeno_token')
    localStorage.removeItem('zeno_user')
  }

  return { token, user, isAuthenticated, login, logout }
})
