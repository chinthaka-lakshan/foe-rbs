<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resources</h2>
      <div class="d-flex gap-2 mt-3">
         <button
          class="btn btn-outline-dark-teal btn-sm"
          @click="navigateToAdd_Equipment"
        >
          <i class="bi bi-list-ul me-1"></i>Add Equipment
        </button>
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
          <div class="resource-actions">
            <button 
                class="btn btn-sm btn-action-edit" 
                @click.stop="navigateToEditResource(resource.id)" 
                title="Edit Resource"
            >
              <i class="bi bi-pencil-square"></i>
            </button>
            <button 
                class="btn btn-sm btn-action-delete" 
                @click.stop="openDeleteResourceConfirmation(resource)" 
                title="Delete Resource"
            >
              <i class="bi bi-trash"></i>
            </button>
          </div>

          <div @click="navigateToSingleResource(resource.id)">
            <div class="resource-image">
              <img :src="resource.image || 'https://via.placeholder.com/300x180?text=No+Image'" :alt="resource.name">
            </div>
            <div class="resource-body">
              <h5>{{ resource.name }}</h5>
              <p v-if="resource.location" class="text-muted mb-1 small">{{ resource.location }}</p>
              <p class="text-muted mb-2">{{ resource.category }}</p>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                
                <div class="d-flex align-items-center gap-2">
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

                <button 
                    v-if="resource.status === 'active'"
                    class="btn btn-sm btn-reserve-card" 
                    @click.stop="handleReserveClick(resource.id)"
                >
                    <i class="bi bi-calendar-check me-1"></i> Reserve
                </button>
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

  <div class="modal fade" :class="{ 'show d-block': showDeleteConfirmation }" tabindex="-1" @click.self="handleCancelDeletion" style="background-color: rgba(0,0,0,0.5);" v-if="showDeleteConfirmation">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Are you sure you want to delete the resource **{{ resourceToDelete?.name }}**?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">No</button>
                <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation">Yes</button>
            </div>
        </template>

        <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
                <button type="button" class="btn-close btn-close-white" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">This action will permanently delete the resource {{ resourceToDelete?.name }}. Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleDeleteResource">
                    Confirm
                </button>
            </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
// NOTE: Adjust these paths if necessary
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

// State for modals
const showAddModal = ref(false);
const showTemplateSelectionModal = ref(false); 
const showDeleteConfirmation = ref(false); // NEW: Delete modal visibility
const resourceToDelete = ref<Resource | null>(null); // NEW: Resource selected for deletion
const deleteStep = ref<'confirm' | 'final'>('confirm'); // NEW: Two-step delete state

// Function to load all resources: defaults + user-added
const getCombinedResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    
    // If local storage is not set or empty, return an empty array.
    if (!storedResourcesString || JSON.parse(storedResourcesString).length === 0) {
        return [];
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

// --- NAVIGATION HANDLERS ---

const navigateToAdd_Equipment = () =>{
  router.push('/add_equipment');
}

const navigateToCategories = () =>{
  router.push('/categories');
};

const navigateToTemplates = () =>{
  router.push('/templates');
};

const navigateToSingleResource = (id: number) => {
    router.push(`/resource/${id}`);
};

const navigateToAdd_Custom = () => {
    showAddModal.value = false;
    router.push('/add-resource'); 
};

const navigateToEditResource = (id: number) => {
    router.push({ path: '/add-resource', query: { id: id, mode: 'edit' } });
};

// **NAVIGATION FOR RESERVE BUTTON**
const handleReserveClick = (id: number) => {
    router.push({ path: '/single-resource-booking', query: { resourceId: id } });
};

// --- DELETE HANDLERS ---

const openDeleteResourceConfirmation = (resource: Resource) => {
    resourceToDelete.value = resource;
    deleteStep.value = 'confirm';
    showDeleteConfirmation.value = true;
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final';
};

const handleCancelDeletion = () => {
    showDeleteConfirmation.value = false;
    resourceToDelete.value = null;
    deleteStep.value = 'confirm';
};

const handleDeleteResource = () => {
    if (!resourceToDelete.value) return;

    const index = resources.value.findIndex(r => r.id === resourceToDelete.value!.id);
    if (index !== -1) {
        resources.value.splice(index, 1);
        updateLocalStorage();
    }
    
    handleCancelDeletion();
};

// --- Template Modal Handlers (Unchanged) ---
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
  position: relative; 
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Clicking the main content navigates to single view */
.resource-card > div:not(.resource-actions) {
  cursor: pointer; 
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

/* --- RESERVE BUTTON on Card --- */
.btn-reserve-card {
    background-color: #1e4449;
    color: white;
    border-color: #1e4449;
    font-size: 0.8rem;
    padding: 0.25rem 0.6rem;
    line-height: 1; /* Ensure button height is small */
}
.btn-reserve-card:hover {
    background-color: #fcc300;
    color: #1e4449;
    border-color: #fcc300;
}

/* --- Action Buttons Overlay --- */
.resource-actions {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10; 
  display: flex;
  gap: 5px;
  opacity: 0;
  transition: opacity 0.2s;
}

.resource-card:hover .resource-actions {
  opacity: 1;
}

.btn-action-edit, .btn-action-delete {
  font-size: 0.8rem;
  padding: 0.3rem 0.5rem;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.btn-action-edit {
  color: #0d6efd;
}
.btn-action-edit:hover {
  background-color: #0d6efd;
  color: white;
}

.btn-action-delete {
  color: #dc3545;
}
.btn-action-delete:hover {
  background-color: #dc3545;
  color: white;
}

/* --- Responsive Modal Styles (Delete Modal Top Positioning) --- */
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

.modal.show .modal-dialog {
  transform: none;
}

.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem);
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


/* --- DELETE MODAL STYLES (Ensures consistent width and colors) --- */

.modal-dialog.delete-modal-top {
    align-items: flex-start;
    margin-top: 50px;
    height: auto; 
}

@media (min-width: 576px) {
    .modal-dialog.delete-modal-top {
        max-width: 500px; 
        margin: 1.75rem auto; 
    }
}

.bg-warning { background-color: #ffc107 !important; }
.btn-warning {
    color: #212529 !important;
    background-color: #ffc107 !important;
    border-color: #ffc300 !important;
}
.btn-warning:hover {
    background-color: #e0a800 !important;
    border-color: #e0a800 !important;
}

.bg-danger { background-color: #dc3545 !important; }
.btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}
.btn-close-white {
    filter: invert(1);
}
</style>