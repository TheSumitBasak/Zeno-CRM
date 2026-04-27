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
              <span class="badge badge-sm" :class="activeStatus === filter.value ? 'badge-warning' : 'badge-ghost'">
                {{ getStatusCount(filter.value) }}
              </span>
            </button>
          </div>
          <!-- Source & Extra Filters -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Source</span>
            <button
              v-for="src in ['all', ...sources]"
              :key="src"
              @click="activeSource = src"
              class="btn btn-sm rounded-lg"
              :class="activeSource === src ? 'btn-secondary' : 'btn-ghost'"
            >
              {{ src === 'all' ? 'All Sources' : src }}
            </button>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-ghost btn-sm text-error ml-auto gap-1">
              <XMarkIcon class="w-3.5 h-3.5" />Clear Filters
            </button>
          </div>
        </div>
      </div>
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
        <div class="flex items-center gap-2 cursor-pointer group" @click.stop="$router.push('/leads/' + row.id)">
          <div class="avatar placeholder">
            <div class="w-7 h-7 rounded-full bg-warning/20">
              <span class="text-warning text-xs font-bold">{{ row.first_name?.[0] }}{{ row.last_name?.[0] }}</span>
            </div>
          </div>
          <span class="font-medium text-base-content group-hover:text-primary transition-colors">{{ row.first_name }} {{ row.last_name }}</span>
        </div>
      </template>
      <template #cell-status="{ row, value }">
        <div class="flex items-center gap-2">
          <StatusBadge :status="value" type="lead" />
          <button
            v-if="value !== 'converted' && value !== 'dead'"
            class="btn btn-xs btn-warning gap-1"
            @click.stop="openConvert(row)"
          >
            <ArrowUpCircleIcon class="w-3 h-3" />
            Promote
          </button>
        </div>
      </template>
      <template #cell-created_at="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Lead' : 'Add Lead'" size="lg">
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
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">First Name *</span></label>
              <input v-model="form.first_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.first_name && errors.first_name }" @blur="handleBlur('first_name')" />
              <p v-if="touched.first_name && errors.first_name" class="text-error text-xs mt-1">{{ errors.first_name }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
              <input v-model="form.last_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.last_name && errors.last_name }" @blur="handleBlur('last_name')" />
              <p v-if="touched.last_name && errors.last_name" class="text-error text-xs mt-1">{{ errors.last_name }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
              <input v-model="form.email" type="email" class="input input-bordered input-sm" :class="{ 'input-error': touched.email && errors.email }" @blur="handleBlur('email')" />
              <p v-if="touched.email && errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Phone *</span></label>
              <input v-model="form.phone" type="tel" class="input input-bordered input-sm" :class="{ 'input-error': touched.phone && errors.phone }" @blur="handleBlur('phone')" />
              <p v-if="touched.phone && errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Company *</span></label>
              <input v-model="form.company" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.company && errors.company }" @blur="handleBlur('company')" />
              <p v-if="touched.company && errors.company" class="text-error text-xs mt-1">{{ errors.company }}</p>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm font-medium">Title *</span></label>
              <input v-model="form.title" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.title && errors.title }" @blur="handleBlur('title')" />
              <p v-if="touched.title && errors.title" class="text-error text-xs mt-1">{{ errors.title }}</p>
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
      </div>

      <div v-show="activeTab === 'tasks'" class="space-y-3">
        <div v-if="relatedTasks.length === 0" class="text-center py-6 text-base-content/40 text-sm">No tasks linked to this lead.</div>
        <div v-for="task in relatedTasks" :key="task.id" class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
          <div>
            <p class="text-sm font-medium">{{ task.name }}</p>
            <div class="flex items-center gap-2 mt-1">
              <StatusBadge :status="task.status" type="task" />
              <StatusBadge :status="task.priority" type="priority" />
              <span v-if="task.due_date" class="text-xs text-base-content/50">Due {{ formatDate(task.due_date) }}</span>
            </div>
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
        <div v-if="relatedMeetings.length === 0" class="text-center py-6 text-base-content/40 text-sm">No meetings linked to this lead.</div>
        <div v-for="meeting in relatedMeetings" :key="meeting.id" class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
          <div>
            <p class="text-sm font-medium">{{ meeting.name }}</p>
            <div class="flex items-center gap-2 mt-1">
              <StatusBadge :status="meeting.status" type="meeting" />
              <span v-if="meeting.start_date" class="text-xs text-base-content/50">{{ formatDateTime(meeting.start_date) }}</span>
            </div>
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
          {{ editingItem ? 'Save Changes' : 'Create Lead' }}
        </button>
      </template>
    </Modal>

    <!-- Promote/Convert Modal -->
    <Modal
      v-model="showConvertModal"
      :title="convertingLead ? `Promote Lead: ${convertingLead.first_name} ${convertingLead.last_name}` : 'Promote Lead'"
      size="lg"
    >
      <div v-if="convertingLead" class="space-y-5">
        <div class="space-y-3">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Account</h3>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="convertForm.account_mode" value="create" class="radio radio-sm radio-primary" />
              <span class="text-sm">Create New Account</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="convertForm.account_mode" value="existing" class="radio radio-sm radio-primary" />
              <span class="text-sm">Link to Existing</span>
            </label>
          </div>
          <div v-if="convertForm.account_mode === 'create'" class="form-control">
            <label class="label"><span class="label-text text-sm">Account Name</span></label>
            <input v-model="convertForm.account_name" type="text" class="input input-bordered input-sm" :placeholder="convertingLead.company || 'Company name'" />
          </div>
          <div v-else class="form-control">
            <label class="label"><span class="label-text text-sm">Select Account</span></label>
            <select v-model="convertForm.account_id" class="select select-bordered select-sm">
              <option value="">Select an account</option>
              <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Contact (will be created)</h3>
          <div class="bg-base-200 rounded-lg p-3 flex items-center gap-3">
            <div class="avatar placeholder">
              <div class="w-10 h-10 rounded-full bg-warning/20">
                <span class="text-warning font-bold">{{ convertingLead.first_name?.[0] }}{{ convertingLead.last_name?.[0] }}</span>
              </div>
            </div>
            <div>
              <p class="font-medium">{{ convertingLead.first_name }} {{ convertingLead.last_name }}</p>
              <p class="text-xs text-base-content/50">{{ convertingLead.title }}</p>
              <p v-if="convertingLead.email" class="text-xs text-primary">{{ convertingLead.email }}</p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Opportunity</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="convertForm.create_opportunity" class="checkbox checkbox-sm checkbox-primary" />
            <span class="text-sm">Create Opportunity</span>
          </label>
          <div v-if="convertForm.create_opportunity" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="form-control sm:col-span-2">
              <label class="label"><span class="label-text text-sm">Opportunity Name</span></label>
              <input v-model="convertForm.opportunity_name" type="text" class="input input-bordered input-sm" :placeholder="(convertingLead.company || 'Deal') + ' - Deal'" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm">Stage</span></label>
              <select v-model="convertForm.opportunity_stage" class="select select-bordered select-sm">
                <option value="prospecting">Prospecting</option>
                <option value="qualification">Qualification</option>
                <option value="proposal">Proposal</option>
                <option value="negotiation">Negotiation</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button @click="showConvertModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleConvert" class="btn btn-warning btn-sm gap-2" :disabled="convertSubmitting">
          <span v-if="convertSubmitting" class="loading loading-spinner loading-xs"></span>
          <ArrowUpCircleIcon v-else class="w-4 h-4" />
          Promote Lead
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Lead" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ deletingItem?.first_name }} {{ deletingItem?.last_name }}"?</p>
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
import { useRouter } from 'vue-router'
import { useLeadsStore } from '../../stores/leads'
import { useTasksStore } from '../../stores/tasks'
import { useMeetingsStore } from '../../stores/meetings'
import { useAccountsStore } from '../../stores/accounts'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useForm, required, email } from '../../composables/useForm'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon, ArrowUpCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const leadsStore = useLeadsStore()
const tasksStore = useTasksStore()
const meetingsStore = useMeetingsStore()
const accountsStore = useAccountsStore()
const oppsStore = useOpportunitiesStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const showConvertModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const convertingLead = ref(null)
const submitting = ref(false)
const convertSubmitting = ref(false)
const activeStatus = ref('all')
const activeSource = ref('all')
const activeTab = ref('details')
const quickTaskName = ref('')
const quickMeetingName = ref('')
const quickMeetingDate = ref('')

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

const initialValues = { first_name: '', last_name: '', email: '', phone: '', company: '', title: '', status: 'new', source: '', description: '' }
const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  first_name: required('First name'),
  last_name: required('Last name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  company: required('Company'),
  title: required('Title'),
})

const defaultConvertForm = { account_mode: 'create', account_name: '', account_id: '', create_opportunity: false, opportunity_name: '', opportunity_stage: 'prospecting' }
const convertForm = reactive({ ...defaultConvertForm })

const columns = [
  { key: 'full_name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'company', label: 'Company' },
  { key: 'status', label: 'Status' },
  { key: 'source', label: 'Source' },
  { key: 'created_at', label: 'Created' },
]

const relatedTasks = computed(() => {
  if (!editingItem.value) return []
  return tasksStore.items.filter(t => t.parent_type === 'Lead' && Number(t.parent_id) === editingItem.value.id)
})

const relatedMeetings = computed(() => {
  if (!editingItem.value) return []
  return meetingsStore.items.filter(m => m.parent_type === 'Lead' && Number(m.parent_id) === editingItem.value.id)
})

const hasActiveFilters = computed(() => activeStatus.value !== 'all' || activeSource.value !== 'all')

const filteredLeads = computed(() => {
  return leadsStore.items.filter(l => {
    const statusOk = activeStatus.value === 'all' || l.status === activeStatus.value
    const sourceOk = activeSource.value === 'all' || l.source === activeSource.value
    return statusOk && sourceOk
  })
})

function getStatusCount(status) {
  if (status === 'all') return leadsStore.items.length
  return leadsStore.items.filter(l => l.status === status).length
}

function clearFilters() {
  activeStatus.value = 'all'
  activeSource.value = 'all'
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

function openConvert(lead) {
  convertingLead.value = lead
  Object.assign(convertForm, {
    ...defaultConvertForm,
    account_name: lead.company || '',
    opportunity_name: (lead.company || 'Deal') + ' - Deal',
  })
  showConvertModal.value = true
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
      await leadsStore.update(editingItem.value.id, { ...form })
    } else {
      await leadsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleConvert() {
  if (!convertingLead.value) return
  convertSubmitting.value = true
  try {
    const payload = {
      create_account: convertForm.account_mode === 'create',
      account_name: convertForm.account_name || convertingLead.value.company,
      account_id: convertForm.account_mode === 'existing' ? convertForm.account_id : null,
      create_opportunity: convertForm.create_opportunity,
      opportunity_name: convertForm.opportunity_name,
      opportunity_stage: convertForm.opportunity_stage,
    }
    await leadsStore.convert(convertingLead.value.id, payload)
    showConvertModal.value = false
    await Promise.all([accountsStore.fetchAll(), oppsStore.fetchAll()])
  } finally {
    convertSubmitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await leadsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

async function addQuickTask() {
  if (!quickTaskName.value.trim() || !editingItem.value) return
  await tasksStore.create({ name: quickTaskName.value.trim(), status: 'not_started', priority: 'medium', parent_type: 'Lead', parent_id: editingItem.value.id })
  quickTaskName.value = ''
}

async function addQuickMeeting() {
  if (!quickMeetingName.value.trim() || !editingItem.value) return
  await meetingsStore.create({ name: quickMeetingName.value.trim(), status: 'planned', parent_type: 'Lead', parent_id: editingItem.value.id, start_date: quickMeetingDate.value || null, duration_hours: 1, duration_minutes: 0 })
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
  leadsStore.fetchAll(),
  tasksStore.fetchAll(),
  meetingsStore.fetchAll(),
  accountsStore.fetchAll(),
  oppsStore.fetchAll(),
]))
</script>
