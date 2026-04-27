<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!opp" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Opportunity not found.</p>
    <RouterLink to="/opportunities" class="btn btn-ghost btn-sm">← Back to Opportunities</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/opportunities" class="text-primary">Opportunities</RouterLink></li>
        <li class="text-base-content/60">{{ opp.name }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold text-base-content">{{ opp.name }}</h1>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
              <StatusBadge :status="opp.stage" type="opportunity" />
              <span class="text-lg font-semibold text-success">${{ Number(opp.amount || 0).toLocaleString() }}</span>
              <span class="text-sm text-base-content/50">{{ opp.probability }}% probability</span>
            </div>
            <div class="mt-2">
              <div class="flex items-center gap-2">
                <div class="w-32 bg-base-300 rounded-full h-1.5">
                  <div class="h-1.5 rounded-full bg-primary transition-all" :style="{ width: (opp.probability || 0) + '%' }"></div>
                </div>
                <span class="text-xs text-base-content/50">{{ opp.probability }}%</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button @click="openEdit" class="btn btn-outline btn-sm gap-1">
              <PencilIcon class="w-4 h-4" />
              Edit
            </button>
            <button @click="showDeleteModal = true" class="btn btn-ghost btn-sm text-error">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stage Pipeline -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-3">Pipeline Stage</h3>
        <div class="flex items-center gap-1 overflow-x-auto pb-1">
          <div
            v-for="(stage, idx) in stages"
            :key="stage.value"
            class="flex items-center gap-1 flex-shrink-0"
          >
            <div
              class="px-3 py-1.5 rounded-full text-xs font-medium transition-all"
              :class="isStageActive(stage.value) ? 'bg-primary text-primary-content' : isStageCompleted(stage.value) ? 'bg-success/20 text-success' : 'bg-base-200 text-base-content/40'"
            >
              {{ stage.label }}
            </div>
            <ChevronRightIcon v-if="idx < stages.length - 1" class="w-3 h-3 text-base-content/20 flex-shrink-0" />
          </div>
        </div>
      </div>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Account & Contact</h3>
          <div class="space-y-2">
            <div v-if="accountName" class="flex items-center gap-2">
              <BuildingOfficeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <RouterLink v-if="opp.account_id" :to="'/accounts/' + opp.account_id" class="text-sm text-primary hover:underline">{{ accountName }}</RouterLink>
              <span v-else class="text-sm">{{ accountName }}</span>
            </div>
            <div v-if="contactName" class="flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <RouterLink v-if="opp.contact_id" :to="'/contacts/' + opp.contact_id" class="text-sm text-primary hover:underline">{{ contactName }}</RouterLink>
              <span v-else class="text-sm">{{ contactName }}</span>
            </div>
            <p v-if="!accountName && !contactName" class="text-sm text-base-content/30">No account or contact linked</p>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Deal Info</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Amount</span>
              <span class="font-semibold text-success">${{ Number(opp.amount || 0).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Stage</span>
              <StatusBadge :status="opp.stage" type="opportunity" />
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Close Date</span>
              <span>{{ formatDate(opp.close_date) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Source</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Lead Source</span>
              <span>{{ opp.lead_source || '—' }}</span>
            </div>
            <div v-if="opp.lead_id" class="flex justify-between items-center">
              <span class="text-base-content/50">From Lead</span>
              <RouterLink :to="'/leads/' + opp.lead_id" class="text-xs text-primary hover:underline">{{ leadName }}</RouterLink>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Assigned To</span>
              <span>{{ getAssigneeName(opp.assigned_to) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="opp.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ opp.description }}</p>
      </div>
    </div>

    <!-- Tasks & Meetings -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-0">
        <div class="flex items-center border-b border-base-200 px-4">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
            :class="activeTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content'"
          >
            {{ tab.label }}
            <span class="badge badge-sm ml-1" :class="activeTab === tab.key ? 'badge-primary' : 'badge-ghost'">
              {{ tab.key === 'tasks' ? relatedTasks.length : relatedMeetings.length }}
            </span>
          </button>
          <div class="ml-auto py-2 flex gap-2">
            <button v-if="activeTab === 'tasks'" @click="openNewTask" class="btn btn-ghost btn-xs gap-1">
              <PlusIcon class="w-3.5 h-3.5" />Add Task
            </button>
            <button v-if="activeTab === 'meetings'" @click="openNewMeeting" class="btn btn-ghost btn-xs gap-1">
              <PlusIcon class="w-3.5 h-3.5" />Schedule
            </button>
          </div>
        </div>

        <!-- Tasks -->
        <div v-if="activeTab === 'tasks'" class="p-4 space-y-2">
          <div v-if="relatedTasks.length === 0" class="text-center py-10">
            <ClipboardDocumentListIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No tasks linked to this opportunity.</p>
            <button @click="openNewTask" class="btn btn-ghost btn-xs mt-2">Add a task</button>
          </div>
          <div
            v-for="task in relatedTasks"
            :key="task.id"
            class="flex items-center gap-3 p-3 rounded-lg bg-base-200/40 hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/tasks/' + task.id)"
          >
            <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot(task.priority)"></div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ task.name }}</p>
              <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                <StatusBadge :status="task.status" type="task" />
                <StatusBadge :status="task.priority" type="priority" />
                <span v-if="task.due_date" class="text-xs" :class="isOverdue(task.due_date) ? 'text-error' : 'text-base-content/40'">
                  Due {{ formatDate(task.due_date) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Meetings -->
        <div v-if="activeTab === 'meetings'" class="p-4 space-y-2">
          <div v-if="relatedMeetings.length === 0" class="text-center py-10">
            <CalendarDaysIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No meetings linked to this opportunity.</p>
            <button @click="openNewMeeting" class="btn btn-ghost btn-xs mt-2">Schedule a meeting</button>
          </div>
          <div
            v-for="meeting in relatedMeetings"
            :key="meeting.id"
            class="flex items-center gap-3 p-3 rounded-lg bg-base-200/40 hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/meetings/' + meeting.id)"
          >
            <CalendarDaysIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ meeting.name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <StatusBadge :status="meeting.status" type="meeting" />
                <span v-if="meeting.start_date" class="text-xs text-base-content/40">{{ formatDateTime(meeting.start_date) }}</span>
                <span v-if="meeting.contact_ids?.length" class="text-xs text-base-content/40">{{ meeting.contact_ids.length }} attendee(s)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Opportunity" size="lg">
      <form @submit.prevent="handleEditSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Opportunity Name *</span></label>
            <input v-model="editForm.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.name && editErrors.name }" @blur="editBlur('name')" />
            <p v-if="editTouched.name && editErrors.name" class="text-error text-xs mt-1">{{ editErrors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Stage</span></label>
            <select v-model="editForm.stage" class="select select-bordered select-sm">
              <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Amount ($) *</span></label>
            <input v-model="editForm.amount" type="number" min="0" step="0.01" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.amount && editErrors.amount }" @blur="editBlur('amount')" />
            <p v-if="editTouched.amount && editErrors.amount" class="text-error text-xs mt-1">{{ editErrors.amount }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Probability (%)</span></label>
            <input v-model="editForm.probability" type="number" min="0" max="100" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Close Date *</span></label>
            <input v-model="editForm.close_date" type="date" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.close_date && editErrors.close_date }" @blur="editBlur('close_date')" />
            <p v-if="editTouched.close_date && editErrors.close_date" class="text-error text-xs mt-1">{{ editErrors.close_date }}</p>
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

    <!-- Add Task Modal -->
    <Modal v-model="showTaskModal" title="Add Task" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control sm:col-span-2">
          <label class="label"><span class="label-text text-sm font-medium">Task Name *</span></label>
          <input v-model="newTask.name" type="text" class="input input-bordered input-sm" placeholder="Task description..." />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
          <select v-model="newTask.status" class="select select-bordered select-sm">
            <option value="not_started">Not Started</option>
            <option value="in_progress">In Progress</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
          <select v-model="newTask.priority" class="select select-bordered select-sm">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Due Date</span></label>
          <input v-model="newTask.due_date" type="date" class="input input-bordered input-sm" />
        </div>
      </div>
      <template #footer>
        <button @click="showTaskModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="saveNewTask" class="btn btn-primary btn-sm" :disabled="!newTask.name.trim()">Add Task</button>
      </template>
    </Modal>

    <!-- Add Meeting Modal -->
    <Modal v-model="showMeetingModal" title="Schedule Meeting" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control sm:col-span-2">
          <label class="label"><span class="label-text text-sm font-medium">Meeting Name *</span></label>
          <input v-model="newMeeting.name" type="text" class="input input-bordered input-sm" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Start Date & Time</span></label>
          <input v-model="newMeeting.start_date" type="datetime-local" class="input input-bordered input-sm" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Duration (hours)</span></label>
          <input v-model="newMeeting.duration_hours" type="number" min="0" max="24" class="input input-bordered input-sm" />
        </div>
      </div>
      <template #footer>
        <button @click="showMeetingModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="saveNewMeeting" class="btn btn-primary btn-sm" :disabled="!newMeeting.name.trim()">Schedule</button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Opportunity" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ opp.name }}"?</p>
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
import { useRoute, useRouter } from 'vue-router'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useAccountsStore } from '../../stores/accounts'
import { useContactsStore } from '../../stores/contacts'
import { useLeadsStore } from '../../stores/leads'
import { useTasksStore } from '../../stores/tasks'
import { useMeetingsStore } from '../../stores/meetings'
import { useUsersStore } from '../../stores/users'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required } from '../../composables/useForm'
import {
  PencilIcon, TrashIcon, PlusIcon,
  BuildingOfficeIcon, UserIcon, ChevronRightIcon,
  ClipboardDocumentListIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const oppsStore = useOpportunitiesStore()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const leadsStore = useLeadsStore()
const tasksStore = useTasksStore()
const meetingsStore = useMeetingsStore()
const usersStore = useUsersStore()

const loading = ref(true)
const showEditModal = ref(false)
const showTaskModal = ref(false)
const showMeetingModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const activeTab = ref('tasks')

const stages = [
  { value: 'prospecting', label: 'Prospecting' },
  { value: 'qualification', label: 'Qualification' },
  { value: 'proposal', label: 'Proposal' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'closed_won', label: 'Closed Won' },
  { value: 'closed_lost', label: 'Closed Lost' },
]

const tabs = [
  { key: 'tasks', label: 'Tasks' },
  { key: 'meetings', label: 'Meetings' },
]

const opp = computed(() => oppsStore.items.find(o => o.id === Number(route.params.id)))
const accountName = computed(() => accountsStore.items.find(a => a.id === Number(opp.value?.account_id))?.name || '')
const contactName = computed(() => {
  const c = contactsStore.items.find(c => c.id === Number(opp.value?.contact_id))
  return c ? `${c.first_name} ${c.last_name}` : ''
})
const leadName = computed(() => {
  const l = leadsStore.items.find(l => l.id === Number(opp.value?.lead_id))
  return l ? `${l.first_name} ${l.last_name}` : '—'
})
const relatedTasks = computed(() =>
  tasksStore.items.filter(t => t.parent_type === 'Opportunity' && Number(t.parent_id) === Number(route.params.id))
)
const relatedMeetings = computed(() =>
  meetingsStore.items.filter(m => m.parent_type === 'Opportunity' && Number(m.parent_id) === Number(route.params.id))
)

const stageOrder = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost']
function isStageActive(s) { return opp.value?.stage === s }
function isStageCompleted(s) {
  if (!opp.value?.stage) return false
  return stageOrder.indexOf(s) < stageOrder.indexOf(opp.value.stage)
}

const editInitial = { name: '', stage: 'prospecting', amount: '', probability: 0, close_date: '', description: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  name: required('Opportunity name'),
  amount: required('Amount'),
  close_date: required('Close date'),
})
const newTask = reactive({ name: '', status: 'not_started', priority: 'medium', due_date: '' })
const newMeeting = reactive({ name: '', start_date: '', duration_hours: 1 })

function openEdit() {
  editReset({ ...editInitial, ...opp.value })
  showEditModal.value = true
}

function openNewTask() {
  Object.assign(newTask, { name: '', status: 'not_started', priority: 'medium', due_date: '' })
  showTaskModal.value = true
}

function openNewMeeting() {
  Object.assign(newMeeting, { name: '', start_date: '', duration_hours: 1 })
  showMeetingModal.value = true
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await oppsStore.update(opp.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function saveNewTask() {
  if (!newTask.name.trim()) return
  await tasksStore.create({ ...newTask, parent_type: 'Opportunity', parent_id: opp.value.id })
  showTaskModal.value = false
}

async function saveNewMeeting() {
  if (!newMeeting.name.trim()) return
  await meetingsStore.create({
    name: newMeeting.name,
    status: 'planned',
    parent_type: 'Opportunity',
    parent_id: opp.value.id,
    start_date: newMeeting.start_date || null,
    duration_hours: newMeeting.duration_hours,
    duration_minutes: 0,
  })
  showMeetingModal.value = false
}

async function handleDelete() {
  await oppsStore.remove(opp.value.id)
  router.push('/opportunities')
}

function getAssigneeName(id) {
  if (!id) return '—'
  return usersStore.items.find(u => u.id === Number(id))?.name || `#${id}`
}

function priorityDot(p) {
  return { low: 'bg-info', medium: 'bg-warning', high: 'bg-orange-400', urgent: 'bg-error' }[p] || 'bg-base-300'
}

function isOverdue(d) { return d && new Date(d) < new Date() }

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  await Promise.all([
    oppsStore.fetchAll(),
    accountsStore.fetchAll(),
    contactsStore.fetchAll(),
    leadsStore.fetchAll(),
    tasksStore.fetchAll(),
    meetingsStore.fetchAll(),
    usersStore.fetchAll(),
  ])
  loading.value = false
})
</script>
