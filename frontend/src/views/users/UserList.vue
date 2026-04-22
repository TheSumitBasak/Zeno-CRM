<template>
  <div class="space-y-4">
    <DataTable
      :columns="columns"
      :data="usersStore.items"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add User
        </button>
      </template>
      <template #cell-name="{ row }">
        <div class="flex items-center gap-2">
          <div class="avatar placeholder">
            <div class="w-8 h-8 rounded-full bg-primary">
              <span class="text-primary-content text-xs font-bold">{{ row.name?.split(' ').map(n => n[0]).join('').slice(0, 2) }}</span>
            </div>
          </div>
          <div>
            <p class="font-medium text-base-content text-sm">{{ row.name }}</p>
            <p class="text-xs text-base-content/60">{{ row.email }}</p>
          </div>
        </div>
      </template>
      <template #cell-role="{ value }">
        <StatusBadge :status="value" type="role" />
      </template>
      <template #cell-is_active="{ value }">
        <StatusBadge :status="String(value)" type="active" />
      </template>
      <template #cell-last_login="{ value }">
        <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit User' : 'Add User'" size="md">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Full Name *</span></label>
            <input v-model="form.name" type="text" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
            <input v-model="form.email" type="email" class="input input-bordered input-sm" required />
          </div>
          <div class="form-control" v-if="!editingItem">
            <label class="label"><span class="label-text text-sm font-medium">Password *</span></label>
            <input v-model="form.password" type="password" class="input input-bordered input-sm" :required="!editingItem" minlength="6" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Role *</span></label>
            <select v-model="form.role" class="select select-bordered select-sm" required>
              <option value="user">Regular User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Team</span></label>
            <input v-model="form.team" type="text" class="input input-bordered input-sm" placeholder="e.g. Sales, Support" />
          </div>
          <div class="form-control" v-if="editingItem">
            <label class="cursor-pointer flex items-center gap-3">
              <input v-model="form.is_active" type="checkbox" class="toggle toggle-primary toggle-sm" />
              <span class="label-text text-sm font-medium">Active</span>
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button @click="showModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handleSubmit" class="btn btn-primary btn-sm" :disabled="submitting">
          <span v-if="submitting" class="loading loading-spinner loading-xs"></span>
          {{ editingItem ? 'Save Changes' : 'Create User' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete User" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="text-base-content font-medium">Delete user "{{ deletingItem?.name }}"?</p>
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
import { useUsersStore } from '../../stores/users'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const usersStore = useUsersStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)

const defaultForm = { name: '', email: '', password: '', role: 'user', team: '', is_active: true }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'name', label: 'User' },
  { key: 'role', label: 'Role' },
  { key: 'team', label: 'Team' },
  { key: 'is_active', label: 'Status' },
  { key: 'last_login', label: 'Last Login' },
]

function openCreate() {
  editingItem.value = null
  Object.assign(form, defaultForm)
  showModal.value = true
}

function openEdit(item) {
  editingItem.value = item
  Object.assign(form, { ...defaultForm, ...item, password: '' })
  showModal.value = true
}

function confirmDelete(item) {
  deletingItem.value = item
  showDeleteModal.value = true
}

async function handleSubmit() {
  if (!form.name || !form.email) return
  submitting.value = true
  const payload = { ...form }
  if (!payload.password) delete payload.password
  try {
    if (editingItem.value) {
      await usersStore.update(editingItem.value.id, payload)
    } else {
      await usersStore.create(payload)
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await usersStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return 'Never'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => usersStore.fetchAll())
</script>
