import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const MOCK_ACCOUNTS = [
  { id: 1, name: 'Acme Corporation', email: 'info@acme.com', phone: '+1-555-0100', industry: 'Technology', type: 'Customer', website: 'https://acme.com', billing_address: '123 Main St, New York, NY', created_at: '2024-01-15' },
  { id: 2, name: 'Globex Industries', email: 'contact@globex.com', phone: '+1-555-0200', industry: 'Manufacturing', type: 'Partner', website: 'https://globex.com', billing_address: '456 Oak Ave, Chicago, IL', created_at: '2024-01-20' },
  { id: 3, name: 'Initech Solutions', email: 'hello@initech.com', phone: '+1-555-0300', industry: 'Finance', type: 'Prospect', website: 'https://initech.com', billing_address: '789 Pine Rd, Austin, TX', created_at: '2024-02-01' },
  { id: 4, name: 'Umbrella Corp', email: 'info@umbrella.com', phone: '+1-555-0400', industry: 'Healthcare', type: 'Customer', website: 'https://umbrella.com', billing_address: '321 Elm St, Seattle, WA', created_at: '2024-02-10' },
  { id: 5, name: 'Massive Dynamic', email: 'contact@massive.com', phone: '+1-555-0500', industry: 'Research', type: 'Prospect', website: 'https://massive.com', billing_address: '654 Cedar Blvd, Boston, MA', created_at: '2024-02-15' },
]

export const useAccountsStore = defineStore('accounts', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/accounts')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_ACCOUNTS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/accounts', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/accounts/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/accounts/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
