<template>
  <Teleport to="body">
    <div v-if="modelValue" class="modal modal-open z-50">
      <div
        class="modal-box relative"
        :class="[sizeClass, 'max-h-screen overflow-y-auto']"
      >
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-base-300">
          <h3 class="text-lg font-semibold text-base-content">{{ title }}</h3>
          <button
            @click="$emit('update:modelValue', false)"
            class="btn btn-ghost btn-sm btn-circle"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <slot />

        <!-- Footer -->
        <div v-if="$slots.footer" class="modal-action border-t border-base-300 pt-3 mt-4">
          <slot name="footer" />
        </div>
      </div>
      <div class="modal-backdrop" @click="$emit('update:modelValue', false)"></div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: Boolean,
  title: String,
  size: {
    type: String,
    default: 'md',
  },
})

defineEmits(['update:modelValue'])

const sizeClass = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}[props.size] || 'max-w-lg'))
</script>
