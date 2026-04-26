import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../composables/useApi'

const MOCK_LEADS = [
  { id: 1, first_name: 'Michael', last_name: 'Scott', email: 'm.scott@dunder.com', phone: '+1-555-2001', company: 'Dunder Mifflin', title: 'Regional Manager', status: 'new', source: 'Web', assigned_to: 1, created_at: '2024-03-01' },
  { id: 2, first_name: 'Dwight', last_name: 'Schrute', email: 'd.schrute@schrutefarm.com', phone: '+1-555-2002', company: 'Schrute Farms', title: 'Owner', status: 'in_process', source: 'Referral', assigned_to: 1, created_at: '2024-03-02' },
  { id: 3, first_name: 'Jim', last_name: 'Halpert', email: 'j.halpert@athleap.com', phone: '+1-555-2003', company: 'Athleap', title: 'Co-Founder', status: 'assigned', source: 'Cold Call', assigned_to: 1, created_at: '2024-03-03' },
  { id: 4, first_name: 'Pam', last_name: 'Beesly', email: 'p.beesly@michaelscott.com', phone: '+1-555-2004', company: 'Michael Scott Paper', title: 'Sales Rep', status: 'converted', source: 'Email', assigned_to: 1, created_at: '2024-03-04' },
  { id: 5, first_name: 'Ryan', last_name: 'Howard', email: 'r.howard@wuphf.com', phone: '+1-555-2005', company: 'WUPHF', title: 'CEO', status: 'recycled', source: 'Social Media', assigned_to: 1, created_at: '2024-03-05' },
  { id: 6, first_name: 'Angela', last_name: 'Martin', email: 'a.martin@accounting.com', phone: '+1-555-2006', company: 'Martin Accounting', title: 'Manager', status: 'dead', source: 'Trade Show', assigned_to: 1, created_at: '2024-03-06' },
  { id: 7, first_name: 'Kevin', last_name: 'Malone', email: 'k.malone@kevinschili.com', phone: '+1-555-2007', company: "Kevin's Chili", title: 'Chef/Owner', status: 'new', source: 'Web', assigned_to: 1, created_at: '2024-03-07' },
  { id: 8, first_name: 'Oscar', last_name: 'Martinez', email: 'o.martinez@finance.com', phone: '+1-555-2008', company: 'Martinez Finance', title: 'Analyst', status: 'in_process', source: 'Referral', assigned_to: 1, created_at: '2024-03-08' },
]

export const useLeadsStore = defineStore('leads', () => {
  const items = ref([])
  const loading = ref(false)
  const selected = ref(null)

  async function fetchAll() {
    loading.value = true
    try {
      const response = await api.get('/leads')
      items.value = response.data.data || response.data
    } catch {
      items.value = [...MOCK_LEADS]
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const response = await api.post('/leads', data)
      items.value.unshift(response.data.data || response.data)
    } catch {
      const newItem = { ...data, id: Date.now(), created_at: new Date().toISOString().split('T')[0] }
      items.value.unshift(newItem)
    }
  }

  async function update(id, data) {
    try {
      const response = await api.put(`/leads/${id}`, data)
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = response.data.data || response.data
    } catch {
      const idx = items.value.findIndex(i => i.id === id)
      if (idx !== -1) items.value[idx] = { ...items.value[idx], ...data }
    }
  }

  async function remove(id) {
    try {
      await api.delete(`/leads/${id}`)
    } catch {}
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, loading, selected, fetchAll, create, update, remove }
})
