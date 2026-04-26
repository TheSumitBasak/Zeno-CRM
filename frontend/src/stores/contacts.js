import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const MOCK_CONTACTS = [
  { id: 1, account_id: 1, first_name: 'John', last_name: 'Smith', email: 'john.smith@acme.com', phone: '+1-555-1001', title: 'CEO', department: 'Executive', created_at: '2024-01-15' },
  { id: 2, account_id: 1, first_name: 'Jane', last_name: 'Doe', email: 'jane.doe@acme.com', phone: '+1-555-1002', title: 'CTO', department: 'Technology', created_at: '2024-01-16' },
  { id: 3, account_id: 2, first_name: 'Bob', last_name: 'Johnson', email: 'bob.j@globex.com', phone: '+1-555-1003', title: 'VP Sales', department: 'Sales', created_at: '2024-01-22' },
  { id: 4, account_id: 2, first_name: 'Alice', last_name: 'Williams', email: 'alice.w@globex.com', phone: '+1-555-1004', title: 'Director', department: 'Operations', created_at: '2024-01-23' },
  { id: 5, account_id: 3, first_name: 'Charlie', last_name: 'Brown', email: 'charlie.b@initech.com', phone: '+1-555-1005', title: 'CFO', department: 'Finance', created_at: '2024-02-02' },
  { id: 6, account_id: 3, first_name: 'Diana', last_name: 'Prince', email: 'diana.p@initech.com', phone: '+1-555-1006', title: 'Manager', department: 'HR', created_at: '2024-02-03' },
  { id: 7, account_id: 4, first_name: 'Edward', last_name: 'Norton', email: 'edward.n@umbrella.com', phone: '+1-555-1007', title: 'President', department: 'Executive', created_at: '2024-02-11' },
  { id: 8, account_id: 4, first_name: 'Fiona', last_name: 'Green', email: 'fiona.g@umbrella.com', phone: '+1-555-1008', title: 'Head of R&D', department: 'Research', created_at: '2024-02-12' },
  { id: 9, account_id: 5, first_name: 'George', last_name: 'Hall', email: 'george.h@massive.com', phone: '+1-555-1009', title: 'Director', department: 'Science', created_at: '2024-02-16' },
  { id: 10, account_id: 5, first_name: 'Helen', last_name: 'Troy', email: 'helen.t@massive.com', phone: '+1-555-1010', title: 'Analyst', department: 'Analytics', created_at: '2024-02-17' },
]

export const useContactsStore = defineStore('contacts', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/contacts')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_CONTACTS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/contacts', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/contacts/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/contacts/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
