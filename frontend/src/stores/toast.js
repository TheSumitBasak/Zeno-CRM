import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let nextId = 0

  function add(message, type = 'error', duration = 4000) {
    const id = ++nextId
    toasts.value.push({ id, message, type })
    setTimeout(() => remove(id), duration)
  }

  function error(message) { add(message, 'error') }
  function success(message) { add(message, 'success', 3000) }
  function warning(message) { add(message, 'warning') }
  function info(message) { add(message, 'info', 3000) }

  function remove(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  return { toasts, error, success, warning, info, remove }
})
