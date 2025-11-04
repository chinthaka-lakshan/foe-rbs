<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resources</h2>
      <div class="d-flex gap-2 mt-3">
        <button
          class="btn btn-outline-dark-teal btn-sm"
          @click="navigateToCategories"
        >
          <i class="bi bi-list-ul me-1"></i>Categories
        </button>
        <button
          class="btn btn-outline-dark-teal btn-sm"
          @click="navigateToTemplates"
        >
          <i class="bi bi-file-text me-1"></i>Templates
        </button>
        <button 
          class="btn btn-success btn-sm" 
          @click="showAddModal = true"
        >
          <i class="bi bi-plus-circle me-1"></i>Add New
        </button>
      </div>
    </div>

    <div class="mb-4">
      <div class="row g-3">
        <div class="col-md-8">
          <input
            type="text"
            class="form-control"
            placeholder="Search resources by name..."
            v-model="searchQuery"
          >
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedCategory">
            <option value="">All Categories</option>
            <option value="academic">Academic Space</option>
            <option value="medical">Medical & Health</option>
            <option value="sports">Sports & Recreational</option>
          </select>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div v-for="resource in filteredResources" :key="resource.id" class="col-md-4">
        <div class="resource-card">
          <div class="resource-image">
            <img :src="resource.image || 'https://via.placeholder.com/300x180?text=No+Image'" :alt="resource.name">
          </div>
          <div class="resource-body">
            <h5>{{ resource.name }}</h5>
            <p v-if="resource.location" class="text-muted mb-1 small">{{ resource.location }}</p>
            <p class="text-muted mb-2">{{ resource.category }}</p>

            <div class="d-flex justify-content-between align-items-center">
              <span class="badge" :class="resource.status === 'active' ? 'bg-success' : 'bg-secondary'">
                {{ resource.status }}
              </span>
              <div class="form-check form-switch">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="resource.status === 'active'"
                  @change="toggleResourceStatus(resource.id)"
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" :class="{ 'show d-block': showAddModal }" tabindex="-1" @click.self="showAddModal = false" style="background-color: rgba(0,0,0,0.5);" v-if="showAddModal">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Resource</h5>
          <button type="button" class="btn-close" @click="showAddModal = false"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-3">How would you like to create the resource?</p>
          <div class="d-grid gap-2">
            <button class="btn btn-outline-dark-teal" @click="navigateToAdd_Custom">
              <i class="bi bi-file-earmark-plus me-2"></i>Custom Resource
            </button>
            <button class="btn btn-outline-dark-teal" @click="navigateToAdd_Template">
              <i class="bi bi-layout-text-window-reverse me-2"></i>From Template
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// Define the structure for a resource
interface Resource {
    id: number;
    name: string;
    location?: string;
    category: string;
    assignee?: string;
    description?: string;
    status: 'active' | 'inactive';
    image: string;
}

const searchQuery = ref('');
const selectedCategory = ref('');
const router = useRouter();

// New state for modal visibility
const showAddModal = ref(false);

// --- Default Resources (Unchanged) ---
const DEFAULT_RESOURCES: Resource[] = [
    { id: 1, name: 'Conference Room A', category: 'Academic Space', status: 'active', image: 'https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 2, name: 'Sports Hall', category: 'Sports & Recreational', status: 'active', image: 'https://images.pexels.com/photos/260024/pexels-photo-260024.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 3, name: 'Library Study Room', category: 'Academic Space', status: 'inactive', image: 'https://images.pexels.com/photos/2041540/pexels-photo-2041540.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 4, name: 'Medical Lab', category: 'Medical & Health', status: 'active', image: 'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 5, name: 'Basketball Court', category: 'Sports & Recreational', status: 'active', image: 'https://images.pexels.com/photos/1752757/pexels-photo-1752757.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 6, name: 'Lecture Hall B', category: 'Academic Space', status: 'active', image: 'https://images.pexels.com/photos/267885/pexels-photo-267885.jpeg?auto=compress&cs=tinysrgb&w=300' },
];

// Function to load all resources: defaults + user-added
const getCombinedResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    
    // If local storage is empty or not initialized, set it to the defaults
    if (!storedResourcesString || JSON.parse(storedResourcesString).length === 0) {
        localStorage.setItem('resources', JSON.stringify(DEFAULT_RESOURCES));
        return DEFAULT_RESOURCES;
    }
    
    return JSON.parse(storedResourcesString);
};

const resources = ref<Resource[]>(getCombinedResources());

// Function to update local storage
const updateLocalStorage = () => {
    localStorage.setItem('resources', JSON.stringify(resources.value));
};

const filteredResources = computed(() => {
  return resources.value.filter(resource => {
    const matchesSearch = resource.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesCategory = !selectedCategory.value || resource.category.toLowerCase().includes(
        selectedCategory.value === 'academic' ? 'academic space' : 
        selectedCategory.value === 'medical' ? 'medical & health' : 
        selectedCategory.value === 'sports' ? 'sports & recreational' : 
        ''
    );
    return matchesSearch && matchesCategory;
  });
});

const toggleResourceStatus = (id: number) => {
  const resource = resources.value.find(r => r.id === id);
  if (resource) {
    resource.status = resource.status === 'active' ? 'inactive' : 'active';
    updateLocalStorage();
  }
};

const navigateToCategories = () =>{
  router.push('/categories');
};

const navigateToTemplates = () =>{
  router.push('/templates');
};

// Updated: Navigate to Add Custom Resource (old Add New page)
const navigateToAdd_Custom = () => {
    showAddModal.value = false; // Close the modal
    router.push('/add-resource'); 
};

// New: Navigate to Add Resource From Template
const navigateToAdd_Template = () => {
    showAddModal.value = false; // Close the modal
    // Assuming a route for adding from a template exists, e.g., '/add-resource-from-template'
    // You can adjust this route path as needed.
    router.push('/add-template'); 
};
</script>

<style scoped>
/* Existing styles */
.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #fcc300;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #fcc300;
}

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

.resource-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.resource-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.resource-image {
  height: 180px;
  overflow: hidden;
}

.resource-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.resource-body {
  padding: 16px;
}

.resource-body h5 {
  margin-bottom: 8px;
  color: #1e4449;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.form-check-input:checked {
  background-color: #fcc300;
  border-color: #fcc300;
}

/* Modal styles to ensure it looks right even without full Bootstrap JS */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1050;
  width: 100%;
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  outline: 0;
}

.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem);
}

@media (min-width: 576px) {
  .modal-dialog-centered {
    min-height: calc(100% - 3.5rem);
  }
}

.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  color: var(--bs-body-color);
  pointer-events: auto;
  background-color: var(--bs-modal-bg);
  background-clip: padding-box;
  border: var(--bs-modal-border-width) solid var(--bs-modal-border-color);
  border-radius: var(--bs-modal-border-radius);
  outline: 0;
}
</style>