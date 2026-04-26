<template>
  <div class="bg-base-100 rounded-2xl p-6 shadow-sm border border-base-300 hover:shadow-md transition-shadow duration-200">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-base-content/50 mb-1">{{ label }}</p>
        <p class="text-3xl font-bold text-base-content">{{ formattedValue }}</p>
        <div v-if="trend !== undefined" class="flex items-center gap-1 mt-2">
          <span
            class="flex items-center gap-0.5 text-xs font-medium"
            :class="trend >= 0 ? 'text-success' : 'text-error'"
          >
            <ArrowTrendingUpIcon v-if="trend >= 0" class="w-3.5 h-3.5" />
            <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
            {{ Math.abs(trend) }}%
          </span>
          <span class="text-xs text-base-content/60">vs last month</span>
        </div>
      </div>
      <div
        class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
        :class="iconBgClass"
      >
        <component :is="icon" class="w-6 h-6" :class="iconClass" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  label: String,
  value: [Number, String],
  icon: Object,
  color: {
    type: String,
    default: 'blue',
  },
  trend: Number,
})

const colorMap = {
  blue: { bg: 'bg-primary/10', icon: 'text-primary' },
  green: { bg: 'bg-success/10', icon: 'text-success' },
  purple: { bg: 'bg-secondary/10', icon: 'text-secondary' },
  orange: { bg: 'bg-warning/10', icon: 'text-warning' },
  pink: { bg: 'bg-accent/10', icon: 'text-accent' },
}

const iconBgClass = computed(() => colorMap[props.color]?.bg || colorMap.blue.bg)
const iconClass = computed(() => colorMap[props.color]?.icon || colorMap.blue.icon)

const formattedValue = computed(() => {
  if (typeof props.value === 'number' && props.value >= 1000) {
    return props.value.toLocaleString()
  }
  return props.value
})
</script>
