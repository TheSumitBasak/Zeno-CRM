<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-3">
        <div class="flex flex-col gap-3">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Account</span>
            <button @click="activeAccount = ''" class="btn btn-sm rounded-lg gap-1" :class="activeAccount === '' ? 'btn-primary' : 'btn-ghost'">
              All <span class="badge badge-sm" :class="activeAccount === '' ? 'badge-warning' : 'badge-ghost'">{{ contactsStore.items.length }}</span>
            </button>
            <button
              v-for="acc in accountsWithContacts" :key="acc.id"
              @click="activeAccount = String(acc.id)"
              class="btn btn-sm rounded-lg gap-1"
              :class="activeAccount === String(acc.id) ? 'btn-primary' : 'btn-ghost'"
            >
              {{ acc.name }}
              <span class="badge badge-sm" :class="activeAccount === String(acc.id) ? 'badge-warning' : 'badge-ghost'">{{ getAccountContactCount(acc.id) }}</span>
            </button>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mr-1">Department</span>
            <button @click="activeDept = ''" class="btn btn-sm rounded-lg" :class="activeDept === '' ? 'btn-secondary' : 'btn-ghost'">All Depts</button>
            <button v-for="dept in uniqueDepartments" :key="dept" @click="activeDept = dept" class="btn btn-sm rounded-lg" :class="activeDept === dept ? 'btn-secondary' : 'btn-ghost'">{{ dept }}</button>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-ghost btn-sm text-error ml-auto gap-1">
              <XMarkIcon class="w-3.5 h-3.5" />Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <DataTable :columns="columns" :data="filteredContacts" @edit="openEdit" @delete="confirmDelete">
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />Add Contact
        </button>
      </template>
      <template #cell-full_name="{ row }">
        <div class="flex items-center gap-2 cursor-pointer group" @click.stop="$router.push('/contacts/' + row.id)">
          <div class="avatar placeholder">
            <div class="w-7 h-7 rounded-full bg-secondary/20">
              <span class="text-secondary text-xs font-bold">{{ row.first_name?.[0] }}{{ row.last_name?.[0] }}</span>
            </div>
          </div>
          <div>
            <p class="font-medium text-base-content group-hover:text-primary transition-colors text-sm">{{ row.first_name }} {{ row.last_name }}</p>
            <p v-if="row.title" class="text-xs text-base-content/50">{{ row.title }}</p>
          </div>
        </div>
      </template>
      <template #cell-account="{ row }">
        <div v-if="getAccountName(row.account_id) !== '—'" class="flex items-center gap-1">
          <BuildingOfficeIcon class="w-3.5 h-3.5 text-base-content/40" />
          <RouterLink :to="'/accounts/' + row.account_id" class="text-sm text-primary hover:underline" @click.stop>{{ getAccountName(row.account_id) }}</RouterLink>
        </div>
        <span v-else class="text-base-content/30 text-xs">—</span>
      </template>
      <template #cell-created_at="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Contact' : 'Add Contact'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">First Name *</span></label>
            <input
              v-model="form.first_name" type="text"
              class="input input-bordered input-sm" :class="{ 'input-error': touched.first_name && errors.first_name }"
              @blur="handleBlur('first_name')"
            />
            <p v-if="touched.first_name && errors.first_name" class="text-error text-xs mt-1">{{ errors.first_name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
            <input
              v-model="form.last_name" type="text"
              class="input input-bordered input-sm" :class="{ 'input-error': touched.last_name && errors.last_name }"
              @blur="handleBlur('last_name')"
            />
            <p v-if="touched.last_name && errors.last_name" class="text-error text-xs mt-1">{{ errors.last_name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
            <input
              v-model="form.email" type="email"
              class="input input-bordered input-sm" :class="{ 'input-error': touched.email && errors.email }"
              @blur="handleBlur('email')"
            />
            <p v-if="touched.email && errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone *</span></label>
            <input v-model="form.phone" type="tel" class="input input-bordered input-sm" :class="{ 'input-error': touched.phone && errors.phone }" @blur="handleBlur('phone')" />
            <p v-if="touched.phone && errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Title *</span></label>
            <input v-model="form.title" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.title && errors.title }" placeholder="Job Title" @blur="handleBlur('title')" />
            <p v-if="touched.title && errors.title" class="text-error text-xs mt-1">{{ errors.title }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Department *</span></label>
            <input v-model="form.department" type="text" class="input input-bordered input-sm" :class="{ 'input-error': touched.department && errors.department }" placeholder="Department" @blur="handleBlur('department')" />
            <p v-if="touched.department && errors.department" class="text-error text-xs mt-1">{{ errors.department }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
            <select v-model="form.account_id" class="select select-bordered select-sm">
              <option value="">Select Account</option>
              <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Birthday</span></label>
            <input v-model="form.birthday" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Address</span></label>
            <input v-model="form.address" type="text" class="input input-bordered input-sm" placeholder="Full address" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="2"></textarea>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create Contact' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Contact" size="sm">
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
import { ref, computed, onMounted } from 'vue'
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import { useForm, required, email } from '../../composables/useForm'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import { PlusIcon, TrashIcon, BuildingOfficeIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeAccount = ref('')
const activeDept = ref('')

const initialValues = { first_name: '', last_name: '', email: '', phone: '', title: '', department: '', account_id: '', birthday: '', address: '', description: '' }

const { values: form, errors, touched, validate, handleBlur, reset } = useForm(initialValues, {
  first_name: required('First name'),
  last_name: required('Last name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  title: required('Title'),
  department: required('Department'),
})

const columns = [
  { key: 'full_name', label: 'Name' },
  { key: 'account', label: 'Account' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'department', label: 'Department' },
  { key: 'created_at', label: 'Created' },
]

const accountsWithContacts = computed(() => {
  const accountIds = [...new Set(contactsStore.items.map(c => c.account_id).filter(Boolean))]
  return accountsStore.items.filter(a => accountIds.includes(a.id)).slice(0, 6)
})

const uniqueDepartments = computed(() => {
  const depts = contactsStore.items.map(c => c.department).filter(Boolean)
  return [...new Set(depts)].sort()
})

const hasActiveFilters = computed(() => activeAccount.value !== '' || activeDept.value !== '')

const filteredContacts = computed(() =>
  contactsStore.items.filter(c => {
    const accountOk = activeAccount.value === '' || String(c.account_id) === activeAccount.value
    const deptOk = activeDept.value === '' || c.department === activeDept.value
    return accountOk && deptOk
  })
)

function getAccountContactCount(accountId) { return contactsStore.items.filter(c => c.account_id === accountId).length }
function clearFilters() { activeAccount.value = ''; activeDept.value = '' }
function getAccountName(id) { return accountsStore.items.find(a => a.id === id)?.name || '—' }

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
      await contactsStore.update(editingItem.value.id, { ...form })
    } else {
      await contactsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await contactsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([contactsStore.fetchAll(), accountsStore.fetchAll()])
})
</script>
