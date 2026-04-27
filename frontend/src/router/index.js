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
        meta: { pageKey: 'accounts' },
      },
      {
        path: 'accounts/:id',
        name: 'AccountDetail',
        component: () => import('../views/accounts/AccountDetail.vue'),
        meta: { pageKey: 'accounts' },
      },
      {
        path: 'contacts',
        name: 'Contacts',
        component: () => import('../views/contacts/ContactList.vue'),
        meta: { pageKey: 'contacts' },
      },
      {
        path: 'contacts/:id',
        name: 'ContactDetail',
        component: () => import('../views/contacts/ContactDetail.vue'),
        meta: { pageKey: 'contacts' },
      },
      {
        path: 'leads',
        name: 'Leads',
        component: () => import('../views/leads/LeadList.vue'),
        meta: { pageKey: 'leads' },
      },
      {
        path: 'leads/:id',
        name: 'LeadDetail',
        component: () => import('../views/leads/LeadDetail.vue'),
        meta: { pageKey: 'leads' },
      },
      {
        path: 'opportunities',
        name: 'Opportunities',
        component: () => import('../views/opportunities/OpportunityList.vue'),
        meta: { pageKey: 'opportunities' },
      },
      {
        path: 'opportunities/:id',
        name: 'OpportunityDetail',
        component: () => import('../views/opportunities/OpportunityDetail.vue'),
        meta: { pageKey: 'opportunities' },
      },
      {
        path: 'meetings',
        name: 'Meetings',
        component: () => import('../views/meetings/MeetingList.vue'),
        meta: { pageKey: 'meetings' },
      },
      {
        path: 'meetings/:id',
        name: 'MeetingDetail',
        component: () => import('../views/meetings/MeetingDetail.vue'),
        meta: { pageKey: 'meetings' },
      },
      {
        path: 'tasks',
        name: 'Tasks',
        component: () => import('../views/tasks/TaskList.vue'),
        meta: { pageKey: 'tasks' },
      },
      {
        path: 'tasks/:id',
        name: 'TaskDetail',
        component: () => import('../views/tasks/TaskDetail.vue'),
        meta: { pageKey: 'tasks' },
      },
      {
        path: 'support',
        name: 'Support',
        component: () => import('../views/support/SupportList.vue'),
        meta: { pageKey: 'support' },
      },
      {
        path: 'support/:id',
        name: 'SupportDetail',
        component: () => import('../views/support/SupportDetail.vue'),
        meta: { pageKey: 'support' },
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

  if (to.meta.pageKey && authStore.user?.role !== 'admin') {
    const permissions = authStore.user?.page_permissions
    if (permissions && permissions.length > 0 && !permissions.includes(to.meta.pageKey)) {
      return next('/dashboard')
    }
  }

  next()
})

export default router
