<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!meeting" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Meeting not found.</p>
    <RouterLink to="/meetings" class="btn btn-ghost btn-sm">← Back to Meetings</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/meetings" class="text-primary">Meetings</RouterLink></li>
        <li class="text-base-content/60">{{ meeting.name }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold">{{ meeting.name }}</h1>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
              <StatusBadge :status="meeting.status" type="meeting" />
              <span v-if="meeting.start_date" class="text-sm text-base-content/60">
                <CalendarIcon class="w-3.5 h-3.5 inline mr-1" />{{ formatDateTime(meeting.start_date) }}
              </span>
              <span class="text-sm text-base-content/50">{{ meeting.duration_hours }}h {{ meeting.duration_minutes }}m</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Meeting Details -->
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Meeting Details</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Status</span>
              <StatusBadge :status="meeting.status" type="meeting" />
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Start</span>
              <span>{{ formatDateTime(meeting.start_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">End</span>
              <span>{{ formatDateTime(meeting.end_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Duration</span>
              <span>{{ meeting.duration_hours }}h {{ meeting.duration_minutes }}m</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Assigned To</span>
              <span>{{ getAssigneeName(meeting.assigned_to) }}</span>
            </div>
            <div v-if="meeting.meeting_link" class="flex justify-between items-center">
              <span class="text-base-content/50">Meeting Link</span>
              <a :href="meeting.meeting_link" target="_blank" class="text-primary hover:underline text-xs truncate max-w-[180px]">{{ meeting.meeting_link }}</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Related To -->
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Related To</h3>
          <div v-if="meeting.parent_type && meeting.parent_id" class="space-y-2">
            <div class="flex items-center gap-2">
              <span class="badge badge-outline badge-sm">{{ meeting.parent_type }}</span>
              <component
                :is="parentRoute ? 'RouterLink' : 'span'"
                :to="parentRoute"
                class="text-sm font-medium"
                :class="parentRoute ? 'text-primary hover:underline' : ''"
              >
                {{ parentName }}
              </component>
            </div>
          </div>
          <p v-else class="text-sm text-base-content/30">Not linked to any record</p>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="meeting.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ meeting.description }}</p>
      </div>
    </div>

    <!-- Contact Attendees -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">
            Attendees
            <span class="badge badge-sm badge-ghost ml-1">{{ attendees.length }}</span>
          </h3>
        </div>
        <div v-if="attendees.length === 0" class="text-center py-6">
          <UsersIcon class="w-8 h-8 text-base-content/20 mx-auto mb-2" />
          <p class="text-sm text-base-content/40">No contact attendees for this meeting.</p>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <div
            v-for="contact in attendees"
            :key="contact.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/contacts/' + contact.id)"
          >
            <div class="avatar placeholder">
              <div class="w-8 h-8 rounded-full bg-secondary/20">
                <span class="text-secondary text-xs font-bold">{{ contact.first_name?.[0] }}{{ contact.last_name?.[0] }}</span>
              </div>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ contact.first_name }} {{ contact.last_name }}</p>
              <p v-if="contact.title" class="text-xs text-base-content/50 truncate">{{ contact.title }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Meeting" size="lg">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Meeting Name *</span></label>
            <input v-model="editForm.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.name && editErrors.name }" @blur="editBlur('name')" />
            <p v-if="editTouched.name && editErrors.name" class="text-error text-xs mt-1">{{ editErrors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="editForm.status" class="select select-bordered select-sm">
              <option value="planned">Planned</option>
              <option value="held">Held</option>
              <option value="not_held">Not Held</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Related To (Type)</span></label>
            <select v-model="editForm.parent_type" class="select select-bordered select-sm" @change="editForm.parent_id = ''">
              <option value="">None</option>
              <option value="Account">Account</option>
              <option value="Contact">Contact</option>
              <option value="Lead">Lead</option>
              <option value="Opportunity">Opportunity</option>
            </select>
          </div>
          <div v-if="editForm.parent_type" class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">{{ editForm.parent_type }}</span></label>
            <select v-model="editForm.parent_id" class="select select-bordered select-sm">
              <option value="">Select {{ editForm.parent_type }}</option>
              <option v-for="item in editParentOptions" :key="item.id" :value="item.id">{{ getItemLabel(editForm.parent_type, item) }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Start Date & Time *</span></label>
            <input v-model="editForm.start_date" type="datetime-local" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.start_date && editErrors.start_date }" @blur="editBlur('start_date')" />
            <p v-if="editTouched.start_date && editErrors.start_date" class="text-error text-xs mt-1">{{ editErrors.start_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">End Date & Time *</span></label>
            <input v-model="editForm.end_date" type="datetime-local" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.end_date && editErrors.end_date }" @blur="editBlur('end_date')" />
            <p v-if="editTouched.end_date && editErrors.end_date" class="text-error text-xs mt-1">{{ editErrors.end_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Duration Hours</span></label>
            <input v-model="editForm.duration_hours" type="number" min="0" max="24" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Duration Minutes</span></label>
            <select v-model="editForm.duration_minutes" class="select select-bordered select-sm">
              <option value="0">0</option><option value="15">15</option><option value="30">30</option><option value="45">45</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Meeting Link</span></label>
            <input v-model="editForm.meeting_link" type="url" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.meeting_link && editErrors.meeting_link }" placeholder="https://meet.google.com/..." @blur="editBlur('meeting_link')" />
            <p v-if="editTouched.meeting_link && editErrors.meeting_link" class="text-error text-xs mt-1">{{ editErrors.meeting_link }}</p>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
          <!-- Attendees -->
          <div class="form-control sm:col-span-2">
            <label class="label">
              <span class="label-text text-sm font-medium">Attendees</span>
              <span class="label-text-alt text-base-content/40">{{ editForm.contact_ids.length }} selected</span>
            </label>
            <div class="border border-base-300 rounded-lg max-h-40 overflow-y-auto p-2 space-y-1">
              <label
                v-for="contact in contactsStore.items"
                :key="contact.id"
                class="flex items-center gap-2 p-1.5 rounded hover:bg-base-200 cursor-pointer"
              >
                <input
                  type="checkbox"
                  class="checkbox checkbox-sm checkbox-primary"
                  :checked="editForm.contact_ids.includes(contact.id)"
                  @change="toggleAttendee(contact.id)"
                />
                <span class="text-sm">{{ contact.first_name }} {{ contact.last_name }}</span>
                <span v-if="contact.title" class="text-xs text-base-content/40">· {{ contact.title }}</span>
              </label>
            </div>
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
    <Modal v-model="showDeleteModal" title="Delete Meeting" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ meeting.name }}"?</p>
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
import { useMeetingsStore } from '../../stores/meetings'
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import { useLeadsStore } from '../../stores/leads'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useUsersStore } from '../../stores/users'
import { useForm, required, url } from '../../composables/useForm'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PencilIcon, TrashIcon, CalendarIcon, UsersIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const meetingsStore = useMeetingsStore()
const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()
const leadsStore = useLeadsStore()
const oppsStore = useOpportunitiesStore()
const usersStore = useUsersStore()

const loading = ref(true)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)

const meeting = computed(() => meetingsStore.items.find(m => m.id === Number(route.params.id)))

const attendees = computed(() => {
  if (!meeting.value?.contact_ids?.length) return []
  return meeting.value.contact_ids
    .map(id => contactsStore.items.find(c => c.id === Number(id)))
    .filter(Boolean)
})

const parentName = computed(() => {
  if (!meeting.value?.parent_type || !meeting.value?.parent_id) return ''
  const id = Number(meeting.value.parent_id)
  const stores = { Account: accountsStore, Contact: contactsStore, Lead: leadsStore, Opportunity: oppsStore }
  const store = stores[meeting.value.parent_type]
  if (!store) return `#${id}`
  const item = store.items.find(i => i.id === id)
  if (!item) return `#${id}`
  return (meeting.value.parent_type === 'Contact' || meeting.value.parent_type === 'Lead')
    ? `${item.first_name} ${item.last_name}`
    : item.name
})

const parentRoute = computed(() => {
  if (!meeting.value?.parent_type || !meeting.value?.parent_id) return null
  const map = { Account: 'accounts', Contact: 'contacts', Lead: 'leads', Opportunity: 'opportunities' }
  const seg = map[meeting.value.parent_type]
  return seg ? `/${seg}/${meeting.value.parent_id}` : null
})

const editInitial = { name: '', status: 'planned', parent_type: '', parent_id: '', start_date: '', end_date: '', duration_hours: 1, duration_minutes: 0, description: '', meeting_link: '', contact_ids: [] }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  name: required('Meeting name'),
  start_date: required('Start date'),
  end_date: required('End date'),
  meeting_link: url(),
})

const editParentOptions = computed(() => {
  const map = { Account: accountsStore, Contact: contactsStore, Lead: leadsStore, Opportunity: oppsStore }
  return map[editForm.parent_type]?.items || []
})

function openEdit() {
  editReset({ ...editInitial, ...meeting.value, contact_ids: [...(meeting.value.contact_ids || [])] })
  showEditModal.value = true
}

function toggleAttendee(id) {
  const numId = Number(id)
  const idx = editForm.contact_ids.indexOf(numId)
  if (idx === -1) editForm.contact_ids.push(numId)
  else editForm.contact_ids.splice(idx, 1)
}

function getItemLabel(type, item) {
  if (type === 'Contact' || type === 'Lead') return `${item.first_name} ${item.last_name}`
  return item.name || item.id
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await meetingsStore.update(meeting.value.id, { ...editForm, contact_ids: [...editForm.contact_ids] })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handleDelete() {
  await meetingsStore.remove(meeting.value.id)
  router.push('/meetings')
}

function getAssigneeName(id) {
  if (!id) return '—'
  return usersStore.items.find(u => u.id === Number(id))?.name || `#${id}`
}

function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  await Promise.all([
    meetingsStore.fetchAll(),
    contactsStore.fetchAll(),
    accountsStore.fetchAll(),
    leadsStore.fetchAll(),
    oppsStore.fetchAll(),
    usersStore.fetchAll(),
  ])
  loading.value = false
})
</script>
