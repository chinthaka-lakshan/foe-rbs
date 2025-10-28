<template>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Users</h2>
      <button class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add New User
      </button>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(75, 182, 109, 0.1);">
            <i class="bi bi-people" style="color: #4BB66D; font-size: 32px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalUsers }}</h3>
            <p>Total Users</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(38, 213, 22, 0.1);">
            <i class="bi bi-person-badge" style="color: #26d516; font-size: 32px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalAdmins }}</h3>
            <p>Total Admins</p>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <div class="row g-3">
        <div class="col-md-8">
          <input
            type="text"
            class="form-control"
            placeholder="Search by name or email..."
            v-model="searchQuery"
          >
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedRole">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span class="badge" :class="user.role === 'admin' ? 'bg-primary' : 'bg-info'">
                  {{ user.role }}
                </span>
              </td>
              <td>
                <span class="badge" :class="user.status === 'active' ? 'bg-success' : 'bg-secondary'">
                  {{ user.status }}
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-primary">Edit</button>
                  <button class="btn btn-outline-danger">Delete</button>
                  <button class="btn btn-outline-warning">Role</button>
                  <button class="btn btn-outline-info">Permissions</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const searchQuery = ref('');
const selectedRole = ref('');

const stats = ref({
  totalUsers: 265,
  totalAdmins: 12
});

const users = ref([
  { id: 'U001', name: 'John Doe', email: 'john@university.edu', role: 'user', status: 'active' },
  { id: 'U002', name: 'Jane Smith', email: 'jane@university.edu', role: 'admin', status: 'active' },
  { id: 'U003', name: 'Bob Johnson', email: 'bob@university.edu', role: 'user', status: 'active' },
  { id: 'U004', name: 'Alice Williams', email: 'alice@university.edu', role: 'admin', status: 'active' },
  { id: 'U005', name: 'Charlie Brown', email: 'charlie@university.edu', role: 'user', status: 'inactive' },
  { id: 'U006', name: 'Diana Prince', email: 'diana@university.edu', role: 'user', status: 'active' }
]);

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesRole = !selectedRole.value || user.role === selectedRole.value;
    return matchesSearch && matchesRole;
  });
});
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-icon {
  width: 70px;
  height: 70px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-content h3 {
  font-size: 32px;
  font-weight: 700;
  color: #1e4449;
  margin: 0;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
}

.table-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.table thead {
  background: #f8f9fa;
}

.btn-success {
  background-color: #26d516;
  border-color: #26d516;
}

.btn-success:hover {
  background-color: #22b913;
  border-color: #22b913;
}
</style>
