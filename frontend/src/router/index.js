import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    redirect: '/dashboard',
    component: () => import('../components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue'),
      },
      {
        path: 'accounts',
        name: 'Accounts',
        component: () => import('../views/accounts/AccountList.vue'),
      },
      {
        path: 'contacts',
        name: 'Contacts',
        component: () => import('../views/contacts/ContactList.vue'),
      },
      {
        path: 'leads',
        name: 'Leads',
        component: () => import('../views/leads/LeadList.vue'),
      },
      {
        path: 'opportunities',
        name: 'Opportunities',
        component: () => import('../views/opportunities/OpportunityList.vue'),
      },
      {
        path: 'meetings',
        name: 'Meetings',
        component: () => import('../views/meetings/MeetingList.vue'),
      },
      {
        path: 'tasks',
        name: 'Tasks',
        component: () => import('../views/tasks/TaskList.vue'),
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('../views/users/UserList.vue'),
        meta: { adminOnly: true },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.public) {
    if (authStore.isAuthenticated) {
      return next('/dashboard')
    }
    return next()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.adminOnly && authStore.user?.role !== 'admin') {
    return next('/dashboard')
  }

  next()
})

export default router
