import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'
import { useToastStore } from './toast'

const MOCK_MEETINGS = [
  { id: 1, name: 'Acme Quarterly Review', parent_type: 'Account', parent_id: 1, status: 'planned', start_date: '2024-04-15 10:00', end_date: '2024-04-15 11:00', duration_hours: 1, duration_minutes: 0, description: 'Q1 review meeting', meeting_link: 'https://meet.google.com/abc-defg-hij', assigned_to: 1, created_at: '2024-03-20', contact_ids: [1, 2] },
  { id: 2, name: 'Product Demo - Globex', parent_type: 'Opportunity', parent_id: 2, status: 'held', start_date: '2024-03-10 14:00', end_date: '2024-03-10 15:30', duration_hours: 1, duration_minutes: 30, description: 'Product demonstration', meeting_link: 'https://zoom.us/j/123456789', assigned_to: 1, created_at: '2024-03-05', contact_ids: [3] },
  { id: 3, name: 'Contract Negotiation', parent_type: 'Opportunity', parent_id: 4, status: 'planned', start_date: '2024-04-20 09:00', end_date: '2024-04-20 10:00', duration_hours: 1, duration_minutes: 0, description: 'Final contract terms discussion', meeting_link: '', assigned_to: 1, created_at: '2024-03-15', contact_ids: [7] },
  { id: 4, name: 'Kickoff Meeting', parent_type: 'Account', parent_id: 5, status: 'held', start_date: '2024-03-16 11:00', end_date: '2024-03-16 12:00', duration_hours: 1, duration_minutes: 0, description: 'Project kickoff', meeting_link: '', assigned_to: 1, created_at: '2024-03-10', contact_ids: [9] },
  { id: 5, name: 'Technical Assessment', parent_type: 'Lead', parent_id: 3, status: 'not_held', start_date: '2024-04-05 15:00', end_date: '2024-04-05 16:00', duration_hours: 1, duration_minutes: 0, description: 'Technical requirements gathering', meeting_link: '', assigned_to: 1, created_at: '2024-03-18', contact_ids: [] },
]

export const useMeetingsStore = defineStore('meetings', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/meetings')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_MEETINGS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/meetings', data)
      items.value.unshift(response.data.data || response.data)
      useToastStore().success('Meeting scheduled successfully')
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0], contact_ids: data.contact_ids || [] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/meetings/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
      useToastStore().success('Meeting updated successfully')
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/meetings/${id}`)
      useToastStore().success('Meeting deleted')
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
