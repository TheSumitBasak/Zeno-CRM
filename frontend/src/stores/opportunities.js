import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const MOCK_OPPORTUNITIES = [
  { id: 1, name: 'Acme Enterprise Deal', account_id: 1, contact_id: 1, stage: 'prospecting', amount: 75000, probability: 20, close_date: '2024-06-30', lead_source: 'Web', assigned_to: 1, created_at: '2024-03-01' },
  { id: 2, name: 'Globex Software License', account_id: 2, contact_id: 3, stage: 'qualification', amount: 45000, probability: 40, close_date: '2024-05-31', lead_source: 'Referral', assigned_to: 1, created_at: '2024-03-05' },
  { id: 3, name: 'Initech Cloud Migration', account_id: 3, contact_id: 5, stage: 'proposal', amount: 120000, probability: 60, close_date: '2024-07-15', lead_source: 'Cold Call', assigned_to: 1, created_at: '2024-03-08' },
  { id: 4, name: 'Umbrella Analytics Suite', account_id: 4, contact_id: 7, stage: 'negotiation', amount: 95000, probability: 80, close_date: '2024-04-30', lead_source: 'Trade Show', assigned_to: 1, created_at: '2024-03-10' },
  { id: 5, name: 'Massive Dynamic Research Platform', account_id: 5, contact_id: 9, stage: 'closed_won', amount: 200000, probability: 100, close_date: '2024-03-15', lead_source: 'Referral', assigned_to: 1, created_at: '2024-02-20' },
  { id: 6, name: 'Acme CRM Integration', account_id: 1, contact_id: 2, stage: 'qualification', amount: 35000, probability: 35, close_date: '2024-08-01', lead_source: 'Email', assigned_to: 1, created_at: '2024-03-12' },
  { id: 7, name: 'Globex HR System', account_id: 2, contact_id: 4, stage: 'prospecting', amount: 55000, probability: 15, close_date: '2024-09-30', lead_source: 'Web', assigned_to: 1, created_at: '2024-03-15' },
  { id: 8, name: 'Initech BI Dashboard', account_id: 3, contact_id: 6, stage: 'proposal', amount: 68000, probability: 55, close_date: '2024-06-15', lead_source: 'Social Media', assigned_to: 1, created_at: '2024-03-18' },
]

export const useOpportunitiesStore = defineStore('opportunities', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/opportunities')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_OPPORTUNITIES]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/opportunities', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    // Optimistic update — move card instantly
    const idx = items.value.findIndex(i => i.id === id)
    const previous = idx !== -1 ? { ...items.value[idx] } : null
    if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    try {
      const response = await api.put(`/opportunities/${id}`, data)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      // keep optimistic update — rollback only on explicit server error if needed
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/opportunities/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
