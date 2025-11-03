<template>
  <Navbar/>
  <MasterAdminSidebar/>
  <div class="template-page section">
    <h2 class="section-title">Resource Templates</h2>
    <div class="page-header">
      <div class="search-box">
        <i class="bi bi-search"></i>
        <input
          type="text"
          class="form-control"
          placeholder="Search Templates..."
          v-model="searchQuery"
        />
      </div>
      <button class="btn btn-success add-new-btn" @click="openAddModal">
        <i class="bi bi-plus-circle"></i> Add New Template
      </button>
    </div>
    
    <div class="content-card table-card">
      <div class="card-header">
        <i class="bi bi-bar-chart-line"></i>
        <h5 class="mb-0">Resource Template List</h5>
      </div>

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
                <button
                  class="btn btn-sm btn-link text-primary"
                  @click="openEditModal(template)"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
                <button
                  class="btn btn-sm btn-link text-danger"
                  @click="confirmDelete(template.id)"
                >
                  <i class="bi bi-trash"></i>
                </button>
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

    <div
      v-if="showDeleteModal"
      class="modal-overlay"
      @click.self="cancelDelete"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center py-5">
            <div class="icon-wrapper mb-4">
              <i class="bi bi-exclamation-triangle text-warning"></i>
            </div>
            <h5 class="mb-3">Are you sure want to delete that template?</h5>
            <div class="d-flex justify-content-center gap-3 mt-4">
              <button
                class="btn btn-success px-4"
                @click="
                  showPermanentDeleteModal = true;
                  showDeleteModal = false;
                "
              >
                Yes
              </button>
              <button class="btn btn-secondary px-4" @click="cancelDelete">
                No
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showPermanentDeleteModal"
      class="modal-overlay"
      @click.self="cancelPermanentDelete"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center py-5">
            <div class="icon-wrapper mb-4">
              <i class="bi bi-trash text-danger"></i>
            </div>
            <h5 class="mb-3">Permanently Delete?</h5>
            <p class="text-muted">This action cannot be undone.</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
              <button class="btn btn-danger px-4" @click="deletePermanently">
                Confirm
              </button>
              <button
                class="btn btn-secondary px-4"
                @click="cancelPermanentDelete"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showFormModal"
      class="modal-overlay form-modal-overlay"
      @click.self="closeFormModal"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ selectedTemplate ? 'Edit Template' : 'Add New Template' }}
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="closeFormModal"
            ></button>
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
                <select class="form-select" v-model="formData.categoryName">
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
                      />
                      <input
                        type="text"
                        class="form-control"
                        placeholder="Example value will go here"
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
                      />
                    </div>

                    <div v-if="field.type === 'photo'" class="photo-upload-area">
                      <i class="bi bi-image"></i>
                      <p>Drag and Drop / Click to Upload</p>
                      <small>Upload Photo (Field Name: {{ field.label }})</small>
                      <input type="hidden" v-model="field.label" />
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
              <i class="bi bi-save"></i> Save
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
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
const showDeleteModal = ref(false);
const showPermanentDeleteModal = ref(false);
const showFormModal = ref(false);
const selectedTemplate = ref<Template | null>(null);
const templateToDelete = ref<number | null>(null);
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

// --- Delete Logic ---
const confirmDelete = (id: number) => {
  templateToDelete.value = id;
  showDeleteModal.value = true;
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  templateToDelete.value = null;
};

const cancelPermanentDelete = () => {
  showPermanentDeleteModal.value = false;
  templateToDelete.value = null;
};

const deletePermanently = () => {
  if (templateToDelete.value !== null) {
    templates.value = templates.value.filter(
      (t) => t.id !== templateToDelete.value
    );
    showPermanentDeleteModal.value = false;
    templateToDelete.value = null;
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
  } else {
    resetFormData();
  }
});


const openAddModal = () => {
  selectedTemplate.value = null;
  resetFormData();
  showFormModal.value = true;
};

const openEditModal = (template: Template) => {
  selectedTemplate.value = template;
  // Data load is handled by the watch function above
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
  selectedTemplate.value = null;
  resetFormData();
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
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Layout & Page Title Styles (Consistent with Category Page)
|--------------------------------------------------------------------------
*/
.template-page.section {
  animation: fadeIn 0.3s ease;
  padding: 20px;
  /* Added margin-left for sidebar integration */
  margin-left: 260px; 
}

@media (max-width: 768px) {
  .template-page.section {
    margin-left: 80px; /* Collapsed sidebar width */
  }
}

.section-title {
  color: #1e4449; /* Dark Teal color */
  font-weight: 600;
  margin-bottom: 24px;
}

/*
|--------------------------------------------------------------------------
| Header, Search, and Add Button Styles
|--------------------------------------------------------------------------
*/
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  gap: 20px;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-box input {
  padding-left: 45px;

}

/* Custom Add New Button Style (Consistent with Category Page) */
.btn-success.add-new-btn {
  border-radius: 25px;
  padding: 10px 25px;
  background-color: #4BB66D; /* Green Success Color */
  border-color: #4BB66D;
}
.btn-success.add-new-btn:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}


/*
|--------------------------------------------------------------------------
| Table & Card Styles
|--------------------------------------------------------------------------
*/
.content-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background-color: #f8f9fa;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #dee2e6;
}

.card-header i {
  font-size: 24px;
}

.table {
  margin-bottom: 0;
}

.table thead th {
  background-color: #f8f9fa;
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
  padding: 15px;
}

.table tbody td {
  padding: 15px;
  vertical-align: middle;
}

/*
|--------------------------------------------------------------------------
| Modal General Styles
|--------------------------------------------------------------------------
*/
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-dialog {
  max-width: 500px; 
  width: 90%;
}

.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.icon-wrapper i {
  font-size: 72px;
}

.btn {
  min-width: 100px;
  border-radius: 8px;
}

/*
|--------------------------------------------------------------------------
| Form Modal Specific Styles
|--------------------------------------------------------------------------
*/
.form-modal-overlay {
  overflow-y: auto;
  padding: 20px;
}

.form-modal-overlay .modal-dialog {
  max-width: 900px; 
  width: 100%;
  margin: auto;
}

.form-modal-overlay .modal-content {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 20px;
  border-radius: 12px 12px 0 0;
}

.modal-body {
  padding: 30px;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 20px;
  display: flex;
  justify-content: flex-end;
}

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

.btn-close {
  background-color: transparent;
  border: none;
  font-size: 24px;
  opacity: 0.5;
}

.btn-close:hover {
  opacity: 1;
}

/*
|--------------------------------------------------------------------------
| Media Queries (Responsiveness)
|--------------------------------------------------------------------------
*/
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    max-width: 100%;
  }

  .form-modal-overlay .modal-dialog {
    max-width: 95%;
  }

  .field-buttons {
    flex-direction: column;
  }

  .field-buttons .btn {
    width: 100%;
  }
}
</style>