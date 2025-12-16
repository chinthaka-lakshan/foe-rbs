<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Users</h2>
      <button class="btn btn-success btn-sm" :disabled="isLoading">
        <i class="bi bi-plus-circle me-1"></i>Add New 
      </button>
    </div>

    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ errorMessage }}
        <button type="button" class="btn-close" @click="errorMessage = ''"></button>
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
             :disabled="isLoading"
           >
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedRole" :disabled="isLoading">
            <option value="">All Roles</option>
            <option value="Master Admin">Master Admin</option>
            <option value="Admin">Admin</option>
            <option value="User">User</option>
          </select>
        </div>
      </div>

    </div>

   <div class="table-card">
        <div v-if="isLoading" class="text-center py-5 text-muted">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading users...
        </div>
        <div v-else-if="filteredUsers.length === 0" class="text-center py-5 text-muted">
            No users found matching your criteria.
        </div>
        <div v-else class="table-responsive">
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
                    <span class="badge" :class="user.primaryRole.toLowerCase().includes('admin') ? 'bg-primary' : 'bg-info'">
                      {{ user.primaryRole }}
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
import { ref, computed, onMounted } from 'vue';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = 'http://localhost:8000/api'; 
const USERS_API_URL = `${API_BASE_URL}/users`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- INTERFACES ---
interface Role {
    id: number;
    name: string; // e.g., 'Master Admin', 'Admin', 'User'
}

interface User {
    id: number | string;
    name: string;
    email: string;
    status: 'active' | 'inactive' | string;
    roles: Role[];
    primaryRole: string; // Frontend helper for display
}

// --- STATE ---
const searchQuery = ref('');
const selectedRole = ref('');
const isLoading = ref(true);
const errorMessage = ref('');

const users = ref<User[]>([]);

// --- COMPUTED STATS ---
const stats = computed(() => {
    const totalUsers = users.value.length;
    const totalAdmins = users.value.filter(u => u.primaryRole.toLowerCase().includes('admin')).length;
    return {
        totalUsers,
        totalAdmins
    };
});

// --- API FETCH FUNCTION ---

const handleApiError = (data: any, status: number) => {
    // If data is null (connection error), provide a generic message.
    if (!data) {
        errorMessage.value = `Network Error (Status: ${status}). Could not reach the authentication service.`;
        return;
    }
    // If the backend returned a JSON error message (from the Gateway or Microservice)
    errorMessage.value = data.message || `Failed to fetch users (Status: ${status}).`;
};

/**
 * Fetches all users from the backend API.
 */
const fetchUsers = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    const token = getAuthToken();

    if (!token) {
        errorMessage.value = "Authentication token missing. Cannot fetch users.";
        isLoading.value = false;
        return;
    }

    try {
        const response = await fetch(USERS_API_URL, {
            method: 'GET',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json' 
            }
        });
        
        // Use text to safely handle non-JSON responses from the proxy/gateway
        const responseText = await response.text();
        let data = null;
        
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            // Non-JSON response, likely a connection/server error handled below
            handleApiError(null, response.status); 
            return; 
        }

        if (response.ok && Array.isArray(data)) {
            // Map the raw backend data to the User interface
            users.value = data.map(user => {
                // Find the primary role (e.g., the highest-priority role or the first one)
                // Note: The Postman response shows 'Master Admin' and 'User' as role names.
                const roleName = user.roles?.[0]?.name || 'User'; 
                
                return {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    status: user.status,
                    roles: user.roles || [],
                    primaryRole: roleName
                } as User;
            });

        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        errorMessage.value = 'Network error: Could not connect to the API Gateway.';
    } finally {
        isLoading.value = false;
    }
};

// --- COMPUTED PROPERTY FOR FILTERING ---

const filteredUsers = computed(() => {
    return users.value.filter(user => {
        const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                              user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        // Filter based on the derived primaryRole (case-insensitive)
        const matchesRole = !selectedRole.value || user.primaryRole.toLowerCase() === selectedRole.value.toLowerCase();
        
        return matchesSearch && matchesRole;
    });
});

// --- LIFECYCLE HOOK ---
onMounted(() => {
    fetchUsers();
});
</script>
<style scoped>
.section {
 animation: fadeIn 0.3s ease;
 margin-left: 260px;
 padding: 20px; 
}
@media (max-width: 768px) {
 /* When the sidebar collapses, reduce the margin to 70px (Collapsed Sidebar Width) */
 .section {
 margin-left: 80px;
 }
}

@keyframes fadeIn {
 from {
 opacity: 0;
 transform: translateY(10px);
 } to {
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
 background-color: #4BB66D; /* Changed color to match typical admin green */
 border-color: #4BB66D;
}

.btn-success:hover {
 background-color: #3f975b;
 border-color: #3f975b;
}
</style>