<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!task" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Task not found.</p>
    <RouterLink to="/tasks" class="btn btn-ghost btn-sm">← Back to Tasks</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/tasks" class="text-primary">Tasks</RouterLink></li>
        <li class="text-base-content/60">{{ task.name }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <div class="w-3 h-3 rounded-full" :class="priorityDot(task.priority)"></div>
              <h1 class="text-xl font-bold">{{ task.name }}</h1>
            </div>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
              <StatusBadge :status="task.status" type="task" />
              <StatusBadge :status="task.priority" type="priority" />
              <span v-if="isOverdue(task.due_date)" class="badge badge-error badge-sm gap-1">
                <ExclamationCircleIcon class="w-3 h-3" />Overdue
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="task.status !== 'completed'"
              @click="markComplete"
              class="btn btn-success btn-sm gap-1"
              :disabled="completing"
            >
              <span v-if="completing" class="loading loading-spinner loading-xs"></span>
              <CheckIcon v-else class="w-4 h-4" />
              Mark Complete
            </button>
            <button @click="openEdit" class="btn btn-outline btn-sm gap-1">
              <PencilIcon class="w-4 h-4" />Edit
            </button>
            <button @click="showDeleteModal = true" class="btn btn-ghost btn-sm text-error">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Timeline</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Start Date</span>
              <span>{{ formatDate(task.start_date) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Due Date</span>
              <span :class="isOverdue(task.due_date) ? 'text-error font-semibold' : ''">
                {{ formatDate(task.due_date) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Status & Priority</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Status</span>
              <StatusBadge :status="task.status" type="task" />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Priority</span>
              <StatusBadge :status="task.priority" type="priority" />
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Assigned To</span>
              <span>{{ getAssigneeName(task.assigned_to) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Related To</h3>
          <div v-if="task.parent_type && task.parent_id">
            <div class="flex items-center gap-2 mb-1">
              <span class="badge badge-outline badge-sm">{{ task.parent_type }}</span>
            </div>
            <component
              :is="parentRoute ? 'RouterLink' : 'span'"
              :to="parentRoute"
              class="text-sm font-medium"
              :class="parentRoute ? 'text-primary hover:underline' : ''"
            >
              {{ parentName }}
            </component>
          </div>
          <div v-if="task.contact_id" class="mt-2">
            <span class="text-xs text-base-content/50">Contact: </span>
            <RouterLink :to="'/contacts/' + task.contact_id" class="text-sm text-primary hover:underline">{{ contactName }}</RouterLink>
          </div>
          <p v-if="!task.parent_type && !task.contact_id" class="text-sm text-base-content/30">No related record</p>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="task.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ task.description }}</p>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Task" size="lg">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Task Name *</span></label>
            <input v-model="editForm.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.name && editErrors.name }" @blur="editBlur('name')" />
            <p v-if="editTouched.name && editErrors.name" class="text-error text-xs mt-1">{{ editErrors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="editForm.status" class="select select-bordered select-sm">
              <option value="not_started">Not Started</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="deferred">Deferred</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
            <select v-model="editForm.priority" class="select select-bordered select-sm">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Start Date</span></label>
            <input v-model="editForm.start_date" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Due Date *</span></label>
            <input v-model="editForm.due_date" type="date" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.due_date && editErrors.due_date }" @blur="editBlur('due_date')" />
            <p v-if="editTouched.due_date && editErrors.due_date" class="text-error text-xs mt-1">{{ editErrors.due_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Related To (Type)</span></label>
            <select v-model="editForm.parent_type" class="select select-bordered select-sm" @change="editForm.parent_id = ''">
              <option value="">None</option>
              <option value="Lead">Lead</option>
              <option value="Opportunity">Opportunity</option>
            </select>
          </div>
          <div v-if="editForm.parent_type" class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">{{ editForm.parent_type }}</span></label>
            <select v-model="editForm.parent_id" class="select select-bordered select-sm">
              <option value="">Select {{ editForm.parent_type }}</option>
              <option v-for="item in parentOptions" :key="item.id" :value="item.id">{{ getItemLabel(editForm.parent_type, item) }}</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showEditModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleEditSubmit" class="btn btn-primary btn-sm" :disabled="editSubmitting">
          <span v-if="editSubmitting" class="loading loading-spinner loading-xs"></span>
          Save Changes
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Task" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ task.name }}"?</p>
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
import { useRoute, useRouter } from 'vue-router'
import { useTasksStore } from '../../stores/tasks'
import { useLeadsStore } from '../../stores/leads'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useContactsStore } from '../../stores/contacts'
import { useUsersStore } from '../../stores/users'
import { useForm, required } from '../../composables/useForm'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PencilIcon, TrashIcon, CheckIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const tasksStore = useTasksStore()
const leadsStore = useLeadsStore()
const oppsStore = useOpportunitiesStore()
const contactsStore = useContactsStore()
const usersStore = useUsersStore()

const loading = ref(true)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const completing = ref(false)

const task = computed(() => tasksStore.items.find(t => t.id === Number(route.params.id)))

const parentName = computed(() => {
  if (!task.value?.parent_type || !task.value?.parent_id) return ''
  const id = Number(task.value.parent_id)
  if (task.value.parent_type === 'Lead') {
    const l = leadsStore.items.find(l => l.id === id)
    return l ? `${l.first_name} ${l.last_name}` : `#${id}`
  }
  if (task.value.parent_type === 'Opportunity') {
    return oppsStore.items.find(o => o.id === id)?.name || `#${id}`
  }
  return `#${id}`
})

const parentRoute = computed(() => {
  if (!task.value?.parent_type || !task.value?.parent_id) return null
  const map = { Lead: 'leads', Opportunity: 'opportunities' }
  const seg = map[task.value.parent_type]
  return seg ? `/${seg}/${task.value.parent_id}` : null
})

const contactName = computed(() => {
  const c = contactsStore.items.find(c => c.id === Number(task.value?.contact_id))
  return c ? `${c.first_name} ${c.last_name}` : ''
})

const parentOptions = computed(() => {
  if (editForm.parent_type === 'Lead') return leadsStore.items
  if (editForm.parent_type === 'Opportunity') return oppsStore.items
  return []
})

const editInitial = { name: '', status: 'not_started', priority: 'medium', start_date: '', due_date: '', parent_type: '', parent_id: '', description: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  name: required('Task name'),
  due_date: required('Due date'),
})

function openEdit() {
  editReset({ ...editInitial, ...task.value })
  showEditModal.value = true
}

async function markComplete() {
  completing.value = true
  try {
    await tasksStore.update(task.value.id, { ...task.value, status: 'completed' })
  } finally {
    completing.value = false
  }
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await tasksStore.update(task.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handleDelete() {
  await tasksStore.remove(task.value.id)
  router.push('/tasks')
}

function getItemLabel(type, item) {
  if (type === 'Lead') return `${item.first_name} ${item.last_name}`
  return item.name || item.id
}

function getAssigneeName(id) {
  if (!id) return '—'
  return usersStore.items.find(u => u.id === Number(id))?.name || `#${id}`
}

function priorityDot(p) {
  return { low: 'bg-info', medium: 'bg-warning', high: 'bg-orange-400', urgent: 'bg-error' }[p] || 'bg-base-300'
}

function isOverdue(d) { return d && new Date(d) < new Date() && task.value?.status !== 'completed' }

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([
    tasksStore.fetchAll(),
    leadsStore.fetchAll(),
    oppsStore.fetchAll(),
    contactsStore.fetchAll(),
    usersStore.fetchAll(),
  ])
  loading.value = false
})
</script>
