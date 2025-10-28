<template>
  <div class="dashboard-container">
    <Navbar />

    <div class="d-flex">
      <Sidebar
        title="Master Admin"
        :menuItems="menuItems"
        v-model="activeSection"
      />

      <div class="main-content">
        <Dashboard v-if="activeSection === 'dashboard'" />
        <Resources v-if="activeSection === 'resources'" />
        <Bookings v-if="activeSection === 'bookings'" />
        <Users v-if="activeSection === 'users'" />
        <Reports v-if="activeSection === 'reports'" />
        <Settings v-if="activeSection === 'settings'" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Navbar from '../../components/Navbar.vue';
import Sidebar from '../../components/Sidebar.vue';
import Dashboard from './Dashboard.vue';
import Resources from './Resources.vue';
import Bookings from './Booking.vue';
import Users from './Users.vue';
import Reports from './Reports.vue';
import Settings from './Setting.vue';

const activeSection = ref('dashboard');

const menuItems = [
  { name: 'dashboard', label: 'Dashboard', icon: 'bi bi-speedometer2' },
  { name: 'resources', label: 'Resources', icon: 'bi bi-box' },
  { name: 'bookings', label: 'Bookings', icon: 'bi bi-calendar-check' },
  { name: 'users', label: 'Users', icon: 'bi bi-people' },
  { name: 'reports', label: 'Reports', icon: 'bi bi-file-text' },
  { name: 'settings', label: 'Settings', icon: 'bi bi-gear' }
];
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background: #f8f9fa;
}

.main-content {
  flex: 1;
  padding: 30px;
  overflow-y: auto;
}
</style>
