<template>
  <aside class="flex flex-col w-64 h-screen sticky top-0 bg-neutral text-neutral-content shadow-2xl">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-neutral-content/20">
      <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center shadow-lg">
        <span class="text-primary-content font-bold text-lg">Z</span>
      </div>
      <div>
        <h1 class="text-lg font-bold text-primary">Zeno CRM</h1>
        <p class="text-xs text-neutral-content/60">Business Platform</p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
      <p class="px-3 text-xs font-semibold text-neutral-content/50 uppercase tracking-wider mb-2">Main Menu</p>

      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group"
        :class="[
          isActive(item.path)
            ? 'bg-primary text-primary-content shadow-lg'
            : 'text-neutral-content/60 hover:bg-base-300/20 hover:text-neutral-content'
        ]"
      >
        <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
        <span>{{ item.label }}</span>
        <span v-if="item.badge" class="ml-auto badge badge-sm" :class="isActive(item.path) ? 'badge-warning' : 'badge-ghost'">
          {{ item.badge }}
        </span>
      </router-link>
    </nav>

    <!-- User section -->
    <div class="px-4 py-4 border-t border-neutral-content/20">
      <div class="flex items-center gap-3 mb-3">
        <div class="avatar placeholder">
          <div class="w-9 rounded-full bg-secondary">
            <span class="text-secondary-content text-sm font-bold">{{ userInitials }}</span>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-neutral-content truncate">{{ authStore.user?.name }}</p>
          <p class="text-xs text-neutral-content/60 truncate">{{ authStore.user?.role }}</p>
        </div>
      </div>
      <button
        @click="handleLogout"
        class="flex items-center gap-2 w-full px-3 py-2 text-sm text-neutral-content/60 hover:text-error hover:bg-base-300/20 rounded-lg transition-all duration-150"
      >
        <ArrowRightOnRectangleIcon class="w-4 h-4" />
        Sign Out
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  HomeIcon,
  BuildingOfficeIcon,
  UsersIcon,
  UserPlusIcon,
  CurrencyDollarIcon,
  CalendarIcon,
  ClipboardDocumentListIcon,
  LifebuoyIcon,
  ShieldCheckIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U'
  return authStore.user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const allNavItems = [
  { path: '/dashboard', label: 'Dashboard', icon: HomeIcon },
  { path: '/accounts', label: 'Accounts', icon: BuildingOfficeIcon, pageKey: 'accounts' },
  { path: '/contacts', label: 'Contacts', icon: UsersIcon, pageKey: 'contacts' },
  { path: '/leads', label: 'Leads', icon: UserPlusIcon, pageKey: 'leads' },
  { path: '/opportunities', label: 'Opportunities', icon: CurrencyDollarIcon, pageKey: 'opportunities' },
  { path: '/meetings', label: 'Meetings', icon: CalendarIcon, pageKey: 'meetings' },
  { path: '/tasks', label: 'Tasks', icon: ClipboardDocumentListIcon, pageKey: 'tasks' },
  { path: '/support', label: 'Support', icon: LifebuoyIcon, pageKey: 'support' },
  { path: '/users', label: 'User Management', icon: ShieldCheckIcon, adminOnly: true },
]

const navItems = computed(() => {
  const isAdmin = authStore.user?.role === 'admin'
  const permissions = authStore.user?.page_permissions

  return allNavItems.filter(item => {
    if (item.adminOnly) return isAdmin
    if (!item.pageKey) return true // dashboard always visible
    if (isAdmin) return true
    if (!permissions || permissions.length === 0) return true // no restrictions set
    return permissions.includes(item.pageKey)
  })
})

function isActive(path) {
  return route.path === path || route.path.startsWith(path + '/')
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
