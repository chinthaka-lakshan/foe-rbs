<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">{{ isEditMode ? 'Edit Resource' : 'Add New Resource' }}</h2>
    </div>

    <div class="card p-4 shadow-sm">
      <form @submit.prevent="isEditMode ? handleUpdate() : handleSave()">
        <div class="row g-4">
          <div class="col-md-6">
            <label for="resourceName" class="form-label fw-bold">Resource Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceName" 
              v-model="newResource.name" 
              required
              placeholder="e.g., Conference Room A 301"
            >
          </div>

          <div class="col-md-6">
            <label for="locationName" class="form-label fw-bold">Location Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="newResource.location_name"
              placeholder="e.g., Building C, Floor 2"
              required
            >
          </div>

          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select class="form-select" id="resourceCategory" v-model="newResource.category_id" required>
              <option value="" disabled>Select a Category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="resourcePrice" class="form-label fw-bold">Resource Base Price (Rs.) <span class="text-danger">*</span></label>
            <input 
              type="number" 
              class="form-control" 
              id="resourcePrice" 
              v-model.number="newResource.base_price"
              placeholder="e.g., 500.00 Rs."
              min="0"
              step="0.01"
              required
            >
          </div>
          
          <div class="col-md-6">
            <label for="assignee" class="form-label fw-bold">Assign Admin</label>
            <select class="form-select" id="assignee" v-model="newResource.assigned_admin_id">
              <option :value="null">No Assignee</option>
              <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                {{ admin.name }}
              </option>
            </select>
          </div>
          
          <div class="col-md-6">
            <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" v-model="newResource.status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <div class="col-12">
            <h5 class="section-subtitle fw-bold mb-3 mt-3">Available Time Duration</h5>
            <div class="availability-matrix border p-3 rounded bg-light">
                
                <div class="row fw-bold text-muted mb-2 border-bottom pb-2 mx-0 small">
                    <div class="col-3">Day</div>
                    <div class="col-3 text-center">Available</div>
                    <div class="col-3">Start Time</div>
                    <div class="col-3">End Time</div>
                </div>

                <div 
                    v-for="(day, index) in newResource.schedule" 
                    :key="day.dayName"
                    class="row align-items-center mb-2 mx-0"
                >
                    <div class="col-3 fw-medium">{{ day.dayName }}</div>
                    
                    <div class="col-3 text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                v-model="day.available"
                            >
                        </div>
                    </div>

                    <div class="col-3">
                        <input 
                            type="time" 
                            class="form-control form-control-sm" 
                            v-model="day.startTime"
                            :disabled="!day.available"
                        >
                    </div>
                    <div class="col-3">
                        <input 
                            type="time" 
                            class="form-control form-control-sm" 
                            v-model="day.endTime"
                            :disabled="!day.available"
                        >
                    </div>
                </div>
            </div>
            <small class="form-text text-muted">Define the daily hours when this resource can be booked. Times are disabled if the day is not available.</small>
          </div>

          <div class="col-12">
            <label class="form-label fw-bold">Custom Equipment/Accessories</label>
            <div class="equipment-list border p-3 rounded">
                <div 
                    v-for="(item, index) in newResource.equipment" 
                    :key="item.id" 
                    class="d-flex align-items-center mb-3 p-2 border-bottom"
                >
                    <div class="flex-grow-1 me-3">
                        <input 
                            type="text" 
                            class="form-control form-control-sm mb-2" 
                            v-model="item.equipment_name" 
                            placeholder="Equipment Name (e.g., Projector)"
                            required
                        >
                    </div>
                    
                    <div class="me-3" style="width: 100px;">
                        <input 
                            type="number" 
                            class="form-control form-control-sm" 
                            v-model.number="item.quantity" 
                            placeholder="Qty"
                            min="1"
                            required
                        >
                    </div>
                    
                    <button 
                        type="button" 
                        class="btn btn-sm btn-outline-danger flex-shrink-0" 
                        @click="removeEquipment(index)"
                    >
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                
                <button 
                    type="button" 
                    class="btn btn-sm btn-outline-dark-teal mt-2" 
                    @click="addEquipment"
                >
                    <i class="bi bi-plus-circle me-1"></i> Add Equipment
                </button>
            </div>
            <small class="form-text text-muted">Define custom equipment with quantities.</small>
          </div>

          <div class="col-12">
            <label for="resourcePhotoFile" class="form-label fw-bold">Upload Photos (Multiple)</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourcePhotoFile" 
              @change="handleFileUpload" 
              accept="image/*"
              multiple
            >
            <small class="form-text text-muted">Select one or more images to upload.</small>
            
            <div v-if="selectedFiles.length > 0" class="mt-3">
              <h6>Selected Images:</h6>
              <div class="d-flex flex-wrap gap-2">
                <div v-for="(preview, idx) in imagePreviews" :key="idx" class="position-relative">
                  <img :src="preview" alt="Preview" class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                    style="padding: 2px 6px;"
                    @click="removeImage(idx)"
                  >
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-12">
            <label for="resourceDescription" class="form-label fw-bold">Description</label>
            <textarea 
              class="form-control" 
              id="resourceDescription" 
              rows="4" 
              v-model="newResource.description"
              placeholder="Provide a detailed description of the resource, its features, and capacity."
            ></textarea>
          </div>
        </div>

        <div v-if="errorMessage" class="alert alert-danger mt-3">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success mt-3">
          {{ successMessage }}
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button type="button" class="btn btn-secondary" @click="router.push('/master-admin/resource')">
            <i class="bi bi-x-circle me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-save me-1"></i> 
            {{ isEditMode ? 'Update Resource' : 'Save Resource' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// Get auth token from localStorage (adjust the key name if different)
const getAuthToken = () => {
    // Check all possible token storage locations
    const token = localStorage.getItem('authToken') ||  // From your login
                  localStorage.getItem('auth_token') || 
                  localStorage.getItem('token') || 
                  localStorage.getItem('access_token') ||
                  sessionStorage.getItem('auth_token');
    
    // Debug: Log token status
    console.log('Auth token found:', token ? 'Yes' : 'No');
    if (token) {
        console.log('Token preview:', token.substring(0, 20) + '...');
    } else {
        console.error('No auth token found! User might not be logged in.');
        console.log('Checking localStorage keys:', Object.keys(localStorage));
    }
    
    return token;
};

// Set default authorization header if token exists
const token = getAuthToken();
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    console.log('Authorization header set');
} else {
    console.warn('No auth token found in storage');
}

const router = useRouter();
const route = useRoute();

// --- DEFINITIONS ---

interface EquipmentItem {
    id: number;
    equipment_name: string;
    quantity: number;
}

interface ScheduleDay {
    dayName: string;
    available: boolean;
    startTime: string; 
    endTime: string;   
}

interface NewResource {
    name: string;
    location_name: string;
    category_id: string;
    base_price: number | null; 
    assigned_admin_id: number | null;
    description: string;
    status: 'Active' | 'Inactive' | 'Maintenance';
    equipment: EquipmentItem[]; 
    schedule: ScheduleDay[]; 
}

let equipmentIdCounter = 1;

const defaultSchedule: ScheduleDay[] = [
    { dayName: 'Monday', available: false, startTime: '09:00', endTime: '17:00' },
    { dayName: 'Tuesday', available: false, startTime: '09:00', endTime: '17:00' },
    { dayName: 'Wednesday', available: false, startTime: '09:00', endTime: '17:00' },
    { dayName: 'Thursday', available: false, startTime: '09:00', endTime: '17:00' },
    { dayName: 'Friday', available: false, startTime: '09:00', endTime: '17:00' },
    { dayName: 'Saturday', available: false, startTime: '09:00', endTime: '13:00' },
    { dayName: 'Sunday', available: false, startTime: '00:00', endTime: '00:00' },
];

const initialResourceState: NewResource = {
    name: '',
    location_name: '',
    category_id: '',
    base_price: null, 
    assigned_admin_id: null,
    description: '',
    status: 'Active',
    equipment: [], 
    schedule: JSON.parse(JSON.stringify(defaultSchedule)), 
};

const newResource = ref<NewResource>({ ...initialResourceState });
const selectedFiles = ref<File[]>([]);
const imagePreviews = ref<string[]>([]);
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Dynamic data from API
const admins = ref<any[]>([]);
const categories = ref<any[]>([]);

// API Base URL - Adjust this to your Laravel backend URL
const API_BASE_URL = 'http://localhost:8000/api';

// --- EQUIPMENT MANAGEMENT ---

const addEquipment = () => { 
    newResource.value.equipment.push({
        id: equipmentIdCounter++,
        equipment_name: '',
        quantity: 1,
    });
};

const removeEquipment = (index: number) => {
    newResource.value.equipment.splice(index, 1);
};

// --- IMAGE HANDLING ---

const handleFileUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        selectedFiles.value = Array.from(input.files);
        
        // Create previews
        imagePreviews.value = [];
        selectedFiles.value.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (e.target && typeof e.target.result === 'string') {
                    imagePreviews.value.push(e.target.result);
                }
            };
            reader.readAsDataURL(file);
        });
    }
};

const removeImage = (index: number) => {
    selectedFiles.value.splice(index, 1);
    imagePreviews.value.splice(index, 1);
};

// --- UTILITIES ---

const isEditMode = computed(() => route.query.mode === 'edit' && !!route.query.id);

// --- PREPARE DATA FOR BACKEND ---

const prepareFormData = (): FormData => {
    const formData = new FormData();
    
    // Basic fields
    formData.append('name', newResource.value.name);
    formData.append('location_name', newResource.value.location_name);
    formData.append('category_id', newResource.value.category_id);
    formData.append('base_price', newResource.value.base_price?.toString() || '0');
    formData.append('status', newResource.value.status);
    
    if (newResource.value.assigned_admin_id) {
        formData.append('assigned_admin_id', newResource.value.assigned_admin_id.toString());
    }
    
    if (newResource.value.description) {
        formData.append('description', newResource.value.description);
    }
    
    // Images
    selectedFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
    });
    
    // Equipment - only include filled equipment
    const validEquipment = newResource.value.equipment.filter(
        item => item.equipment_name.trim() && item.quantity > 0
    );
    validEquipment.forEach((item, index) => {
        formData.append(`equipment[${index}][equipment_name]`, item.equipment_name);
        formData.append(`equipment[${index}][quantity]`, item.quantity.toString());
    });
    
    // Availability - ONLY send available days (where available === true)
    const availableDays = newResource.value.schedule.filter(day => day.available);
    availableDays.forEach((day, index) => {
        formData.append(`availability[${index}][day_of_week]`, day.dayName);
        formData.append(`availability[${index}][is_available]`, '1');
        formData.append(`availability[${index}][start_time]`, day.startTime);
        formData.append(`availability[${index}][end_time]`, day.endTime);
    });
    
    return formData;
};

// --- API HANDLERS ---

const handleSave = async () => {
    errorMessage.value = '';
    successMessage.value = '';
    isSubmitting.value = true;
    
    try {
        const formData = prepareFormData();
        const token = getAuthToken();
        
        const response = await axios.post(`${API_BASE_URL}/resources`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });
        
        successMessage.value = 'Resource created successfully!';
        
        // Redirect after 2 seconds
        setTimeout(() => {
            router.push('/master-admin/resource');
        }, 2000);
        
    } catch (error: any) {
        console.error('Error creating resource:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Failed to create resource. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

const handleUpdate = async () => {
    errorMessage.value = '';
    successMessage.value = '';
    isSubmitting.value = true;
    
    try {
        const formData = prepareFormData();
        formData.append('_method', 'PUT'); // Laravel method spoofing for FormData
        
        const idToUpdate = route.query.id as string;
        const token = getAuthToken();
        
        const response = await axios.post(`${API_BASE_URL}/resources/${idToUpdate}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });
        
        successMessage.value = 'Resource updated successfully!';
        
        // Redirect after 2 seconds
        setTimeout(() => {
            router.push('/master-admin/resource');
        }, 2000);
        
    } catch (error: any) {
        console.error('Error updating resource:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Failed to update resource. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

// --- LOAD RESOURCE FOR EDIT ---

const loadResourceForEdit = async (resourceId: string) => {
    try {
        const token = getAuthToken();
        const response = await axios.get(`${API_BASE_URL}/resources/${resourceId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        const resource = response.data.resource || response.data;
        
        // Load basic fields
        newResource.value.name = resource.name;
        newResource.value.location_name = resource.location_name;
        newResource.value.category_id = resource.category_id?.toString() || '';
        newResource.value.base_price = resource.base_price;
        newResource.value.assigned_admin_id = resource.assigned_admin_id;
        newResource.value.description = resource.description || '';
        newResource.value.status = resource.status;
        
        // Load equipment
        if (resource.equipment && Array.isArray(resource.equipment)) {
            newResource.value.equipment = resource.equipment.map((item: any) => ({
                id: equipmentIdCounter++,
                equipment_name: item.equipment_name,
                quantity: item.quantity,
            }));
        }
        
        // Load availability
        if (resource.availability && Array.isArray(resource.availability)) {
            newResource.value.schedule = defaultSchedule.map(defaultDay => {
                const savedDay = resource.availability.find(
                    (a: any) => a.day_name === defaultDay.dayName
                );
                
                if (savedDay) {
                    return {
                        dayName: defaultDay.dayName,
                        available: savedDay.is_available,
                        startTime: savedDay.start_time || defaultDay.startTime,
                        endTime: savedDay.end_time || defaultDay.endTime,
                    };
                }
                
                return { ...defaultDay };
            });
        }
        
    } catch (error: any) {
        console.error('Error loading resource:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
        } else {
            errorMessage.value = 'Failed to load resource data.';
        }
    }
};

// --- FETCH ADMINS AND CATEGORIES ---

const fetchAdmins = async () => {
    try {
        const token = getAuthToken();
        const response = await axios.get(`${API_BASE_URL}/admins`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        admins.value = response.data.admins || response.data;
    } catch (error: any) {
        console.error('Error fetching admins:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
        } else {
            errorMessage.value = 'Failed to load admin list.';
        }
    }
};

const fetchCategories = async () => {
    try {
        const token = getAuthToken();
        const response = await axios.get(`${API_BASE_URL}/categories`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        categories.value = response.data.categories || response.data;
    } catch (error: any) {
        console.error('Error fetching categories:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
        } else {
            errorMessage.value = 'Failed to load category list.';
        }
    }
};

// --- INITIALIZATION ---

onMounted(async () => {
    // Load admins and categories first
    await Promise.all([fetchAdmins(), fetchCategories()]);
    
    if (isEditMode.value) {
        const idToEdit = route.query.id as string;
        await loadResourceForEdit(idToEdit);
    } else {
        // Initialize with default equipment for new resource
        addEquipment();
        addEquipment();
    }
});
</script>

<style scoped>
/* Inherited section styles */
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

.section-title {
    color: #1e4449;
    font-weight: 600;
    margin-bottom: 24px;
}
.section-subtitle {
    color: #1e4449;
    font-size: 1.1rem;
}
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

.btn-success:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
}

.img-thumbnail {
    object-fit: cover;
    max-width: 100%;
}

.card {
    align-items: flex-start;
}

/* Schedule and Equipment Styling */
.equipment-list {
    max-height: 350px;
    overflow-y: auto;
    background-color: #f8f9fa;
}
.equipment-list .form-check {
    margin-bottom: 0;
}
.btn-outline-danger {
    --bs-btn-color: #dc3545;
    --bs-btn-border-color: #dc3545;
    --bs-btn-hover-bg: #dc3545;
    --bs-btn-hover-color: white;
}
.availability-matrix {
    background-color: #fafafa !important;
}
.availability-matrix .form-check-input {
    margin-top: 0.2rem;
    cursor: pointer;
}
/* Disable styling when checkbox is disabled */
.availability-matrix input[type="time"]:disabled {
    background-color: #e9ecef;
    opacity: 0.8;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}
</style>