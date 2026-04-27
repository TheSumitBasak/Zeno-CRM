<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-3">
        <div class="flex flex-col gap-3">
          <!-- Status Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Status</span>
            <button
              v-for="filter in statusFilters"
              :key="filter.value"
              @click="activeStatus = filter.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activeStatus === filter.value ? 'btn-primary' : 'btn-ghost'"
            >
              {{ filter.label }}
              <span class="badge badge-sm" :class="activeStatus === filter.value ? 'badge-warning' : 'badge-ghost'">{{ getStatusCount(filter.value) }}</span>
            </button>
          </div>
          <!-- Priority Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Priority</span>
            <button
              v-for="pf in priorityFilters"
              :key="pf.value"
              @click="activePriority = pf.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activePriority === pf.value ? 'btn-secondary' : 'btn-ghost'"
            >
              <span v-if="pf.value !== 'all'" class="w-2 h-2 rounded-full" :class="priorityDotClass(pf.value)"></span>
              {{ pf.label }}
            </button>
            <!-- Related To Filter -->
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide ml-3 mr-1">Related To</span>
            <button
              v-for="pt in parentTypeFilters"
              :key="pt.value"
              @click="activeParentType = pt.value"
              class="btn btn-sm rounded-lg"
              :class="activeParentType === pt.value ? 'btn-accent' : 'btn-ghost'"
            >{{ pt.label }}</button>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-ghost btn-sm text-error ml-auto gap-1">
              <XMarkIcon class="w-3.5 h-3.5" />Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :data="filteredTasks"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Task
        </button>
      </template>
      <template #cell-name="{ row, value }">
        <div class="flex items-center gap-2 cursor-pointer group" @click.stop="$router.push('/tasks/' + row.id)">
          <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDotClass(row.priority)"></div>
          <span class="font-medium group-hover:text-primary transition-colors">{{ value }}</span>
        </div>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="task" />
      </template>
      <template #cell-priority="{ value }">
        <StatusBadge :status="value" type="priority" />
      </template>
      <template #cell-related_to="{ row }">
        <span v-if="row.parent_type && row.parent_id" class="text-xs text-base-content/70">
          <span class="font-medium">{{ row.parent_type }}:</span> {{ getParentName(row.parent_type, row.parent_id) }}
        </span>
        <span v-else class="text-xs text-base-content/30">—</span>
      </template>
      <template #cell-due_date="{ value }">
        <span :class="isOverdue(value) ? 'text-error font-medium text-xs' : 'text-xs text-base-content'">
          {{ formatDate(value) }}
          <span v-if="isOverdue(value)" class="ml-1">(Overdue)</span>
        </span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Task' : 'Add Task'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Task Name *</span></label>
            <input v-model="form.name" type="text" placeholder="Task description..." class="input input-bordered input-sm" :class="{ 'input-error': touched.name && errors.name }" @blur="handleBlur('name')" />
            <p v-if="touched.name && errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="form.status" class="select select-bordered select-sm">
              <option value="not_started">Not Started</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="deferred">Deferred</option>
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
            <label class="label"><span class="label-text text-sm font-medium">Start Date</span></label>
            <input v-model="form.start_date" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Due Date *</span></label>
            <input v-model="form.due_date" type="date" class="input input-bordered input-sm" :class="{ 'input-error': touched.due_date && errors.due_date }" @blur="handleBlur('due_date')" />
            <p v-if="touched.due_date && errors.due_date" class="text-error text-xs mt-1">{{ errors.due_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Related To (Type)</span></label>
            <select v-model="form.parent_type" class="select select-bordered select-sm" @change="form.parent_id = ''">
              <option value="">None</option>
              <option value="Lead">Lead</option>
              <option value="Opportunity">Opportunity</option>
            </select>
          </div>
          <div class="form-control" v-if="form.parent_type">
            <label class="label"><span class="label-text text-sm font-medium">{{ form.parent_type }}</span></label>
            <select v-model="form.parent_id" class="select select-bordered select-sm">
              <option value="">Select {{ form.parent_type }}</option>
              <option v-for="item in parentOptions" :key="item.id" :value="item.id">{{ getItemLabel(form.parent_type, item) }}</option>
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
          {{ editingItem ? 'Save Changes' : 'Create Task' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Task" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ deletingItem?.name }}"?</p>
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
import { useTasksStore } from '../../stores/tasks'
import { useLeadsStore } from '../../stores/leads'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useContactsStore } from '../../stores/contacts'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required } from '../../composables/useForm'
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const tasksStore = useTasksStore()
const leadsStore = useLeadsStore()
const oppsStore = useOpportunitiesStore()
const contactsStore = useContactsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeStatus = ref('all')
const activePriority = ref('all')
const activeParentType = ref('all')

const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'deferred', label: 'Deferred' },
]

const priorityFilters = [
  { value: 'all', label: 'All' },
  { value: 'urgent', label: 'Urgent' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
]

const parentTypeFilters = [
  { value: 'all', label: 'All' },
  { value: 'Lead', label: 'Leads' },
  { value: 'Opportunity', label: 'Opportunities' },
]

const initialValues = { name: '', status: 'not_started', priority: 'medium', start_date: '', due_date: '', parent_type: '', parent_id: '', contact_id: '', description: '' }
const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  name: required('Task name'),
  due_date: required('Due date'),
})

const columns = [
  { key: 'name', label: 'Task Name' },
  { key: 'status', label: 'Status' },
  { key: 'priority', label: 'Priority' },
  { key: 'related_to', label: 'Related To' },
  { key: 'due_date', label: 'Due Date' },
]

const hasActiveFilters = computed(() => activeStatus.value !== 'all' || activePriority.value !== 'all' || activeParentType.value !== 'all')

const filteredTasks = computed(() => {
  return tasksStore.items.filter(t => {
    const statusOk = activeStatus.value === 'all' || t.status === activeStatus.value
    const priorityOk = activePriority.value === 'all' || t.priority === activePriority.value
    const parentOk = activeParentType.value === 'all' || t.parent_type === activeParentType.value
    return statusOk && priorityOk && parentOk
  })
})

function getStatusCount(status) {
  if (status === 'all') return tasksStore.items.length
  return tasksStore.items.filter(t => t.status === status).length
}

function clearFilters() {
  activeStatus.value = 'all'
  activePriority.value = 'all'
  activeParentType.value = 'all'
}

const parentOptions = computed(() => {
  if (form.parent_type === 'Lead') return leadsStore.items
  if (form.parent_type === 'Opportunity') return oppsStore.items
  return []
})

function getItemLabel(type, item) {
  if (type === 'Lead') return `${item.first_name} ${item.last_name}`
  if (type === 'Opportunity') return item.name
  return item.name || item.id
}

function getParentName(type, id) {
  const numId = Number(id)
  if (type === 'Lead') {
    const item = leadsStore.items.find(i => i.id === numId)
    return item ? `${item.first_name} ${item.last_name}` : `#${id}`
  }
  if (type === 'Opportunity') {
    return oppsStore.items.find(i => i.id === numId)?.name || `#${id}`
  }
  if (type === 'Contact') {
    const item = contactsStore.items.find(i => i.id === numId)
    return item ? `${item.first_name} ${item.last_name}` : `#${id}`
  }
  return `#${id}`
}

function priorityDotClass(p) {
  return { low: 'bg-info', medium: 'bg-warning', high: 'bg-orange-400', urgent: 'bg-error' }[p] || 'bg-base-300'
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
      await tasksStore.update(editingItem.value.id, { ...form })
    } else {
      await tasksStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await tasksStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function isOverdue(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

onMounted(() => Promise.all([
  tasksStore.fetchAll(),
  leadsStore.fetchAll(),
  oppsStore.fetchAll(),
  contactsStore.fetchAll(),
]))
</script>
