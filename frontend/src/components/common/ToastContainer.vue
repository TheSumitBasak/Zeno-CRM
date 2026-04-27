<template>
  <div class="toast toast-end toast-bottom z-[9999] pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="t in toastStore.toasts"
        :key="t.id"
        class="alert shadow-lg max-w-sm pointer-events-auto"
        :class="alertClass(t.type)"
      >
        <component :is="iconFor(t.type)" class="w-5 h-5 flex-shrink-0" />
        <span class="text-sm">{{ t.message }}</span>
        <button class="btn btn-ghost btn-xs ml-auto" @click="toastStore.remove(t.id)">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastStore } from '../../stores/toast'
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'

const toastStore = useToastStore()

function alertClass(type) {
  return {
    error: 'alert-error',
    success: 'alert-success',
    warning: 'alert-warning',
    info: 'alert-info',
  }[type] ?? 'alert-error'
}

function iconFor(type) {
  return {
    error: ExclamationCircleIcon,
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  }[type] ?? ExclamationCircleIcon
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
