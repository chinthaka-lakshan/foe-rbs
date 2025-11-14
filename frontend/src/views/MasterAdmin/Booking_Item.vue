<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="booking-items-page section">
    <h2 class="section-title">Booking Items Management</h2>
    
    <div class="page-header">
      <div class="input-group mb-3 mb-md-0 w-100 w-md-auto me-md-3" style="max-width: 300px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input
          type="text"
          class="form-control"
          placeholder="Search Booking Items..."
          v-model="searchTerm"
        />
      </div>
      <button
        @click="openAddItemModal"
        class="btn btn-success add-new-btn" 
      >
        <i class="bi bi-plus-circle me-2"></i>Add Booking Item
      </button>
    </div>
    
    <div class="table-card">
      <h5 class="mb-3">Booking Item List</h5>

      <div v-if="loading" class="text-center py-5 text-muted">Loading booking items...</div>

      <div v-else-if="filteredItems.length === 0" class="text-center py-5 text-muted">
        {{ searchTerm ? 'No items found matching your search.' : 'No booking items yet. Add your first item!' }}
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Description</th>
              <th>Price (Rs./hr)</th>
              <th>Quantity</th>
              <th>Availability</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filteredItems" :key="item.id">
              <td>{{ item.id }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.description }}</td>
              <td>Rs. {{ item.price_per_hour.toFixed(2) }}</td>
              <td>{{ item.quantity }}</td>
              <td>
                <span :class="item.available ? 'text-success' : 'text-danger'" class="fw-medium">
                    <i :class="item.available ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'" class="me-1"></i>
                    {{ item.available ? 'Available' : 'Unavailable' }}
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button
                    @click="openEditItemModal(item)"
                    class="btn btn-outline-primary"
                    title="Edit"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button
                    @click="openDeleteConfirmation(item)"
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

  <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true" ref="itemModalRef">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="itemModalLabel">{{ isEditMode ? 'Edit Booking Item' : 'Add New Booking Item' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSave">
            
            <div class="mb-3">
              <label for="itemName" class="form-label">Booking Item Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                id="itemName"
                placeholder="Enter item name (e.g., Tennis Racket)"
                v-model="modalData.name"
                required
              >
            </div>
            
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea
                class="form-control"
                id="description"
                rows="2"
                placeholder="Brief description of the item"
                v-model="modalData.description"
              ></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="pricePerHour" class="form-label">Price (Rs./hr) <span class="text-danger">*</span></label>
                <input
                  type="number"
                  class="form-control"
                  id="pricePerHour"
                  placeholder="e.g., 50.00"
                  v-model.number="modalData.price_per_hour"
                  min="0"
                  step="0.01"
                  required
                >
              </div>
              <div class="col-md-4 mb-3">
                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                <input
                  type="number"
                  class="form-control"
                  id="quantity"
                  placeholder="e.g., 10"
                  v-model.number="modalData.quantity"
                  min="1"
                  step="1"
                  required
                >
              </div>
              <div class="col-md-4 mb-3 d-flex align-items-center pt-md-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="itemAvailability" v-model="modalData.available">
                  <label class="form-check-label" for="itemAvailability">Currently Available</label>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-success" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update Item' : 'Save Item') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Are you sure you want to delete the item **{{ itemToDelete?.name }}**?</p>
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
                <p class="mb-0">This action will **permanently delete** the item **{{ itemToDelete?.name }}**. Are you sure?</p>
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
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- TYPES AND MOCK DATA ---

interface BookingItem {
  id: number;
  name: string;
  description: string;
  price_per_hour: number;
  quantity: number;
  available: boolean;
}

// Initial data source (simulating database records)
const initialBookingItems: BookingItem[] = [
  { id: 1, name: 'Tennis Racket', description: 'Standard adult size racket.', price_per_hour: 50.00, quantity: 15, available: true },
  { id: 2, name: 'Basketball', description: 'Size 7, official weight.', price_per_hour: 40.00, quantity: 10, available: true },
  { id: 3, name: 'Projector', description: 'High definition short-throw projector.', price_per_hour: 150.00, quantity: 3, available: false },
  { id: 4, name: 'Microphone (Wireless)', description: 'Lapel mic for presentations.', price_per_hour: 75.00, quantity: 5, available: true },
];

// --- STATE ---

const items = ref<BookingItem[]>([]); 
const searchTerm = ref('');
const loading = ref(true);
const saving = ref(false);

// Modal state
const isEditMode = ref(false);
const modalData = ref<Partial<BookingItem>>({
  name: '',
  description: '',
  price_per_hour: 0.00,
  quantity: 1,
  available: true,
});
const itemToDelete = ref<BookingItem | null>(null);

// NEW STATE FOR TWO-STEP DELETE
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Bootstrap Modal References and Instances
const itemModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let itemModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---

const filteredItems = computed(() => {
  const term = searchTerm.value.toLowerCase();
  
  return items.value.filter(
    (item) =>
      item.name.toLowerCase().includes(term) ||
      item.description.toLowerCase().includes(term) ||
      item.id.toString().includes(term)
  );
});

// --- API MOCKING LOGIC (Using initialBookingItems as mock DB) ---

const fetchItems = async () => {
  loading.value = true;
  await new Promise(resolve => setTimeout(resolve, 500)); // Simulate API delay
  
  // Sort by ID and assign to ref
  items.value = [...initialBookingItems].sort((a, b) => a.id - b.id);
    
  loading.value = false;
};

// --- MODAL AND CRUD HANDLERS ---

const resetModalData = () => {
  modalData.value = {
    name: '',
    description: '',
    price_per_hour: 0.00,
    quantity: 1,
    available: true,
  };
};

const openAddItemModal = () => {
  isEditMode.value = false;
  resetModalData();
  itemModalInstance?.show();
};

const openEditItemModal = (item: BookingItem) => {
  isEditMode.value = true;
  modalData.value = { ...item };
  itemModalInstance?.show();
};

const handleSave = async () => {
  if (!modalData.value.name || modalData.value.price_per_hour === undefined || modalData.value.quantity === undefined) {
    alert('Please fill in Item Name, Price, and Quantity.');
    return;
  }
  if (modalData.value.price_per_hour < 0 || modalData.value.quantity < 1) {
    alert('Price cannot be negative, and Quantity must be at least 1.');
    return;
  }

  saving.value = true;
  await new Promise(resolve => setTimeout(resolve, 800)); // Simulate API delay

  try {
    const itemData = {
        name: modalData.value.name.trim(),
        description: modalData.value.description ? modalData.value.description.trim() : '',
        price_per_hour: Number(modalData.value.price_per_hour),
        quantity: Number(modalData.value.quantity),
        available: modalData.value.available !== undefined ? modalData.value.available : true,
    } as BookingItem;

    if (isEditMode.value && modalData.value.id) {
      // UPDATE Logic (Mock)
      const indexInInitial = initialBookingItems.findIndex(c => c.id === modalData.value.id);
      if (indexInInitial !== -1) {
        initialBookingItems[indexInInitial] = { ...initialBookingItems[indexInInitial], ...itemData };
      }
    } else {
      // INSERT Logic (Mock)
      const newId = initialBookingItems.length > 0 ? Math.max(...initialBookingItems.map(c => c.id)) + 1 : 1;
      initialBookingItems.push({ id: newId, ...itemData }); 
    }
    
    await fetchItems(); 
    itemModalInstance?.hide();
  } catch (error) {
    console.error('Failed to save booking item:', error);
    alert('Failed to save booking item. Please try again.');
  } finally {
    saving.value = false;
  }
};

// --- DELETE HANDLERS (Two Steps) ---

const openDeleteConfirmation = (item: BookingItem) => {
  itemToDelete.value = item;
  deleteStep.value = 'confirm'; // Start with the first confirmation step
  deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; // Move to the second, permanent delete confirmation step
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    itemToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset state
};

const handleDelete = async () => {
  // Only proceed if we are on the final confirmation step
  if (deleteStep.value !== 'final' || !itemToDelete.value) return; 
  
  saving.value = true;
  await new Promise(resolve => setTimeout(resolve, 800)); // Simulate API delay

  try {
    // DELETE Logic (Mock)
    const deletedId = itemToDelete.value.id;
    
    // Remove from initial data source (simulated permanent delete)
    const indexInInitial = initialBookingItems.findIndex(c => c.id === deletedId);
    if (indexInInitial !== -1) {
      initialBookingItems.splice(indexInInitial, 1);
    }
    
    await fetchItems();
    deleteModalInstance?.hide();
  } catch (error) {
    console.error('Failed to delete booking item:', error);
    alert('Failed to delete booking item. Please try again.');
  } finally {
    saving.value = false;
    itemToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset step for next time
  }
};

// --- LIFECYCLE ---

onMounted(() => {
  // Initialize Bootstrap Modals
  if (itemModalRef.value) {
    itemModalInstance = new Modal(itemModalRef.value);
    itemModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value);
    // Add listener to reset state if modal is closed via 'X' or backdrop click
    deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
  }
  
  fetchItems();
});
</script>

<style scoped>

/* --- Structure and Layout --- */
.booking-items-page.section {
  animation: fadeIn 0.3s ease;
  padding: 20px;
  margin-left: 260px; /* Standard sidebar width */
}

@media (max-width: 768px) {
  .booking-items-page.section {
    margin-left: 80px; /* Collapsed sidebar width */
  }
  .modal-dialog.modal-lg {
    max-width: 95% !important;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.section-title {
  color: #1e4449; /* Dark Teal color */
  font-weight: 600;
  margin-bottom: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px; 
  gap: 20px;
}
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  .input-group, .add-new-btn {
    width: 100% !important;
    max-width: 100% !important;
  }
}

/* --- Table Styles --- */
.table-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
}

.table thead {
  background: #f8f9fa;
}

.table thead th {
  background-color: #f8f9fa;
  font-weight: 600;
  border-bottom: 1px solid #dee2e6;
  padding: 12px 15px;
}

.table tbody td {
  padding: 12px 15px; 
  vertical-align: middle;
}

/* --- Button Styles --- */
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}
.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}
.btn-success.add-new-btn {
  padding: 10px 20px;
  border-radius: 8px; 
}
.btn-group-sm .btn {
  padding: 0.25rem 0.5rem; 
}
.btn-outline-primary {
  --bs-btn-color: #0d6efd;
  --bs-btn-border-color: #0d6efd;
}
.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
}

/* --- Modal Styles --- */
.modal-content {
  border-radius: 12px;
}
.modal-header {
  border-bottom: 1px solid #dee2e6;
}
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