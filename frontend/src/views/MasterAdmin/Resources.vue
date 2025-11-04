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
        <div class="resource-card" @click="navigateToSingleResource(resource.id)">
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
              <div class="form-check form-switch" @click.stop>
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
            <button class="btn btn-outline-dark-teal" @click="openTemplateSelectionModal">
              <i class="bi bi-layout-text-window-reverse me-2"></i>From Template
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade" :class="{ 'show d-block': showTemplateSelectionModal }" tabindex="-1" @click.self="showTemplateSelectionModal = false" style="background-color: rgba(0,0,0,0.5);" v-if="showTemplateSelectionModal">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Select Template Category</h5>
          <button type="button" class="btn-close" @click="showTemplateSelectionModal = false"></button>
        </div>
        <div class="modal-body">
          <p class="mb-3 text-center">Which type of resource template do you need?</p>
          <div class="d-grid gap-2">
            <button class="btn btn-outline-dark-teal" @click="navigateToTemplateCategory('academic')">
              <i class="bi bi-book me-2"></i>Academic Space
            </button>
            <button class="btn btn-outline-dark-teal" @click="navigateToTemplateCategory('it')">
              <i class="bi bi-laptop me-2"></i>IT Space
            </button>
            <button class="btn btn-outline-dark-teal" @click="navigateToTemplateCategory('medical')">
              <i class="bi bi-bandaid me-2"></i>Medical & Health
            </button>
            <button class="btn btn-outline-dark-teal" @click="navigateToTemplateCategory('sports')">
              <i class="bi bi-trophy me-2"></i>Sports & Recreational
            </button>
            <button class="btn btn-outline-dark-teal" @click="navigateToTemplateCategory('cultural')">
              <i class="bi bi-camera me-2"></i>Cultural
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

// State for both modals
const showAddModal = ref(false);
const showTemplateSelectionModal = ref(false); 

// --- Default Resources (Unchanged) ---
const DEFAULT_RESOURCES: Resource[] = [
    { id: 1, name: 'Conference Room A', location: 'Main Building, Lvl 3', category: 'Academic Space', assignee: 'Dr. Jane Doe', description: 'Large conference room equipped with video conferencing tools.', status: 'active', image: 'https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 2, name: 'Sports Hall', location: 'Gymnasium Complex', category: 'Sports & Recreational', assignee: 'Mr. John Smith', description: 'Large multi-purpose sports hall for basketball, volleyball, etc.', status: 'active', image: 'https://images.pexels.com/photos/260024/pexels-photo-260024.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 3, name: 'Library Study Room', location: 'Central Library, Zone C', category: 'Academic Space', assignee: 'Ms. Emily Brown', description: 'Quiet small group study room for up to 6 people.', status: 'inactive', image: 'https://images.pexels.com/photos/2041540/pexels-photo-2041540.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 4, name: 'Medical Lab', location: 'Health Wing, Ground Floor', category: 'Medical & Health', assignee: 'Dr. Alan Turing', description: 'Fully equipped clinical testing laboratory.', status: 'active', image: 'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 5, name: 'Basketball Court', location: 'Outdoor Courts', category: 'Sports & Recreational', assignee: 'Mr. John Smith', description: 'Full-size outdoor basketball court with floodlights.', status: 'active', image: 'https://images.pexels.com/photos/1752757/pexels-photo-1752757.jpeg?auto=compress&cs=tinysrgb&w=300' },
    { id: 6, name: 'Lecture Hall B', location: 'Faculty of Eng, Block B', category: 'Academic Space', assignee: 'Dr. Jane Doe', description: 'Medium lecture hall with seating for 100 students.', status: 'active', image: 'https://images.pexels.com/photos/267885/pexels-photo-267885.jpeg?auto=compress&cs=tinysrgb&w=300' },
];

// Function to load all resources: defaults + user-added
const getCombinedResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    
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

// New: Navigate to a single resource page
const navigateToSingleResource = (id: number) => {
    router.push(`/resource/${id}`);
};

// --- Modal Navigation Functions ---
const navigateToAdd_Custom = () => {
    showAddModal.value = false;
    router.push('/add-resource'); 
};

const openTemplateSelectionModal = () => {
    showAddModal.value = false;
    showTemplateSelectionModal.value = true;
};

const navigateToTemplateCategory = (categoryKey: string) => {
    showTemplateSelectionModal.value = false;
    router.push({ path: '/use-template', query: { category: categoryKey } }); 
};
</script>

<style scoped>
/* --- General Section & Sidebar Layout --- */
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px; /* Space for desktop sidebar */
  padding: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 80px; /* Space for mobile/collapsed sidebar */
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

/* --- Button Styling --- */
.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #fcc300;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #fcc300;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* --- Resource Card Styling --- */
.resource-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer; /* Added cursor to indicate clickability */
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

/* Status switch color */
.form-check-input:checked {
  background-color: #fcc300;
  border-color: #fcc300;
}

/* --- Responsive Modal Styles --- */
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
  opacity: 0;
  transition: opacity 0.15s linear;
}

.modal.show {
  opacity: 1;
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 0.5rem; 
  pointer-events: none;
  transition: transform 0.3s ease-out;
  transform: translate(0, -50px);
}

.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem);
}

.modal.show .modal-dialog {
  transform: none;
}

.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  pointer-events: auto;
  background-color: #ffffff; 
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 0.3rem;
  outline: 0;
}

@media (min-width: 576px) {
    .modal-dialog {
        max-width: 300px; 
        margin: 1.75rem auto;
    }
    .modal-dialog-centered {
        min-height: calc(100% - 3.5rem);
    }
}
</style>