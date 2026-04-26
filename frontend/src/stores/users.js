import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const ALL_PAGES = ['accounts', 'contacts', 'leads', 'opportunities', 'meetings', 'tasks']

const MOCK_USERS = [
  { id: 1, name: 'Admin User', email: 'admin@zenocrm.com', role: 'admin', team: 'Management', is_active: true, last_login: '2024-03-28', created_at: '2024-01-01', page_permissions: ALL_PAGES },
  { id: 2, name: 'Sarah Connor', email: 'sarah.connor@zenocrm.com', role: 'user', team: 'Sales', is_active: true, last_login: '2024-03-27', created_at: '2024-01-15', page_permissions: ['accounts', 'contacts', 'leads', 'opportunities'] },
  { id: 3, name: 'Tom Brady', email: 'tom.brady@zenocrm.com', role: 'user', team: 'Sales', is_active: true, last_login: '2024-03-26', created_at: '2024-02-01', page_permissions: ['leads', 'opportunities', 'meetings', 'tasks'] },
  { id: 4, name: 'Lisa Simpson', email: 'lisa.simpson@zenocrm.com', role: 'user', team: 'Support', is_active: false, last_login: '2024-03-10', created_at: '2024-02-15', page_permissions: ['contacts', 'tasks'] },
]

export const useUsersStore = defineStore('users', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/users')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_USERS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/users', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0], is_active: true }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/users/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/users/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
