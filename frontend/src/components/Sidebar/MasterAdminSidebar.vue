<template>
  <div class="sidebar">
    <div class="logo-container">
      <img src="/logo.png" alt="FOE RBS" class="logo" />
      <h4 class="logo-text">FOE RBS</h4>
    </div>

    <nav class="nav-menu">
      <router-link
        to="/master-admin/dashboard"
        class="nav-item"
        :class="{ active: isActive('/master-admin/dashboard') }"
      >
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </router-link>

      <router-link
        to="/master-admin/resource"
        class="nav-item"
        :class="{ active: isActive('/master-admin/resource') }"
      >
        <i class="bi bi-building"></i>
        <span>Resource</span>
      </router-link>

      <router-link
        to="/master-admin/booking"
        class="nav-item"
        :class="{ active: isActive('/master-admin/booking') }"
      >
        <i class="bi bi-calendar-check"></i>
        <span>Booking</span>
      </router-link>

      <router-link
        to="/master-admin/users"
        class="nav-item"
        :class="{ active: isActive('/master-admin/users') }"
      >
        <i class="bi bi-people"></i>
        <span>Users</span>
      </router-link>

      <router-link
        to="/master-admin/reports"
        class="nav-item"
        :class="{ active: isActive('/master-admin/reports') }"
      >
        <i class="bi bi-bar-chart"></i>
        <span>Reports</span>
      </router-link>

      <router-link
        to="/master-admin/setting"
        class="nav-item"
        :class="{ active: isActive('/master-admin/setting') }"
      >
        <i class="bi bi-gear"></i>
        <span>Setting</span>
      </router-link>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

const route = useRoute();

const isActive = (path: string): boolean => {
  return route.path === path;
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  /* Key change: Start the sidebar 60px from the top (assuming a top Navbar height) */
  top: 60px; 
  /* Reduce height by 60px to ensure it doesn't extend past the bottom of the viewport */
  height: calc(100vh - 60px); 
  background-color: #2c3e50;
  color: white;
  position: fixed;
  left: 0;
  padding: 20px 0;
  overflow-y: auto;
  z-index: 1000; /* Added z-index to ensure it sits above dashboard content */
}

.logo-container {
  display: flex;
  align-items: center;
  padding: 0 20px 30px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
}

.logo {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  margin-right: 10px;
}

.logo-text {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
}

.nav-menu {
  display: flex;
  flex-direction: column;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 15px 20px;
  color: #ecf0f1;
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-item.active {
  background-color: #34495e;
  border-left-color: #3498db;
  color: white;
}

.nav-item i {
  font-size: 1.2rem;
  margin-right: 15px;
  width: 24px;
}

.nav-item span {
  font-size: 1rem;
}

@media (max-width: 768px) {
  .sidebar {
    width: 70px;
  }

  .logo-text,
  .nav-item span {
    display: none;
  }

  .logo-container {
    justify-content: center;
    padding: 0 10px 20px;
  }

  .nav-item {
    justify-content: center;
    padding: 15px 10px;
  }

  .nav-item i {
    margin-right: 0;
  }
}
</style>