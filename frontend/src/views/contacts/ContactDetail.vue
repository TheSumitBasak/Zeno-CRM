<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!contact" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Contact not found.</p>
    <RouterLink to="/contacts" class="btn btn-ghost btn-sm">← Back to Contacts</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/contacts" class="text-primary">Contacts</RouterLink></li>
        <li class="text-base-content/60">{{ contact.first_name }} {{ contact.last_name }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="avatar placeholder">
              <div class="w-16 h-16 rounded-full bg-secondary/20">
                <span class="text-secondary text-xl font-bold">{{ contact.first_name?.[0] }}{{ contact.last_name?.[0] }}</span>
              </div>
            </div>
            <div>
              <h1 class="text-xl font-bold">{{ contact.first_name }} {{ contact.last_name }}</h1>
              <p class="text-base-content/50 text-sm">{{ [contact.title, contact.department].filter(Boolean).join(' · ') }}</p>
              <div v-if="accountName" class="flex items-center gap-1 mt-1">
                <BuildingOfficeIcon class="w-3.5 h-3.5 text-base-content/40" />
                <RouterLink v-if="contact.account_id" :to="'/accounts/' + contact.account_id" class="text-sm text-primary hover:underline">{{ accountName }}</RouterLink>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
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

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Contact Info</h3>
          <div class="space-y-2">
            <div v-if="contact.email" class="flex items-center gap-2">
              <EnvelopeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <a :href="'mailto:' + contact.email" class="text-sm text-primary hover:underline truncate">{{ contact.email }}</a>
            </div>
            <div v-if="contact.phone" class="flex items-center gap-2">
              <PhoneIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <span class="text-sm">{{ contact.phone }}</span>
            </div>
            <div v-if="contact.address" class="flex items-start gap-2">
              <MapPinIcon class="w-4 h-4 text-base-content/40 flex-shrink-0 mt-0.5" />
              <span class="text-sm text-base-content/70">{{ contact.address }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Work</h3>
          <div class="space-y-2 text-sm">
            <div v-if="accountName" class="flex justify-between items-center">
              <span class="text-base-content/50">Account</span>
              <RouterLink v-if="contact.account_id" :to="'/accounts/' + contact.account_id" class="text-primary hover:underline">{{ accountName }}</RouterLink>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Department</span>
              <span>{{ contact.department || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Title</span>
              <span>{{ contact.title || '—' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Personal</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Birthday</span>
              <span>{{ formatDate(contact.birthday) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Created</span>
              <span>{{ formatDate(contact.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="contact.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Notes</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ contact.description }}</p>
      </div>
    </div>

    <!-- Related Opportunities -->
    <div v-if="relatedOpportunities.length > 0" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-3">
          Opportunities
          <span class="badge badge-sm badge-ghost ml-1">{{ relatedOpportunities.length }}</span>
        </h3>
        <div class="space-y-2">
          <div
            v-for="opp in relatedOpportunities"
            :key="opp.id"
            class="flex items-center justify-between p-2 rounded-lg hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/opportunities/' + opp.id)"
          >
            <div>
              <p class="text-sm font-medium">{{ opp.name }}</p>
              <span class="text-xs text-base-content/40">${{ Number(opp.amount || 0).toLocaleString() }}</span>
            </div>
            <StatusBadge :status="opp.stage" type="opportunity" />
          </div>
        </div>
      </div>
    </div>

    <!-- Meetings & Tasks Tabs -->
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
              {{ tab.key === 'meetings' ? relatedMeetings.length : relatedTasks.length }}
            </span>
          </button>
        </div>

        <!-- Meetings -->
        <div v-if="activeTab === 'meetings'" class="p-4 space-y-2">
          <div v-if="relatedMeetings.length === 0" class="text-center py-10">
            <CalendarDaysIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No meetings with this contact.</p>
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
              </div>
            </div>
          </div>
        </div>

        <!-- Tasks -->
        <div v-if="activeTab === 'tasks'" class="p-4 space-y-2">
          <div v-if="relatedTasks.length === 0" class="text-center py-10">
            <ClipboardDocumentListIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No tasks linked to this contact.</p>
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
              <div class="flex items-center gap-2 mt-0.5">
                <StatusBadge :status="task.status" type="task" />
                <span v-if="task.due_date" class="text-xs text-base-content/40">Due {{ formatDate(task.due_date) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Contact" size="lg">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">First Name *</span></label>
            <input v-model="editForm.first_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.first_name && editErrors.first_name }" @blur="editBlur('first_name')" />
            <p v-if="editTouched.first_name && editErrors.first_name" class="text-error text-xs mt-1">{{ editErrors.first_name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
            <input v-model="editForm.last_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.last_name && editErrors.last_name }" @blur="editBlur('last_name')" />
            <p v-if="editTouched.last_name && editErrors.last_name" class="text-error text-xs mt-1">{{ editErrors.last_name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
            <input v-model="editForm.email" type="email" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.email && editErrors.email }" @blur="editBlur('email')" />
            <p v-if="editTouched.email && editErrors.email" class="text-error text-xs mt-1">{{ editErrors.email }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone *</span></label>
            <input v-model="editForm.phone" type="tel" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.phone && editErrors.phone }" @blur="editBlur('phone')" />
            <p v-if="editTouched.phone && editErrors.phone" class="text-error text-xs mt-1">{{ editErrors.phone }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Title *</span></label>
            <input v-model="editForm.title" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.title && editErrors.title }" @blur="editBlur('title')" />
            <p v-if="editTouched.title && editErrors.title" class="text-error text-xs mt-1">{{ editErrors.title }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Department *</span></label>
            <input v-model="editForm.department" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.department && editErrors.department }" @blur="editBlur('department')" />
            <p v-if="editTouched.department && editErrors.department" class="text-error text-xs mt-1">{{ editErrors.department }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
            <select v-model="editForm.account_id" class="select select-bordered select-sm">
              <option value="">No Account</option>
              <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Birthday</span></label>
            <input v-model="editForm.birthday" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Address</span></label>
            <input v-model="editForm.address" type="text" class="input input-bordered input-sm" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Notes</span></label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
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
    <Modal v-model="showDeleteModal" title="Delete Contact" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ contact.first_name }} {{ contact.last_name }}"?</p>
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
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useMeetingsStore } from '../../stores/meetings'
import { useTasksStore } from '../../stores/tasks'
import { useForm, required, email } from '../../composables/useForm'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import {
  PencilIcon, TrashIcon, BuildingOfficeIcon,
  EnvelopeIcon, PhoneIcon, MapPinIcon,
  CalendarDaysIcon, ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()
const oppsStore = useOpportunitiesStore()
const meetingsStore = useMeetingsStore()
const tasksStore = useTasksStore()

const loading = ref(true)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const activeTab = ref('meetings')

const tabs = [
  { key: 'meetings', label: 'Meetings' },
  { key: 'tasks', label: 'Tasks' },
]

const contact = computed(() => contactsStore.items.find(c => c.id === Number(route.params.id)))
const accountName = computed(() => accountsStore.items.find(a => a.id === Number(contact.value?.account_id))?.name || '')
const relatedOpportunities = computed(() =>
  oppsStore.items.filter(o => Number(o.contact_id) === Number(route.params.id))
)
const relatedMeetings = computed(() =>
  meetingsStore.items.filter(m =>
    (m.contact_ids && m.contact_ids.includes(Number(route.params.id))) ||
    (m.parent_type === 'Contact' && Number(m.parent_id) === Number(route.params.id))
  )
)
const relatedTasks = computed(() =>
  tasksStore.items.filter(t =>
    Number(t.contact_id) === Number(route.params.id) ||
    (t.parent_type === 'Contact' && Number(t.parent_id) === Number(route.params.id))
  )
)

const editInitial = { first_name: '', last_name: '', email: '', phone: '', title: '', department: '', account_id: '', birthday: '', address: '', description: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  first_name: required('First name'),
  last_name: required('Last name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  title: required('Title'),
  department: required('Department'),
})

function openEdit() {
  editReset({ ...editInitial, ...contact.value })
  showEditModal.value = true
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await contactsStore.update(contact.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handleDelete() {
  await contactsStore.remove(contact.value.id)
  router.push('/contacts')
}

function priorityDot(p) {
  return { low: 'bg-info', medium: 'bg-warning', high: 'bg-orange-400', urgent: 'bg-error' }[p] || 'bg-base-300'
}

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
    contactsStore.fetchAll(),
    accountsStore.fetchAll(),
    oppsStore.fetchAll(),
    meetingsStore.fetchAll(),
    tasksStore.fetchAll(),
  ])
  loading.value = false
})
</script>
