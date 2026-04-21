<template>
  <div class="space-y-4">
    <DataTable
      :columns="columns"
      :data="meetingsStore.items"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Schedule Meeting
        </button>
      </template>
      <template #cell-name="{ value }">
        <span class="font-medium text-base-content">{{ value }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="meeting" />
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
            <input v-model="form.name" type="text" class="input input-bordered input-sm" required />
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
            <label class="label"><span class="label-text text-sm font-medium">Parent Type</span></label>
            <select v-model="form.parent_type" class="select select-bordered select-sm">
              <option value="">None</option>
              <option value="Account">Account</option>
              <option value="Contact">Contact</option>
              <option value="Lead">Lead</option>
              <option value="Opportunity">Opportunity</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Start Date & Time</span></label>
            <input v-model="form.start_date" type="datetime-local" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">End Date & Time</span></label>
            <input v-model="form.end_date" type="datetime-local" class="input input-bordered input-sm" />
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
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
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
import { useMeetingsStore } from '../../stores/meetings'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const meetingsStore = useMeetingsStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)

const defaultForm = { name: '', status: 'planned', parent_type: '', parent_id: '', start_date: '', end_date: '', duration_hours: 1, duration_minutes: 0, description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'name', label: 'Meeting Name' },
  { key: 'status', label: 'Status' },
  { key: 'parent_type', label: 'Related To' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'duration', label: 'Duration' },
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
      await meetingsStore.update(editingItem.value.id, { ...form })
    } else {
      await meetingsStore.create({ ...form })
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

onMounted(() => meetingsStore.fetchAll())
</script>
