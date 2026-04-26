<template>
  <div class="flex min-h-screen bg-base-200">
    <!-- Sidebar -->
    <div
      class="hidden lg:flex flex-shrink-0"
      :class="sidebarOpen ? 'flex' : ''"
    >
      <Sidebar />
    </div>

    <!-- Mobile sidebar overlay -->
    <div
      v-if="mobileSidebarOpen"
      class="fixed inset-0 z-40 lg:hidden"
    >
      <div class="absolute inset-0 bg-black/50" @click="mobileSidebarOpen = false"></div>
      <div class="relative flex flex-col w-64 h-full">
        <Sidebar />
      </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar @toggle-sidebar="mobileSidebarOpen = !mobileSidebarOpen" />

      <main class="flex-1 overflow-y-auto p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './Sidebar.vue'
import Navbar from './Navbar.vue'

const sidebarOpen = ref(true)
const mobileSidebarOpen = ref(false)
</script>
