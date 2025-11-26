<template>
    <Navbar/>
    <MasterAdminSidebar/>
    <div class="template-page section">
        <h2 class="section-title">Resource Templates</h2>
        
        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ successMessage }}
            <button type="button" class="btn-close" @click="successMessage = ''"></button>
        </div>
        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ errorMessage }}
            <button type="button" class="btn-close" @click="errorMessage = ''"></button>
        </div>

        <div class="page-header">
            <div class="input-group mb-3 mb-md-0 w-100 w-md-auto me-md-3" style="max-width: 300px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Search Templates..."
                    v-model="searchQuery"
                    :disabled="isLoading"
                />
            </div>

            <button class="btn btn-success add-new-btn" @click="openAddModal" :disabled="isLoading">
                <i class="bi bi-plus-circle me-2"></i> Add New Template
            </button>
        </div>
        
        <div class="table-card">
            <h5 class="mb-3">Resource Template List</h5> 

            <div v-if="isLoading" class="text-center py-5 text-muted">
                 <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading templates...
            </div>
             <div v-else-if="filteredTemplates.length === 0" class="text-center py-5 text-muted">
                {{ searchQuery ? 'No templates found matching your search.' : 'No templates yet. Add your first template!' }}
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Template ID</th>
                            <th>Category Name</th>
                            <th>Template Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="template in filteredTemplates" :key="template.id">
                            <td>{{ template.id }}</td>
                            <td>{{ template.category?.name || 'N/A' }}</td>
                            <td>{{ template.template_name }}</td>
                            <td>{{ template.description }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        class="btn btn-outline-primary"
                                        title="Edit"
                                        @click="openEditModal(template)"
                                        :disabled="isSaving"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        class="btn btn-outline-danger ms-1"
                                        title="Delete"
                                        @click="openDeleteConfirmation(template)"
                                        :disabled="isSaving"
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
                         <div v-if="modalErrorMessage" class="alert alert-danger">{{ modalErrorMessage }}</div>
                         <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="field-buttons">
                                    <button
                                        class="btn btn-outline-primary btn-sm"
                                        @click="addField('input')"
                                        :class="{ active: activeFieldType === 'input' }"
                                        :disabled="isSaving"
                                    >
                                        <i class="bi bi-plus-square"></i> Input Field
                                    </button>
                                    <button
                                        class="btn btn-outline-primary btn-sm"
                                        @click="addField('checkbox')"
                                        :class="{ active: activeFieldType === 'checkbox' }"
                                        :disabled="isSaving"
                                    >
                                        <i class="bi bi-check-square"></i> Check Box
                                    </button>
                                    <button
                                        class="btn btn-outline-primary btn-sm"
                                        @click="addField('photo')"
                                        :class="{ active: activeFieldType === 'photo' }"
                                        :disabled="isSaving"
                                    >
                                        <i class="bi bi-image"></i> Add Photo
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" v-model="formData.category_id" required :disabled="isSaving">
                                    <option :value="null" disabled>Select Category </option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <small class="text-danger" v-if="validationErrors.category_id">{{ validationErrors.category_id[0] }}</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Template Name</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Template Name"
                                v-model="formData.template_name"
                                required
                                :disabled="isSaving"
                            />
                             <small class="text-danger" v-if="validationErrors.template_name">{{ validationErrors.template_name[0] }}</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Template Description"
                                v-model="formData.description"
                                :disabled="isSaving"
                            />
                            <small class="text-danger" v-if="validationErrors.description">{{ validationErrors.description[0] }}</small>
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
                                            <label class="form-label small text-muted">Field Type: Text Input</label>
                                            <input type="text" class="form-control" placeholder="Value is set by resource creator" disabled/>
                                        </div>
                                        <div v-else-if="field.type === 'checkbox'" class="d-flex align-items-center gap-3">
                                            <input type="checkbox" class="form-check-input" disabled />
                                            <label class="form-label small text-muted">Field Type: Checkbox</label>
                                        </div>
                                        <div v-else-if="field.type === 'photo'" class="photo-upload-area">
                                            <i class="bi bi-image"></i>
                                            <p>Photo Placeholder</p>
                                        </div>
                                        
                                        <input
                                            type="text"
                                            class="form-control mt-2"
                                            :placeholder="field.type === 'photo' ? 'Enter Photo Field Name' : 'Enter Field Name'"
                                            v-model="field.field_name"
                                            required
                                            :disabled="isSaving"
                                        />
                                        <small class="text-danger" v-if="validationErrors[`fields.${index}.field_name`]">{{ validationErrors[`fields.${index}.field_name`][0] }}</small>

                                    </div>
                                    <button
                                        class="btn btn-sm btn-danger flex-shrink-0"
                                        @click="removeField(index)"
                                        :disabled="isSaving"
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
                                <p class="mt-3">Click buttons above to add fields to your template</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-success" @click="saveTemplate" :disabled="isSaving">
                            <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <i v-else class="bi bi-save me-2"></i> {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update' : 'Save') }}
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
                            <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isSaving">No</button>
                            <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="isSaving">Yes</button>
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
                            <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isSaving">Cancel</button>
                            <button type="button" class="btn btn-danger" @click="deletePermanently" :disabled="isSaving">
                                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ isSaving ? 'Deleting...' : 'Confirm' }}
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
const TEMPLATES_API_URL = `${API_BASE_URL}/resource-templates`; 
const CATEGORIES_API_URL = `${API_BASE_URL}/categories`; 
const getAuthToken = () => localStorage.getItem('authToken');
const saving = ref(false);

// --- Interfaces (Mapping to Backend) ---
interface Field {
    id?: number; // Added for updates/deletes
    field_key?: string;
    field_name: string; // Maps to label in original frontend
    field_type: 'text' | 'checkbox' | 'image' | 'number' | 'textarea'; // Must match backend enum
}

interface BackendCategory {
    id: number;
    name: string;
}

interface Template {
    id: number;
    template_name: string; // Maps to templateName in original frontend
    category_id: number;
    description: string;
    status: 'Active' | 'Inactive';
    category?: BackendCategory; // Nested object
    fields: Field[];
}

interface TemplateData {
    id?: number;
    category_id: number | null; // Maps to categoryName in original frontend
    template_name: string;
    description: string;
    fields: Field[];
    created_by: number; // Required by backend store method
    status: 'Active' | 'Inactive'; // Required by backend store method
}

interface ValidationErrors {
    [key: string]: string[];
}

// --- Template Data & State ---
const searchQuery = ref('');
const isEditMode = ref(false); 
const activeFieldType = ref<string | null>(null);

// API/UI State
const isLoading = ref(true);
const isSaving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const modalErrorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});

// Form data for the TemplateFormModal
const formData = ref<Partial<TemplateData>>({
    category_id: null,
    template_name: '',
    description: '',
    fields: [],
    status: 'Active',
    created_by: 1, // Mock user ID 
});

const templates = ref<Template[]>([]);
const categories = ref<BackendCategory[]>([]); // To hold fetched categories

// MODAL STATE AND REFS
const templateModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let templateModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

const selectedTemplate = ref<Template | null>(null);
const templateToDeleteId = ref<number | null>(null);
const templateToDeleteName = computed(() => templates.value.find(t => t.id === templateToDeleteId.value)?.template_name || 'this template');
const deleteStep = ref<'confirm' | 'final'>('confirm');


// --- Computed Property for Search ---
const filteredTemplates = computed(() => {
    if (!searchQuery.value) return templates.value;
    const query = searchQuery.value.toLowerCase();
    return templates.value.filter(
        (t) =>
            t.template_name.toLowerCase().includes(query) ||
            t.description.toLowerCase().includes(query) ||
            (t.category?.name?.toLowerCase()?.includes(query) ?? false)
    );
});


// --- Helper Functions ---

const frontendTypeToBackendType = (type: 'input' | 'checkbox' | 'photo'): Field['field_type'] => {
    if (type === 'input') return 'text';
    if (type === 'checkbox') return 'checkbox';
    if (type === 'photo') return 'image';
    return 'text';
};

const backendTypeToFrontendType = (type: Field['field_type']): 'input' | 'checkbox' | 'photo' => {
    if (type === 'text' || type === 'number' || type === 'textarea') return 'input';
    if (type === 'checkbox') return 'checkbox';
    if (type === 'image') return 'photo';
    return 'input';
};

const handleApiError = (data: any, status: number) => {
    validationErrors.value = {};
    if (status === 422 && data.errors) {
        validationErrors.value = data.errors;
        modalErrorMessage.value = "Validation failed. Please check the fields and try again.";
    } else {
        errorMessage.value = data.message || `An error occurred (Status: ${status}).`;
    }
};

// --- API FETCH (READ) ---

const fetchTemplates = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    const token = getAuthToken();

    try {
        const response = await fetch(TEMPLATES_API_URL, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json();

        if (response.ok) {
            templates.value = data;
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        errorMessage.value = 'Failed to connect to the API to load templates.';
    } finally {
        isLoading.value = false;
    }
};

const fetchCategories = async () => {
    const token = getAuthToken();
    try {
        const response = await fetch(CATEGORIES_API_URL, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json();
        if (response.ok) {
            categories.value = data;
        }
    } catch (e) {
        console.error("Could not fetch categories:", e);
    }
}

// --- API CRUD (CREATE/UPDATE/DELETE) ---

const saveTemplate = async () => {
    if (!formData.value.category_id || !formData.value.template_name) {
        modalErrorMessage.value = 'Template Name and Category are required.';
        return;
    }
    if (!formData.value.fields || formData.value.fields.length === 0) {
        modalErrorMessage.value = 'Please add at least one field to the template.';
        return;
    }

    isSaving.value = true;
    modalErrorMessage.value = '';
    validationErrors.value = {};
    successMessage.value = '';
    errorMessage.value = '';
    
    const token = getAuthToken();
    const isUpdate = isEditMode.value && selectedTemplate.value?.id;
    const url = isUpdate ? `${TEMPLATES_API_URL}/${selectedTemplate.value!.id}` : TEMPLATES_API_URL;
    const method = isUpdate ? 'PUT' : 'POST';

    try {
        // Prepare API payload (Mapping frontend structure to backend keys)
        const payload = {
            template_name: formData.value.template_name.trim(),
            category_id: formData.value.category_id,
            description: formData.value.description || null,
            status: formData.value.status,
            created_by: formData.value.created_by,
            fields: formData.value.fields.map(field => ({
                // Keep the 'id' for existing fields during update
                ...(isUpdate && field.id && { id: field.id }), 
                field_name: field.field_name,
                field_type: field.field_type,
                is_required: true, // Assuming all template fields are required by default
            })),
        };

        const response = await fetch(url, {
            method: method,
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok) {
            successMessage.value = data.message || (isUpdate ? 'Template updated successfully!' : 'Template created successfully!');
            await fetchTemplates();
            templateModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
            modalErrorMessage.value = validationErrors.value.template_name?.[0] || validationErrors.value['fields.0.field_name']?.[0] || data.message || "Failed to save template.";
        }
    } catch (e) {
        errorMessage.value = 'Network error while saving template.';
    } finally {
        isSaving.value = false;
    }
};


const deletePermanently = async () => {
    if (deleteStep.value !== 'final' || templateToDeleteId.value === null) return; 

    isSaving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const id = templateToDeleteId.value;

    try {
        const response = await fetch(`${TEMPLATES_API_URL}/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json();

        if (response.ok) {
            successMessage.value = data.message || 'Template deleted successfully.';
            await fetchTemplates();
            deleteModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
            // Display error prominently
            errorMessage.value = data.message || "Failed to delete template.";
        }

    } catch (e) {
        errorMessage.value = 'Network error while deleting template.';
    } finally {
        isSaving.value = false;
        handleCancelDeletion(); // Close and reset modal state
    }
};

// --- Form Logic (Add/Edit) ---

const resetFormData = () => {
    formData.value = {
        category_id: null,
        template_name: '',
        description: '',
        fields: [],
        status: 'Active',
        created_by: 1, // Mock user ID 
    };
    activeFieldType.value = null;
    modalErrorMessage.value = '';
    validationErrors.value = {};
};


const openAddModal = () => {  
    isEditMode.value = false;
    resetFormData();
    templateModalInstance?.show();
};

const openEditModal = async (template: Template) => {
    selectedTemplate.value = template;
    resetFormData(); // Resetting first clears old validation errors
    isEditMode.value = true;
    
    // Set basic fields
    formData.value.id = template.id;
    formData.value.template_name = template.template_name;
    formData.value.description = template.description;
    formData.value.category_id = template.category_id;
    formData.value.status = template.status;

    // Map backend fields to frontend format
    formData.value.fields = template.fields.map(f => ({
        id: f.id,
        field_name: f.field_name,
        field_type: f.field_type,
        // Map backend type string to frontend component type
        type: backendTypeToFrontendType(f.field_type), 
    } as Field)); 

    templateModalInstance?.show();
};

const addField = (type: 'input' | 'checkbox' | 'photo') => {
    activeFieldType.value = type;
    const defaultName =
        type === 'input' ? 'New Input Field' :
        type === 'checkbox' ? 'New Check Box' : 'Photo Upload';

    formData.value.fields!.push({
        field_name: defaultName,
        field_type: frontendTypeToBackendType(type), // Store backend type
        type: type, // Store frontend type for rendering
    } as Field);
};

const removeField = (index: number) => {
    formData.value.fields!.splice(index, 1);
};


// --- Delete Logic ---

const openDeleteConfirmation = (template: Template) => {
    templateToDeleteId.value = template.id;
    deleteStep.value = 'confirm'; 
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    templateToDeleteId.value = null;
    deleteStep.value = 'confirm'; 
};


// --- LIFECYCLE ---
onMounted(() => {
    // Initialize Bootstrap Modals
    if (templateModalRef.value) {
        templateModalInstance = new Modal(templateModalRef.value);
        templateModalRef.value.addEventListener('hidden.bs.modal', resetFormData);
    }
    if (deleteModalRef.value) {
        deleteModalInstance = new Modal(deleteModalRef.value);
        deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
    }
    
    // Fetch initial data
    fetchCategories();
    fetchTemplates();
});
</script>

<style scoped>
/* --- General Section & Sidebar Layout --- */
.template-page.section {
    animation: fadeIn 0.3s ease;
    padding: 20px;
    margin-left: 260px; 
}
@media (max-width: 768px) {
    .template-page.section {
        margin-left: 80px; 
    }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.section-title {
    color: #1e4449;
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

/* --- Button Styling --- */
.btn-success { background-color: #4BB66D; border-color: #4BB66D; }
.btn-success:hover { background-color: #3f975b; border-color: #3f975b; }
.btn-success.add-new-btn { padding: 10px 20px; border-radius: 8px; }

.btn-outline-primary { --bs-btn-color: #0d6efd; --bs-btn-border-color: #0d6efd; }
.btn-outline-danger { --bs-btn-color: #dc3545; --bs-btn-border-color: #dc3545; }

.btn-warning { color: #212529 !important; background-color: #ffc107 !important; border-color: #ffc107 !important; }
.btn-warning:hover { background-color: #e0a800 !important; border-color: #e0a800 !important; }

/* --- Table & Card Styling --- */
.table-card {
    background: white;
    border-radius: 8px; 
    padding: 24px; 
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
    overflow: hidden;
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
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem; 
}

/* --- Modal & Builder Styles --- */
.modal-dialog.modal-lg { max-width: 900px !important; }
.modal-content { border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); }
.modal-header, .modal-footer { border-color: #dee2e6; padding: 20px; }
.modal-body { padding: 30px; }
.modal-dialog.delete-modal-top { align-items: flex-start; margin-top: 50px; height: auto; }


.field-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.field-buttons .btn { border-radius: 8px; }
.field-buttons .btn.active { background-color: #0d6efd; color: white; }

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
.photo-upload-area i { font-size: 48px; color: #6c757d; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: stretch; }
    .input-group, .template-page .add-new-btn { width: 100% !important; max-width: 100% !important; }
    .field-buttons { flex-direction: column; }
    .field-buttons .btn { width: 100%; }
}
</style>