<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Users</h2>
      <button class="btn btn-success btn-sm" @click="openAddModal" :disabled="isLoading">
        <i class="bi bi-plus-circle me-1"></i>Add New 
      </button>
    </div>

    <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ successMessage }}
      <button type="button" class="btn-close" @click="successMessage = ''"></button>
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
                <!-- Status Toggle Switch -->
                <div class="form-check form-switch d-inline-block">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :id="'statusToggle-' + user.id"
                    :checked="user.status === 'active'"
                    @change="toggleUserStatus(user)"
                    :disabled="isStatusUpdating"
                  >
                  <label class="form-check-label ms-2" :for="'statusToggle-' + user.id">
                    <span class="badge" :class="user.status === 'active' ? 'bg-success' : 'bg-secondary'">
                      {{ user.status }}
                    </span>
                  </label>
                </div>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button
                    v-if="user.primaryRole !== 'Master Admin'"
                    @click="openDeleteConfirmation(user)"
                    class="btn btn-outline-danger"
                    title="Delete"
                    :disabled="isDeleting"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                  <!-- Only show role update button if NOT Master Admin -->
                  <button 
                    v-if="user.primaryRole !== 'Master Admin'"
                    class="btn btn-outline-warning" 
                    @click="openRoleModal(user)"
                    title="Change Role"
                    :disabled="isRoleUpdating"
                  >
                    <i class="bi bi-person-gear"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="userFormModal" tabindex="-1" aria-labelledby="userFormModalLabel" aria-hidden="true" ref="userModalRef">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userFormModalLabel">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="handleStore">
            <div class="modal-body">
              <div v-if="modalErrorMessage" class="alert alert-danger">{{ modalErrorMessage }}</div>

              <div class="mb-3">
                <label for="userName" class="form-label fw-bold">User Name <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="userName" 
                  v-model="newUser.name" 
                  :disabled="isSaving"
                  placeholder="Enter full name"
                />
                <small class="text-danger" v-if="validationErrors.name">{{ validationErrors.name[0] }}</small>
              </div>
              
              <div class="mb-3">
                <label for="userEmail" class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                <input 
                  type="email" 
                  class="form-control" 
                  id="userEmail" 
                  v-model="newUser.email" 
                  :disabled="isSaving"
                  placeholder="Enter email address"
                />
                <small class="text-danger" v-if="validationErrors.email">{{ validationErrors.email[0] }}</small>
              </div>

              <div class="mb-3">
                <label for="userPassword" class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="userPassword" 
                  v-model="newUser.password" 
                  :disabled="isSaving"
                  placeholder="Minimum 6 characters"
                />
                <small class="text-danger" v-if="validationErrors.password">{{ validationErrors.password[0] }}</small>
              </div>

              <div class="mb-3">
                <label for="userConfirmPassword" class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="userConfirmPassword" 
                  v-model="newUser.password_confirmation" 
                  :disabled="isSaving"
                  placeholder="Re-enter password"
                />
                <small class="text-danger" v-if="validationErrors.password_confirmation">{{ validationErrors.password_confirmation[0] }}</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :disabled="isSaving">Cancel</button>
              <button type="submit" class="btn btn-success" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="bi bi-save me-1"></i> Save User
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Role Change Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true" ref="roleModalRef">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="roleModalLabel">Change User Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="handleRoleUpdate">
            <div class="modal-body">
              <div v-if="roleErrorMessage" class="alert alert-danger">{{ roleErrorMessage }}</div>

              <p class="mb-3">
                <span class="fw-bold">{{ userToEdit?.name }}</span>'s current role: 
                <span class="badge" :class="userToEdit?.primaryRole.toLowerCase().includes('admin') ? 'bg-primary' : 'bg-info'">
                  {{ userToEdit?.primaryRole }}
                </span>
              </p>

              <div class="mb-3">
                <label for="roleSelect" class="form-label fw-bold">New Role</label>
                <select id="roleSelect" class="form-select" v-model="selectedNewRole" :disabled="isRoleUpdating">
                  <option 
                    v-for="role in availableRoles" 
                    :key="role" 
                    :value="role">
                    {{ role }}
                  </option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :disabled="isRoleUpdating">Cancel</button>
              <button type="submit" class="btn btn-warning text-dark" :disabled="isRoleUpdating">
                <span v-if="isRoleUpdating" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="bi bi-person-gear me-1"></i> Update Role
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal (same as category page) -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
      <div class="modal-dialog delete-modal-top"> 
        <div class="modal-content">
          <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
              <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <p class="mb-0">Are you sure you want to delete the user <strong>"{{ userToDelete?.name }}"</strong>?</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isDeleting">No</button>
              <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="isDeleting">Yes</button>
            </div>
          </template>

          <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <p class="mb-0">This action will permanently delete the user <strong>"{{ userToDelete?.name }}"</strong>. Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isDeleting">Cancel</button>
              <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="isDeleting">
                <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ isDeleting ? 'Deleting...' : 'Confirm' }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap'; 
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = 'http://localhost:8000/api'; 
const USERS_API_URL = `${API_BASE_URL}/users`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- INTERFACES ---
interface Role {
  id: number;
  name: string;
}

interface User {
  id: number | string;
  name: string;
  email: string;
  status: 'active' | 'inactive' | string;
  roles: Role[];
  primaryRole: string;
}

interface NewUserForm {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface ValidationErrors {
  [key: string]: string[];
}

// --- STATE ---
const searchQuery = ref('');
const selectedRole = ref('');
const isLoading = ref(true);
const isSaving = ref(false);
const isDeleting = ref(false);
const isStatusUpdating = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const modalErrorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});

const users = ref<User[]>([]);

// Delete modal state (same as category page)
const userToDelete = ref<User | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Role modal state
const isRoleUpdating = ref(false);
const roleErrorMessage = ref('');
const userToEdit = ref<User | null>(null);
const selectedNewRole = ref('');
const availableRoles = ref(['Admin', 'User']);

// Add User modal state
const initialNewUserState: NewUserForm = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
};
const newUser = ref<NewUserForm>({ ...initialNewUserState });

// Modal references and instances
const userModalRef = ref<HTMLElement | null>(null);
const roleModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let userModalInstance: Modal | null = null;
let roleModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---
const stats = computed(() => {
  const totalUsers = users.value.length;
  const totalAdmins = users.value.filter(u => u.primaryRole.toLowerCase().includes('admin')).length;
  return {
    totalUsers,
    totalAdmins 
  };
});

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesRole = !selectedRole.value || user.primaryRole.toLowerCase() === selectedRole.value.toLowerCase();
    return matchesSearch && matchesRole;
  });
});

// --- HELPER FUNCTIONS ---
const handleApiError = (data: any, status: number) => {
  validationErrors.value = {};
  if (status === 422 && data.errors) {
    validationErrors.value = data.errors;
    modalErrorMessage.value = "Validation failed. Please check the fields and try again.";
  } else {
    errorMessage.value = data?.message || `Failed to perform operation (Status: ${status}).`;
  }
};

const resetNewUserForm = () => {
  newUser.value = { ...initialNewUserState };
  validationErrors.value = {};
  modalErrorMessage.value = '';
};

// --- STATUS TOGGLE FUNCTIONALITY ---
const toggleUserStatus = async (user: User) => {
  isStatusUpdating.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  if (!token) {
    isStatusUpdating.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  const userId = user.id;
  const newStatus = user.status === 'active' ? 'inactive' : 'active';
  
  // Use the existing update endpoint (PUT /api/users/{user})
  const url = `${USERS_API_URL}/${userId}`;

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        status: newStatus,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      errorMessage.value = `Error: Failed to process request (Status: ${response.status}).`;
      isStatusUpdating.value = false;
      // Revert the toggle if API call fails
      await fetchUsers();
      return;
    }

    if (response.ok) {
      successMessage.value = `User "${user.name}" status updated to ${newStatus} successfully!`;
      // Update the local user status immediately for better UX
      user.status = newStatus;
    } else {
      handleApiError(data, response.status);
      // Revert the toggle if API call fails
      await fetchUsers();
    }
  } catch (e) {
    errorMessage.value = 'Network error: Could not connect to the API Gateway.';
    // Revert the toggle if network error
    await fetchUsers();
  } finally {
    isStatusUpdating.value = false;
  }
};

// --- DELETE FUNCTIONALITY (same as category page) ---
const openDeleteConfirmation = (user: User) => {
  userToDelete.value = user;
  deleteStep.value = 'confirm'; 
  deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
  deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
  deleteModalInstance?.hide();
  userToDelete.value = null;
  deleteStep.value = 'confirm'; 
};

const handleDelete = async () => {
  if (deleteStep.value !== 'final' || !userToDelete.value) return; 
  
  isDeleting.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  const userId = userToDelete.value.id;

  if (!token) {
    isDeleting.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  try {
    const response = await fetch(`${USERS_API_URL}/${userId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
    
    const data = await response.json().catch(() => ({ 
      message: 'Request successful but no content received.', 
      status: response.status 
    }));

    if (response.ok) {
      successMessage.value = data.message || `User "${userToDelete.value.name}" deleted successfully.`;
      await fetchUsers();
      deleteModalInstance?.hide();
    } else {
      handleApiError(data, response.status);
    }
  } catch (e) {
    console.error('Failed to delete user:', e);
    errorMessage.value = 'Failed to delete user due to a network error.';
  } finally {
    isDeleting.value = false;
    if (successMessage.value || errorMessage.value) {
      userToDelete.value = null;
      deleteStep.value = 'confirm'; 
    }
  }
};

// --- ROLE CHANGE FUNCTIONALITY ---
const openRoleModal = (user: User) => {
  roleErrorMessage.value = '';
  userToEdit.value = user;
  selectedNewRole.value = user.primaryRole; 
  roleModalInstance?.show();
};

const handleRoleUpdate = async () => {
  if (!userToEdit.value || !selectedNewRole.value || selectedNewRole.value === userToEdit.value.primaryRole) {
    roleErrorMessage.value = 'Please select a new role to update.';
    return;
  }

  isRoleUpdating.value = true;
  roleErrorMessage.value = '';
  errorMessage.value = '';
  const token = getAuthToken();

  if (!token) {
    isRoleUpdating.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  const userId = userToEdit.value.id;
  const url = `${USERS_API_URL}/${userId}`;

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        role: selectedNewRole.value,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      roleErrorMessage.value = `Error: Failed to process request (Status: ${response.status}).`;
      isRoleUpdating.value = false;
      return;
    }

    if (response.ok) {
      successMessage.value = `Role for ${userToEdit.value.name} updated to ${selectedNewRole.value} successfully!`;
      roleModalInstance?.hide();
      await fetchUsers();
    } else {
      roleErrorMessage.value = data.message || `Update failed (Status: ${response.status}).`;
    }
  } catch (e) {
    roleErrorMessage.value = 'Network error: Could not connect to the API Gateway.';
  } finally {
    isRoleUpdating.value = false;
  }
};

// --- ADD USER FUNCTIONALITY ---
const openAddModal = () => {
  resetNewUserForm();
  userModalInstance?.show();
};

const handleStore = async () => {
  isSaving.value = true;
  modalErrorMessage.value = '';
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  if (!token) {
    isSaving.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  try {
    const response = await fetch(USERS_API_URL, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        name: newUser.value.name,
        email: newUser.value.email,
        password: newUser.value.password,
        password_confirmation: newUser.value.password_confirmation,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      handleApiError(null, response.status);
      userModalInstance?.hide();
      return;
    }

    if (response.ok) {
      successMessage.value = `User '${data.name}' created successfully!`;
      userModalInstance?.hide();
      await fetchUsers();
    } else {
      handleApiError(data, response.status);
    }
  } catch (e) {
    modalErrorMessage.value = 'Network error: Could not connect to the API Gateway.';
  } finally {
    isSaving.value = false;
  }
};

// --- FETCH USERS ---
const fetchUsers = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
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

    const responseText = await response.text();
    let data = null;

    try {
      data = JSON.parse(responseText);
    } catch (e) {
      handleApiError(null, response.status); 
      return; 
    }

    if (response.ok && Array.isArray(data)) {
      users.value = data.map(user => {
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

// --- LIFECYCLE HOOK ---
onMounted(() => {
  // Initialize Bootstrap Modals
  if (userModalRef.value) {
    userModalInstance = new Modal(userModalRef.value);
    userModalRef.value.addEventListener('hidden.bs.modal', resetNewUserForm);
  }
  
  if (roleModalRef.value) {
    roleModalInstance = new Modal(roleModalRef.value);
  }
  
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value);
    deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
  }

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
  .section {
    margin-left: 80px;
  }
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
  background-color: #4BB66D; 
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Action button styling */
.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
}

.btn-outline-warning {
  --bs-btn-color: #ffc107;
  --bs-btn-border-color: #ffc107;
}

/* Status toggle switch styling */
.form-check-input:checked {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.form-check-input:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

.form-switch {
  padding-left: 2.5em;
}

.form-switch .form-check-input {
  width: 2em;
  margin-left: -2.5em;
}

/* Delete modal styling (same as category page) */
.modal-dialog.delete-modal-top { 
  align-items: flex-start; 
  margin-top: 50px; 
  height: auto; 
}

.btn-warning {
  color: #212529 !important;
  background-color: #ffc107 !important;
  border-color: #ffc107 !important;
}

.btn-warning:hover {
  background-color: #e0a800 !important;
  border-color: #e0a800 !important;
}
</style>