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

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Account' : 'Add Account'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account Name *</span></label>
            <input v-model="form.name" type="text" class="input input-bordered input-sm" required placeholder="Company name" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email</span></label>
            <input v-model="form.email" type="email" class="input input-bordered input-sm" placeholder="contact@company.com" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone</span></label>
            <input v-model="form.phone" type="tel" class="input input-bordered input-sm" placeholder="+1-555-0000" />
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
            <input v-model="form.website" type="url" class="input input-bordered input-sm" placeholder="https://example.com" />
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

    <!-- Delete Confirm Modal -->
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
import { ref, reactive, onMounted } from 'vue'
import { useAccountsStore } from '../../stores/accounts'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const accountsStore = useAccountsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)

const industries = ['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail', 'Education', 'Research', 'Media', 'Real Estate', 'Other']
const accountTypes = ['Customer', 'Partner', 'Prospect', 'Competitor', 'Other']

const defaultForm = { name: '', email: '', phone: '', industry: '', type: '', website: '', billing_address: '', description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'name', label: 'Account Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'industry', label: 'Industry' },
  { key: 'type', label: 'Type' },
  { key: 'website', label: 'Website' },
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
  if (!form.name) return
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

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => accountsStore.fetchAll())
</script>
