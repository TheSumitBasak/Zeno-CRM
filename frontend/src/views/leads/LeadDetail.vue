<template>
  <div v-if="loading" class="flex justify-center items-center py-20">
    <span class="loading loading-spinner loading-lg text-primary"></span>
  </div>

  <div v-else-if="!lead" class="text-center py-20">
    <p class="text-base-content/40 mb-4">Lead not found.</p>
    <RouterLink to="/leads" class="btn btn-ghost btn-sm">← Back to Leads</RouterLink>
  </div>

  <div v-else class="space-y-5">
    <!-- Breadcrumb -->
    <div class="text-sm breadcrumbs">
      <ul>
        <li><RouterLink to="/leads" class="text-primary">Leads</RouterLink></li>
        <li class="text-base-content/60">{{ lead.first_name }} {{ lead.last_name }}</li>
      </ul>
    </div>

    <!-- Hero Card -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="avatar placeholder">
              <div class="w-16 h-16 rounded-full bg-warning/20">
                <span class="text-warning text-xl font-bold">{{ lead.first_name?.[0] }}{{ lead.last_name?.[0] }}</span>
              </div>
            </div>
            <div>
              <h1 class="text-xl font-bold text-base-content">{{ lead.first_name }} {{ lead.last_name }}</h1>
              <p class="text-base-content/50 text-sm">{{ [lead.title, lead.company].filter(Boolean).join(' at ') }}</p>
              <div class="flex items-center gap-2 mt-2 flex-wrap">
                <StatusBadge :status="lead.status" type="lead" />
                <span v-if="lead.source" class="badge badge-outline badge-sm">{{ lead.source }}</span>
                <span class="text-xs text-base-content/40">Created {{ formatDate(lead.created_at) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button
              v-if="lead.status !== 'converted' && lead.status !== 'dead'"
              @click="openPromote"
              class="btn btn-warning btn-sm gap-2"
            >
              <ArrowUpCircleIcon class="w-4 h-4" />
              Promote to Opportunity
            </button>
            <button
              v-if="lead.status !== 'dead' && !lead.converted_support_id"
              @click="openPromoteSupport"
              class="btn btn-info btn-sm gap-2"
            >
              <LifebuoyIcon class="w-4 h-4" />
              Promote to Support
            </button>
            <RouterLink
              v-if="lead.converted_support_id"
              :to="'/support/' + lead.converted_support_id"
              class="btn btn-info btn-outline btn-sm gap-1"
            >
              <LifebuoyIcon class="w-4 h-4" />
              View Ticket
            </RouterLink>
            <button @click="openEdit" class="btn btn-outline btn-sm gap-1">
              <PencilIcon class="w-4 h-4" />
              Edit
            </button>
            <button @click="showDeleteModal = true" class="btn btn-ghost btn-sm text-error">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Converted banner -->
    <div v-if="lead.status === 'converted'" class="alert alert-success">
      <CheckCircleIcon class="w-5 h-5" />
      <div>
        <p class="font-semibold">Lead Converted</p>
        <p class="text-sm opacity-80">This lead was promoted on {{ formatDate(lead.converted_at) }}</p>
      </div>
      <RouterLink v-if="lead.converted_opportunity_id" :to="'/opportunities/' + lead.converted_opportunity_id" class="btn btn-success btn-sm">
        View Opportunity →
      </RouterLink>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Contact Info</h3>
          <div class="space-y-2">
            <div v-if="lead.email" class="flex items-center gap-2">
              <EnvelopeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <a :href="'mailto:' + lead.email" class="text-sm text-primary hover:underline truncate">{{ lead.email }}</a>
            </div>
            <div v-if="lead.phone" class="flex items-center gap-2">
              <PhoneIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <span class="text-sm">{{ lead.phone }}</span>
            </div>
            <p v-if="!lead.email && !lead.phone" class="text-sm text-base-content/30">No contact info</p>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Company</h3>
          <div class="space-y-1">
            <div v-if="lead.company" class="flex items-center gap-2">
              <BuildingOfficeIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
              <span class="text-sm font-medium">{{ lead.company }}</span>
            </div>
            <p v-if="lead.title" class="text-sm text-base-content/60 pl-6">{{ lead.title }}</p>
            <p v-if="!lead.company" class="text-sm text-base-content/30">No company info</p>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 space-y-3">
          <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Lead Details</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-base-content/50">Status</span>
              <StatusBadge :status="lead.status" type="lead" />
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Source</span>
              <span>{{ lead.source || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-base-content/50">Assigned To</span>
              <span>{{ getAssigneeName(lead.assigned_to) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div v-if="lead.description" class="card bg-base-100 shadow-sm border border-base-200">
      <div class="card-body p-4">
        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">Description</h3>
        <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ lead.description }}</p>
      </div>
    </div>

    <!-- Tasks & Meetings -->
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
              {{ tab.key === 'tasks' ? relatedTasks.length : relatedMeetings.length }}
            </span>
          </button>
          <div class="ml-auto py-2 flex gap-2">
            <button v-if="activeTab === 'tasks'" @click="openNewTask" class="btn btn-ghost btn-xs gap-1">
              <PlusIcon class="w-3.5 h-3.5" />Add Task
            </button>
            <button v-if="activeTab === 'meetings'" @click="openNewMeeting" class="btn btn-ghost btn-xs gap-1">
              <PlusIcon class="w-3.5 h-3.5" />Schedule
            </button>
          </div>
        </div>

        <!-- Tasks Panel -->
        <div v-if="activeTab === 'tasks'" class="p-4 space-y-2">
          <div v-if="relatedTasks.length === 0" class="text-center py-10">
            <ClipboardDocumentListIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No tasks linked to this lead.</p>
            <button @click="openNewTask" class="btn btn-ghost btn-xs mt-2">Add a task</button>
          </div>
          <div
            v-for="task in relatedTasks"
            :key="task.id"
            class="flex items-center gap-3 p-3 rounded-lg bg-base-200/40 hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/tasks/' + task.id)"
          >
            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-0.5" :class="priorityDot(task.priority)"></div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ task.name }}</p>
              <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                <StatusBadge :status="task.status" type="task" />
                <StatusBadge :status="task.priority" type="priority" />
                <span v-if="task.due_date" class="text-xs" :class="isOverdue(task.due_date) ? 'text-error font-medium' : 'text-base-content/40'">
                  Due {{ formatDate(task.due_date) }}{{ isOverdue(task.due_date) ? ' · Overdue' : '' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Meetings Panel -->
        <div v-if="activeTab === 'meetings'" class="p-4 space-y-2">
          <div v-if="relatedMeetings.length === 0" class="text-center py-10">
            <CalendarDaysIcon class="w-10 h-10 text-base-content/20 mx-auto mb-2" />
            <p class="text-sm text-base-content/40">No meetings linked to this lead.</p>
            <button @click="openNewMeeting" class="btn btn-ghost btn-xs mt-2">Schedule a meeting</button>
          </div>
          <div
            v-for="meeting in relatedMeetings"
            :key="meeting.id"
            class="flex items-center gap-3 p-3 rounded-lg bg-base-200/40 hover:bg-base-200 transition-colors cursor-pointer"
            @click="$router.push('/meetings/' + meeting.id)"
          >
            <CalendarDaysIcon class="w-4 h-4 text-base-content/40 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ meeting.name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <StatusBadge :status="meeting.status" type="meeting" />
                <span v-if="meeting.start_date" class="text-xs text-base-content/40">{{ formatDateTime(meeting.start_date) }}</span>
                <span v-if="meeting.contact_ids?.length" class="text-xs text-base-content/40">{{ meeting.contact_ids.length }} attendee(s)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <Modal v-model="showEditModal" title="Edit Lead" size="lg">
      <form @submit.prevent="handleEditSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">First Name *</span></label>
            <input v-model="editForm.first_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.first_name && editErrors.first_name }" @blur="editBlur('first_name')" />
            <p v-if="editTouched.first_name && editErrors.first_name" class="text-error text-xs mt-1">{{ editErrors.first_name }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Last Name *</span></label>
            <input v-model="editForm.last_name" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.last_name && editErrors.last_name }" @blur="editBlur('last_name')" />
            <p v-if="editTouched.last_name && editErrors.last_name" class="text-error text-xs mt-1">{{ editErrors.last_name }}</p>
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
            <label class="label"><span class="label-text text-sm font-medium">Company *</span></label>
            <input v-model="editForm.company" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.company && editErrors.company }" @blur="editBlur('company')" />
            <p v-if="editTouched.company && editErrors.company" class="text-error text-xs mt-1">{{ editErrors.company }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Title *</span></label>
            <input v-model="editForm.title" type="text" class="input input-bordered input-sm" :class="{ 'input-error': editTouched.title && editErrors.title }" @blur="editBlur('title')" />
            <p v-if="editTouched.title && editErrors.title" class="text-error text-xs mt-1">{{ editErrors.title }}</p>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="editForm.status" class="select select-bordered select-sm">
              <option value="new">New</option>
              <option value="in_process">In Process</option>
              <option value="assigned">Assigned</option>
              <option value="recycled">Recycled</option>
              <option value="converted">Converted</option>
              <option value="dead">Dead</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Source</span></label>
            <select v-model="editForm.source" class="select select-bordered select-sm">
              <option value="">Select Source</option>
              <option v-for="src in sources" :key="src" :value="src">{{ src }}</option>
            </select>
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

    <!-- Promote Modal -->
    <Modal v-model="showPromoteModal" :title="`Promote Lead: ${lead.first_name} ${lead.last_name}`" size="lg">
      <div class="space-y-5">
        <div class="space-y-3">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Account</h3>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="promoteForm.account_mode" value="create" class="radio radio-sm radio-primary" />
              <span class="text-sm">Create New Account</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="promoteForm.account_mode" value="existing" class="radio radio-sm radio-primary" />
              <span class="text-sm">Link to Existing</span>
            </label>
          </div>
          <div v-if="promoteForm.account_mode === 'create'" class="form-control">
            <label class="label"><span class="label-text text-sm">Account Name</span></label>
            <input v-model="promoteForm.account_name" type="text" class="input input-bordered input-sm" :placeholder="lead.company || 'Company name'" />
          </div>
          <div v-else class="form-control">
            <label class="label"><span class="label-text text-sm">Select Account</span></label>
            <select v-model="promoteForm.account_id" class="select select-bordered select-sm">
              <option value="">Select an account</option>
              <option v-for="acc in accountsStore.items" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Contact (will be created)</h3>
          <div class="bg-base-200 rounded-lg p-3 flex items-center gap-3">
            <div class="avatar placeholder">
              <div class="w-10 h-10 rounded-full bg-warning/20">
                <span class="text-warning font-bold">{{ lead.first_name?.[0] }}{{ lead.last_name?.[0] }}</span>
              </div>
            </div>
            <div>
              <p class="font-medium">{{ lead.first_name }} {{ lead.last_name }}</p>
              <p class="text-xs text-base-content/50">{{ lead.title }}</p>
              <p v-if="lead.email" class="text-xs text-primary">{{ lead.email }}</p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-sm font-semibold border-b border-base-300 pb-1">Opportunity</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="promoteForm.create_opportunity" class="checkbox checkbox-sm checkbox-primary" />
            <span class="text-sm">Create Opportunity</span>
          </label>
          <div v-if="promoteForm.create_opportunity" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="form-control sm:col-span-2">
              <label class="label"><span class="label-text text-sm">Opportunity Name</span></label>
              <input v-model="promoteForm.opportunity_name" type="text" class="input input-bordered input-sm" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-sm">Stage</span></label>
              <select v-model="promoteForm.opportunity_stage" class="select select-bordered select-sm">
                <option value="prospecting">Prospecting</option>
                <option value="qualification">Qualification</option>
                <option value="proposal">Proposal</option>
                <option value="negotiation">Negotiation</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button @click="showPromoteModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handlePromote" class="btn btn-warning btn-sm gap-2" :disabled="promoteSubmitting">
          <span v-if="promoteSubmitting" class="loading loading-spinner loading-xs"></span>
          <ArrowUpCircleIcon v-else class="w-4 h-4" />
          Promote Lead
        </button>
      </template>
    </Modal>

    <!-- Add Task Modal -->
    <Modal v-model="showTaskModal" title="Add Task" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control sm:col-span-2">
          <label class="label"><span class="label-text text-sm font-medium">Task Name *</span></label>
          <input v-model="newTask.name" type="text" class="input input-bordered input-sm" placeholder="Task description..." />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
          <select v-model="newTask.status" class="select select-bordered select-sm">
            <option value="not_started">Not Started</option>
            <option value="in_progress">In Progress</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
          <select v-model="newTask.priority" class="select select-bordered select-sm">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Due Date</span></label>
          <input v-model="newTask.due_date" type="date" class="input input-bordered input-sm" />
        </div>
      </div>
      <template #footer>
        <button @click="showTaskModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="saveNewTask" class="btn btn-primary btn-sm" :disabled="!newTask.name.trim()">Add Task</button>
      </template>
    </Modal>

    <!-- Add Meeting Modal -->
    <Modal v-model="showMeetingModal" title="Schedule Meeting" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control sm:col-span-2">
          <label class="label"><span class="label-text text-sm font-medium">Meeting Name *</span></label>
          <input v-model="newMeeting.name" type="text" class="input input-bordered input-sm" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Start Date & Time</span></label>
          <input v-model="newMeeting.start_date" type="datetime-local" class="input input-bordered input-sm" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Duration (hours)</span></label>
          <input v-model="newMeeting.duration_hours" type="number" min="0" max="24" class="input input-bordered input-sm" />
        </div>
      </div>
      <template #footer>
        <button @click="showMeetingModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="saveNewMeeting" class="btn btn-primary btn-sm" :disabled="!newMeeting.name.trim()">Schedule</button>
      </template>
    </Modal>

    <!-- Promote to Support Modal -->
    <Modal v-model="showSupportModal" :title="`Raise Support Ticket: ${lead.first_name} ${lead.last_name}`" size="md">
      <div class="space-y-4">
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Subject *</span></label>
          <input v-model="supportForm.subject" type="text" class="input input-bordered input-sm" :placeholder="(lead.company || lead.first_name) + ' - Support Request'" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Priority</span></label>
            <select v-model="supportForm.priority" class="select select-bordered select-sm">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text text-sm font-medium">Status</span></label>
            <select v-model="supportForm.status" class="select select-bordered select-sm">
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text text-sm font-medium">Description</span></label>
          <textarea v-model="supportForm.description" class="textarea textarea-bordered textarea-sm" rows="3" placeholder="Describe the support issue..."></textarea>
        </div>
      </div>
      <template #footer>
        <button @click="showSupportModal = false" class="btn btn-ghost btn-sm">Cancel</button>
        <button @click="handlePromoteSupport" class="btn btn-info btn-sm gap-2" :disabled="supportSubmitting">
          <span v-if="supportSubmitting" class="loading loading-spinner loading-xs"></span>
          <LifebuoyIcon v-else class="w-4 h-4" />
          Create Ticket
        </button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal v-model="showDeleteModal" title="Delete Lead" size="sm">
      <div class="text-center py-2">
        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto mb-3">
          <TrashIcon class="w-6 h-6 text-error" />
        </div>
        <p class="font-medium">Delete "{{ lead.first_name }} {{ lead.last_name }}"?</p>
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
import { useRoute, useRouter } from 'vue-router'
import { useLeadsStore } from '../../stores/leads'
import { useTasksStore } from '../../stores/tasks'
import { useMeetingsStore } from '../../stores/meetings'
import { useAccountsStore } from '../../stores/accounts'
import { useOpportunitiesStore } from '../../stores/opportunities'
import { useSupportStore } from '../../stores/support'
import { useUsersStore } from '../../stores/users'
import Modal from '../../components/common/Modal.vue'
import StatusBadge from '../../components/common/StatusBadge.vue'
import { useForm, required, email } from '../../composables/useForm'
import {
  ArrowUpCircleIcon, PencilIcon, TrashIcon, PlusIcon, CheckCircleIcon,
  EnvelopeIcon, PhoneIcon, BuildingOfficeIcon, LifebuoyIcon,
  ClipboardDocumentListIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const leadsStore = useLeadsStore()
const tasksStore = useTasksStore()
const meetingsStore = useMeetingsStore()
const accountsStore = useAccountsStore()
const oppsStore = useOpportunitiesStore()
const supportStore = useSupportStore()
const usersStore = useUsersStore()

const loading = ref(true)
const showEditModal = ref(false)
const showPromoteModal = ref(false)
const showSupportModal = ref(false)
const showTaskModal = ref(false)
const showMeetingModal = ref(false)
const showDeleteModal = ref(false)
const editSubmitting = ref(false)
const promoteSubmitting = ref(false)
const supportSubmitting = ref(false)
const activeTab = ref('tasks')

const tabs = [
  { key: 'tasks', label: 'Tasks' },
  { key: 'meetings', label: 'Meetings' },
]

const sources = ['Web', 'Referral', 'Cold Call', 'Email', 'Social Media', 'Trade Show', 'Other']

const lead = computed(() => leadsStore.items.find(l => l.id === Number(route.params.id)))

const relatedTasks = computed(() =>
  tasksStore.items.filter(t => t.parent_type === 'Lead' && Number(t.parent_id) === Number(route.params.id))
)
const relatedMeetings = computed(() =>
  meetingsStore.items.filter(m => m.parent_type === 'Lead' && Number(m.parent_id) === Number(route.params.id))
)

const editInitial = { first_name: '', last_name: '', email: '', phone: '', company: '', title: '', status: 'new', source: '', description: '' }
const { values: editForm, errors: editErrors, touched: editTouched, validate: editValidate, handleBlur: editBlur, reset: editReset } = useForm(editInitial, {
  first_name: required('First name'),
  last_name: required('Last name'),
  email: v => required('Email')(v) || email()(v),
  phone: required('Phone'),
  company: required('Company'),
  title: required('Title'),
})
const promoteForm = reactive({ account_mode: 'create', account_name: '', account_id: '', create_opportunity: true, opportunity_name: '', opportunity_stage: 'prospecting' })
const supportForm = reactive({ subject: '', priority: 'medium', status: 'open', description: '' })
const newTask = reactive({ name: '', status: 'not_started', priority: 'medium', due_date: '' })
const newMeeting = reactive({ name: '', start_date: '', duration_hours: 1 })

function openEdit() {
  editReset({ ...editInitial, ...lead.value })
  showEditModal.value = true
}

function openPromote() {
  Object.assign(promoteForm, {
    account_mode: 'create',
    account_name: lead.value.company || '',
    account_id: '',
    create_opportunity: true,
    opportunity_name: (lead.value.company || 'Deal') + ' - Deal',
    opportunity_stage: 'prospecting',
  })
  showPromoteModal.value = true
}

function openPromoteSupport() {
  Object.assign(supportForm, {
    subject: (lead.value.company || lead.value.first_name) + ' - Support Request',
    priority: 'medium',
    status: 'open',
    description: lead.value.description || '',
  })
  showSupportModal.value = true
}

function openNewTask() {
  Object.assign(newTask, { name: '', status: 'not_started', priority: 'medium', due_date: '' })
  showTaskModal.value = true
}

function openNewMeeting() {
  Object.assign(newMeeting, { name: '', start_date: '', duration_hours: 1 })
  showMeetingModal.value = true
}

async function handleEditSubmit() {
  if (!editValidate()) return
  editSubmitting.value = true
  try {
    await leadsStore.update(lead.value.id, { ...editForm })
    showEditModal.value = false
  } finally {
    editSubmitting.value = false
  }
}

async function handlePromote() {
  promoteSubmitting.value = true
  try {
    const payload = {
      create_account: promoteForm.account_mode === 'create',
      account_name: promoteForm.account_name || lead.value.company,
      account_id: promoteForm.account_mode === 'existing' ? promoteForm.account_id : null,
      create_opportunity: promoteForm.create_opportunity,
      opportunity_name: promoteForm.opportunity_name,
      opportunity_stage: promoteForm.opportunity_stage,
    }
    await leadsStore.convert(lead.value.id, payload)
    showPromoteModal.value = false
    await Promise.all([accountsStore.fetchAll(), oppsStore.fetchAll()])
  } finally {
    promoteSubmitting.value = false
  }
}

async function handlePromoteSupport() {
  supportSubmitting.value = true
  try {
    await supportStore.promoteFromLead(lead.value.id, { ...supportForm })
    // Refresh lead to get converted_support_id
    await leadsStore.fetchAll()
    showSupportModal.value = false
  } finally {
    supportSubmitting.value = false
  }
}

async function saveNewTask() {
  if (!newTask.name.trim()) return
  await tasksStore.create({
    ...newTask,
    parent_type: 'Lead',
    parent_id: lead.value.id,
  })
  showTaskModal.value = false
}

async function saveNewMeeting() {
  if (!newMeeting.name.trim()) return
  await meetingsStore.create({
    name: newMeeting.name,
    status: 'planned',
    parent_type: 'Lead',
    parent_id: lead.value.id,
    start_date: newMeeting.start_date || null,
    duration_hours: newMeeting.duration_hours,
    duration_minutes: 0,
  })
  showMeetingModal.value = false
}

async function handleDelete() {
  await leadsStore.remove(lead.value.id)
  router.push('/leads')
}

function getAssigneeName(id) {
  if (!id) return '—'
  const u = usersStore.items.find(u => u.id === Number(id))
  return u?.name || `#${id}`
}

function priorityDot(p) {
  return { low: 'bg-info', medium: 'bg-warning', high: 'bg-orange-400', urgent: 'bg-error' }[p] || 'bg-base-300'
}

function isOverdue(d) {
  return d && new Date(d) < new Date()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  await Promise.all([
    leadsStore.fetchAll(),
    tasksStore.fetchAll(),
    meetingsStore.fetchAll(),
    accountsStore.fetchAll(),
    oppsStore.fetchAll(),
    supportStore.fetchAll(),
    usersStore.fetchAll(),
  ])
  loading.value = false
})
</script>
