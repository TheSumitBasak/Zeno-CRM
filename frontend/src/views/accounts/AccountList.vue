<template>
  <div class="space-y-4">
    <DataTable
      :columns="columns"
      :data="accountsStore.items"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Account
        </button>
      </template>
      <template #cell-name="{ row }">
        <button class="font-medium text-primary hover:underline text-left" @click.stop="openDetail(row)">
          {{ row.name }}
        </button>
      </template>
      <template #cell-contacts_count="{ row }">
        <span class="badge badge-sm badge-ghost">{{ getContactsCount(row.id) }}</span>
      </template>
      <template #cell-opps_count="{ row }">
        <span class="badge badge-sm badge-ghost">{{ getOppsCount(row.id) }}</span>
      </template>
      <template #cell-type="{ value }">
        <span class="badge badge-sm badge-outline">{{ value }}</span>
      </template>
      <template #cell-website="{ value }">
        <a v-if="value" :href="value" target="_blank" class="text-primary hover:underline text-xs">
          {{ value.replace('https://', '') }}
        </a>
        <span v-else class="text-base-content/60">-</span>
      </template>
      <template #cell-created_at="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Account Detail Modal -->
    <Modal v-model="showDetailModal" :title="viewingAccount?.name || ''" size="xl">
      <div v-if="viewingAccount">
        <div class="tabs tabs-bordered mb-4">
          <button
            v-for="tab in detailTabs"
            :key="tab.value"
            class="tab tab-sm"
            :class="activeDetailTab === tab.value ? 'tab-active' : ''"
            @click="activeDetailTab = tab.value"
          >
            {{ tab.label }}
            <span v-if="tab.count !== undefined" class="badge badge-sm ml-1">{{ tab.count }}</span>
          </button>
        </div>

        <div v-show="activeDetailTab === 'overview'">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Industry</p>
              <p class="font-medium">{{ viewingAccount.industry || '—' }}</p>
            </div>
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Type</p>
              <p class="font-medium">{{ viewingAccount.type || '—' }}</p>
            </div>
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Phone</p>
              <p class="font-medium">{{ viewingAccount.phone || '—' }}</p>
            </div>
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Email</p>
              <p class="font-medium">{{ viewingAccount.email || '—' }}</p>
            </div>
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Website</p>
              <p class="font-medium">
                <a v-if="viewingAccount.website" :href="viewingAccount.website" target="_blank" class="text-primary hover:underline">
                  {{ viewingAccount.website }}
                </a>
                <span v-else>—</span>
              </p>
            </div>
            <div class="card bg-base-200 p-4">
              <p class="text-xs text-base-content/50 mb-1">Billing Address</p>
              <p class="font-medium text-sm">{{ viewingAccount.billing_address || '—' }}</p>
            </div>
            <div v-if="viewingAccount.description" class="card bg-base-200 p-4 sm:col-span-2">
              <p class="text-xs text-base-content/50 mb-1">Description</p>
              <p class="text-sm">{{ viewingAccount.description }}</p>
            </div>
          </div>
        </div>

        <div v-show="activeDetailTab === 'contacts'" class="space-y-3">
          <div v-if="accountContacts.length === 0" class="text-center py-6 text-base-content/40 text-sm">No contacts linked to this account.</div>
          <div v-for="c in accountContacts" :key="c.id" class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
            <div class="avatar placeholder">
              <div class="w-8 h-8 rounded-full bg-secondary">
                <span class="text-secondary-content text-xs">{{ c.first_name?.[0] }}{{ c.last_name?.[0] }}</span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm text-base-content">{{ c.first_name }} {{ c.last_name }}</p>
              <p class="text-xs text-base-content/50">{{ [c.title, c.department].filter(Boolean).join(' · ') }}</p>
              <p v-if="c.email" class="text-xs text-primary">{{ c.email }}</p>
            </div>
          </div>
          <div class="pt-3 border-t border-base-300">
            <p class="text-xs font-medium text-base-content/50 mb-2">Add a contact</p>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="quickContact.first_name" type="text" class="input input-bordered input-xs" placeholder="First Name" />
              <input v-model="quickContact.last_name" type="text" class="input input-bordered input-xs" placeholder="Last Name" />
              <input v-model="quickContact.title" type="text" class="input input-bordered input-xs" placeholder="Title" />
              <input v-model="quickContact.email" type="email" class="input input-bordered input-xs" placeholder="Email" />
              <input v-model="quickContact.phone" type="tel" class="input input-bordered input-xs" placeholder="Phone" />
              <button class="btn btn-primary btn-xs" @click="addQuickContact" :disabled="!quickContact.first_name || !quickContact.last_name">Add Contact</button>
            </div>
          </div>
        </div>

        <div v-show="activeDetailTab === 'opportunities'" class="space-y-3">
          <div v-if="accountOpps.length === 0" class="text-center py-6 text-base-content/40 text-sm">No opportunities linked to this account.</div>
          <div v-for="opp in accountOpps" :key="opp.id" class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
            <div>
              <p class="font-medium text-sm text-base-content">{{ opp.name }}</p>
              <div class="flex items-center gap-2 mt-1">
                <StatusBadge :status="opp.stage" type="opportunity" />
                <span v-if="opp.amount" class="text-xs font-semibold text-success">${{ Number(opp.amount).toLocaleString() }}</span>
                <span v-if="opp.close_date" class="text-xs text-base-content/50">Close: {{ formatDate(opp.close_date) }}</span>
              </div>
            </div>
          </div>
          <div class="pt-3 border-t border-base-300">
            <p class="text-xs font-medium text-base-content/50 mb-2">Add an opportunity</p>
            <div class="flex gap-2">
              <input v-model="quickOppName" type="text" class="input input-bordered input-xs flex-1" placeholder="Opportunity name..." @keydown.enter.prevent="addQuickOpp" />
              <button class="btn btn-primary btn-xs" @click="addQuickOpp" :disabled="!quickOppName.trim()">Add</button>
            </div>
          </div>
        </div>

        <div v-show="activeDetailTab === 'meetings'" class="space-y-3">
          <div v-if="accountMeetings.length === 0" class="text-center py-6 text-base-content/40 text-sm">No meetings linked to this account.</div>
          <div v-for="m in accountMeetings" :key="m.id" class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
            <div>
              <p class="font-medium text-sm text-base-content">{{ m.name }}</p>
              <div class="flex items-center gap-2 mt-1">
                <StatusBadge :status="m.status" type="meeting" />
                <span v-if="m.start_date" class="text-xs text-base-content/50">{{ formatDateTime(m.start_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-show="activeDetailTab === 'tasks'" class="space-y-3">
          <div v-if="accountTasks.length === 0" class="text-center py-6 text-base-content/40 text-sm">No tasks linked to this account.</div>
          <div v-for="t in accountTasks" :key="t.id" class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
            <div>
              <p class="font-medium text-sm text-base-content">{{ t.name }}</p>
              <div class="flex items-center gap-2 mt-1">
                <StatusBadge :status="t.status" type="task" />
                <StatusBadge :status="t.priority" type="priority" />
                <span v-if="t.due_date" class="text-xs text-base-content/50">Due {{ formatDate(t.due_date) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button @click="showDetailModal = false" class="btn btn-ghost btn-sm">Close</button>
        <button @click="() => { showDetailModal = false; openEdit(viewingAccount) }" class="btn btn-primary btn-sm">Edit Account</button>
      </template>
    </Modal>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Account' : 'Add Account'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account Name *</span></label>
            <input
              v-model="form.name" type="text" placeholder="Company name"
              class="input input-bordered input-sm" :class="{ 'input-error': touched.name && errors.name }"
              @blur="handleBlur('name')"
            />
            <p v-if="touched.name && errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
            <input
              v-model="form.email" type="email" placeholder="contact@company.com"
              class="input input-bordered input-sm" :class="{ 'input-error': touched.email && errors.email }"
              @blur="handleBlur('email')"
            />
            <p v-if="touched.email && errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone *</span></label>
            <input v-model="form.phone" type="tel" class="input input-bordered input-sm" :class="{ 'input-error': touched.phone && errors.phone }" placeholder="+1-555-0000" @blur="handleBlur('phone')" />
            <p v-if="touched.phone && errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Industry</span></label>
            <select v-model="form.industry" class="select select-bordered select-sm">
              <option value="">Select Industry</option>
              <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Type</span></label>
            <select v-model="form.type" class="select select-bordered select-sm">
              <option value="">Select Type</option>
              <option v-for="t in accountTypes" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Website</span></label>
            <input v-model="form.website" type="url" class="input input-bordered input-sm" :class="{ 'input-error': touched.website && errors.website }" placeholder="https://example.com" @blur="handleBlur('website')" />
            <p v-if="touched.website && errors.website" class="text-error text-xs mt-1">{{ errors.website }}</p>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Billing Address</span></label>
            <input v-model="form.billing_address" type="text" class="input input-bordered input-sm" placeholder="Street, City, State, ZIP" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="3" placeholder="About this account..."></textarea>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create Account' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Account" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="text-base-content font-medium">Delete "{{ deletingItem?.name }}"?</p>
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
import { useAccountsStore } from '../../stores/accounts'
import { useContactsStore } from '../../stores/contacts'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useTasksStore } from '../../stores/tasks'
import { useMeetingsStore } from '../../stores/meetings'
import { useForm, required, email, url } from '../../composables/useForm'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const oppsStore = useOpportunitiesStore()
const tasksStore = useTasksStore()
const meetingsStore = useMeetingsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const showDetailModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const viewingAccount = ref(null)
const submitting = ref(false)
const activeDetailTab = ref('overview')

const quickOppName = ref('')
const quickContact = reactive({ first_name: '', last_name: '', title: '', email: '', phone: '' })

const industries = ['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail', 'Education', 'Research', 'Media', 'Real Estate', 'Other']
const accountTypes = ['Customer', 'Partner', 'Prospect', 'Competitor', 'Other']

const initialValues = { name: '', email: '', phone: '', industry: '', type: '', website: '', billing_address: '', description: '' }

const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  name: required('Account name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  website: url(),
})

const columns = [
  { key: 'name', label: 'Account Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'industry', label: 'Industry' },
  { key: 'type', label: 'Type' },
  { key: 'contacts_count', label: 'Contacts' },
  { key: 'opps_count', label: 'Opportunities' },
  { key: 'created_at', label: 'Created' },
]

const accountContacts = computed(() => viewingAccount.value ? contactsStore.items.filter(c => c.account_id === viewingAccount.value.id) : [])
const accountOpps = computed(() => viewingAccount.value ? oppsStore.items.filter(o => Number(o.account_id) === viewingAccount.value.id) : [])
const accountMeetings = computed(() => viewingAccount.value ? meetingsStore.items.filter(m => m.parent_type === 'Account' && Number(m.parent_id) === viewingAccount.value.id) : [])
const accountTasks = computed(() => viewingAccount.value ? tasksStore.items.filter(t => t.parent_type === 'Account' && Number(t.parent_id) === viewingAccount.value.id) : [])

const detailTabs = computed(() => [
  { value: 'overview', label: 'Overview' },
  { value: 'contacts', label: 'Contacts', count: accountContacts.value.length },
  { value: 'opportunities', label: 'Opportunities', count: accountOpps.value.length },
  { value: 'meetings', label: 'Meetings', count: accountMeetings.value.length },
  { value: 'tasks', label: 'Tasks', count: accountTasks.value.length },
])

function getContactsCount(id) { return contactsStore.items.filter(c => c.account_id === id).length }
function getOppsCount(id) { return oppsStore.items.filter(o => Number(o.account_id) === id).length }

function openDetail(item) { router.push('/accounts/' + item.id) }

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
      await accountsStore.update(editingItem.value.id, { ...form })
    } else {
      await accountsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await accountsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

async function addQuickContact() {
  if (!quickContact.first_name || !quickContact.last_name || !viewingAccount.value) return
  await contactsStore.create({ ...quickContact, account_id: viewingAccount.value.id })
  Object.assign(quickContact, { first_name: '', last_name: '', title: '', email: '', phone: '' })
}

async function addQuickOpp() {
  if (!quickOppName.value.trim() || !viewingAccount.value) return
  await oppsStore.create({ name: quickOppName.value.trim(), account_id: viewingAccount.value.id, stage: 'prospecting' })
  quickOppName.value = ''
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
  accountsStore.fetchAll(),
  contactsStore.fetchAll(),
  oppsStore.fetchAll(),
  tasksStore.fetchAll(),
  meetingsStore.fetchAll(),
]))
</script>
