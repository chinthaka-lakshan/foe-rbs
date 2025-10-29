<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold" href="#">
        <span class="brand-text">University Resources</span>
      </a>

      <div class="d-flex align-items-center">
        <span class="me-3 text-muted">{{ userName }}</span>
        <button class="btn btn-outline-danger btn-sm" @click="handleLogout">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const userName = ref(localStorage.getItem('userName') || 'User');

const handleLogout = () => {
  localStorage.clear();
  router.push('/login');
};
</script>

<style scoped>
.navbar {
  border-bottom: 1px solid #e0e0e0;
  position: sticky;
  top: 0;
  z-index: 1020;
}

.brand-text {
  color: #1e4449;
  font-size: 1.2rem;
}

.btn-outline-danger {
  border-radius: 6px;
}
</style>
