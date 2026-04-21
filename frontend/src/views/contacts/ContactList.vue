<template>
  <div class="space-y-4">
    <DataTable
      :columns="columns"
      :data="contactsStore.items"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Contact
        </button>
      </template>
      <template #cell-full_name="{ row }">
        <div class="flex items-center gap-2">
          <div class="avatar placeholder">
            <div class="w-7 h-7 rounded-full bg-secondary">
              <span class="text-secondary-content text-xs">{{ row.first_name?.[0] }}{{ row.last_name?.[0] }}</span>
            </div>
          </div>
          <span class="font-medium text-base-content">{{ row.first_name }} {{ row.last_name }}</span>
        </div>
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
            <input v-model="form.first_name" type="text" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
            <input v-model="form.last_name" type="text" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email</span></label>
            <input v-model="form.email" type="email" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone</span></label>
            <input v-model="form.phone" type="tel" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Title</span></label>
            <input v-model="form.title" type="text" class="input input-bordered input-sm" placeholder="Job Title" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Department</span></label>
            <input v-model="form.department" type="text" class="input input-bordered input-sm" placeholder="Department" />
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
        <p class="text-base-content font-medium">Delete "{{ deletingItem?.first_name }} {{ deletingItem?.last_name }}"?</p>
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
import { ref, reactive, onMounted } from 'vue'
import { useContactsStore } from '../../stores/contacts'
import { useAccountsStore } from '../../stores/accounts'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const contactsStore = useContactsStore()
const accountsStore = useAccountsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)

const defaultForm = { first_name: '', last_name: '', email: '', phone: '', title: '', department: '', account_id: '', birthday: '', address: '', description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'full_name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'title', label: 'Title' },
  { key: 'department', label: 'Department' },
  { key: 'created_at', label: 'Created' },
]

function openCreate() {
  editingItem.value = null
  Object.assign(form, defaultForm)
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  Object.assign(form, { ...defaultForm, ...item })
  showModal.value = true
}

function confirmDelete(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

async function handleSubmit() {
  if (!form.first_name || !form.last_name) return
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
