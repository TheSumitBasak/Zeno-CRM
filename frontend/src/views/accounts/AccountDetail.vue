<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!account" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Account not found.</p>
    <RouterLink to="/accounts" class="btn btn-ghost btn-sm">← Back to Accounts</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/accounts" class="text-primary">Accounts</RouterLink></li>
        <li class="text-base-content/60">{{ account.name }}</li>
      </ul>
    </div>

    <!-- Hero -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
              <BuildingOffice2Icon class="w-7 h-7 text-primary" />
            </div>
            <div>
              <h1 class="text-xl font-bold">{{ account.name }}</h1>
              <div class="flex items-center gap-2 mt-1 flex-wrap">
                <span v-if="account.industry" class="badge badge-outline badge-sm">{{ account.industry }}</span>
                <span v-if="account.type" class="badge badge-ghost badge-sm">{{ account.type }}</span>
                <a v-if="account.website" :href="account.website" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1">
                  <GlobeAltIcon class="w-3.5 h-3.5" />{{ account.website }}
                </a>
              </div>
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
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Contact Info</h3>
          <div class="space-y-2">
            <div v-if="account.email" class="flex items-center gap-2">
              <EnvelopeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <a :href="'mailto:' + account.email" class="text-sm text-primary hover:underline">{{ account.email }}</a>
            </div>
            <div v-if="account.phone" class="flex items-center gap-2">
              <PhoneIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <span class="text-sm">{{ account.phone }}</span>
            </div>
            <div v-if="account.billing_address" class="flex items-start gap-2">
              <MapPinIcon class="w-4 h-4 text-base-content/40 flex-shrink-0 mt-0.5" />
              <span class="text-sm text-base-content/70">{{ account.billing_address }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Details</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-base-content/50">Industry</span>
              <span>{{ account.industry || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Type</span>
              <span>{{ account.type || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Created</span>
              <span>{{ formatDate(account.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="account.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ account.description }}</p>
      </div>
    </div>

    <!-- Contacts & Opportunities Tabs -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-0">
        <div class="flex items-center border-b border-base-200 px-4">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
            :class="activeTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content'"
          >
            {{ tab.label }}
            <span class="badge badge-sm ml-1" :class="activeTab === tab.key ? 'badge-primary' : 'badge-ghost'">
              {{ tab.key === 'contacts' ? relatedContacts.length : relatedOpportunities.length }}
            </span>
          </button>
        </div>

        <!-- Contacts -->
        <div v-if="activeTab === 'contacts'" class="p-4">
          <div v-if="relatedContacts.length === 0" class="text-center py-10">
            <UsersIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No contacts linked to this account.</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div
              v-for="contact in relatedContacts"
              :key="contact.id"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200 transition-colors cursor-pointer"
              @click="$router.push('/contacts/' + contact.id)"
            >
              <div class="avatar placeholder">
                <div class="w-9 h-9 rounded-full bg-secondary/20">
                  <span class="text-secondary text-sm font-bold">{{ contact.first_name?.[0] }}{{ contact.last_name?.[0] }}</span>
                </div>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ contact.first_name }} {{ contact.last_name }}</p>
                <p v-if="contact.title" class="text-xs text-base-content/50 truncate">{{ contact.title }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Opportunities -->
        <div v-if="activeTab === 'opportunities'" class="p-4 space-y-2">
          <div v-if="relatedOpportunities.length === 0" class="text-center py-10">
            <BriefcaseIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No opportunities for this account.</p>
          </div>
          <div
            v-for="opp in relatedOpportunities"
            :key="opp.id"
            class="flex items-center justify-between p-3 rounded-lg hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/opportunities/' + opp.id)"
          >
            <div>
              <p class="text-sm font-medium">{{ opp.name }}</p>
              <p class="text-xs text-base-content/50">Close: {{ formatDate(opp.close_date) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-success">${{ Number(opp.amount || 0).toLocaleString() }}</span>
              <StatusBadge :status="opp.stage" type="opportunity" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Account" size="lg">
      <form class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Account Name *</span></label>
            <input v-model="editForm.name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.name && editErrors.name }" @blur="editBlur('name')" />
            <p v-if="editTouched.name && editErrors.name" class="text-error text-xs mt-1">{{ editErrors.name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Email *</span></label>
            <input v-model="editForm.email" type="email" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.email && editErrors.email }" @blur="editBlur('email')" />
            <p v-if="editTouched.email && editErrors.email" class="text-error text-xs mt-1">{{ editErrors.email }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Phone *</span></label>
            <input v-model="editForm.phone" type="tel" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.phone && editErrors.phone }" @blur="editBlur('phone')" />
            <p v-if="editTouched.phone && editErrors.phone" class="text-error text-xs mt-1">{{ editErrors.phone }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Industry</span></label>
            <input v-model="editForm.industry" type="text" class="input input-bordered input-sm" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Type</span></label>
            <select v-model="editForm.type" class="select select-bordered select-sm">
              <option value="">Select Type</option>
              <option value="Customer">Customer</option>
              <option value="Partner">Partner</option>
              <option value="Prospect">Prospect</option>
              <option value="Competitor">Competitor</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Website</span></label>
            <input v-model="editForm.website" type="url" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.website && editErrors.website }" @blur="editBlur('website')" />
            <p v-if="editTouched.website && editErrors.website" class="text-error text-xs mt-1">{{ editErrors.website }}</p>
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Billing Address</span></label>
            <input v-model="editForm.billing_address" type="text" class="input input-bordered input-sm" />
          </div>
          <div class="form-control sm:col-span-2">
            <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
            <textarea v-model="editForm.description" class="textarea textarea-bordered textarea-sm" rows="3"></textarea>
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
    <Modal v-model="showDeleteModal" title="Delete Account" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ account.name }}"?</p>
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
import { useAccountsStore } from '../../stores/accounts'
import { useContactsStore } from '../../stores/contacts'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useForm, required, email, url } from '../../composables/useForm'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import {
  PencilIcon, TrashIcon, BuildingOffice2Icon, GlobeAltIcon,
  EnvelopeIcon, PhoneIcon, MapPinIcon, UsersIcon, BriefcaseIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const oppsStore = useOpportunitiesStore()

const loading = ref(true)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const activeTab = ref('contacts')

const tabs = [
  { key: 'contacts', label: 'Contacts' },
  { key: 'opportunities', label: 'Opportunities' },
]

const account = computed(() => accountsStore.items.find(a => a.id === Number(route.params.id)))
const relatedContacts = computed(() =>
  contactsStore.items.filter(c => Number(c.account_id) === Number(route.params.id))
)
const relatedOpportunities = computed(() =>
  oppsStore.items.filter(o => Number(o.account_id) === Number(route.params.id))
)

const editInitial = { name: '', email: '', phone: '', industry: '', type: '', website: '', billing_address: '', description: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  name: required('Account name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  website: url(),
})

function openEdit() {
  editReset({ ...editInitial, ...account.value })
  showEditModal.value = true
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await accountsStore.update(account.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handleDelete() {
  await accountsStore.remove(account.value.id)
  router.push('/accounts')
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([accountsStore.fetchAll(), contactsStore.fetchAll(), oppsStore.fetchAll()])
  loading.value = false
})
</script>
