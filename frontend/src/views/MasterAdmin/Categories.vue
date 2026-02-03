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
                    v-model="searchTerm"
                    :disabled="loading"
                />
            </div>
            <button
                @click="openAddTemplateModal"
                class="btn btn-success add-new-btn" 
                :disabled="loading"
            >
                <i class="bi bi-plus-circle me-2"></i>Add New Template
            </button>
        </div>
        
        <div class="table-card">
            
            <h5 class="mb-3">Template List</h5>

            <div v-if="loading" class="text-center py-5 text-muted">
                 <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading templates...
            </div>

            <div v-else-if="filteredTemplates.length === 0" class="text-center py-5 text-muted">
                {{ searchTerm ? 'No templates found matching your search.' : 'No templates yet. Add your first template!' }}
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Template ID</th>
                            <th>Template Name</th>
                            <th>Category</th>
                            <th>Fields</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(template, index) in filteredTemplates" :key="template.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ template.template_name }}</td>
                            <td>{{ template.category?.name || 'Uncategorized' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ template.fields?.length || 0 }} fields</span>
                            </td>
                            <td>
                                <span :class="['badge', template.status === 'Active' ? 'bg-success' : 'bg-secondary']">
                                    {{ template.status }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        @click="openEditTemplateModal(template)"
                                        class="btn btn-outline-primary"
                                        title="Edit"
                                        :disabled="saving"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        @click="openDeleteConfirmation(template)"
                                        class="btn btn-outline-danger ms-1"
                                        title="Delete"
                                        :disabled="saving"
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

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true" ref="templateModalRef">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="templateModalLabel">{{ isEditMode ? 'Edit Template' : 'Add New Template' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="handleSave">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="templateName" class="form-label">Template Name</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="templateName"
                                    placeholder="Enter template name (e.g., Laptop Template)"
                                    v-model="modalData.template_name"
                                    required
                                    :disabled="saving"
                                >
                                <small class="text-danger" v-if="validationErrors.template_name">{{ validationErrors.template_name[0] }}</small>
                            </div>
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select
                                    class="form-select"
                                    id="category"
                                    v-model="modalData.category_id"
                                    required
                                    :disabled="saving"
                                >
                                    <option value="" disabled>Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <small class="text-danger" v-if="validationErrors.category_id">{{ validationErrors.category_id[0] }}</small>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="description"
                                    placeholder="Enter description"
                                    v-model="modalData.description"
                                    :disabled="saving"
                                >
                                <small class="text-danger" v-if="validationErrors.description">{{ validationErrors.description[0] }}</small>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select
                                    class="form-select"
                                    id="status"
                                    v-model="modalData.status"
                                    :disabled="saving"
                                >
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <small class="text-danger" v-if="validationErrors.status">{{ validationErrors.status[0] }}</small>
                            </div>
                        </div>

                        <!-- Template Fields Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Template Fields</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" @click="addField('input')" :disabled="saving">
                                        <i class="bi bi-input-cursor-text me-1"></i>Text Input
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" @click="addField('checkbox')" :disabled="saving">
                                        <i class="bi bi-check-square me-1"></i>Checkbox
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" @click="addField('dropdown')" :disabled="saving">
                                        <i class="bi bi-menu-down me-1"></i>Dropdown
                                    </button>
                                </div>
                            </div>

                            <div class="fields-container bg-light p-3 rounded border" style="max-height: 400px; overflow-y: auto;">
                                <div v-if="modalData.fields.length === 0" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No fields added yet. Click buttons above to add fields.</p>
                                </div>
                                
                                <div v-for="(field, index) in modalData.fields" :key="index" class="field-item bg-white p-3 rounded border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge" :class="getFieldTypeBadgeClass(field.type)">
                                            {{ getFieldTypeLabel(field.type) }}
                                        </span>
                                        <button
                                            type="button"
                                            class="btn-close btn-sm"
                                            @click="removeField(index)"
                                            :disabled="saving"
                                        ></button>
                                    </div>
                                    
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-8">
                                            <label class="small text-muted mb-1">Field Name</label>
                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="Enter field name (e.g., Serial Number)"
                                                v-model="field.field_name"
                                                required
                                                :disabled="saving"
                                            >
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check h-100 d-flex align-items-center">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :id="`required-${index}`"
                                                    v-model="field.is_required"
                                                    :disabled="saving"
                                                >
                                                <label class="form-check-label small ms-2" :for="`required-${index}`">
                                                    Required Field
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dropdown Options -->
                                    <div v-if="field.type === 'dropdown'" class="mt-3">
                                        <label class="small fw-semibold mb-2">Dropdown Options:</label>
                                        <div v-for="(option, optIndex) in field.options" :key="optIndex" class="input-group input-group-sm mb-2">
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter option value"
                                                v-model="field.options[optIndex]"
                                                :disabled="saving"
                                            >
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                @click="removeOption(index, optIndex)"
                                                :disabled="saving || field.options.length <= 1"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            @click="addOption(index)"
                                            :disabled="saving"
                                        >
                                            <i class="bi bi-plus-circle me-1"></i> Add Option
                                        </button>
                                    </div>
                                </div>
                            </div>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
        <div class="modal-dialog delete-modal-top"> 
            <div class="modal-content">

                <template v-if="deleteStep === 'confirm'">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0">Are you sure you want to delete the template <strong>{{ templateToDelete?.template_name }}</strong>?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="saving">No</button>
                        <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="saving">Yes</button>
                    </div>
                </template>

                <template v-else-if="deleteStep === 'final'">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0">This action will permanently delete the template <strong>{{ templateToDelete?.template_name }}</strong>. Are you sure?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="saving">Cancel</button>
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
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = 'http://localhost:8000/api'; 
const TEMPLATES_API_URL = `${API_BASE_URL}/resource-templates`; 
const CATEGORIES_API_URL = `${API_BASE_URL}/categories`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- TYPES ---
interface Field {
    id?: number;
    field_name: string;
    field_type: string;
    type: 'input' | 'checkbox' | 'dropdown';
    is_required: boolean;
    options?: string[];
    metadata?: any;
}

interface Template {
    id: number;
    template_name: string;
    category_id: number;
    description: string;
    status: 'Active' | 'Inactive';
    created_by: number;
    fields: Field[];
    category?: { id: number; name: string };
}

interface Category {
    id: number;
    name: string;
    description: string;
}

interface ValidationErrors {
    [key: string]: string[];
}

// --- STATE ---
const templates = ref<Template[]>([]);
const categories = ref<Category[]>([]);
const searchTerm = ref('');
const loading = ref(true);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});

// Modal state
const isEditMode = ref(false);
const modalData = ref({
    id: null as number | null,
    template_name: '',
    category_id: '' as number | '',
    description: '',
    status: 'Active' as 'Active' | 'Inactive',
    created_by: 1,
    fields: [] as Field[]
});
const templateToDelete = ref<Template | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');
const deletedFieldIds = ref<number[]>([]);

// Bootstrap Modal References and Instances
const templateModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let templateModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---
const filteredTemplates = computed(() => {
    const term = searchTerm.value.toLowerCase();
    
    return templates.value.filter(
        (template) =>
            template.template_name.toLowerCase().includes(term) ||
            (template.category?.name.toLowerCase() || '').includes(term) ||
            (template.description?.toLowerCase() || '').includes(term)
    );
});

// --- HELPER FUNCTIONS ---
const getUiType = (fieldType: string): 'input' | 'checkbox' | 'dropdown' => {
    if (fieldType === 'dropdown') return 'dropdown';
    if (fieldType === 'checkbox') return 'checkbox';
    return 'input';
};

const getBackendType = (uiType: string): string => {
    if (uiType === 'input') return 'text';
    return uiType;
};

const getFieldTypeLabel = (type: string): string => {
    switch(type) {
        case 'input': return 'Text';
        case 'checkbox': return 'Checkbox';
        case 'dropdown': return 'Dropdown';
        default: return type;
    }
};

const getFieldTypeBadgeClass = (type: string): string => {
    switch(type) {
        case 'input': return 'bg-info text-dark';
        case 'checkbox': return 'bg-success';
        case 'dropdown': return 'bg-primary';
        default: return 'bg-secondary';
    }
};

// --- API METHODS ---
const handleApiError = (data: any, status: number) => {
    validationErrors.value = {};
    if (status === 422 && data.errors) {
        validationErrors.value = data.errors;
        errorMessage.value = "Validation failed. Check the modal fields.";
    } else {
        errorMessage.value = data.message || `An error occurred (Status: ${status}).`;
    }
};

/**
 * GET: Fetches all templates from the backend.
 */
const fetchTemplates = async () => {
    loading.value = true;
    errorMessage.value = '';
    const token = getAuthToken();
    
    if (!token) {
        errorMessage.value = "Authentication token missing. Please log in.";
        loading.value = false;
        return;
    }

    try {
        const response = await fetch(TEMPLATES_API_URL, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            templates.value = data.map((template: Template) => ({
                ...template,
                fields: template.fields.map((field: Field) => ({
                    ...field,
                    type: getUiType(field.field_type),
                    options: field.metadata ? JSON.parse(field.metadata).options || [] : []
                }))
            }));
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Network or connection error during fetch:', e);
        errorMessage.value = 'Network error: Could not reach the API server to fetch templates.';
    } finally {
        loading.value = false;
    }
};

/**
 * GET: Fetches all categories for dropdown
 */
const fetchCategories = async () => {
    const token = getAuthToken();
    try {
        const response = await fetch(CATEGORIES_API_URL, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            categories.value = data;
        }
    } catch (e) {
        console.error('Failed to fetch categories:', e);
    }
};

/**
 * POST/PUT: Handles creating or updating a template.
 */
const handleSave = async () => {
    if (!modalData.value.template_name) {
        errorMessage.value = 'Template Name is required.';
        return;
    }
    
    if (!modalData.value.category_id) {
        errorMessage.value = 'Category is required.';
        return;
    }

    saving.value = true;
    errorMessage.value = '';
    validationErrors.value = {};
    successMessage.value = '';
    
    const token = getAuthToken();
    if (!token) {
        saving.value = false;
        errorMessage.value = "Authentication token missing.";
        return;
    }
    
    const isUpdate = isEditMode.value && modalData.value.id;
    const url = isUpdate ? `${TEMPLATES_API_URL}/${modalData.value.id}` : TEMPLATES_API_URL;
    const method = isUpdate ? 'PUT' : 'POST';

    try {
        // Prepare fields for payload
        const fieldsPayload = modalData.value.fields.map((field, index) => {
            const fieldData: any = {
                field_name: field.field_name,
                field_type: getBackendType(field.type),
                is_required: field.is_required,
                order_index: index
            };

            // Add id for existing fields during update
            if (isUpdate && field.id) {
                fieldData.id = field.id;
            }

            // For dropdown fields, include options in metadata
            if (field.type === 'dropdown' && field.options) {
                const validOptions = field.options.filter(opt => opt.trim() !== '');
                if (validOptions.length === 0) {
                    throw new Error(`Dropdown field "${field.field_name}" must have at least one valid option`);
                }
                
                const metadata = { options: validOptions };
                fieldData.metadata = JSON.stringify(metadata);
            }

            return fieldData;
        });

        // Prepare API payload
        const payload: any = {
            template_name: modalData.value.template_name.trim(),
            category_id: modalData.value.category_id,
            description: modalData.value.description || null,
            status: modalData.value.status,
            created_by: modalData.value.created_by,
            fields: fieldsPayload,
        };

        // For update operations, include delete_fields if needed
        if (isUpdate && deletedFieldIds.value.length > 0) {
            payload.delete_fields = deletedFieldIds.value;
        }

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
            successMessage.value = data.message || (isUpdate ? 'Template updated successfully!' : 'Template added successfully!');
            await fetchTemplates();
            templateModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
        }
    } catch (e: any) {
        console.error('Failed to save template:', e);
        errorMessage.value = e.message || 'Failed to save template due to a network error.';
    } finally {
        saving.value = false;
    }
};

/**
 * DELETE: Handles deleting a template.
 */
const handleDelete = async () => {
    if (deleteStep.value !== 'final' || !templateToDelete.value) return; 
    
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const templateId = templateToDelete.value.id;

    try {
        const response = await fetch(`${TEMPLATES_API_URL}/${templateId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });
        
        const data = await response.json();

        if (response.ok) {
            successMessage.value = data.message || 'Template deleted successfully.';
            await fetchTemplates();
            deleteModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Failed to delete template:', e);
        errorMessage.value = 'Failed to delete template due to a network error.';
    } finally {
        saving.value = false;
        if (successMessage.value || errorMessage.value) {
            templateToDelete.value = null;
            deleteStep.value = 'confirm'; 
        }
    }
};

// --- TEMPLATE FIELD METHODS ---
const addField = (type: 'input' | 'checkbox' | 'dropdown') => {
    const newField: Field = {
        field_name: '',
        field_type: getBackendType(type),
        type: type,
        is_required: false,
    };

    if (type === 'dropdown') {
        newField.options = ['Option 1', 'Option 2'];
    }

    modalData.value.fields.push(newField);
};

const removeField = (index: number) => {
    const field = modalData.value.fields[index];
    if (field.id) deletedFieldIds.value.push(field.id);
    modalData.value.fields.splice(index, 1);
};

const addOption = (fieldIndex: number) => {
    const field = modalData.value.fields[fieldIndex];
    if (field.type === 'dropdown') {
        if (!field.options) {
            field.options = [];
        }
        const optionNumber = field.options.length + 1;
        field.options.push(`Option ${optionNumber}`);
    }
};

const removeOption = (fieldIndex: number, optionIndex: number) => {
    const field = modalData.value.fields[fieldIndex];
    if (field.type === 'dropdown' && field.options && field.options.length > 1) {
        field.options.splice(optionIndex, 1);
    }
};

// --- MODAL AND UI HANDLERS ---
const resetModalData = () => {
    modalData.value = {
        id: null,
        template_name: '',
        category_id: '',
        description: '',
        status: 'Active',
        created_by: 1,
        fields: []
    };
    deletedFieldIds.value = [];
    validationErrors.value = {};
};

const openAddTemplateModal = () => {
    isEditMode.value = false;
    resetModalData();
    templateModalInstance?.show();
};

const openEditTemplateModal = (template: Template) => {
    isEditMode.value = true;
    modalData.value = {
        id: template.id,
        template_name: template.template_name,
        category_id: template.category_id,
        description: template.description || '',
        status: template.status,
        created_by: template.created_by,
        fields: template.fields.map(f => ({
            id: f.id,
            field_name: f.field_name,
            field_type: f.field_type,
            type: f.type,
            is_required: f.is_required || false,
            options: f.type === 'dropdown' ? [...(f.options || ['Option 1', 'Option 2'])] : []
        }))
    };
    deletedFieldIds.value = [];
    validationErrors.value = {};
    templateModalInstance?.show();
};

const openDeleteConfirmation = (template: Template) => {
    templateToDelete.value = template;
    deleteStep.value = 'confirm'; 
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    templateToDelete.value = null;
    deleteStep.value = 'confirm'; 
};

// --- LIFECYCLE ---
onMounted(() => {
    // Initialize Bootstrap Modals
    if (templateModalRef.value) {
        templateModalInstance = new Modal(templateModalRef.value);
        templateModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
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
.section {
    animation: fadeIn 0.3s ease;
    padding: 20px; 
    margin-left: 260px; /* Standard sidebar width */
}
@media (max-width: 768px) {
    .section {
        margin-left: 80px; /* Collapsed sidebar width */
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
/* Custom Success Button Color */
.btn-success { background-color: #4BB66D; border-color: #4BB66D; }
.btn-success:hover { background-color: #3f975b; border-color: #3f975b; }
.btn-success.add-new-btn { padding: 10px 20px; border-radius: 8px; }
/* Table card structure */
.table-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
}
.table thead { background: #f8f9fa; }
.table thead th {
    background-color: #f8f9fa; 
    font-weight: 600;
    border-bottom: 1px solid #dee2e6; 
    padding: 12px 15px;
}
.table tbody td { padding: 12px 15px; vertical-align: middle; }
/* Ensure outline buttons are visible */
.btn-outline-primary { --bs-btn-color: #0d6efd; --bs-btn-border-color: #0d6efd; }
.btn-outline-danger { --bs-btn-color: #dc3545; --bs-btn-border-color: #dc3545; }
/* Action button sizing */
.btn-group-sm .btn { padding: 0.25rem 0.5rem; }
/* NEW STYLING TO MOVE DELETE MODAL TO THE TOP */
.modal-dialog.delete-modal-top { align-items: flex-start; margin-top: 50px; height: auto; }
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
/* Modal styling */
.modal-dialog.modal-lg { max-width: 900px !important; }
/* Fields container styling */
.fields-container {
    scrollbar-width: thin;
    scrollbar-color: #adb5bd #f1f3f5;
}
.fields-container::-webkit-scrollbar {
    width: 6px;
}
.fields-container::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}
.fields-container::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 10px;
}
.field-item {
    border-left: 4px solid #0d6efd;
    transition: all 0.2s;
}
.field-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: stretch; }
    .input-group { width: 100% !important; max-width: 100% !important; }
    .btn-success.add-new-btn { width: 100%; }
    .modal-dialog.modal-lg { max-width: 95% !important; }
    .fields-container { max-height: 300px; }
}
</style>