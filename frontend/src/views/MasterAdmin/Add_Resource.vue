<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">{{ isEditMode ? 'Edit Resource' : 'Add New Resource' }}</h2>
      <div class="d-flex gap-2">
        <button 
          type="button" 
          class="btn btn-outline-secondary" 
          @click="goBack"
          :disabled="saving"
        >
          <i class="bi bi-arrow-left me-1"></i> Back
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading form data...</p>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>Error!</strong> {{ error }}
      <button type="button" class="btn-close" @click="error = ''"></button>
    </div>

    <!-- Success Alert -->
    <div v-if="success" class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Success!</strong> {{ success }}
      <button type="button" class="btn-close" @click="success = ''"></button>
    </div>

    <div v-if="!loading" class="card p-4 shadow-sm">
      <form @submit.prevent="isEditMode ? handleUpdate() : handleSave()" id="resourceForm">
        <div class="row g-4">
          <!-- Resource Name (REQUIRED) -->
          <div class="col-md-6">
            <label for="resourceName" class="form-label fw-bold">Resource Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceName" 
              v-model="newResource.name" 
              required
              placeholder="e.g., Conference Room A 301"
              :disabled="saving"
            >
          </div>

          <!-- Location Name (REQUIRED) -->
          <div class="col-md-6">
            <label for="locationName" class="form-label fw-bold">Location Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="newResource.location_name"
              required
              placeholder="e.g., Building C, Floor 2"
              :disabled="saving"
            >
          </div>

          <!-- Category (REQUIRED) -->
          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select 
              class="form-select" 
              id="resourceCategory" 
              v-model="newResource.category_id" 
              required
              :disabled="saving || loadingCategories"
            >
              <option value="" disabled>Select a Category</option>
              <option 
                v-for="category in categories" 
                :key="category.id" 
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
            <div v-if="loadingCategories" class="form-text">
              <small class="text-muted">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Loading categories...
              </small>
            </div>
          </div>

          <!-- Base Price (REQUIRED) -->
          <div class="col-md-6">
            <label for="resourcePrice" class="form-label fw-bold">Resource Base Price (Rs.) <span class="text-danger">*</span></label>
            <input 
              type="number" 
              class="form-control" 
              id="resourcePrice" 
              v-model.number="newResource.base_price"
              required
              placeholder="e.g., 500.00"
              min="0"
              step="0.01"
              :disabled="saving"
            >
          </div>
          
          <!-- Assign Admin (NOT REQUIRED) -->
          <div class="col-md-6">
            <label for="assignee" class="form-label fw-bold">Assign Admin</label>
            <select 
              class="form-select" 
              id="assignee" 
              v-model="newResource.assigned_admin_id"
              :disabled="saving || loadingAdmins"
            >
              <option value="">No Assignee</option>
              <option 
                v-for="admin in admins" 
                :key="admin.id" 
                :value="admin.id"
              >
                {{ admin.name }} ({{ admin.email }})
              </option>
            </select>
            <div v-if="loadingAdmins" class="form-text">
              <small class="text-muted">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Loading admins...
              </small>
            </div>
          </div>
          
          <!-- Status (REQUIRED) -->
          <div class="col-md-6">
            <label for="resourceStatus" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select 
              class="form-select" 
              id="resourceStatus" 
              v-model="newResource.status"
              required
              :disabled="saving"
            >
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <!-- Availability Schedule (NOT REQUIRED - can be all unavailable) -->
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
                v-for="(day, index) in newResource.availability" 
                :key="day.day_of_week"
                class="row align-items-center mb-2 mx-0"
              >
                <div class="col-3 fw-medium">{{ day.day_of_week }}</div>
                
                <div class="col-3 text-center">
                  <div class="form-check form-switch d-inline-block">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="day.is_available"
                      :true-value="true"
                      :false-value="false"
                      :disabled="saving"
                      @change="handleAvailabilityToggle(index)"
                    >
                  </div>
                </div>

                <div class="col-3">
                  <input 
                    type="time" 
                    class="form-control form-control-sm" 
                    v-model="day.start_time"
                    :disabled="!day.is_available || saving"
                    placeholder="HH:mm"
                    @change="validateTime(day)"
                  >
                </div>
                <div class="col-3">
                  <input 
                    type="time" 
                    class="form-control form-control-sm" 
                    v-model="day.end_time"
                    :disabled="!day.is_available || saving"
                    placeholder="HH:mm"
                    @change="validateTime(day)"
                  >
                </div>
              </div>
            </div>
            <small class="form-text text-muted">Define the daily hours when this resource can be booked. You can set all days as unavailable if needed.</small>
          </div>

          <!-- Equipment (NOT REQUIRED) -->
          <div class="col-12">
            <label class="form-label fw-bold">Equipment/Accessories</label>
            <div class="equipment-list border p-3 rounded">
              <div 
                v-for="(item, index) in newResource.equipment" 
                :key="index" 
                class="d-flex align-items-center mb-3 p-2 border-bottom"
              >
                <div class="flex-grow-1 me-3">
                  <input 
                    type="text" 
                    class="form-control form-control-sm" 
                    v-model="item.equipment_name" 
                    placeholder="Equipment Name (e.g., Projector)"
                    :disabled="saving"
                  >
                </div>
                
                <div class="me-3" style="width: 120px;">
                  <input 
                    type="number" 
                    class="form-control form-control-sm" 
                    v-model.number="item.quantity"
                    min="1"
                    placeholder="Quantity"
                    :disabled="saving"
                  >
                </div>
                
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-danger flex-shrink-0" 
                  @click="removeEquipment(index)"
                  :disabled="saving"
                >
                  <i class="bi bi-x"></i>
                </button>
              </div>
              
              <button 
                type="button" 
                class="btn btn-sm btn-outline-dark-teal mt-2" 
                @click="addEquipment"
                :disabled="saving"
              >
                <i class="bi bi-plus-circle me-1"></i> Add Equipment
              </button>
            </div>
            <small class="form-text text-muted">Define equipment and their quantities (optional).</small>
          </div>

          <!-- Images (NOT REQUIRED) -->
          <div class="col-12">
            <label for="resourceImages" class="form-label fw-bold">Upload Photos</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourceImages" 
              @change="handleFileUpload" 
              accept="image/*"
              multiple
              :disabled="saving"
            >
            <small class="form-text text-muted">You can select multiple images (JPEG, PNG, JPG, GIF, max 2MB each) - optional.</small>
            
            <!-- Image preview -->
            <div v-if="imagePreviews.length > 0" class="mt-3">
              <h6 class="fw-bold mb-2">Image Previews:</h6>
              <div class="row g-2">
                <div v-for="(preview, index) in imagePreviews" :key="index" class="col-md-3">
                  <div class="position-relative">
                    <img :src="preview" alt="Preview" class="img-thumbnail" style="height: 100px; object-fit: cover;">
                    <button 
                      type="button" 
                      class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                      @click="removeImage(index)"
                      :disabled="saving"
                    >
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Description (NOT REQUIRED) -->
          <div class="col-12">
            <label for="resourceDescription" class="form-label fw-bold">Description</label>
            <textarea 
              class="form-control" 
              id="resourceDescription" 
              rows="4" 
              v-model="newResource.description"
              placeholder="Provide a detailed description of the resource, its features, and capacity (optional)."
              :disabled="saving"
            ></textarea>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button 
            type="button" 
            class="btn btn-secondary" 
            @click="goBack"
            :disabled="saving"
          >
            Cancel
          </button>
          <!-- TEST BUTTON: Use this for testing without images -->
          <button 
            v-if="isEditMode"
            type="button" 
            class="btn btn-warning" 
            @click="handleUpdate"
            :disabled="saving"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
            Update Resource
          </button>
          <button 
            v-else
            type="button" 
            class="btn btn-success" 
            @click="handleSave"
            :disabled="saving"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i v-else class="bi bi-save me-1"></i>
            Save Resource
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const router = useRouter();
const route = useRoute();

// --- API Configuration (Match your category page) ---
const API_BASE_URL = 'http://localhost:8000/api';
const RESOURCE_ENDPOINT = `${API_BASE_URL}/resources`;
const CATEGORY_ENDPOINT = `${API_BASE_URL}/categories`;
const ADMIN_ENDPOINT = `${API_BASE_URL}/admins`;

// Get auth token (same as category page)
const getAuthToken = () => localStorage.getItem('authToken');

// --- DEFINITIONS ---
interface EquipmentItem {
  equipment_name: string;
  quantity: number;
}

interface AvailabilityDay {
  day_of_week: string;
  is_available: boolean;
  start_time: string | null;
  end_time: string | null;
}

interface Category {
  id: number;
  name: string;
  description: string;
}

interface Admin {
  id: number;
  name: string;
  email: string;
}

interface NewResource {
  name: string;
  location_name: string;
  category_id: number | null;
  assigned_admin_id: number | null;
  description: string;
  base_price: number | null;
  status: string;
  images: File[];
  equipment: EquipmentItem[];
  availability: AvailabilityDay[];
}

// --- STATE ---
const newResource = reactive<NewResource>({
  name: '',
  location_name: '',
  category_id: null,
  assigned_admin_id: null,
  description: '',
  base_price: null,
  status: 'Active',
  images: [],
  equipment: [],
  availability: []
});

const categories = ref<Category[]>([]);
const admins = ref<Admin[]>([]);
const loading = ref(false);
const saving = ref(false);
const loadingCategories = ref(false);
const loadingAdmins = ref(false);
const error = ref('');
const success = ref('');
const imagePreviews = ref<string[]>([]);

// --- HELPER FUNCTIONS ---

// Initialize availability days - make all days available by default
const initializeAvailability = () => {
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  newResource.availability = days.map(day => ({
    day_of_week: day,
    is_available: true, // Default all days to available
    start_time: '09:00',
    end_time: day === 'Saturday' || day === 'Sunday' ? '13:00' : '17:00'
  }));
};

// Handle availability toggle - clear times when unavailable
const handleAvailabilityToggle = (index: number) => {
  const day = newResource.availability[index];
  if (!day.is_available) {
    // Clear times when day becomes unavailable
    day.start_time = null;
    day.end_time = null;
  } else {
    // Set default times when day becomes available
    day.start_time = '09:00';
    day.end_time = '17:00';
  }
};

// Validate time for a specific day
const validateTime = (day: AvailabilityDay) => {
  if (day.is_available && day.start_time && day.end_time) {
    const start = new Date(`2000-01-01T${day.start_time}`);
    const end = new Date(`2000-01-01T${day.end_time}`);
    
    if (start >= end) {
      error.value = `End time must be after start time for ${day.day_of_week}`;
      return false;
    }
  }
  return true;
};

// --- LOAD CATEGORIES ---
const loadCategories = async () => {
  loadingCategories.value = true;
  const token = getAuthToken();
  
  if (!token) {
    error.value = "Authentication token missing. Please log in.";
    loadingCategories.value = false;
    return;
  }

  try {
    const response = await fetch(CATEGORY_ENDPOINT, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });
    
    const data = await response.json().catch(() => {
      return response.status === 200 ? [] : null;
    });

    if (response.ok) {
      categories.value = Array.isArray(data) ? data : [];
      if (categories.value.length > 0 && !newResource.category_id) {
        newResource.category_id = categories.value[0].id;
      }
    } else {
      error.value = data?.message || 'Failed to load categories';
      categories.value = [
        { id: 1, name: 'Academic Space', description: 'Academic spaces' },
        { id: 2, name: 'Medical & Health', description: 'Medical facilities' },
        { id: 3, name: 'Sports & Recreational', description: 'Sports facilities' },
        { id: 4, name: 'IT Space', description: 'IT resources' },
        { id: 5, name: 'Cultural', description: 'Cultural resources' }
      ];
    }
  } catch (err) {
    console.error('Error loading categories:', err);
    error.value = 'Network error loading categories. Using defaults.';
    categories.value = [
      { id: 1, name: 'Academic Space', description: 'Academic spaces' },
      { id: 2, name: 'Medical & Health', description: 'Medical facilities' },
      { id: 3, name: 'Sports & Recreational', description: 'Sports facilities' },
      { id: 4, name: 'IT Space', description: 'IT resources' },
      { id: 5, name: 'Cultural', description: 'Cultural resources' }
    ];
  } finally {
    loadingCategories.value = false;
  }
};

// --- LOAD ADMINS ---
const loadAdmins = async () => {
  loadingAdmins.value = true;
  const token = getAuthToken();
  
  if (!token) {
    loadingAdmins.value = false;
    return;
  }

  try {
    const response = await fetch(ADMIN_ENDPOINT, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });
    
    const data = await response.json().catch(() => {
      return response.status === 200 ? [] : null;
    });

    if (response.ok) {
      admins.value = Array.isArray(data) ? data : [];
    }
  } catch (err) {
    console.error('Error loading admins:', err);
  } finally {
    loadingAdmins.value = false;
  }
};

// --- EQUIPMENT MANAGEMENT ---
const addEquipment = () => {
  newResource.equipment.push({
    equipment_name: '',
    quantity: 1
  });
};

const removeEquipment = (index: number) => {
  newResource.equipment.splice(index, 1);
};

// --- IMAGE HANDLING ---
const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    for (let i = 0; i < input.files.length; i++) {
      const file = input.files[i];
      if (file.size > 2 * 1024 * 1024) {
        error.value = `File ${file.name} exceeds 2MB limit`;
        continue;
      }
      newResource.images.push(file);
      
      const reader = new FileReader();
      reader.onload = (e) => {
        if (e.target && typeof e.target.result === 'string') {
          imagePreviews.value.push(e.target.result);
        }
      };
      reader.readAsDataURL(file);
    }
  }
};

const removeImage = (index: number) => {
  newResource.images.splice(index, 1);
  imagePreviews.value.splice(index, 1);
};

// --- FORM SUBMISSION ---
const handleSave = async () => {
  // Validate only required fields (not availability)
  if (!validateForm()) {
    return;
  }
  
  saving.value = true;
  error.value = '';
  success.value = '';
  
  try {
    // Get auth token
    const token = getAuthToken();
    if (!token) {
      error.value = "Authentication token missing. Please log in.";
      saving.value = false;
      return;
    }
    
    // Create FormData for sending with images
    const formData = new FormData();
    
    // Add basic fields
    formData.append('name', newResource.name.trim());
    formData.append('location_name', newResource.location_name.trim());
    formData.append('category_id', newResource.category_id!.toString());
    formData.append('base_price', newResource.base_price!.toString());
    formData.append('status', newResource.status);
    
    // Add optional fields
    if (newResource.description.trim()) {
      formData.append('description', newResource.description.trim());
    }
    
    if (newResource.assigned_admin_id) {
      formData.append('assigned_admin_id', newResource.assigned_admin_id.toString());
    }
    
    // Add equipment
    newResource.equipment
      .filter(eq => eq.equipment_name.trim())
      .forEach((equipment, index) => {
        formData.append(`equipment[${index}][equipment_name]`, equipment.equipment_name.trim());
        formData.append(`equipment[${index}][quantity]`, equipment.quantity.toString());
      });
    
    // **FIXED: Handle availability correctly**
    newResource.availability.forEach((day, index) => {
      formData.append(`availability[${index}][day_of_week]`, day.day_of_week);
      
      // Send is_available as 1/0 (not true/false)
      formData.append(`availability[${index}][is_available]`, day.is_available ? '1' : '0');
      
      // **CRITICAL FIX: Only add times if day is available and times are provided**
      if (day.is_available && day.start_time && day.end_time) {
        formData.append(`availability[${index}][start_time]`, day.start_time);
        formData.append(`availability[${index}][end_time]`, day.end_time);
      } else {
        // For unavailable days, send empty strings (NOT null)
        // Laravel will treat empty strings as null when validation allows nullable
        formData.append(`availability[${index}][start_time]`, '');
        formData.append(`availability[${index}][end_time]`, '');
      }
    });
    
    // Add images
    newResource.images.forEach((image, index) => {
      formData.append(`images[${index}]`, image);
    });
    
    console.log('Sending FormData...');
    
    // Log what we're sending (for debugging)
    console.log('Availability data being sent:');
    newResource.availability.forEach((day, index) => {
      console.log(`Day ${index}:`, {
        day_of_week: day.day_of_week,
        is_available: day.is_available,
        start_time: day.start_time,
        end_time: day.end_time
      });
    });
    
    const response = await fetch(RESOURCE_ENDPOINT, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        // Don't set Content-Type for FormData - browser will set it with boundary
      },
      body: formData
    });
    
    const responseData = await response.json().catch(() => ({
      message: 'Server response error',
      status: response.status
    }));
    
    console.log('Response status:', response.status);
    console.log('Response data:', responseData);
    
    if (response.ok) {
      success.value = responseData.message || 'Resource created successfully!';
      
      // Reset form after successful save
      setTimeout(() => {
        resetForm();
        router.push('/master-admin/resource');
      }, 1500);
      
    } else {
      // Handle validation errors
      if (response.status === 422 && responseData.errors) {
        const errors = Object.values(responseData.errors).flat();
        error.value = 'Validation errors: ' + errors.join(', ');
      } else {
        error.value = responseData.message || `Failed to create resource (Status: ${response.status})`;
      }
    }
    
  } catch (err: any) {
    console.error('Error saving resource:', err);
    error.value = err.message || 'Network error: Could not reach the server';
  } finally {
    saving.value = false;
  }
};

// **OPTIONAL: Use this version if you want to send JSON (without images)**
const handleSaveWithoutImages = async () => {
  if (!validateForm()) {
    return;
  }
  
  saving.value = true;
  error.value = '';
  success.value = '';
  
  try {
    const token = getAuthToken();
    if (!token) {
      error.value = "Authentication token missing. Please log in.";
      saving.value = false;
      return;
    }
    
    // Create JSON payload
    const payload = {
      name: newResource.name.trim(),
      location_name: newResource.location_name.trim(),
      category_id: newResource.category_id,
      base_price: newResource.base_price,
      status: newResource.status,
      description: newResource.description.trim() || null,
      assigned_admin_id: newResource.assigned_admin_id || null,
      equipment: newResource.equipment
        .filter(eq => eq.equipment_name.trim())
        .map(eq => ({
          equipment_name: eq.equipment_name.trim(),
          quantity: eq.quantity
        })),
      // **FIXED: Send null for unavailable days**
      availability: newResource.availability.map(day => ({
        day_of_week: day.day_of_week,
        is_available: day.is_available,
        start_time: day.is_available && day.start_time ? day.start_time : null,
        end_time: day.is_available && day.end_time ? day.end_time : null
      }))
    };
    
    console.log('Sending JSON payload:', payload);
    
    const response = await fetch(RESOURCE_ENDPOINT, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload)
    });
    
    const responseData = await response.json();
    
    if (response.ok) {
      success.value = responseData.message || 'Resource created successfully!';
      setTimeout(() => {
        resetForm();
        router.push('/master-admin/resource');
      }, 1500);
    } else {
      if (response.status === 422 && responseData.errors) {
        const errors = Object.values(responseData.errors).flat();
        error.value = 'Validation errors: ' + errors.join(', ');
      } else {
        error.value = responseData.message || `Failed (Status: ${response.status})`;
      }
    }
    
  } catch (err: any) {
    console.error('Error:', err);
    error.value = err.message || 'Network error';
  } finally {
    saving.value = false;
  }
};

// --- UPDATE HANDLER ---
const handleUpdate = async () => {
  error.value = 'Edit functionality is not implemented yet. Please use the save functionality for new resources.';
};

// --- FORM VALIDATION (Fixed for availability) ---
const validateForm = (): boolean => {
  // Check required fields only
  if (!newResource.name.trim()) {
    error.value = 'Resource name is required';
    return false;
  }
  
  if (!newResource.location_name.trim()) {
    error.value = 'Location name is required';
    return false;
  }
  
  if (!newResource.category_id) {
    error.value = 'Category is required';
    return false;
  }
  
  if (!newResource.base_price || newResource.base_price < 0) {
    error.value = 'Valid base price is required';
    return false;
  }
  
  // **FIXED: Validate times only for available days**
  for (const day of newResource.availability) {
    if (day.is_available) {
      // If day is available, times are required
      if (!day.start_time || !day.end_time) {
        error.value = `Both start and end times are required for ${day.day_of_week} when day is available`;
        return false;
      }
      
      // Validate time order
      const start = new Date(`2000-01-01T${day.start_time}`);
      const end = new Date(`2000-01-01T${day.end_time}`);
      
      if (start >= end) {
        error.value = `End time must be after start time for ${day.day_of_week}`;
        return false;
      }
    }
    // **No validation for unavailable days**
  }
  
  return true;
};

// --- FORM RESET ---
const resetForm = () => {
  newResource.name = '';
  newResource.location_name = '';
  newResource.category_id = null;
  newResource.assigned_admin_id = null;
  newResource.description = '';
  newResource.base_price = null;
  newResource.status = 'Active';
  newResource.images = [];
  newResource.equipment = [];
  initializeAvailability();
  imagePreviews.value = [];
};

// --- NAVIGATION ---
const goBack = () => {
  router.push('/master-admin/resource');
};

// --- COMPUTED PROPERTIES ---
const isEditMode = computed(() => route.query.mode === 'edit' && !!route.query.id);

// --- LIFECYCLE HOOKS ---
onMounted(async () => {
  loading.value = true;
  
  try {
    initializeAvailability();
    
    await Promise.all([
      loadCategories(),
      loadAdmins()
    ]);
    
  } catch (err) {
    console.error('Error initializing form:', err);
    error.value = 'Failed to initialize form. Please refresh the page.';
  } finally {
    loading.value = false;
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

/* Disable styling when inputs are disabled */
.availability-matrix input[type="time"]:disabled,
.form-control:disabled,
.form-select:disabled {
  background-color: #e9ecef;
  opacity: 0.8;
  cursor: not-allowed;
}

/* Loading spinner */
.spinner-border {
  width: 1rem;
  height: 1rem;
}

/* Required field asterisk */
.text-danger {
  color: #dc3545 !important;
}

/* Optional field styling */
.form-label:not(.fw-bold span) {
  font-weight: 500;
}
</style>