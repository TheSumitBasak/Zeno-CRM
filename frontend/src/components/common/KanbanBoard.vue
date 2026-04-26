<template>
  <div class="flex gap-4 overflow-x-auto pb-4 items-stretch">
    <div
      v-for="column in columns"
      :key="column.key"
      class="flex-shrink-0 w-72 flex flex-col"
    >
      <!-- Column Header -->
      <div
        class="flex items-center justify-between px-3 py-2 rounded-t-xl mb-2"
        :class="column.headerClass"
      >
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full" :class="column.dotClass"></div>
          <span class="text-sm font-semibold">{{ column.label }}</span>
        </div>
        <span class="badge badge-sm" :class="column.badgeClass">
          {{ getColumnItems(column.key).length }}
        </span>
      </div>

      <!-- Cards -->
      <div
        class="flex-1 space-y-2 p-1 rounded-b-xl border transition-all duration-150"
        :class="dragOverColumn === column.key
          ? 'bg-primary/10 border-primary border-dashed'
          : 'bg-base-200/50 border-base-300'"
        @dragover.prevent="onDragOver($event, column.key)"
        @dragenter.prevent="onDragEnter(column.key)"
        @dragleave="onDragLeave(column.key)"
        @drop.prevent="onDrop($event, column.key)"
      >
        <div
          v-for="item in getColumnItems(column.key)"
          :key="item.id"
          draggable="true"
          @dragstart="onDragStart($event, item)"
          @dragend="onDragEnd"
          class="bg-base-100 rounded-xl p-3 shadow-sm border border-base-300 cursor-grab active:cursor-grabbing hover:shadow-md transition-all duration-150 group"
          :class="draggingItem?.id === item.id ? 'opacity-40' : ''"
        >
          <!-- Card Header -->
          <div class="flex items-start justify-between mb-2">
            <h4 class="text-sm font-medium text-base-content line-clamp-2 flex-1 mr-2">{{ item.name }}</h4>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click="$emit('edit', item)"
                class="btn btn-ghost btn-xs btn-circle"
              >
                <PencilIcon class="w-3 h-3 text-base-content/50" />
              </button>
            </div>
          </div>

          <!-- Amount -->
          <div v-if="item.amount" class="flex items-center gap-1 mb-2">
            <CurrencyDollarIcon class="w-4 h-4 text-success" />
            <span class="text-sm font-semibold text-success">${{ Number(item.amount).toLocaleString() }}</span>
          </div>

          <!-- Probability -->
          <div v-if="item.probability" class="mb-2">
            <div class="flex justify-between text-xs text-base-content/50 mb-1">
              <span>Probability</span>
              <span>{{ item.probability }}%</span>
            </div>
            <div class="w-full bg-base-300 rounded-full h-1.5">
              <div
                class="h-1.5 rounded-full transition-all"
                :class="probabilityClass(item.probability)"
                :style="{ width: item.probability + '%' }"
              ></div>
            </div>
          </div>

          <!-- Close Date -->
          <div v-if="item.close_date" class="flex items-center gap-1 text-xs text-base-content/60">
            <CalendarIcon class="w-3 h-3" />
            {{ formatDate(item.close_date) }}
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="getColumnItems(column.key).length === 0"
          class="flex flex-col items-center justify-center py-6 text-center text-base-content/30"
        >
          <div class="w-8 h-8 rounded-full border-2 border-dashed border-base-300 flex items-center justify-center mb-2">
            <PlusIcon class="w-4 h-4" />
          </div>
          <p class="text-xs">Drop here</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { PencilIcon, CurrencyDollarIcon, CalendarIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  stageField: {
    type: String,
    default: 'stage',
  },
})

const emit = defineEmits(['edit', 'stage-change'])

const columns = [
  { key: 'prospecting', label: 'Prospecting', headerClass: 'bg-base-200 text-base-content', dotClass: 'bg-neutral', badgeClass: 'badge-ghost' },
  { key: 'qualification', label: 'Qualification', headerClass: 'bg-info/10 text-info', dotClass: 'bg-info', badgeClass: 'badge-info' },
  { key: 'proposal', label: 'Proposal', headerClass: 'bg-warning/10 text-warning', dotClass: 'bg-warning', badgeClass: 'badge-warning' },
  { key: 'negotiation', label: 'Negotiation', headerClass: 'bg-secondary/10 text-secondary', dotClass: 'bg-secondary', badgeClass: 'badge-primary' },
  { key: 'closed_won', label: 'Closed Won', headerClass: 'bg-success/10 text-success', dotClass: 'bg-success', badgeClass: 'badge-success' },
]

const draggingItem = ref(null)
const dragOverColumn = ref(null)

function getColumnItems(key) {
  return props.items.filter(item => item[props.stageField] === key)
}

function onDragStart(event, item) {
  draggingItem.value = item
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(item.id))
}

function onDragEnd() {
  draggingItem.value = null
  dragOverColumn.value = null
}

function onDragOver(event, columnKey) {
  event.dataTransfer.dropEffect = 'move'
  dragOverColumn.value = columnKey
}

function onDragEnter(columnKey) {
  dragOverColumn.value = columnKey
}

function onDragLeave(columnKey) {
  if (dragOverColumn.value === columnKey) {
    dragOverColumn.value = null
  }
}

function onDrop(event, stageKey) {
  dragOverColumn.value = null
  const item = draggingItem.value
  draggingItem.value = null
  if (item && item[props.stageField] !== stageKey) {
    emit('stage-change', { item, newStage: stageKey })
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function probabilityClass(prob) {
  if (prob >= 75) return 'bg-success'
  if (prob >= 50) return 'bg-primary'
  if (prob >= 25) return 'bg-warning'
  return 'bg-base-300'
}
</script>
