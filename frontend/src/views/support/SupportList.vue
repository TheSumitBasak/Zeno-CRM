<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-3">
        <div class="flex flex-col gap-3">
          <!-- Status filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Status</span>
            <button
              v-for="s in statusFilters"
              :key="s.value"
              @click="activeStatus = s.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activeStatus === s.value ? 'btn-primary' : 'btn-ghost'"
            >
              {{ s.label }}
              <span class="badge badge-sm" :class="activeStatus === s.value ? 'badge-warning' : 'badge-ghost'">{{ getStatusCount(s.value) }}</span>
            </button>
          </div>
          <!-- Priority filters + actions -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Priority</span>
            <button
              v-for="p in priorityFilters"
              :key="p.value"
              @click="activePriority = p.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activePriority === p.value ? 'btn-secondary' : 'btn-ghost'"
            >
              {{ p.label }}
            </button>
            <button
              v-if="activeStatus !== 'all' || activePriority !== 'all'"
              @click="activeStatus = 'all'; activePriority = 'all'"
              class="btn btn-ghost btn-sm text-error ml-auto gap-1"
            >
              <XMarkIcon class="w-3.5 h-3.5" />Clear
            </button>
            <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2 ml-auto">
              <PlusIcon class="w-4 h-4" />New Ticket
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :columns="columns"
      :data="filteredTickets"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #cell-subject="{ row, value }">
        <span
          class="font-medium text-base-content cursor-pointer hover:text-primary transition-colors"
          @click.stop="$router.push('/support/' + row.id)"
        >{{ value }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="support" />
      </template>
      <template #cell-priority="{ value }">
        <StatusBadge :status="value" type="priority" />
      </template>
      <template #cell-lead_id="{ value }">
        <RouterLink v-if="value" :to="'/leads/' + value" class="text-xs text-primary hover:underline" @click.stop>
          {{ getLeadName(value) }}
        </RouterLink>
        <span v-else class="text-base-content/30 text-xs">—</span>
      </template>
      <template #cell-created_at="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Support Ticket' : 'New Support Ticket'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Subject *</span></label>
            <input v-model="form.subject" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.subject && errors.subject }" @blur="handleBlur('subject')" />
            <p v-if="touched.subject && errors.subject" class="text-error text-xs mt-1">{{ errors.subject }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="form.status" class="select select-bordered select-sm">
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
            <select v-model="form.priority" class="select select-bordered select-sm">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Contact</span></label>
            <select v-model="form.contact_id" class="select select-bordered select-sm">
              <option value="">No contact</option>
              <option v-for="c in contactsStore.items" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
            <select v-model="form.account_id" class="select select-bordered select-sm">
              <option value="">No account</option>
              <option v-for="a in accountsStore.items" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
          <div v-if="editingItem" class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Resolution Notes</span></label>
            <textarea v-model="form.resolution" class="textarea textarea-bordered textarea-sm" rows="3" placeholder="How was this resolved?"></textarea>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create Ticket' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Ticket" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ deletingItem?.subject }}"?</p>
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
import { ref, computed, onMounted } from 'vue'
import { useSupportStore } from '../../stores/support'
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import { useLeadsStore } from '../../stores/leads'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required } from '../../composables/useForm'
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const supportStore = useSupportStore()
const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()
const leadsStore = useLeadsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeStatus = ref('all')
const activePriority = ref('all')

const statuses = [
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'pending', label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
]

const statusFilters = [{ value: 'all', label: 'All' }, ...statuses]
const priorityFilters = [
  { value: 'all', label: 'All' },
  { value: 'urgent', label: 'Urgent' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
]

const initialValues = { subject: '', status: 'open', priority: 'medium', contact_id: '', account_id: '', description: '', resolution: '' }
const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  subject: required('Subject'),
})

const columns = [
  { key: 'subject', label: 'Subject' },
  { key: 'status', label: 'Status' },
  { key: 'priority', label: 'Priority' },
  { key: 'lead_id', label: 'From Lead' },
  { key: 'created_at', label: 'Created' },
]

const filteredTickets = computed(() => {
  let list = supportStore.items
  if (activeStatus.value !== 'all') list = list.filter(t => t.status === activeStatus.value)
  if (activePriority.value !== 'all') list = list.filter(t => t.priority === activePriority.value)
  return list
})

function getStatusCount(status) {
  if (status === 'all') return supportStore.items.length
  return supportStore.items.filter(t => t.status === status).length
}

function getLeadName(id) {
  const l = leadsStore.items.find(l => l.id === Number(id))
  return l ? `${l.first_name} ${l.last_name}` : `#${id}`
}

function openCreate() {
  editingItem.value = null
  reset()
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  reset({ ...initialValues, ...item })
  showModal.value = true
}

function confirmDelete(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

async function handleSubmit() {
  if (!validate()) return
  submitting.value = true
  try {
    if (editingItem.value) {
      await supportStore.update(editingItem.value.id, { ...form })
    } else {
      await supportStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await supportStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => Promise.all([
  supportStore.fetchAll(),
  contactsStore.fetchAll(),
  accountsStore.fetchAll(),
  leadsStore.fetchAll(),
]))
</script>
