import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const MOCK_TASKS = [
  { id: 1, name: 'Follow up with Acme CEO', status: 'not_started', priority: 'high', start_date: '2024-04-01', due_date: '2024-04-05', contact_id: 1, assigned_to: 1, description: 'Schedule follow-up call', created_at: '2024-03-25' },
  { id: 2, name: 'Send proposal to Initech', status: 'in_progress', priority: 'urgent', start_date: '2024-03-28', due_date: '2024-04-02', contact_id: 5, assigned_to: 1, description: 'Prepare and send proposal document', created_at: '2024-03-25' },
  { id: 3, name: 'Update Globex contact info', status: 'completed', priority: 'low', start_date: '2024-03-20', due_date: '2024-03-22', contact_id: 3, assigned_to: 1, description: 'Update CRM records', created_at: '2024-03-18' },
  { id: 4, name: 'Prepare quarterly report', status: 'in_progress', priority: 'medium', start_date: '2024-03-25', due_date: '2024-04-10', contact_id: null, assigned_to: 1, description: 'Compile Q1 sales data', created_at: '2024-03-22' },
  { id: 5, name: 'Demo Massive Dynamic platform', status: 'not_started', priority: 'high', start_date: '2024-04-08', due_date: '2024-04-12', contact_id: 9, assigned_to: 1, description: 'Prepare demo environment', created_at: '2024-03-26' },
]

export const useTasksStore = defineStore('tasks', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/tasks')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_TASKS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/tasks', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/tasks/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/tasks/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
