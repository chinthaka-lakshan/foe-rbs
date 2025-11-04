<template>
  <Navbar/>
  <MasterAdminSidebar/>
  <div class="template-page section">
    <h2 class="section-title">Resource Templates</h2>
    <div class="page-header">
      <div class="input-group mb-3 mb-md-0 w-100 w-md-auto me-md-3" style="max-width: 300px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input
            type="text"
            class="form-control"
            placeholder="Search Templates..."
            v-model="searchQuery"
          />
      </div>

      <button class="btn btn-success add-new-btn" @click="openAddModal">
        <i class="bi bi-plus-circle me-2"></i> Add New Template
      </button>
    </div>
    
    <div class="table-card">
      <h5 class="mb-3">Resource Template List</h5> 

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Template ID</th>
              <th>Category Name</th>
              <th>Description</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="template in filteredTemplates" :key="template.id">
              <td>{{ template.id }}</td>
              <td>{{ template.categoryName }}</td>
              <td>{{ template.description }}</td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button
                    class="btn btn-outline-primary"
                    title="Edit"
                    @click="openEditModal(template)"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button
                    class="btn btn-outline-danger ms-1"
                    title="Delete"
                    @click="openDeleteConfirmation(template)"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredTemplates.length === 0">
              <td colspan="4" class="text-center text-muted py-4">
                No templates found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="modal fade" id="templateFormModal" tabindex="-1" aria-labelledby="templateFormModalLabel" aria-hidden="true" ref="templateModalRef">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="templateFormModalLabel">
              {{ isEditMode ? 'Edit Template' : 'Add New Template' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="row mb-4">
              <div class="col-md-6 mb-3 mb-md-0">
                <div class="field-buttons">
                  <button
                    class="btn btn-outline-primary btn-sm"
                    @click="addField('input')"
                    :class="{ active: activeFieldType === 'input' }"
                  >
                    <i class="bi bi-plus-square"></i> Input Field
                  </button>
                  <button
                    class="btn btn-outline-primary btn-sm"
                    @click="addField('checkbox')"
                    :class="{ active: activeFieldType === 'checkbox' }"
                  >
                    <i class="bi bi-check-square"></i> Check Box
                  </button>
                  <button
                    class="btn btn-outline-primary btn-sm"
                    @click="addField('photo')"
                    :class="{ active: activeFieldType === 'photo' }"
                  >
                    <i class="bi bi-image"></i> Add Photo
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <select class="form-select" v-model="formData.categoryName" required>
                  <option value="" disabled>Select Category *</option>
                  <option v-for="cat in categories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>
            </div>

            <div class="template-builder">
              <div
                v-for="(field, index) in formData.fields"
                :key="index"
                class="field-item mb-3"
              >
                <div class="d-flex gap-2 align-items-start">
                  <div class="flex-grow-1">
                    <div v-if="field.type === 'input'">
                      <input
                        type="text"
                        class="form-control mb-2"
                        placeholder="Enter Input Field Name"
                        v-model="field.label"
                        required
                      />
                      <input
                        type="text"
                        class="form-control"
                        placeholder="Enter value"
                        disabled
                      />
                    </div>

                    <div
                      v-if="field.type === 'checkbox'"
                      class="d-flex align-items-center gap-3"
                    >
                      <input type="checkbox" class="form-check-input" disabled />
                      <input
                        type="text"
                        class="form-control"
                        placeholder="Enter Check Box Name"
                        v-model="field.label"
                        required
                      />
                    </div>

                    <div v-if="field.type === 'photo'" class="photo-upload-area">
                      <i class="bi bi-image"></i>
                      <p>Drag and Drop / Click to Upload</p>
                      <small>Upload Photo (Field Name: {{ field.label }})</small>
                      <input
                        type="text"
                        class="form-control mt-2"
                        placeholder="Enter Photo Field Name (e.g., Facility Image)"
                        v-model="field.label"
                        required
                      />
                    </div>
                  </div>
                  <button
                    class="btn btn-sm btn-danger flex-shrink-0"
                    @click="removeField(index)"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>

              <div
                v-if="formData.fields.length === 0"
                class="text-center text-muted py-5"
              >
                <i class="bi bi-inbox" style="font-size: 48px"></i>
                <p class="mt-3">
                  Click buttons above to add fields to your template
                </p>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-success" @click="saveTemplate">
              <i class="bi bi-save me-2"></i> Save
            </button>
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
                  <p class="mb-0">Are you sure you want to delete the template for **{{ templateToDeleteName }}**?</p>
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
                  <p class="mb-0">This action will **permanently delete** the template for **{{ templateToDeleteName }}**. Are you sure?</p>
              </div>
              <div class="modal-footer justify-content-center">
                  <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                  <button type="button" class="btn btn-danger" @click="deletePermanently">
                      Confirm
                  </button>
              </div>
          </template>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Modal } from 'bootstrap'; 
// Assuming these components are available in your structure
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- Interfaces ---
interface Field {
  type: 'input' | 'checkbox' | 'photo';
  label: string;
}

interface Template {
  id: number;
  categoryName: string;
  description: string;
  fields: Field[];
}

interface TemplateData {
  categoryName: string;
  fields: Field[];
}

// --- Template Data & State ---
const searchQuery = ref('');
const isEditMode = ref(false); 
const activeFieldType = ref<string | null>(null);

// Form data for the TemplateFormModal
const formData = ref<TemplateData>({
  categoryName: '',
  fields: [],
});

const templates = ref<Template[]>([
  {
    id: 1,
    categoryName: 'Academic Space',
    description: 'Capacity: 300 seats, Projector Check',
    fields: [
      { type: 'input', label: 'Capacity' },
      { type: 'checkbox', label: 'Projector Check' },
    ],
  },
  {
    id: 2,
    categoryName: 'Sports & Recreation',
    description: 'Equipment count, Photo of Facility',
    fields: [
      { type: 'input', label: 'Equipment count' },
      { type: 'photo', label: 'Photo of Facility' },
    ],
  },
  {
    id: 3,
    categoryName: 'Medical & Health',
    description: 'Treatment package, Notes',
    fields: [
      { type: 'input', label: 'Treatment package' },
      { type: 'input', label: 'Notes' },
    ],
  },
]);

// Categories for the dropdown (Mocked data)
const categories = ref([
  'Academic Space',
  'Sports & Recreation',
  'Medical & Health',
  'Cultural & Event',
  'IT & Digital',
  'Transport',
  'Residential & Accommodation',
]);

// MODAL STATE AND REFS (Matching Category Page)
const templateModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let templateModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

const selectedTemplate = ref<Template | null>(null);
const templateToDelete = ref<number | null>(null);
const templateToDeleteName = computed(() => templates.value.find(t => t.id === templateToDelete.value)?.categoryName || 'this template');
const deleteStep = ref<'confirm' | 'final'>('confirm');


// --- Computed Property for Search ---
const filteredTemplates = computed(() => {
  if (!searchQuery.value) return templates.value;
  const query = searchQuery.value.toLowerCase();
  return templates.value.filter(
    (t) =>
      t.categoryName.toLowerCase().includes(query) ||
      t.description.toLowerCase().includes(query)
  );
});

// --- Delete Logic (Modified to Match Category Page) ---

const openDeleteConfirmation = (template: Template) => {
  templateToDelete.value = template.id;
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
    templateToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset state
};

const deletePermanently = () => {
  // Only proceed if we are on the final confirmation step
  if (deleteStep.value !== 'final' || templateToDelete.value === null) return; 
  
  if (templateToDelete.value !== null) {
    templates.value = templates.value.filter(
      (t) => t.id !== templateToDelete.value
    );
    deleteModalInstance?.hide();
    templateToDelete.value = null;
    deleteStep.value = 'confirm'; // Reset step for next time
  }
};

// --- Form Logic (Add/Edit) ---
const resetFormData = () => {
  formData.value = {
    categoryName: '',
    fields: [],
  };
  activeFieldType.value = null;
};

// Watcher to load data when editing starts
watch(selectedTemplate, (newTemplate) => {
  if (newTemplate) {
    formData.value = {
      categoryName: newTemplate.categoryName || '',
      fields: newTemplate.fields ? [...newTemplate.fields] : [] // Deep copy fields for editing
    }
    isEditMode.value = true;
  } else {
    resetFormData();
    isEditMode.value = false;
  }
});


const openAddModal = () => {
  selectedTemplate.value = null;
  resetFormData();
  templateModalInstance?.show();
};

const openEditModal = (template: Template) => {
  selectedTemplate.value = template;
  // Data load is handled by the watch function above
  templateModalInstance?.show();
};

const closeFormModal = () => {
  templateModalInstance?.hide();
};

const addField = (type: 'input' | 'checkbox' | 'photo') => {
  activeFieldType.value = type;
  const defaultLabel =
    type === 'input'
      ? 'New Input Field'
      : type === 'checkbox'
      ? 'New Check Box'
      : 'Photo Upload';

  formData.value.fields.push({
    type,
    label: defaultLabel,
  });
};

const removeField = (index: number) => {
  formData.value.fields.splice(index, 1);
};

const saveTemplate = () => {
  if (!formData.value.categoryName) {
    alert('Please select a category before saving.');
    return;
  }

  // Generate description from field labels
  const description =
    formData.value.fields.map((f) => f.label).join(', ') || 'No fields defined';

  const templateData = {
    categoryName: formData.value.categoryName,
    description: description,
    fields: formData.value.fields,
  };

  if (selectedTemplate.value) {
    // Edit existing template
    const index = templates.value.findIndex(
      (t) => t.id === selectedTemplate.value!.id
    );
    if (index !== -1) {
      templates.value[index] = {
        ...templateData,
        id: selectedTemplate.value.id,
      };
    }
  } else {
    // Add new template
    const maxId = templates.value.length
      ? Math.max(...templates.value.map((t) => t.id))
      : 0;
    const newId = maxId + 1;
    templates.value.push({ ...templateData, id: newId });
  }

  closeFormModal();
};

// --- LIFECYCLE ---
onMounted(() => {
  // Initialize Bootstrap Modals
  if (templateModalRef.value) {
    templateModalInstance = new Modal(templateModalRef.value);
    // Use the hidden.bs.modal event to reset state
    templateModalRef.value.addEventListener('hidden.bs.modal', () => {
      selectedTemplate.value = null;
      resetFormData();
    });
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value);
    // Add listener to reset state if modal is closed via 'X' or backdrop click
    deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
  }
});
</script>

<style scoped>

.template-page.section {
  animation: fadeIn 0.3s ease;
  padding: 20px;
  /* Standard sidebar width */
  margin-left: 260px; 
}

@media (max-width: 768px) {
  .template-page.section {
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


.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px; 
  gap: 20px;
}

.input-group-text {
  background-color: white;
  border-right: none;
}

.search-box input, .input-group input {
  /* Using standard Bootstrap form-control now */
  padding-left: 10px; /* Reset padding as icon is in input-group-text */
}


.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.btn-success.add-new-btn {
  /* Removed border-radius/padding overrides to use standard btn-success/btn settings */
  /* Keeping only the margin/icon spacing */
  padding: 10px 20px;
  border-radius: 8px; /* Using Bootstrap default look or simplified */
}



/* This class defines the look for the entire table container */
.table-card {
  background: white;
  border-radius: 8px; /* Use 8px to match Category page */
  padding: 24px; 
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
  overflow: hidden;
}

.table thead {
  background: #f8f9fa; /* Light grey header background */
}

.table thead th {
  background-color: #f8f9fa; /* Ensure consistency */
  font-weight: 600;
  border-bottom: 1px solid #dee2e6; /* Standard border */
  padding: 12px 15px; /* Adjust padding for better look, was 15px/15px */
}

.table tbody td {
  padding: 12px 15px; /* Adjust padding to match header if needed */
  vertical-align: middle;
}

/* Action button styles (Unified) */
.btn-group-sm .btn {
  padding: 0.25rem 0.5rem; /* Standard sm button padding */
}

.btn-outline-primary {
  --bs-btn-color: #0d6efd;
  --bs-btn-border-color: #0d6efd;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
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

/* Modal sizing and common styles */
.modal-dialog {
  max-width: 500px;
}

.modal-dialog.modal-lg {
  max-width: 900px !important;
}

.modal-content {
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  border-bottom: 1px solid #dee2e6;
  padding: 20px;
  border-radius: 12px 12px 0 0;
}

.modal-body {
  padding: 30px;
}

.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 20px;
}

/* NEW STYLING TO MOVE DELETE MODAL TO THE TOP */
.modal-dialog.delete-modal-top {
    /* Override standard Bootstrap modal positioning */
    align-items: flex-start; /* Ensures content aligns to the top of the viewport */
    margin-top: 50px; /* Optional: Adds some space from the absolute top */
    /* Ensure modal-dialog has full height to allow flex-start alignment if using display:flex on modal parent */
    height: auto; 
}


/* Field Builder styles (Keeping original design as it's specific to the form) */
.field-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.field-buttons .btn {
  border-radius: 8px;
}

.field-buttons .btn.active {
  background-color: #0d6efd;
  color: white;
}

.template-builder {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  padding: 20px;
  min-height: 300px;
  background-color: #f8f9fa;
}

.field-item {
  background: white;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.photo-upload-area {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  background-color: #f8f9fa;
}

.photo-upload-area i {
  font-size: 48px;
  color: #6c757d;
}


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

  .template-page .add-new-btn {
    width: 100%;
  }

  .modal-dialog.modal-lg {
    max-width: 95% !important;
  }

  .field-buttons {
    flex-direction: column;
  }

  .field-buttons .btn {
    width: 100%;
  }
}
</style>