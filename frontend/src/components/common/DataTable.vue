<template>
  <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300">
    <!-- Table Header / Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 border-b border-base-300">
      <div class="flex items-center gap-2 w-full sm:w-auto">
        <div class="relative flex-1 sm:w-72">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/60" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="input input-bordered input-sm w-full pl-9 bg-base-200 border-base-300 focus:bg-base-100"
          />
        </div>
      </div>
      <div class="flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="table table-sm w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th
              v-for="col in columns"
              :key="col.key"
              @click="col.sortable !== false && sortBy(col.key)"
              class="text-xs font-semibold text-base-content/50 uppercase tracking-wider py-3"
              :class="col.sortable !== false ? 'cursor-pointer hover:text-base-content select-none' : ''"
            >
              <div class="flex items-center gap-1">
                {{ col.label }}
                <span v-if="col.sortable !== false" class="text-base-content/30">
                  <ChevronUpIcon v-if="sortKey === col.key && sortDir === 'asc'" class="w-3 h-3 text-primary" />
                  <ChevronDownIcon v-else-if="sortKey === col.key && sortDir === 'desc'" class="w-3 h-3 text-primary" />
                  <ChevronUpDownIcon v-else class="w-3 h-3" />
                </span>
              </div>
            </th>
            <th class="text-xs font-semibold text-base-content/50 uppercase tracking-wider py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in paginatedData"
            :key="row.id"
            class="hover:bg-base-200/50 transition-colors border-b border-base-200 last:border-0"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="py-3 text-sm text-base-content"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </td>
            <td class="py-3">
              <div class="flex items-center gap-1">
                <button
                  @click="$emit('edit', row)"
                  class="btn btn-ghost btn-xs text-primary hover:bg-primary/10"
                >
                  <PencilIcon class="w-3.5 h-3.5" />
                  Edit
                </button>
                <button
                  @click="$emit('delete', row)"
                  class="btn btn-ghost btn-xs text-error hover:bg-error/10"
                >
                  <TrashIcon class="w-3.5 h-3.5" />
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="filteredData.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
      <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
        <InboxIcon class="w-8 h-8 text-base-content/60" />
      </div>
      <h3 class="text-base font-medium text-base-content mb-1">No records found</h3>
      <p class="text-sm text-base-content/60">{{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by adding your first record.' }}</p>
    </div>

    <!-- Pagination -->
    <div v-if="filteredData.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-base-300">
      <p class="text-xs text-base-content/50">
        Showing {{ startIndex + 1 }}-{{ Math.min(endIndex, filteredData.length) }} of {{ filteredData.length }} results
      </p>
      <div class="flex items-center gap-1">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="btn btn-ghost btn-xs btn-square disabled:opacity-40"
        >
          <ChevronLeftIcon class="w-4 h-4" />
        </button>
        <template v-for="page in visiblePages" :key="page">
          <button
            v-if="page !== '...'"
            @click="currentPage = page"
            class="btn btn-xs btn-square"
            :class="page === currentPage ? 'btn-primary' : 'btn-ghost'"
          >{{ page }}</button>
          <span v-else class="text-base-content/60 px-1 text-xs">...</span>
        </template>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="btn btn-ghost btn-xs btn-square disabled:opacity-40"
        >
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  MagnifyingGlassIcon,
  PencilIcon,
  TrashIcon,
  InboxIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronUpDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  columns: Array,
  data: {
    type: Array,
    default: () => [],
  },
  pageSize: {
    type: Number,
    default: 10,
  },
})

defineEmits(['edit', 'delete'])

const searchQuery = ref('')
const currentPage = ref(1)
const sortKey = ref('')
const sortDir = ref('asc')

const filteredData = computed(() => {
  let data = [...props.data]
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    data = data.filter(row =>
      Object.values(row).some(val =>
        String(val).toLowerCase().includes(q)
      )
    )
  }
  if (sortKey.value) {
    data.sort((a, b) => {
      const av = a[sortKey.value]
      const bv = b[sortKey.value]
      const cmp = String(av).localeCompare(String(bv))
      return sortDir.value === 'asc' ? cmp : -cmp
    })
  }
  return data
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / props.pageSize))
const startIndex = computed(() => (currentPage.value - 1) * props.pageSize)
const endIndex = computed(() => startIndex.value + props.pageSize)
const paginatedData = computed(() => filteredData.value.slice(startIndex.value, endIndex.value))

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages = []
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i)
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

function sortBy(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
  currentPage.value = 1
}

// Reset page when search changes
import { watch } from 'vue'
watch(searchQuery, () => { currentPage.value = 1 })
</script>
