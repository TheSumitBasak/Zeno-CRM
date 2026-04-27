import { reactive } from 'vue'

export function useForm(initialValues, rules = {}) {
  const values = reactive({ ...initialValues })
  const errors = reactive({})
  const touched = reactive({})

  function validateField(field) {
    const rule = rules[field]
    if (!rule) {
      delete errors[field]
      return true
    }
    const msg = rule(values[field], values)
    if (msg) {
      errors[field] = msg
      return false
    }
    delete errors[field]
    return true
  }

  function validate() {
    let valid = true
    for (const field of Object.keys(rules)) {
      touched[field] = true
      if (!validateField(field)) valid = false
    }
    return valid
  }

  function handleBlur(field) {
    touched[field] = true
    validateField(field)
  }

  function reset(newValues) {
    const src = newValues ?? initialValues
    for (const k of Object.keys(values)) delete values[k]
    Object.assign(values, { ...initialValues, ...src })
    for (const k of Object.keys(errors)) delete errors[k]
    for (const k of Object.keys(touched)) delete touched[k]
  }

  return { values, errors, touched, validate, handleBlur, reset }
}

// Shared validators
export const required = label => v =>
  !String(v ?? '').trim() ? `${label} is required` : null

export const email = () => v =>
  v && !/\S+@\S+\.\S+/.test(v) ? 'Invalid email format' : null

export const url = () => v => {
  if (!v) return null
  try { new URL(v); return null } catch { return 'Must be a valid URL (include https://)' }
}
