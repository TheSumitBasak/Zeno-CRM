import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'
import { useToastStore } from './toast'

const MOCK_TICKETS = [
  { id: 1, subject: 'Dunder Mifflin - Support Request', status: 'open', priority: 'high', lead_id: 1, contact_id: null, account_id: null, assigned_to: 1, description: 'Customer needs help with onboarding.', resolution: null, created_at: '2024-04-01T09:00:00', updated_at: '2024-04-01T09:00:00' },
  { id: 2, subject: 'Schrute Farms - Billing Issue', status: 'in_progress', priority: 'medium', lead_id: 2, contact_id: null, account_id: null, assigned_to: 1, description: 'Invoice discrepancy reported.', resolution: null, created_at: '2024-04-02T10:00:00', updated_at: '2024-04-02T10:00:00' },
  { id: 3, subject: 'Athleap - Feature Request', status: 'pending', priority: 'low', lead_id: 3, contact_id: null, account_id: null, assigned_to: 2, description: 'Requesting custom reporting module.', resolution: null, created_at: '2024-04-03T11:00:00', updated_at: '2024-04-03T11:00:00' },
  { id: 4, subject: 'Martinez Finance - Account Access', status: 'resolved', priority: 'urgent', lead_id: 8, contact_id: null, account_id: null, assigned_to: 1, description: 'User locked out of CRM account.', resolution: 'Password reset completed.', created_at: '2024-04-04T08:00:00', updated_at: '2024-04-04T12:00:00' },
]

export const useSupportStore = defineStore('support', () => {
  const items = ref([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/support')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_TICKETS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/support', data)
      items.value.unshift(response.data.data || response.data)
      useToastStore().success('Support ticket created successfully')
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString(), updated_at: new Date().toISOString() }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/support/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
      useToastStore().success('Ticket updated successfully')
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/support/${id}`)
      useToastStore().success('Ticket deleted')
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  async function promoteFromLead(leadId, data) {
    try {
      const response = await api.post(`/leads/${leadId}/promote_support`, data)
      const result = response.data.data || response.data
      items.value.unshift(result.ticket)
      useToastStore().success('Lead promoted to support ticket')
      return result
    } catch {
      const ticket = {
        id: Date.now(),
        subject: data.subject || 'Support Request',
        status: data.status || 'open',
        priority: data.priority || 'medium',
        lead_id: leadId,
        contact_id: null,
        account_id: null,
        assigned_to: data.assigned_to || null,
        description: data.description || null,
        resolution: null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }
      items.value.unshift(ticket)
      return { ticket, ticket_id: ticket.id, lead: null }
    }
  }

  return { items, loading, fetchAll, create, update, remove, promoteFromLead }
})
