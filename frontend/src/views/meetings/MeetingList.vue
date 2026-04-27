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
              v-for="f in statusFilters"
              :key="f.value"
              @click="activeStatus = f.value"
              class="btn btn-sm rounded-lg gap-1"
              :class="activeStatus === f.value ? 'btn-primary' : 'btn-ghost'"
            >
              {{ f.label }}
              <span class="badge badge-sm" :class="activeStatus === f.value ? 'badge-warning' : 'badge-ghost'">{{ getStatusCount(f.value) }}</span>
            </button>
          </div>
          <!-- Related To Filter -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Related To</span>
            <button
              v-for="pt in parentTypeFilters"
              :key="pt.value"
              @click="activeParentType = pt.value"
              class="btn btn-sm rounded-lg"
              :class="activeParentType === pt.value ? 'btn-secondary' : 'btn-ghost'"
            >{{ pt.label }}</button>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-ghost btn-sm text-error ml-auto gap-1">
              <XMarkIcon class="w-3.5 h-3.5" />Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :data="filteredMeetings"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Schedule Meeting
        </button>
      </template>
      <template #cell-name="{ row, value }">
        <span class="font-medium text-base-content cursor-pointer hover:text-primary transition-colors" @click.stop="$router.push('/meetings/' + row.id)">{{ value }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="meeting" />
      </template>
      <template #cell-related_to="{ row }">
        <span v-if="row.parent_type && row.parent_id" class="text-xs text-base-content/70">
          <span class="font-medium">{{ row.parent_type }}:</span> {{ getParentName(row.parent_type, row.parent_id) }}
        </span>
        <span v-else class="text-xs text-base-content/30">—</span>
      </template>
      <template #cell-attendees="{ row }">
        <div v-if="row.contact_ids && row.contact_ids.length > 0" class="flex items-center gap-1">
          <span class="badge badge-sm badge-ghost">{{ row.contact_ids.length }}</span>
          <span class="text-xs text-base-content/60">{{ getContactName(row.contact_ids[0]) }}</span>
          <span v-if="row.contact_ids.length > 1" class="text-xs text-base-content/40">+{{ row.contact_ids.length - 1 }}</span>
        </div>
        <span v-else class="text-xs text-base-content/30">—</span>
      </template>
      <template #cell-start_date="{ value }">
        <span class="text-xs text-base-content">{{ formatDateTime(value) }}</span>
      </template>
      <template #cell-duration="{ row }">
        <span class="text-xs text-base-content">{{ row.duration_hours }}h {{ row.duration_minutes }}m</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Meeting' : 'Schedule Meeting'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Meeting Name *</span></label>
            <input v-model="form.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.name && errors.name }" @blur="handleBlur('name')" />
            <p v-if="touched.name && errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="form.status" class="select select-bordered select-sm">
              <option value="planned">Planned</option>
              <option value="held">Held</option>
              <option value="not_held">Not Held</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Related To (Type)</span></label>
            <select v-model="form.parent_type" class="select select-bordered select-sm" @change="form.parent_id = ''">
              <option value="">None</option>
              <option value="Account">Account</option>
              <option value="Contact">Contact</option>
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
          <div class="form-control" :class="form.parent_type ? '' : 'sm:col-span-1'">
            <label class="label"><span class="label-text text-sm font-medium">Start Date & Time *</span></label>
            <input v-model="form.start_date" type="datetime-local" class="input input-bordered input-sm" :class="{ 'input-error': touched.start_date && errors.start_date }" @blur="handleBlur('start_date')" />
            <p v-if="touched.start_date && errors.start_date" class="text-error text-xs mt-1">{{ errors.start_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">End Date & Time *</span></label>
            <input v-model="form.end_date" type="datetime-local" class="input input-bordered input-sm" :class="{ 'input-error': touched.end_date && errors.end_date }" @blur="handleBlur('end_date')" />
            <p v-if="touched.end_date && errors.end_date" class="text-error text-xs mt-1">{{ errors.end_date }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Duration Hours</span></label>
            <input v-model="form.duration_hours" type="number" min="0" max="24" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Duration Minutes</span></label>
            <select v-model="form.duration_minutes" class="select select-bordered select-sm">
              <option value="0">0</option>
              <option value="15">15</option>
              <option value="30">30</option>
              <option value="45">45</option>
            </select>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Meeting Link</span></label>
            <input v-model="form.meeting_link" type="url" class="input input-bordered input-sm" :class="{ 'input-error': touched.meeting_link && errors.meeting_link }" placeholder="https://meet.google.com/..." @blur="handleBlur('meeting_link')" />
            <p v-if="touched.meeting_link && errors.meeting_link" class="text-error text-xs mt-1">{{ errors.meeting_link }}</p>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
          </div>
          <!-- Attendees -->
          <div class="form-control sm:col-span-2">
            <label class="label">
              <span class="label-text text-sm font-medium">Attendees</span>
              <span class="label-text-alt text-base-content/40">{{ form.contact_ids.length }} selected</span>
            </label>
            <div v-if="availableContacts.length > 0" class="border border-base-300 rounded-lg max-h-40 overflow-y-auto p-2 space-y-1">
              <label
                v-for="contact in availableContacts"
                :key="contact.id"
                class="flex items-center gap-2 p-1.5 rounded hover:bg-base-200 cursor-pointer"
              >
                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" :checked="form.contact_ids.includes(contact.id)" @change="toggleAttendee(contact.id)" />
                <div class="avatar placeholder">
                  <div class="w-6 h-6 rounded-full bg-secondary/20">
                    <span class="text-secondary text-xs">{{ contact.first_name?.[0] }}{{ contact.last_name?.[0] }}</span>
                  </div>
                </div>
                <div>
                  <p class="text-sm leading-none">{{ contact.first_name }} {{ contact.last_name }}</p>
                  <p v-if="contact.title" class="text-xs text-base-content/50">{{ contact.title }}</p>
                </div>
              </label>
            </div>
            <div v-else class="text-xs text-base-content/40 py-2 px-1">No contacts available.</div>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Schedule Meeting' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Meeting" size="sm">
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
import { useMeetingsStore } from '../../stores/meetings'
import { useAccountsStore } from '../../stores/accounts'
import { useContactsStore } from '../../stores/contacts'
import { useLeadsStore } from '../../stores/leads'
import { useOpportunitiesStore } from '../../stores/opportunities'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required, url } from '../../composables/useForm'
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const meetingsStore = useMeetingsStore()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const leadsStore = useLeadsStore()
const oppsStore = useOpportunitiesStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeStatus = ref('all')
const activeParentType = ref('all')

const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'planned', label: 'Planned' },
  { value: 'held', label: 'Held' },
  { value: 'not_held', label: 'Not Held' },
]

const parentTypeFilters = [
  { value: 'all', label: 'All' },
  { value: 'Lead', label: 'Leads' },
  { value: 'Opportunity', label: 'Opportunities' },
  { value: 'Contact', label: 'Contacts' },
  { value: 'Account', label: 'Accounts' },
  { value: '', label: 'Unlinked' },
]

const initialValues = { name: '', status: 'planned', parent_type: '', parent_id: '', start_date: '', end_date: '', duration_hours: 1, duration_minutes: 0, description: '', meeting_link: '', contact_ids: [] }
const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  name: required('Meeting name'),
  start_date: required('Start date'),
  end_date: required('End date'),
  meeting_link: url(),
})

const columns = [
  { key: 'name', label: 'Meeting Name' },
  { key: 'status', label: 'Status' },
  { key: 'related_to', label: 'Related To' },
  { key: 'attendees', label: 'Attendees' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'duration', label: 'Duration' },
]

const hasActiveFilters = computed(() => activeStatus.value !== 'all' || activeParentType.value !== 'all')

const filteredMeetings = computed(() => {
  return meetingsStore.items.filter(m => {
    const statusOk = activeStatus.value === 'all' || m.status === activeStatus.value
    let parentOk = true
    if (activeParentType.value === '') {
      parentOk = !m.parent_type
    } else if (activeParentType.value !== 'all') {
      parentOk = m.parent_type === activeParentType.value
    }
    return statusOk && parentOk
  })
})

function getStatusCount(status) {
  if (status === 'all') return meetingsStore.items.length
  return meetingsStore.items.filter(m => m.status === status).length
}

function clearFilters() {
  activeStatus.value = 'all'
  activeParentType.value = 'all'
}

const parentOptions = computed(() => {
  if (form.parent_type === 'Account') return accountsStore.items
  if (form.parent_type === 'Contact') return contactsStore.items
  if (form.parent_type === 'Lead') return leadsStore.items
  if (form.parent_type === 'Opportunity') return oppsStore.items
  return []
})

const availableContacts = computed(() => {
  if (!form.parent_type) return contactsStore.items
  if (form.parent_type === 'Account') return contactsStore.items.filter(c => c.account_id === Number(form.parent_id))
  if (form.parent_type === 'Opportunity') {
    const opp = oppsStore.items.find(o => o.id === Number(form.parent_id))
    if (opp?.account_id) return contactsStore.items.filter(c => c.account_id === Number(opp.account_id))
  }
  if (form.parent_type === 'Contact') return contactsStore.items.filter(c => c.id === Number(form.parent_id))
  return contactsStore.items
})

function toggleAttendee(contactId) {
  const id = Number(contactId)
  const idx = form.contact_ids.indexOf(id)
  if (idx === -1) form.contact_ids.push(id)
  else form.contact_ids.splice(idx, 1)
}

function getContactName(id) {
  const c = contactsStore.items.find(c => c.id === Number(id))
  return c ? `${c.first_name} ${c.last_name}` : `#${id}`
}

function getItemLabel(type, item) {
  if (type === 'Account' || type === 'Opportunity') return item.name
  if (type === 'Contact' || type === 'Lead') return `${item.first_name} ${item.last_name}`
  return item.name || item.id
}

function getParentName(type, id) {
  const numId = Number(id)
  const stores = { Account: accountsStore, Contact: contactsStore, Lead: leadsStore, Opportunity: oppsStore }
  const store = stores[type]
  if (!store) return `#${id}`
  const item = store.items.find(i => i.id === numId)
  if (!item) return `#${id}`
  return (type === 'Contact' || type === 'Lead') ? `${item.first_name} ${item.last_name}` : item.name
}

function openCreate() {
  editingItem.value = null
  reset({ ...initialValues, contact_ids: [] })
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  reset({ ...initialValues, ...item, contact_ids: Array.isArray(item.contact_ids) ? [...item.contact_ids] : [] })
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
      await meetingsStore.update(editingItem.value.id, { ...form, contact_ids: [...form.contact_ids] })
    } else {
      await meetingsStore.create({ ...form, contact_ids: [...form.contact_ids] })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await meetingsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDateTime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => Promise.all([
  meetingsStore.fetchAll(),
  accountsStore.fetchAll(),
  contactsStore.fetchAll(),
  leadsStore.fetchAll(),
  oppsStore.fetchAll(),
]))
</script>
