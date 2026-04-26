<template>
  <div class="space-y-4">
    <!-- View Toggle -->
    <div class="flex items-center justify-between">
      <div class="flex gap-1 bg-base-200 rounded-lg p-1">
        <button
          @click="viewMode = 'kanban'"
          class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all"
          :class="viewMode === 'kanban' ? 'bg-base-100 shadow text-base-content' : 'text-base-content/50 hover:text-base-content'"
        >
          <ViewColumnsIcon class="w-4 h-4" />
          Kanban
        </button>
        <button
          @click="viewMode = 'list'"
          class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all"
          :class="viewMode === 'list' ? 'bg-base-100 shadow text-base-content' : 'text-base-content/50 hover:text-base-content'"
        >
          <ListBulletIcon class="w-4 h-4" />
          List
        </button>
      </div>
      <button @click="openCreate" class="btn btn-primary btn-sm rounded-lg gap-2">
        <PlusIcon class="w-4 h-4" />
        Add Opportunity
      </button>
    </div>

    <!-- Kanban View -->
    <div v-if="viewMode === 'kanban'">
      <KanbanBoard
        :items="oppsStore.items"
        stage-field="stage"
        @edit="openEdit"
        @stage-change="handleStageChange"
      />
    </div>

    <!-- List View -->
    <div v-else>
      <DataTable
        :columns="columns"
        :data="oppsStore.items"
        @edit="openEdit"
        @delete="confirmDelete"
      >
        <template #cell-name="{ value }">
          <span class="font-medium text-base-content">{{ value }}</span>
        </template>
        <template #cell-stage="{ value }">
          <StatusBadge :status="value" type="opportunity" />
        </template>
        <template #cell-amount="{ value }">
          <span class="font-semibold text-success">${{ Number(value || 0).toLocaleString() }}</span>
        </template>
        <template #cell-probability="{ value }">
          <div class="flex items-center gap-2">
            <div class="w-16 bg-base-300 rounded-full h-1.5">
              <div class="h-1.5 rounded-full bg-primary" :style="{ width: value + '%' }"></div>
            </div>
            <span class="text-xs text-base-content">{{ value }}%</span>
          </div>
        </template>
        <template #cell-close_date="{ value }">
          <span class="text-xs text-base-content/50">{{ formatDate(value) }}</span>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Opportunity' : 'Add Opportunity'" size="lg">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Opportunity Name *</span></label>
            <input v-model="form.name" type="text" class="input input-bordered input-sm" required placeholder="e.g. Acme Enterprise Deal" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Stage *</span></label>
            <select v-model="form.stage" class="select select-bordered select-sm" required>
              <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Amount ($)</span></label>
            <input v-model="form.amount" type="number" min="0" step="0.01" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Probability (%)</span></label>
            <input v-model="form.probability" type="number" min="0" max="100" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Close Date</span></label>
            <input v-model="form.close_date" type="date" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Account</span></label>
            <select v-model="form.account_id" class="select select-bordered select-sm">
              <option value="">Select Account</option>
              <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Lead Source</span></label>
            <select v-model="form.lead_source" class="select select-bordered select-sm">
              <option value="">Select Source</option>
              <option v-for="src in sources" :key="src" :value="src">{{ src }}</option>
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
          {{ editingItem ? 'Save Changes' : 'Create Opportunity' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Opportunity" size="sm">
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
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useAccountsStore } from '../../stores/accounts'
import DataTable from '../../components/common/DataTable.vue'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import KanbanBoard from '../../components/common/KanbanBoard.vue'
import { PlusIcon, TrashIcon, ViewColumnsIcon, ListBulletIcon } from '@heroicons/vue/24/outline'

const oppsStore = useOpportunitiesStore()
const accountsStore = useAccountsStore()

const viewMode = ref('kanban')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)
const submitting = ref(false)

const stages = [
  { value: 'prospecting', label: 'Prospecting' },
  { value: 'qualification', label: 'Qualification' },
  { value: 'proposal', label: 'Proposal' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'closed_won', label: 'Closed Won' },
  { value: 'closed_lost', label: 'Closed Lost' },
]

const sources = ['Web', 'Referral', 'Cold Call', 'Email', 'Social Media', 'Trade Show', 'Other']

const defaultForm = { name: '', stage: 'prospecting', amount: '', probability: 0, close_date: '', account_id: '', contact_id: '', lead_source: '', description: '' }
const form = reactive({ ...defaultForm })

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'stage', label: 'Stage' },
  { key: 'amount', label: 'Amount' },
  { key: 'probability', label: 'Probability' },
  { key: 'close_date', label: 'Close Date' },
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

async function handleStageChange({ item, newStage }) {
  await oppsStore.update(item.id, { ...item, stage: newStage })
}

async function handleSubmit() {
  if (!form.name) return
  submitting.value = true
  try {
    if (editingItem.value) {
      await oppsStore.update(editingItem.value.id, { ...form })
    } else {
      await oppsStore.create({ ...form })
    }
    showModal.value = false
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (deletingItem.value) {
    await oppsStore.remove(deletingItem.value.id)
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([oppsStore.fetchAll(), accountsStore.fetchAll()])
})
</script>
