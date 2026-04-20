<template>
  <div class="min-h-screen flex">
    <!-- Left Branding Panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-primary text-primary-content flex-col justify-between p-12">
      <!-- Logo -->
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary-content/10 border border-primary-content/20 flex items-center justify-center">
          <span class="text-primary-content font-bold text-xl">Z</span>
        </div>
        <span class="text-primary-content text-xl font-bold">Zeno CRM</span>
      </div>

      <!-- Center Content -->
      <div>
        <div class="inline-flex items-center gap-2 bg-primary-content/10 rounded-full px-4 py-2 mb-6">
          <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
          <span class="text-primary-content/80 text-sm">Trusted by 1,000+ businesses</span>
        </div>
        <h1 class="text-4xl font-bold text-primary-content mb-4 leading-tight">
          Manage your<br />
          <span class="text-primary-content opacity-80">
            customer relationships
          </span><br />
          with confidence.
        </h1>
        <p class="text-primary-content/60 text-lg leading-relaxed">
          Track leads, manage deals, and grow your business with the most intuitive CRM platform.
        </p>

        <!-- Feature bullets -->
        <div class="mt-8 space-y-3">
          <div v-for="feature in features" :key="feature" class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-success/20 border border-success/30 flex items-center justify-center flex-shrink-0">
              <CheckIcon class="w-3 h-3 text-success" />
            </div>
            <span class="text-primary-content/70 text-sm">{{ feature }}</span>
          </div>
        </div>
      </div>

      <!-- Bottom Stats -->
      <div class="flex gap-8">
        <div v-for="stat in stats" :key="stat.label">
          <p class="text-2xl font-bold text-primary-content">{{ stat.value }}</p>
          <p class="text-primary-content/50 text-xs">{{ stat.label }}</p>
        </div>
      </div>
    </div>

    <!-- Right Login Panel -->
    <div class="flex-1 flex items-center justify-center p-8 bg-base-200">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="flex items-center gap-2 mb-8 lg:hidden">
          <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
            <span class="text-primary-content font-bold">Z</span>
          </div>
          <span class="text-xl font-bold text-base-content">Zeno CRM</span>
        </div>

        <div class="bg-base-100 rounded-2xl shadow-xl border border-base-300 p-8">
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-base-content mb-1">Welcome back</h2>
            <p class="text-base-content/50 text-sm">Sign in to your account to continue</p>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="alert alert-error mb-6 rounded-xl py-3">
            <ExclamationCircleIcon class="w-5 h-5" />
            <span class="text-sm">{{ error }}</span>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div class="form-control">
              <label class="label pt-0">
                <span class="label-text text-sm font-medium text-base-content">Email Address</span>
              </label>
              <div class="relative">
                <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-base-content/60" />
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="admin@zenocrm.com"
                  class="input input-bordered w-full pl-10 bg-base-200 focus:bg-base-100"
                  :class="{ 'input-error': errors.email }"
                  required
                />
              </div>
              <label v-if="errors.email" class="label py-1">
                <span class="label-text-alt text-error">{{ errors.email }}</span>
              </label>
            </div>

            <div class="form-control">
              <label class="label pt-0">
                <span class="label-text text-sm font-medium text-base-content">Password</span>
              </label>
              <div class="relative">
                <LockClosedIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-base-content/60" />
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="••••••••"
                  class="input input-bordered w-full pl-10 pr-10 bg-base-200 focus:bg-base-100"
                  :class="{ 'input-error': errors.password }"
                  required
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/60 hover:text-base-content"
                >
                  <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                  <EyeSlashIcon v-else class="w-5 h-5" />
                </button>
              </div>
              <label v-if="errors.password" class="label py-1">
                <span class="label-text-alt text-error">{{ errors.password }}</span>
              </label>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" />
                <span class="text-sm text-base-content">Remember me</span>
              </label>
              <a href="#" class="text-sm text-primary hover:opacity-80 font-medium">Forgot password?</a>
            </div>

            <button
              type="submit"
              class="btn btn-primary w-full rounded-xl"
              :disabled="loading"
            >
              <span v-if="loading" class="loading loading-spinner loading-sm"></span>
              <span v-else class="flex items-center gap-2">
                Sign In
                <ArrowRightIcon class="w-4 h-4" />
              </span>
            </button>
          </form>

          <!-- Demo credentials -->
          <div class="mt-6 p-4 bg-info/10 rounded-xl border border-info/20">
            <p class="text-xs font-semibold text-info mb-2">Demo Credentials</p>
            <div class="space-y-1">
              <p class="text-xs text-info">Email: <span class="font-mono font-medium">admin@zenocrm.com</span></p>
              <p class="text-xs text-info">Password: <span class="font-mono font-medium">Admin@123</span></p>
            </div>
            <button
              @click="fillDemo"
              class="btn btn-xs btn-outline btn-primary mt-2"
            >
              Fill Demo
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  CheckIcon,
  EnvelopeIcon,
  LockClosedIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowRightIcon,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

const features = [
  'Complete lead & pipeline management',
  'Real-time dashboard & analytics',
  'Team collaboration tools',
  'Automated task tracking',
]

const stats = [
  { value: '10K+', label: 'Users worldwide' },
  { value: '99.9%', label: 'Uptime SLA' },
  { value: '24/7', label: 'Support' },
]

function validate() {
  errors.email = ''
  errors.password = ''
  if (!form.email) { errors.email = 'Email is required'; return false }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { errors.email = 'Invalid email format'; return false }
  if (!form.password) { errors.password = 'Password is required'; return false }
  if (form.password.length < 6) { errors.password = 'Password must be at least 6 characters'; return false }
  return true
}

async function handleLogin() {
  if (!validate()) return
  loading.value = true
  error.value = ''
  try {
    const result = await authStore.login(form.email, form.password)
    if (result.success) {
      router.push('/dashboard')
    } else {
      error.value = result.message || 'Login failed'
    }
  } catch (e) {
    error.value = 'An unexpected error occurred'
  } finally {
    loading.value = false
  }
}

function fillDemo() {
  form.email = 'admin@zenocrm.com'
  form.password = 'Admin@123'
}
</script>
