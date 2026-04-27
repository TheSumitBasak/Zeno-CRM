<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-3">
        <div class="flex flex-col gap-3">
          <!-- Stage Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Stage</span>
            <button
              v-for="s in stageFilters"
              :key="s.value"
              @click="activeStage = s.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activeStage === s.value ? 'btn-primary' : 'btn-ghost'"
            >
              {{ s.label }}
              <span class="badge badge-sm" :class="activeStage === s.value ? 'badge-warning' : 'badge-ghost'">{{ getStageCount(s.value) }}</span>
            </button>
          </div>
          <!-- View Toggle + Extra -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">View</span>
            <div class="flex gap-1 bg-base-200 rounded-lg p-0.5">
              <button
                @click="viewMode = 'kanban'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all"
                :class="viewMode === 'kanban' ? 'bg-base-100 shadow text-base-content' : 'text-base-content/50 hover:text-base-content'"
              >
                <ViewColumnsIcon class="w-3.5 h-3.5" />Kanban
              </button>
              <button
                @click="viewMode = 'list'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all"
                :class="viewMode === 'list' ? 'bg-base-100 shadow text-base-content' : 'text-base-content/50 hover:text-base-content'"
              >
                <ListBulletIcon class="w-3.5 h-3.5" />List
              </button>
            </div>
            <button v-if="activeStage !== 'all'" @click="activeStage = 'all'" class="btn btn-ghost btn-sm text-error ml-auto gap-1">
              <XMarkIcon class="w-3.5 h-3.5" />Clear Filter
            </button>
            <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2 ml-auto">
              <PlusIcon class="w-4 h-4" />Add Opportunity
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Kanban View -->
    <div v-if="viewMode === 'kanban'">
      <KanbanBoard
        :items="filteredOpps"
        stage-field="stage"
        @edit="openEdit"
        @stage-change="handleStageChange"
      />
    </div>

    <!-- List View -->
    <div v-else>
      <DataTable
        :columns="columns"
        :data="filteredOpps"
        @edit="openEdit"
        @delete="confirmDelete"
      >
        <template #cell-name="{ row, value }">
          <span class="font-medium text-base-content cursor-pointer hover:text-primary transition-colors" @click.stop="$router.push('/opportunities/' + row.id)">{{ value }}</span>
        </template>
        <template #cell-contact_info="{ row }">
          <div v-if="row.contact_id">
            <p class="text-sm">{{ getContactName(row.contact_id) }}</p>
            <p class="text-xs text-base-content/50">{{ getContactTitle(row.contact_id) }}</p>
          </div>
          <span v-else class="text-base-content/30 text-xs">—</span>
        </template>
        <template #cell-account_name="{ row }">
          <span class="text-sm">{{ getAccountName(row.account_id) }}</span>
        </template>
        <template #cell-stage="{ value }">
          <StatusBadge :status="value" type="opportunity" />
        </template>
        <template #cell-amount="{ value }">
          <span class="font-semibold text-success">${{ Number(value || 0).toLocaleString() }}</span>
        </template>
        <template #cell-probability="{ value }">
          <div class="flex items-center gap-2">
            <div class="w-16 bg-base-300 rounded-full h-1.5">
              <div class="h-1.5 rounded-full bg-primary" :style="{ width: value + '%' }"></div>
            </div>
            <span class="text-xs">{{ value }}%</span>
          </div>
        </template>
        <template #cell-close_date="{ value }">
          <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Opportunity' : 'Add Opportunity'" size="lg">
      <div v-if="editingItem" class="tabs tabs-bordered mb-4">
        <button class="tab tab-sm" :class="activeTab === 'details' ? 'tab-active' : ''" @click="activeTab = 'details'">Details</button>
        <button class="tab tab-sm" :class="activeTab === 'tasks' ? 'tab-active' : ''" @click="activeTab = 'tasks'">
          Tasks <span class="badge badge-sm ml-1">{{ relatedTasks.length }}</span>
        </button>
        <button class="tab tab-sm" :class="activeTab === 'meetings' ? 'tab-active' : ''" @click="activeTab = 'meetings'">
          Meetings <span class="badge badge-sm ml-1">{{ relatedMeetings.length }}</span>
        </button>
      </div>

      <div v-show="activeTab === 'details'">
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="form-control sm:col-span-2">
              <label class="label"><span class="label-text text-sm font-medium">Opportunity Name *</span></label>
              <input v-model="form.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.name && errors.name }" @blur="handleBlur('name')" />
              <p v-if="touched.name && errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Stage *</span></label>
              <select v-model="form.stage" class="select select-bordered select-sm" required>
                <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Amount ($) *</span></label>
              <input v-model="form.amount" type="number" min="0" step="0.01" class="input input-bordered input-sm" :class="{ 'input-error': touched.amount && errors.amount }" @blur="handleBlur('amount')" />
              <p v-if="touched.amount && errors.amount" class="text-error text-xs mt-1">{{ errors.amount }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Probability (%)</span></label>
              <input v-model="form.probability" type="number" min="0" max="100" class="input input-bordered input-sm" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Close Date *</span></label>
              <input v-model="form.close_date" type="date" class="input input-bordered input-sm" :class="{ 'input-error': touched.close_date && errors.close_date }" @blur="handleBlur('close_date')" />
              <p v-if="touched.close_date && errors.close_date" class="text-error text-xs mt-1">{{ errors.close_date }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
              <select v-model="form.account_id" class="select select-bordered select-sm">
                <option value="">Select Account</option>
                <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Contact</span></label>
              <select v-model="form.contact_id" class="select select-bordered select-sm">
                <option value="">Select Contact</option>
                <option v-for="c in filteredContacts" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Source Lead</span></label>
              <select v-model="form.lead_id" class="select select-bordered select-sm">
                <option value="">No linked lead</option>
                <option v-for="l in activeLeads" :key="l.id" :value="l.id">{{ l.first_name }} {{ l.last_name }} — {{ l.company }}</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Lead Source</span></label>
              <select v-model="form.lead_source" class="select select-bordered select-sm">
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
      </div>

      <div v-show="activeTab === 'tasks'" class="space-y-3">
        <div v-if="relatedTasks.length === 0" class="text-center py-6 text-base-content/40 text-sm">No tasks linked to this opportunity.</div>
        <div v-for="task in relatedTasks" :key="task.id" class="p-3 bg-base-200 rounded-lg">
          <p class="text-sm font-medium">{{ task.name }}</p>
          <div class="flex items-center gap-2 mt-1">
            <StatusBadge :status="task.status" type="task" />
            <StatusBadge :status="task.priority" type="priority" />
            <span v-if="task.due_date" class="text-xs text-base-content/50">Due {{ formatDate(task.due_date) }}</span>
          </div>
        </div>
        <div class="pt-2 border-t border-base-300">
          <div class="flex gap-2">
            <input v-model="quickTaskName" type="text" class="input input-bordered input-xs flex-1" placeholder="Task name..." @keydown.enter.prevent="addQuickTask" />
            <button class="btn btn-primary btn-xs" @click="addQuickTask" :disabled="!quickTaskName.trim()">Add</button>
          </div>
        </div>
      </div>

      <div v-show="activeTab === 'meetings'" class="space-y-3">
        <div v-if="relatedMeetings.length === 0" class="text-center py-6 text-base-content/40 text-sm">No meetings linked to this opportunity.</div>
        <div v-for="meeting in relatedMeetings" :key="meeting.id" class="p-3 bg-base-200 rounded-lg">
          <p class="text-sm font-medium">{{ meeting.name }}</p>
          <div class="flex items-center gap-2 mt-1">
            <StatusBadge :status="meeting.status" type="meeting" />
            <span v-if="meeting.start_date" class="text-xs text-base-content/50">{{ formatDateTime(meeting.start_date) }}</span>
          </div>
        </div>
        <div class="pt-2 border-t border-base-300">
          <div class="flex gap-2">
            <input v-model="quickMeetingName" type="text" class="input input-bordered input-xs flex-1" placeholder="Meeting name..." @keydown.enter.prevent="addQuickMeeting" />
            <input v-model="quickMeetingDate" type="datetime-local" class="input input-bordered input-xs w-44" />
            <button class="btn btn-primary btn-xs" @click="addQuickMeeting" :disabled="!quickMeetingName.trim()">Add</button>
          </div>
        </div>
      </div>

      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button v-if="activeTab === 'details'" @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create Opportunity' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Opportunity" size="sm">
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
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useAccountsStore } from '../../stores/accounts'
import { useContactsStore } from '../../stores/contacts'
import { useLeadsStore } from '../../stores/leads'
import { useTasksStore } from '../../stores/tasks'
import { useMeetingsStore } from '../../stores/meetings'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import KanbanBoard from '../../components/common/KanbanBoard.vue'
import { useForm, required } from '../../composables/useForm'
import { PlusIcon, TrashIcon, ViewColumnsIcon, ListBulletIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const oppsStore = useOpportunitiesStore()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const leadsStore = useLeadsStore()
const tasksStore = useTasksStore()
const meetingsStore = useMeetingsStore()

const viewMode = ref('kanban')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeTab = ref('details')
const activeStage = ref('all')
const quickTaskName = ref('')
const quickMeetingName = ref('')
const quickMeetingDate = ref('')

const stages = [
  { value: 'prospecting', label: 'Prospecting' },
  { value: 'qualification', label: 'Qualification' },
  { value: 'proposal', label: 'Proposal' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'closed_won', label: 'Closed Won' },
  { value: 'closed_lost', label: 'Closed Lost' },
]

const stageFilters = [{ value: 'all', label: 'All' }, ...stages]
const sources = ['Web', 'Referral', 'Cold Call', 'Email', 'Social Media', 'Trade Show', 'Other']

const initialValues = { name: '', stage: 'prospecting', amount: '', probability: 0, close_date: '', account_id: '', contact_id: '', lead_id: '', lead_source: '', description: '' }
const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  name: required('Opportunity name'),
  amount: required('Amount'),
  close_date: required('Close date'),
})

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'account_name', label: 'Account' },
  { key: 'contact_info', label: 'Contact' },
  { key: 'stage', label: 'Stage' },
  { key: 'amount', label: 'Amount' },
  { key: 'probability', label: 'Probability' },
  { key: 'close_date', label: 'Close Date' },
]

const filteredOpps = computed(() => {
  if (activeStage.value === 'all') return oppsStore.items
  return oppsStore.items.filter(o => o.stage === activeStage.value)
})

function getStageCount(stage) {
  if (stage === 'all') return oppsStore.items.length
  return oppsStore.items.filter(o => o.stage === stage).length
}

const activeLeads = computed(() => leadsStore.items.filter(l => l.status !== 'dead'))
const filteredContacts = computed(() => {
  if (form.account_id) return contactsStore.items.filter(c => c.account_id === Number(form.account_id))
  return contactsStore.items
})

const relatedTasks = computed(() => {
  if (!editingItem.value) return []
  return tasksStore.items.filter(t => t.parent_type === 'Opportunity' && Number(t.parent_id) === editingItem.value.id)
})

const relatedMeetings = computed(() => {
  if (!editingItem.value) return []
  return meetingsStore.items.filter(m => m.parent_type === 'Opportunity' && Number(m.parent_id) === editingItem.value.id)
})

function getContactName(id) {
  const c = contactsStore.items.find(c => c.id === Number(id))
  return c ? `${c.first_name} ${c.last_name}` : '—'
}

function getContactTitle(id) {
  return contactsStore.items.find(c => c.id === Number(id))?.title || ''
}

function getAccountName(id) {
  return accountsStore.items.find(a => a.id === Number(id))?.name || '—'
}

function openCreate() {
  editingItem.value = null
  activeTab.value = 'details'
  reset()
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  activeTab.value = 'details'
  quickTaskName.value = ''
  quickMeetingName.value = ''
  quickMeetingDate.value = ''
  reset({ ...initialValues, ...item })
  showModal.value = true
}

function confirmDelete(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

function handleStageChange({ item, newStage }) {
  oppsStore.update(item.id, { ...item, stage: newStage })
}

async function handleSubmit() {
  if (!validate()) return
  submitting.value = true
  try {
    if (editingItem.value) {
      await oppsStore.update(editingItem.value.id, { ...form })
    } else {
      await oppsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await oppsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

async function addQuickTask() {
  if (!quickTaskName.value.trim() || !editingItem.value) return
  await tasksStore.create({ name: quickTaskName.value.trim(), status: 'not_started', priority: 'medium', parent_type: 'Opportunity', parent_id: editingItem.value.id })
  quickTaskName.value = ''
}

async function addQuickMeeting() {
  if (!quickMeetingName.value.trim() || !editingItem.value) return
  await meetingsStore.create({ name: quickMeetingName.value.trim(), status: 'planned', parent_type: 'Opportunity', parent_id: editingItem.value.id, start_date: quickMeetingDate.value || null, duration_hours: 1, duration_minutes: 0 })
  quickMeetingName.value = ''
  quickMeetingDate.value = ''
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => Promise.all([
  oppsStore.fetchAll(),
  accountsStore.fetchAll(),
  contactsStore.fetchAll(),
  leadsStore.fetchAll(),
  tasksStore.fetchAll(),
  meetingsStore.fetchAll(),
]))
</script>
