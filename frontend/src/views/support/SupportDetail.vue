<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!ticket" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Support ticket not found.</p>
    <RouterLink to="/support" class="btn btn-ghost btn-sm">← Back to Support</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/support" class="text-primary">Support</RouterLink></li>
        <li class="text-base-content/60">{{ ticket.subject }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-info/20 flex items-center justify-center flex-shrink-0">
              <LifebuoyIcon class="w-6 h-6 text-info" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-base-content">{{ ticket.subject }}</h1>
              <div class="flex items-center gap-2 mt-2 flex-wrap">
                <StatusBadge :status="ticket.status" type="support" />
                <StatusBadge :status="ticket.priority" type="priority" />
                <span class="text-xs text-base-content/40">Created {{ formatDate(ticket.created_at) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
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

    <!-- Status pipeline -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-3">Ticket Pipeline</h3>
        <div class="flex items-center gap-1 overflow-x-auto pb-1">
          <div
            v-for="(s, idx) in statuses"
            :key="s.value"
            class="flex items-center gap-1 flex-shrink-0"
          >
            <div
              class="px-3 py-1.5 rounded-full text-xs font-medium transition-all"
              :class="isStatusActive(s.value) ? 'bg-primary text-primary-content' : isStatusCompleted(s.value) ? 'bg-success/20 text-success' : 'bg-base-200 text-base-content/40'"
            >
              {{ s.label }}
            </div>
            <ChevronRightIcon v-if="idx < statuses.length - 1" class="w-3 h-3 text-base-content/20 flex-shrink-0" />
          </div>
        </div>
      </div>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Linked Records</h3>
          <div class="space-y-2">
            <div v-if="leadName" class="flex items-center gap-2">
              <UserPlusIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <RouterLink :to="'/leads/' + ticket.lead_id" class="text-sm text-primary hover:underline">{{ leadName }}</RouterLink>
            </div>
            <div v-if="contactName" class="flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <RouterLink :to="'/contacts/' + ticket.contact_id" class="text-sm text-primary hover:underline">{{ contactName }}</RouterLink>
            </div>
            <div v-if="accountName" class="flex items-center gap-2">
              <BuildingOfficeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <RouterLink :to="'/accounts/' + ticket.account_id" class="text-sm text-primary hover:underline">{{ accountName }}</RouterLink>
            </div>
            <p v-if="!leadName && !contactName && !accountName" class="text-sm text-base-content/30">No linked records</p>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Ticket Details</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Status</span>
              <StatusBadge :status="ticket.status" type="support" />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Priority</span>
              <StatusBadge :status="ticket.priority" type="priority" />
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Assigned To</span>
              <span>{{ getAssigneeName(ticket.assigned_to) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Timeline</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Created</span>
              <span>{{ formatDate(ticket.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Updated</span>
              <span>{{ formatDate(ticket.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="ticket.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ ticket.description }}</p>
      </div>
    </div>

    <!-- Resolution -->
    <div class="card bg-base-100 shadow-sm border border-base-200" :class="ticket.resolution ? 'border-success/30 bg-success/5' : ''">
      <div class="card-body p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Resolution Notes</h3>
          <button v-if="!showResolutionEdit" @click="openResolutionEdit" class="btn btn-ghost btn-xs gap-1">
            <PencilIcon class="w-3 h-3" />{{ ticket.resolution ? 'Edit' : 'Add' }}
          </button>
        </div>
        <div v-if="!showResolutionEdit">
          <p v-if="ticket.resolution" class="text-sm text-base-content/70 whitespace-pre-wrap">{{ ticket.resolution }}</p>
          <p v-else class="text-sm text-base-content/30 italic">No resolution notes yet.</p>
        </div>
        <div v-else class="space-y-2">
          <textarea v-model="resolutionText" class="textarea textarea-bordered textarea-sm w-full" rows="4" placeholder="Describe how this was resolved..."></textarea>
          <div class="flex gap-2">
            <button @click="saveResolution" class="btn btn-success btn-sm">Save</button>
            <button @click="showResolutionEdit = false" class="btn btn-ghost btn-sm">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Status Update -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-3">Quick Status Update</h3>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="s in statuses"
            :key="s.value"
            @click="quickStatusUpdate(s.value)"
            class="btn btn-sm"
            :class="ticket.status === s.value ? 'btn-primary' : 'btn-ghost'"
            :disabled="ticket.status === s.value"
          >
            {{ s.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Support Ticket" size="lg">
      <form @submit.prevent="handleEditSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Subject *</span></label>
            <input v-model="editForm.subject" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.subject && editErrors.subject }" @blur="editBlur('subject')" />
            <p v-if="editTouched.subject && editErrors.subject" class="text-error text-xs mt-1">{{ editErrors.subject }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="editForm.status" class="select select-bordered select-sm">
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
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
            <label class="label"><span class="label-text text-sm font-medium">Contact</span></label>
            <select v-model="editForm.contact_id" class="select select-bordered select-sm">
              <option value="">No contact</option>
              <option v-for="c in contactsStore.items" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
            <select v-model="editForm.account_id" class="select select-bordered select-sm">
              <option value="">No account</option>
              <option v-for="a in accountsStore.items" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Resolution Notes</span></label>
            <textarea v-model="editForm.resolution" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
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
    <Modal v-model="showDeleteModal" title="Delete Ticket" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ ticket.subject }}"?</p>
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
import { useSupportStore } from '../../stores/support'
import { useLeadsStore } from '../../stores/leads'
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import { useUsersStore } from '../../stores/users'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required } from '../../composables/useForm'
import {
  PencilIcon, TrashIcon, ChevronRightIcon,
  LifebuoyIcon, UserPlusIcon, UserIcon, BuildingOfficeIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const supportStore = useSupportStore()
const leadsStore = useLeadsStore()
const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()
const usersStore = useUsersStore()

const loading = ref(true)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const showResolutionEdit = ref(false)
const resolutionText = ref('')

const statuses = [
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'pending', label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
]

const statusOrder = statuses.map(s => s.value)

const ticket = computed(() => supportStore.items.find(t => t.id === Number(route.params.id)))

const leadName = computed(() => {
  if (!ticket.value?.lead_id) return ''
  const l = leadsStore.items.find(l => l.id === Number(ticket.value.lead_id))
  return l ? `${l.first_name} ${l.last_name}` : ''
})
const contactName = computed(() => {
  if (!ticket.value?.contact_id) return ''
  const c = contactsStore.items.find(c => c.id === Number(ticket.value.contact_id))
  return c ? `${c.first_name} ${c.last_name}` : ''
})
const accountName = computed(() => {
  if (!ticket.value?.account_id) return ''
  return accountsStore.items.find(a => a.id === Number(ticket.value.account_id))?.name || ''
})

function isStatusActive(s) { return ticket.value?.status === s }
function isStatusCompleted(s) {
  if (!ticket.value?.status) return false
  return statusOrder.indexOf(s) < statusOrder.indexOf(ticket.value.status)
}

const editInitial = { subject: '', status: 'open', priority: 'medium', contact_id: '', account_id: '', description: '', resolution: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  subject: required('Subject'),
})

function openEdit() {
  editReset({ ...editInitial, ...ticket.value })
  showEditModal.value = true
}

function openResolutionEdit() {
  resolutionText.value = ticket.value.resolution || ''
  showResolutionEdit.value = true
}

async function saveResolution() {
  await supportStore.update(ticket.value.id, { resolution: resolutionText.value })
  showResolutionEdit.value = false
}

async function quickStatusUpdate(status) {
  await supportStore.update(ticket.value.id, { status })
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await supportStore.update(ticket.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handleDelete() {
  await supportStore.remove(ticket.value.id)
  router.push('/support')
}

function getAssigneeName(id) {
  if (!id) return '—'
  return usersStore.items.find(u => u.id === Number(id))?.name || `#${id}`
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([
    supportStore.fetchAll(),
    leadsStore.fetchAll(),
    contactsStore.fetchAll(),
    accountsStore.fetchAll(),
    usersStore.fetchAll(),
  ])
  loading.value = false
})
</script>
