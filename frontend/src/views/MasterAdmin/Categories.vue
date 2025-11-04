<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="category-page section"> <h2 class="section-title">Resource Categories</h2>
    
    <div class="page-header">
      <div class="input-group mb-3 mb-md-0 w-100 w-md-auto me-md-3" style="max-width: 300px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input
          type="text"
          class="form-control"
          placeholder="Search Category..."
          v-model="searchTerm"
        />
      </div>
      <button
        @click="openAddCategoryModal"
        class="btn btn-success add-new-btn" 
      >
        <i class="bi bi-plus-circle me-2"></i>Add New Category
      </button>
    </div>
    <div class="table-card">
      
      <h5 class="mb-3">Category List</h5>

      <div v-if="loading" class="text-center py-5 text-muted">Loading categories...</div>

      <div v-else-if="filteredCategories.length === 0" class="text-center py-5 text-muted">
        {{ searchTerm ? 'No categories found matching your search.' : 'No categories yet. Add your first category!' }}
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Category List Number</th>
              <th>Category Name</th>
              <th>Description</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in filteredCategories" :key="category.id">
              <td>{{ category.category_list_number }}</td>
              <td>{{ category.category_list }}</td>
              <td>{{ category.description }}</td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button
                    @click="openEditCategoryModal(category)"
                    class="btn btn-outline-primary"
                    title="Edit"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button
                    @click="openDeleteConfirmation(category)"
                    class="btn btn-outline-danger ms-1"
                    title="Delete"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" ref="categoryModalRef">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalLabel">{{ isEditMode ? 'Edit Category' : 'Add New Category' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSave">
            <div class="mb-3">
              <label for="categoryList" class="form-label">Category Name</label>
              <input
                type="text"
                class="form-control"
                id="categoryList"
                placeholder="Enter category name (e.g., Academic Space)"
                v-model="modalData.category_list"
                required
              >
            </div>
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <input
                type="text"
                class="form-control"
                id="description"
                placeholder="Enter description (e.g., Lecture Halls)"
                v-model="modalData.description"
                required
              >
            </div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-success" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update' : 'Save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
    <div class="modal-dialog"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Are you sure you want to delete the category **{{ categoryToDelete?.category_list }}**?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">No</button>
                <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation">Yes</button>
            </div>
        </template>

        <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">This action will **permanently delete** the category **{{ categoryToDelete?.category_list }}**. Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ saving ? 'Deleting...' : 'Confirm' }}
                </button>
            </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap'; 
// Assuming these components are available in your structure
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- TYPES AND MOCK DATA ---

interface Category {
  id: number;
  category_list_number: number;
  category_list: string;
  description: string;
}

// Initial data source (simulating database records)
const initialCategories: Category[] = [
  { id: 1, category_list_number: 1, category_list: 'Academic Space', description: 'Lecture Halls' },
  { id: 2, category_list_number: 2, category_list: 'Residential Space', description: 'Dorms and Hostels' },
  { id: 3, category_list_number: 3, category_list: 'Administrative', description: 'Offices and Records' },
  { id: 4, category_list_number: 4, category_list: 'Recreational', description: 'Gyms and Fields' },
];

// --- STATE ---

const categories = ref<Category[]>([]); 
const searchTerm = ref('');
const loading = ref(true);
const saving = ref(false);

// Modal state
const isEditMode = ref(false);
const modalData = ref<Partial<Category>>({
  category_list: '',
  description: '',
});
const categoryToDelete = ref<Category | null>(null);

// NEW STATE FOR TWO-STEP DELETE
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Bootstrap Modal References and Instances
const categoryModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let categoryModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---

const filteredCategories = computed(() => {
  const term = searchTerm.value.toLowerCase();
  
  const filtered = categories.value.filter(
    (category) =>
      category.category_list.toLowerCase().includes(term) ||
      category.description.toLowerCase().includes(term)
  );

  return filtered.sort((a, b) => a.category_list_number - b.category_list_number);
});

// --- API MOCKING LOGIC ---

const fetchCategories = async () => {
  loading.value = true;
  await new Promise(resolve => setTimeout(resolve, 500)); // Simulate API delay
  
  // 1. Sort the "database" by ID (or creation time)
  const sortedInitial = initialCategories.sort((a, b) => a.id - b.id);
  
  // 2. Map and re-assign sequential category_list_number
  categories.value = sortedInitial.map((c, index) => ({ 
    ...c, 
    category_list_number: index + 1 
  }));
    
  loading.value = false;
};

// --- MODAL AND CRUD HANDLERS ---

const resetModalData = () => {
  modalData.value = {
    category_list: '',
    description: '',
  };
};

const openAddCategoryModal = () => {
  isEditMode.value = false;
  resetModalData();
  categoryModalInstance?.show();
};

const openEditCategoryModal = (category: Category) => {
  isEditMode.value = true;
  modalData.value = {
    id: category.id,
    category_list: category.category_list,
    description: category.description,
  };
  categoryModalInstance?.show();
};

const handleSave = async () => {
  if (!modalData.value.category_list || !modalData.value.description) {
    alert('Please fill in both Category Name and Description.');
    return;
  }

  saving.value = true;
  await new Promise(resolve => setTimeout(resolve, 800)); // Simulate API delay

  try {
    if (isEditMode.value && modalData.value.id) {
      // UPDATE Logic (Mock)
      const indexInInitial = initialCategories.findIndex(c => c.id === modalData.value.id);
      if (indexInInitial !== -1) {
        initialCategories[indexInInitial].category_list = modalData.value.category_list.trim();
        initialCategories[indexInInitial].description = modalData.value.description.trim();
      }
    } else {
      // INSERT Logic (Mock)
      const newId = initialCategories.length > 0 ? Math.max(...initialCategories.map(c => c.id)) + 1 : 1;
      const newCategory: Category = {
        id: newId,
        category_list_number: 0,
        category_list: modalData.value.category_list.trim(),
        description: modalData.value.description.trim(),
      };
      
      initialCategories.push(newCategory); 
    }
    
    await fetchCategories(); 
    categoryModalInstance?.hide();
  } catch (error) {
    console.error('Failed to save category:', error);
    alert('Failed to save category. Please try again.');
  } finally {
    saving.value = false;
  }
};

// --- DELETE HANDLERS (Modified for Two Steps) ---

const openDeleteConfirmation = (category: Category) => {
  categoryToDelete.value = category;
  deleteStep.value = 'confirm'; // Start with the first confirmation step
  deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    // Logic for "Yes" on the first popup
    deleteStep.value = 'final'; // Move to the second, permanent delete confirmation step
};

const handleCancelDeletion = () => {
    // Logic for "No" (Step 1) and "Cancel" (Step 2)
    deleteModalInstance?.hide();
    categoryToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset state
};

const handleDelete = async () => {
  // Only proceed if we are on the final confirmation step
  if (deleteStep.value !== 'final' || !categoryToDelete.value) return; 
  
  saving.value = true;
  await new Promise(resolve => setTimeout(resolve, 800)); // Simulate API delay

  try {
    // DELETE Logic (Mock) - Triggered by "Confirm" on Step 2
    const deletedId = categoryToDelete.value.id;
    
    // Remove from initial data source (simulated permanent delete)
    const indexInInitial = initialCategories.findIndex(c => c.id === deletedId);
    if (indexInInitial !== -1) {
      initialCategories.splice(indexInInitial, 1);
    }
    
    await fetchCategories();
    deleteModalInstance?.hide();
  } catch (error) {
    console.error('Failed to delete category:', error);
    alert('Failed to delete category. Please try again.');
  } finally {
    saving.value = false;
    categoryToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset step for next time
  }
};

// --- LIFECYCLE ---

onMounted(() => {
  // Initialize Bootstrap Modals
  if (categoryModalRef.value) {
    categoryModalInstance = new Modal(categoryModalRef.value);
    categoryModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value);
    // Add listener to reset state if modal is closed via 'X' or backdrop click
    deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
  }
  
  fetchCategories();
});
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Layout & Page Title Styles 
|--------------------------------------------------------------------------
*/
.section {
  animation: fadeIn 0.3s ease;
  padding: 20px; /* Added padding for consistency */
  margin-left: 260px; /* Standard sidebar width */
}

@media (max-width: 768px) {
  .section {
    margin-left: 80px; /* Collapsed sidebar width */
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
  color: #1e4449; /* Dark Teal color */
  font-weight: 600;
  margin-bottom: 24px;
}

/*
|--------------------------------------------------------------------------
| NEW: Header, Search, and Add Button Styles (Matching Template Page)
|--------------------------------------------------------------------------
*/
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px; /* Consistent spacing with Template Page */
  gap: 20px;
}

/* Custom Success Button Color */
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.btn-success.add-new-btn {
  /* Ensure the button matches the Template Page's style */
  padding: 10px 20px;
  border-radius: 8px; 
}


/* Table card structure */
.table-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); /* Soft shadow */
}

.table thead {
  background: #f8f9fa; /* Light grey header background */
}

.table thead th {
  background-color: #f8f9fa; /* Ensure consistency */
  font-weight: 600;
  border-bottom: 1px solid #dee2e6; /* Standard border */
  padding: 12px 15px; /* Added padding for better look, matching Template */
}

.table tbody td {
  padding: 12px 15px; /* Added padding, matching Template */
  vertical-align: middle;
}

/* Ensure outline buttons are visible */
.btn-outline-primary {
  --bs-btn-color: #0d6efd;
  --bs-btn-border-color: #0d6efd;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
}

/* Action button sizing */
.btn-group-sm .btn {
  padding: 0.25rem 0.5rem; /* Standard sm button padding, matching Template */
}


/* Custom style for the first step button (Modal) */
.btn-warning {
    color: #212529 !important;
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
}
.btn-warning:hover {
    background-color: #e0a800 !important;
    border-color: #e0a800 !important;
}

/* Modal styling (Already consistent, just ensuring defaults are here) */
.modal-dialog.modal-lg {
    max-width: 900px !important;
}

/*
|--------------------------------------------------------------------------
| Media Queries (Responsiveness) - Matching Template Page
|--------------------------------------------------------------------------
*/
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  /* Ensuring search and button take full width on mobile */
  .input-group {
    width: 100% !important;
    max-width: 100% !important;
  }

  .btn-success.add-new-btn {
    width: 100%;
  }

  /* Modal sizing for mobile */
  .modal-dialog.modal-lg {
    max-width: 95% !important;
  }
}
</style>