<template>
  <div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-primary text-primary-content rounded-2xl p-6 shadow-lg">
      <div class="flex items-start justify-between">
        <div>
          <h2 class="text-xl font-bold mb-1">Good {{ timeOfDay }}, {{ authStore.user?.name?.split(' ')[0] }}!</h2>
          <p class="text-primary-content/80 text-sm">Here's what's happening with your business today.</p>
        </div>
        <div class="text-right text-primary-content/80 text-sm">
          <p class="font-medium">{{ currentDate }}</p>
        </div>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard
        label="Total Accounts"
        :value="stats.accounts"
        :icon="BuildingOfficeIcon"
        color="blue"
        :trend="12"
      />
      <StatCard
        label="Total Contacts"
        :value="stats.contacts"
        :icon="UsersIcon"
        color="purple"
        :trend="8"
      />
      <StatCard
        label="Active Leads"
        :value="stats.leads"
        :icon="UserPlusIcon"
        color="orange"
        :trend="-3"
      />
      <StatCard
        label="Open Opportunities"
        :value="stats.opportunities"
        :icon="CurrencyDollarIcon"
        color="green"
        :trend="15"
      />
    </div>

    <!-- Main Content Row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <!-- Recent Leads (2/3 width) -->
      <div class="xl:col-span-2 bg-base-100 rounded-2xl shadow-sm border border-base-300">
        <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
          <div>
            <h3 class="font-semibold text-base-content">Recent Leads</h3>
            <p class="text-xs text-base-content/50 mt-0.5">Latest incoming leads</p>
          </div>
          <router-link to="/leads" class="btn btn-ghost btn-xs text-primary">
            View all
            <ChevronRightIcon class="w-3 h-3" />
          </router-link>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-sm w-full">
            <thead>
              <tr class="bg-base-200/50 text-xs text-base-content/50 uppercase">
                <th>Name</th>
                <th>Company</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="lead in recentLeads"
                :key="lead.id"
                class="hover:bg-base-200/50 text-sm"
              >
                <td>
                  <div class="flex items-center gap-2">
                    <div class="avatar placeholder">
                      <div class="w-7 h-7 rounded-full bg-primary">
                        <span class="text-primary-content text-xs">{{ lead.first_name?.[0] }}{{ lead.last_name?.[0] }}</span>
                      </div>
                    </div>
                    <span class="font-medium text-base-content">{{ lead.first_name }} {{ lead.last_name }}</span>
                  </div>
                </td>
                <td class="text-base-content">{{ lead.company }}</td>
                <td><StatusBadge :status="lead.status" type="lead" /></td>
                <td class="text-base-content/60 text-xs">{{ formatDate(lead.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Column -->
      <div class="space-y-4">
        <!-- Opportunities by Stage Chart -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-5">
          <h3 class="font-semibold text-base-content mb-1">Pipeline Overview</h3>
          <p class="text-xs text-base-content/50 mb-4">Opportunities by stage</p>
          <div class="flex justify-center" style="height: 200px">
            <Doughnut v-if="chartLoaded" :data="chartData" :options="chartOptions" />
          </div>
          <!-- Legend -->
          <div class="grid grid-cols-2 gap-1 mt-3">
            <div
              v-for="(item, i) in chartLegend"
              :key="item.label"
              class="flex items-center gap-2 text-xs text-base-content"
            >
              <div class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: chartColors[i] }"></div>
              <span class="truncate">{{ item.label }}: {{ item.count }}</span>
            </div>
          </div>
        </div>

        <!-- My Upcoming Tasks -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300">
          <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
            <h3 class="font-semibold text-base-content">My Tasks</h3>
            <router-link to="/tasks" class="btn btn-ghost btn-xs text-primary">
              View all
            </router-link>
          </div>
          <div class="p-3 space-y-2">
            <div
              v-for="task in upcomingTasks"
              :key="task.id"
              class="flex items-start gap-3 p-2 rounded-lg hover:bg-base-200 transition-colors"
            >
              <div
                class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
                :class="priorityDot(task.priority)"
              ></div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-base-content truncate">{{ task.name }}</p>
                <p class="text-xs text-base-content/60">Due {{ formatDate(task.due_date) }}</p>
              </div>
              <StatusBadge :status="task.priority" type="priority" />
            </div>
            <div v-if="upcomingTasks.length === 0" class="text-center py-4 text-base-content/60 text-sm">
              No upcoming tasks
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Opportunities Value -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div
        v-for="stage in topStages"
        :key="stage.name"
        class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-5"
      >
        <div class="flex items-center justify-between mb-3">
          <span class="text-sm text-base-content/50">{{ stage.name }}</span>
          <span class="badge badge-sm" :class="stage.badge">{{ stage.count }}</span>
        </div>
        <p class="text-2xl font-bold text-base-content">${{ Number(stage.value).toLocaleString() }}</p>
        <p class="text-xs text-base-content/60 mt-1">Total pipeline value</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { useAuthStore } from '../stores/auth'
import { useLeadsStore } from '../stores/leads'
import { useAccountsStore } from '../stores/accounts'
import { useContactsStore } from '../stores/contacts'
import { useOpportunitiesStore } from '../stores/opportunities'
import { useTasksStore } from '../stores/tasks'
import StatCard from '../components/common/StatCard.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import {
  BuildingOfficeIcon,
  UsersIcon,
  UserPlusIcon,
  CurrencyDollarIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

ChartJS.register(ArcElement, Tooltip, Legend)

const authStore = useAuthStore()
const leadsStore = useLeadsStore()
const accountsStore = useAccountsStore()
const contactsStore = useContactsStore()
const oppsStore = useOpportunitiesStore()
const tasksStore = useTasksStore()

const chartLoaded = ref(false)

const timeOfDay = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
})

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

const stats = computed(() => ({
  accounts: accountsStore.items.length,
  contacts: contactsStore.items.length,
  leads: leadsStore.items.filter(l => !['converted', 'dead'].includes(l.status)).length,
  opportunities: oppsStore.items.filter(o => !['closed_won', 'closed_lost'].includes(o.stage)).length,
}))

const recentLeads = computed(() => [...leadsStore.items].slice(0, 5))
const upcomingTasks = computed(() =>
  [...tasksStore.items]
    .filter(t => t.status !== 'completed')
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
    .slice(0, 4)
)

const chartColors = ['#64748b', '#3b82f6', '#f59e0b', '#8b5cf6', '#10b981']

const chartLegend = computed(() => {
  const stages = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won']
  return stages.map(s => ({
    label: s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
    count: oppsStore.items.filter(o => o.stage === s).length,
  }))
})

const chartData = computed(() => ({
  labels: chartLegend.value.map(i => i.label),
  datasets: [{
    data: chartLegend.value.map(i => i.count),
    backgroundColor: chartColors,
    borderWidth: 0,
    hoverOffset: 4,
  }],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.raw}` } },
  },
  cutout: '65%',
}

const topStages = computed(() => {
  const opps = oppsStore.items
  return [
    {
      name: 'In Negotiation',
      count: opps.filter(o => o.stage === 'negotiation').length,
      value: opps.filter(o => o.stage === 'negotiation').reduce((s, o) => s + Number(o.amount || 0), 0),
      badge: 'badge-primary',
    },
    {
      name: 'In Proposal',
      count: opps.filter(o => o.stage === 'proposal').length,
      value: opps.filter(o => o.stage === 'proposal').reduce((s, o) => s + Number(o.amount || 0), 0),
      badge: 'badge-warning',
    },
    {
      name: 'Closed Won',
      count: opps.filter(o => o.stage === 'closed_won').length,
      value: opps.filter(o => o.stage === 'closed_won').reduce((s, o) => s + Number(o.amount || 0), 0),
      badge: 'badge-success',
    },
  ]
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function priorityDot(priority) {
  return {
    low: 'bg-neutral',
    medium: 'bg-primary',
    high: 'bg-warning',
    urgent: 'bg-error',
  }[priority] || 'bg-neutral'
}

onMounted(async () => {
  await Promise.all([
    accountsStore.fetchAll(),
    contactsStore.fetchAll(),
    leadsStore.fetchAll(),
    oppsStore.fetchAll(),
    tasksStore.fetchAll(),
  ])
  chartLoaded.value = true
})
</script>
