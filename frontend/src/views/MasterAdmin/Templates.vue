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

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input v-model="searchQuery" type="text" class="form-control" placeholder="Search Templates...">
                </div>
            </div>
            <button class="btn btn-success" @click="openAddModal">
                <i class="bi bi-plus-circle me-2"></i> Add New Template
            </button>
        </div>
        
        <div class="table-responsive card shadow-sm p-3">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Template Name</th>
                        <th>Category</th>
                        <th>Fields</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="5" class="text-center py-4">Loading...</td></tr>
                    <tr v-for="template in filteredTemplates" :key="template.id">
                        <td><strong>{{ template.template_name }}</strong></td>
                        <td>{{ template.category?.name || 'Uncategorized' }}</td>
                        <td><span class="badge bg-secondary">{{ template.fields?.length || 0 }} Fields</span></td>
                        <td>
                            <span :class="['badge', template.status === 'Active' ? 'bg-success' : 'bg-danger']">
                                {{ template.status }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-2" @click="openEditModal(template)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(template)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">{{ isEditMode ? 'Edit Template' : 'Create Template' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveTemplate">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Template Name</label>
                                    <input v-model="formData.template_name" type="text" class="form-control" placeholder="e.g. Laptop Specification" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category</label>
                                    <select v-model="formData.category_id" class="form-select" required>
                                        <option value="" disabled>Select Category</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Description</label>
                                    <textarea v-model="formData.description" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2"></i>Form Fields</h6>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('input')">+ Text Input</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('checkbox')">+ Checkbox</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('dropdown')">+ Dropdown</button>
                                </div>
                            </div>

                            <div class="field-container p-3 bg-light rounded border">
                                <div v-if="formData.fields.length === 0" class="text-center text-muted py-4">
                                    No fields added yet. Click a button above to add fields.
                                </div>
                                
                                <div v-for="(field, index) in formData.fields" :key="index" class="field-card mb-3 p-3 bg-white shadow-sm border-start border-4" 
                                    :class="field.type === 'dropdown' ? 'border-primary' : (field.type === 'checkbox' ? 'border-success' : 'border-info')">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge rounded-pill text-uppercase" :class="field.type === 'dropdown' ? 'bg-primary' : (field.type === 'checkbox' ? 'bg-success' : 'bg-info')">
                                            {{ field.type === 'input' ? 'Text' : field.type }}
                                        </span>
                                        <button type="button" class="btn-close" @click="removeField(index)"></button>
                                    </div>

                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-7">
                                            <label class="small text-muted mb-1">Field Label / Name</label>
                                            <input v-model="field.field_name" type="text" class="form-control" placeholder="e.g. Serial Number" required>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mb-2">
                                                <input v-model="field.is_required" class="form-check-input" type="checkbox" :id="'req-'+index">
                                                <label class="form-check-label" :for="'req-'+index">Required?</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="field.type === 'dropdown'" class="mt-3 p-2 bg-light rounded">
                                        <label class="small fw-bold mb-2">Dropdown Options:</label>
                                        <div v-for="(opt, optIdx) in field.options" :key="optIdx" class="input-group input-group-sm mb-1">
                                            <input v-model="field.options![optIdx]" type="text" class="form-control" placeholder="Option Name">
                                            <button type="button" class="btn btn-outline-danger" @click="removeOption(index, optIdx)"><i class="bi bi-dash"></i></button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none" @click="addOption(index)">+ Add Option</button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer mt-4 px-0 pb-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    {{ isEditMode ? 'Update Changes' : 'Save Template' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Delete Template?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete <strong>{{ selectedTemplate?.template_name }}</strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deleteTemplate">Confirm Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Modal } from 'bootstrap';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// Types
interface Field {
    id?: number;
    field_name: string;
    field_type: string; // text, checkbox, dropdown (Backend)
    type: 'input' | 'checkbox' | 'dropdown'; // UI Logic
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
    fields: any[];
    category?: { id: number; name: string };
}

// State
const templates = ref<Template[]>([]);
const categories = ref<any[]>([]);
const searchQuery = ref('');
const isLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const isEditMode = ref(false);
const selectedTemplate = ref<Template | null>(null);
const deletedFieldIds = ref<number[]>([]);

const formData = ref({
    id: null as number | null,
    template_name: '',
    category_id: '' as number | '',
    description: '',
    status: 'Active' as 'Active' | 'Inactive',
    created_by: 1,
    fields: [] as Field[]
});

let templateModal: Modal | null = null;
let deleteModal: Modal | null = null;

// Helpers
const getUiType = (fieldType: string): 'input' | 'checkbox' | 'dropdown' => {
    if (fieldType === 'dropdown') return 'dropdown';
    if (fieldType === 'checkbox') return 'checkbox';
    return 'input'; // Default 'text' to 'input'
};

const getBackendType = (uiType: string): string => {
    if (uiType === 'input') return 'text';
    return uiType; // checkbox, dropdown stay same
};

// Data Actions
const fetchData = async () => {
    isLoading.value = true;
    try {
        const [tRes, cRes] = await Promise.all([
            fetch('http://127.0.0.1:8000/api/resource-templates'),
            fetch('http://127.0.0.1:8000/api/categories')
        ]);
        templates.value = await tRes.json();
        categories.value = await cRes.json();
    } catch (e) { errorMessage.value = "Load error."; }
    finally { isLoading.value = false; }
};

const openAddModal = () => {
    isEditMode.value = false;
    formData.value = { id: null, template_name: '', category_id: '', description: '', status: 'Active', created_by: 1, fields: [] };
    deletedFieldIds.value = [];
    templateModal?.show();
};

const openEditModal = (template: Template) => {
    isEditMode.value = true;
    selectedTemplate.value = template;
    deletedFieldIds.value = [];
    formData.value = {
        id: template.id,
        template_name: template.template_name,
        category_id: template.category_id,
        description: template.description || '',
        status: template.status,
        created_by: template.created_by,
        fields: template.fields.map(f => {
            const uiType = getUiType(f.field_type);
            let opts: string[] = [];
            if (f.metadata) {
                const meta = typeof f.metadata === 'string' ? JSON.parse(f.metadata) : f.metadata;
                opts = meta.options || [];
            }
            return {
                id: f.id,
                field_name: f.field_name,
                field_type: f.field_type,
                type: uiType,
                is_required: Boolean(f.is_required),
                options: uiType === 'dropdown' ? (opts.length ? [...opts] : ['Option 1', 'Option 2']) : []
            };
        })
    };
    templateModal?.show();
};

const addField = (uiType: 'input' | 'checkbox' | 'dropdown') => {
    formData.value.fields.push({
        field_name: '',
        field_type: getBackendType(uiType),
        type: uiType,
        is_required: false,
        options: uiType === 'dropdown' ? ['Option 1', 'Option 2'] : []
    });
};

const removeField = (index: number) => {
    const field = formData.value.fields[index];
    if (field.id) deletedFieldIds.value.push(field.id);
    formData.value.fields.splice(index, 1);
};

const addOption = (idx: number) => formData.value.fields[idx].options?.push(`Option ${formData.value.fields[idx].options!.length + 1}`);
const removeOption = (fIdx: number, oIdx: number) => formData.value.fields[fIdx].options?.splice(oIdx, 1);

const saveTemplate = async () => {
    isLoading.value = true;
    try {
        const payloadFields = formData.value.fields.map((f, i) => {
            const obj: any = {
                field_name: f.field_name,
                field_type: getBackendType(f.type),
                is_required: f.is_required,
                order_index: i
            };
            if (f.id) obj.id = f.id;
            if (f.type === 'dropdown') {
                obj.metadata = JSON.stringify({ options: (f.options || []).filter(o => o.trim() !== '') });
            }
            return obj;
        });

        const url = isEditMode.value ? `http://127.0.0.1:8000/api/resource-templates/${formData.value.id}` : 'http://127.0.0.1:8000/api/resource-templates';
        const res = await fetch(url, {
            method: isEditMode.value ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...formData.value, fields: payloadFields, delete_fields: deletedFieldIds.value })
        });

        if (res.ok) {
            successMessage.value = "Template saved successfully!";
            templateModal?.hide();
            fetchData();
        } else {
            const err = await res.json();
            errorMessage.value = err.message || "Save failed.";
        }
    } catch (e) { errorMessage.value = "Network error."; }
    finally { isLoading.value = false; }
};

const confirmDelete = (t: Template) => { selectedTemplate.value = t; deleteModal?.show(); };
const deleteTemplate = async () => {
    try {
        await fetch(`http://127.0.0.1:8000/api/resource-templates/${selectedTemplate.value?.id}`, { method: 'DELETE' });
        successMessage.value = "Template deleted.";
        deleteModal?.hide();
        fetchData();
    } catch (e) { errorMessage.value = "Delete failed."; }
};

onMounted(() => {
    fetchData();
    templateModal = new Modal(document.getElementById('templateModal')!);
    deleteModal = new Modal(document.getElementById('deleteConfirmModal')!);
});

const filteredTemplates = computed(() => templates.value.filter(t => t.template_name.toLowerCase().includes(searchQuery.value.toLowerCase())));
</script>

<style scoped>
.template-page { background: #f8f9fa; min-height: 100vh; padding: 2rem; }
.field-card { border-radius: 8px; transition: all 0.2s; }
.field-card:hover { transform: translateY(-2px); }
.field-container { max-height: 500px; overflow-y: auto; }
</style>