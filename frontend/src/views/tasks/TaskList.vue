<template>
  <div class="space-y-4">
    <!-- Priority filter -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="filter in statusFilters"
        :key="filter.value"
        @click="activeFilter = filter.value"
        class="btn btn-sm rounded-lg"
        :class="activeFilter === filter.value ? 'btn-primary' : 'btn-ghost'"
      >
        {{ filter.label }}
        <span class="badge badge-sm ml-1" :class="activeFilter === filter.value ? 'badge-warning' : 'badge-ghost'">
          {{ getFilterCount(filter.value) }}
        </span>
      </button>
    </div>

    <DataTable
      :columns="columns"
      :data="filteredTasks"
      @edit="openEdit"
      @delete="confirmDelete"
    >
      <template #actions>
        <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Task
        </button>
      </template>
      <template #cell-name="{ value }">
        <span class="font-medium text-base-content">{{ value }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" type="task" />
      </template>
      <template #cell-priority="{ value }">
        <StatusBadge :status="value" type="priority" />
      </template>
      <template #cell-due_date="{ value }">
        <span :class="isOverdue(value) ? 'text-error font-medium' : 'text-xs text-base-content'">
          {{ formatDate(value) }}
          <span v-if="isOverdue(value)" class="ml-1 text-xs">(Overdue)</span>
        </span>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Task' : 'Add Task'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Task Name *</span></label>
            <input v-model="form.name" type="text" class="input input-bordered input-sm" required placeholder="Task description..." />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="form.status" class="select select-bordered select-sm">
              <option value="not_started">Not Started</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="deferred">Deferred</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
            <select v-model="form.priority" class="select select-bordered select-sm">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Start Date</span></label>
            <input v-model="form.start_date" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Due Date</span></label>
            <input v-model="form.due_date" type="date" class="input input-bordered input-sm" />
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
          {{ editingItem ? 'Save Changes' : 'Create Task' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Task" size="sm">
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
import { useTasksStore } from '../../stores/tasks'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const tasksStore = useTasksStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)
const activeFilter = ref('all')

const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'deferred', label: 'Deferred' },
]

const defaultForm = { name: '', status: 'not_started', priority: 'medium', start_date: '', due_date: '', contact_id: '', description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'name', label: 'Task Name' },
  { key: 'status', label: 'Status' },
  { key: 'priority', label: 'Priority' },
  { key: 'due_date', label: 'Due Date' },
]

const filteredTasks = computed(() => {
  if (activeFilter.value === 'all') return tasksStore.items
  return tasksStore.items.filter(t => t.status === activeFilter.value)
})

function getFilterCount(status) {
  if (status === 'all') return tasksStore.items.length
  return tasksStore.items.filter(t => t.status === status).length
}

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
      await tasksStore.update(editingItem.value.id, { ...form })
    } else {
      await tasksStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await tasksStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function isOverdue(dateStr) {
  if (!dateStr) return false
  return new Date(dateStr) < new Date() && true
}

onMounted(() => tasksStore.fetchAll())
</script>
