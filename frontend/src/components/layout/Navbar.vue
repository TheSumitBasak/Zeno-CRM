<template>
  <header class="h-16 bg-base-100 border-b border-base-300 flex items-center justify-between px-6 shadow-sm">
    <!-- Page Title -->
    <div class="flex items-center gap-3">
      <button
        @click="$emit('toggle-sidebar')"
        class="btn btn-ghost btn-sm btn-square lg:hidden"
      >
        <Bars3Icon class="w-5 h-5" />
      </button>
      <div>
        <h2 class="text-lg font-semibold text-base-content">{{ pageTitle }}</h2>
        <p class="text-xs text-base-content/50">{{ pageSubtitle }}</p>
      </div>
    </div>

    <!-- Right Side Actions -->
    <div class="flex items-center gap-2">
      <!-- Search -->
      <!-- <div class="hidden md:flex items-center gap-2 bg-base-200 border border-base-300 rounded-lg px-3 py-1.5">
        <MagnifyingGlassIcon class="w-4 h-4 text-base-content/60" />
        <input
          type="text"
          placeholder="Search..."
          class="bg-transparent text-sm outline-none text-base-content w-36"
        />
      </div> -->

      <!-- Notifications -->
      <!-- <div class="indicator">
        <span class="indicator-item badge badge-error badge-xs"></span>
        <button class="btn btn-ghost btn-sm btn-square">
          <BellIcon class="w-5 h-5 text-base-content/60" />
        </button>
      </div> -->

      <!-- User avatar -->
      <div class="dropdown dropdown-end">
        <label tabindex="0" class="btn btn-ghost btn-sm btn-circle avatar placeholder cursor-pointer">
          <div class="w-8 rounded-full bg-primary">
            <span class="text-xs text-primary-content font-bold">{{ userInitials }}</span>
          </div>
        </label>
        <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-xl w-52 border border-base-300 mt-1 z-50">
          <li class="menu-title px-2 py-1">
            <div>
              <p class="font-medium text-base-content">{{ authStore.user?.name }}</p>
              <p class="text-xs text-base-content/50">{{ authStore.user?.email }}</p>
            </div>
          </li>
          <div class="divider my-1"></div>
          <li>
            <button @click="handleLogout" class="text-error hover:bg-error/10">
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
              Sign Out
            </button>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  Bars3Icon,
  MagnifyingGlassIcon,
  BellIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const pageTitles = {
  '/dashboard': { title: 'Dashboard', subtitle: 'Welcome back!' },
  '/accounts': { title: 'Accounts', subtitle: 'Manage your business accounts' },
  '/contacts': { title: 'Contacts', subtitle: 'Manage your contacts' },
  '/leads': { title: 'Leads', subtitle: 'Track and manage sales leads' },
  '/opportunities': { title: 'Opportunities', subtitle: 'Monitor your sales pipeline' },
  '/meetings': { title: 'Meetings', subtitle: 'Schedule and track meetings' },
  '/tasks': { title: 'Tasks', subtitle: 'Manage your tasks and to-dos' },
  '/users': { title: 'User Management', subtitle: 'Manage team members and roles' },
}

const pageTitle = computed(() => pageTitles[route.path]?.title || 'Zeno CRM')
const pageSubtitle = computed(() => pageTitles[route.path]?.subtitle || '')

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U'
  return authStore.user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
