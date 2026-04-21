<template>
  <div class="space-y-4">
    <!-- Status filter tabs -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="filter in statusFilters"
        :key="filter.value"
        @click="activeFilter = filter.value"
        class="btn btn-sm rounded-lg"
        :class="activeFilter === filter.value ? 'btn-primary' : 'btn-ghost'"
      >
        {{ filter.label }}
        <span class="badge badge-sm ml-1" :class="activeFilter === filter.value ? 'badge-warning' : 'badge-ghost'">
          {{ getStatusCount(filter.value) }}
        </span>
      </button>
    </div>

    <DataTable
      :columns="columns"
      :data="filteredLeads"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Lead
        </button>
      </template>
      <template #cell-full_name="{ row }">
        <div class="flex items-center gap-2">
          <div class="avatar placeholder">
            <div class="w-7 h-7 rounded-full bg-warning">
              <span class="text-warning-content text-xs">{{ row.first_name?.[0] }}{{ row.last_name?.[0] }}</span>
            </div>
          </div>
          <span class="font-medium text-base-content">{{ row.first_name }} {{ row.last_name }}</span>
        </div>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="lead" />
      </template>
      <template #cell-created_at="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Lead' : 'Add Lead'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">First Name *</span></label>
            <input v-model="form.first_name" type="text" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
            <input v-model="form.last_name" type="text" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email</span></label>
            <input v-model="form.email" type="email" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone</span></label>
            <input v-model="form.phone" type="tel" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Company</span></label>
            <input v-model="form.company" type="text" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Title</span></label>
            <input v-model="form.title" type="text" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status *</span></label>
            <select v-model="form.status" class="select select-bordered select-sm" required>
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Source</span></label>
            <select v-model="form.source" class="select select-bordered select-sm">
              <option value="">Select Source</option>
              <option v-for="src in sources" :key="src" :value="src">{{ src }}</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create Lead' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Lead" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="text-base-content font-medium">Delete "{{ deletingItem?.first_name }} {{ deletingItem?.last_name }}"?</p>
        <p class="text-base-content/50 text-sm mt-1">This action cannot be undone.</p>
      </div>
      <template #footer>
        <button @click="showDeleteModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleDelete" class="btn btn-error btn-sm">Delete</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useLeadsStore } from '../../stores/leads'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const leadsStore = useLeadsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeFilter = ref('all')

const statuses = [
  { value: 'new', label: 'New' },
  { value: 'in_process', label: 'In Process' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'recycled', label: 'Recycled' },
  { value: 'converted', label: 'Converted' },
  { value: 'dead', label: 'Dead' },
]

const statusFilters = [{ value: 'all', label: 'All' }, ...statuses]

const sources = ['Web', 'Referral', 'Cold Call', 'Email', 'Social Media', 'Trade Show', 'Other']

const defaultForm = { first_name: '', last_name: '', email: '', phone: '', company: '', title: '', status: 'new', source: '', description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'full_name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'company', label: 'Company' },
  { key: 'status', label: 'Status' },
  { key: 'source', label: 'Source' },
  { key: 'created_at', label: 'Created' },
]

const filteredLeads = computed(() => {
  if (activeFilter.value === 'all') return leadsStore.items
  return leadsStore.items.filter(l => l.status === activeFilter.value)
})

function getStatusCount(status) {
  if (status === 'all') return leadsStore.items.length
  return leadsStore.items.filter(l => l.status === status).length
}

function openCreate() {
  editingItem.value = null
  Object.assign(form, defaultForm)
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  Object.assign(form, { ...defaultForm, ...item })
  showModal.value = true
}

function confirmDelete(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

async function handleSubmit() {
  if (!form.first_name || !form.last_name) return
  submitting.value = true
  try {
    if (editingItem.value) {
      await leadsStore.update(editingItem.value.id, { ...form })
    } else {
      await leadsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await leadsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => leadsStore.fetchAll())
</script>
