<template>
  <span class="badge badge-sm font-medium" :class="badgeClass">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: String,
  type: {
    type: String,
    default: 'lead',
  },
})

const leadStatusMap = {
  new: { class: 'badge-info', label: 'New' },
  in_process: { class: 'badge-warning', label: 'In Process' },
  assigned: { class: 'badge-primary', label: 'Assigned' },
  recycled: { class: 'badge-ghost', label: 'Recycled' },
  converted: { class: 'badge-success', label: 'Converted' },
  dead: { class: 'badge-error', label: 'Dead' },
}

const opportunityStageMap = {
  prospecting: { class: 'badge-ghost', label: 'Prospecting' },
  qualification: { class: 'badge-info', label: 'Qualification' },
  proposal: { class: 'badge-warning', label: 'Proposal' },
  negotiation: { class: 'badge-primary', label: 'Negotiation' },
  closed_won: { class: 'badge-success', label: 'Closed Won' },
  closed_lost: { class: 'badge-error', label: 'Closed Lost' },
}

const meetingStatusMap = {
  planned: { class: 'badge-info', label: 'Planned' },
  held: { class: 'badge-success', label: 'Held' },
  not_held: { class: 'badge-error', label: 'Not Held' },
}

const taskStatusMap = {
  not_started: { class: 'badge-ghost', label: 'Not Started' },
  in_progress: { class: 'badge-warning', label: 'In Progress' },
  completed: { class: 'badge-success', label: 'Completed' },
  deferred: { class: 'badge-error', label: 'Deferred' },
}

const taskPriorityMap = {
  low: { class: 'badge-ghost', label: 'Low' },
  medium: { class: 'badge-info', label: 'Medium' },
  high: { class: 'badge-warning', label: 'High' },
  urgent: { class: 'badge-error', label: 'Urgent' },
}

const userRoleMap = {
  admin: { class: 'badge-primary', label: 'Admin' },
  user: { class: 'badge-ghost', label: 'User' },
}

const activeMap = {
  true: { class: 'badge-success', label: 'Active' },
  false: { class: 'badge-error', label: 'Inactive' },
}

const maps = {
  lead: leadStatusMap,
  opportunity: opportunityStageMap,
  meeting: meetingStatusMap,
  task: taskStatusMap,
  priority: taskPriorityMap,
  role: userRoleMap,
  active: activeMap,
}

const currentMap = computed(() => maps[props.type] || leadStatusMap)
const statusData = computed(() => currentMap.value[String(props.status)] || { class: 'badge-ghost', label: props.status || 'Unknown' })
const badgeClass = computed(() => statusData.value.class)
const label = computed(() => statusData.value.label)
</script>
