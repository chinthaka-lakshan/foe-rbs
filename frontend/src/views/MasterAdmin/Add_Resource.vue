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
          <!-- Resource Name -->
          <div class="col-md-6">
            <label for="resourceName" class="form-label fw-bold">Resource Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceName" 
              v-model="resource.name" 
              required
              placeholder="e.g., Conference Room A 301"
            >
          </div>

          <!-- Location Name -->
          <div class="col-md-6">
            <label for="locationName" class="form-label fw-bold">Location Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="resource.location_name"
              placeholder="e.g., Building C, Floor 2"
              required
            >
          </div>

          <!-- Category -->
          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select class="form-select" id="resourceCategory" v-model="resource.category_id" required>
              <option value="" disabled>Select a Category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <!-- Department -->
          <div class="col-md-6">
            <label for="resourceDepartment" class="form-label fw-bold">Department</label>
            <select class="form-select" id="resourceDepartment" v-model="resource.department_id">
              <option :value="null">No Department</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">
                {{ department.name }}
              </option>
            </select>
          </div>

          <!-- Base Price -->
          <div class="col-md-6">
            <label for="resourcePrice" class="form-label fw-bold">Resource Base Price (Rs.) <span class="text-danger">*</span></label>
            <input 
              type="number" 
              class="form-control" 
              id="resourcePrice" 
              v-model.number="resource.base_price"
              placeholder="e.g., 500.00 Rs."
              min="0"
              step="0.01"
              required
            >
          </div>
          
          <!-- Assigned Admin -->
          <div class="col-md-6">
            <label for="assignee" class="form-label fw-bold">Assign Admin</label>
            <select class="form-select" id="assignee" v-model="resource.assigned_admin_id">
              <option :value="null">No Assignee</option>
              <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                {{ admin.name }} ({{ admin.email }})
              </option>
            </select>
          </div>
          
          <!-- Status -->
          <div class="col-md-6">
            <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" v-model="resource.status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <!-- Availability -->
          <div class="col-12">
            <h5 class="section-subtitle fw-bold mb-3 mt-3">Availability & Time Slots</h5>
            <div class="availability-matrix border p-3 rounded bg-light">
              <div class="row fw-bold text-muted mb-2 border-bottom pb-2 mx-0 small">
                <div class="col-2">Day</div>
                <div class="col-2 text-center">Available</div>
                <div class="col-5">Time Slots</div>
                <div class="col-3">Actions</div>
              </div>

              <div 
                v-for="(day, dayIndex) in availability" 
                :key="day.day_name"
                class="row align-items-center mb-3 mx-0 border-bottom pb-3"
              >
                <div class="col-2 fw-medium">{{ day.day_name }}</div>
                
                <div class="col-2 text-center">
                  <div class="form-check form-switch d-inline-block">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="day.is_available"
                      @change="handleAvailabilityChange(dayIndex)"
                    >
                  </div>
                </div>

                <div class="col-5">
                  <!-- Time Slots -->
                  <div v-for="(slot, slotIndex) in day.slots" :key="slotIndex" class="row g-2 mb-2 align-items-center">
                    <div class="col-5">
                      <input 
                        type="time" 
                        class="form-control form-control-sm" 
                        v-model="slot.start_time"
                        :disabled="!day.is_available"
                        required
                      >
                    </div>
                    <div class="col-5">
                      <input 
                        type="time" 
                        class="form-control form-control-sm" 
                        v-model="slot.end_time"
                        :disabled="!day.is_available"
                        required
                      >
                    </div>
                    <div class="col-2">
                      <button 
                        v-if="slotIndex > 0"
                        type="button" 
                        class="btn btn-sm btn-outline-danger"
                        @click="removeSlot(dayIndex, slotIndex)"
                        :disabled="!day.is_available"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Add Slot Button -->
                  <button 
                    v-if="day.is_available"
                    type="button" 
                    class="btn btn-sm btn-outline-secondary mt-1"
                    @click="addSlot(dayIndex)"
                  >
                    <i class="bi bi-plus-circle me-1"></i> Add Time Slot
                  </button>
                  
                  <div v-else class="text-muted small mt-1">
                    <em>Enable day to add time slots</em>
                  </div>
                </div>

                <div class="col-3">
                  <span v-if="day.is_available && day.slots.length > 0" class="badge bg-info">
                    {{ day.slots.length }} slot(s)
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Equipment -->
          <div class="col-12">
            <label class="form-label fw-bold">Custom Equipment/Accessories</label>
            <div class="equipment-list border p-3 rounded">
              <div 
                v-for="(item, index) in equipment" 
                :key="index" 
                class="d-flex align-items-center mb-3 p-2 border-bottom"
              >
                <div class="flex-grow-1 me-3">
                  <input 
                    type="text" 
                    class="form-control form-control-sm mb-2" 
                    v-model="item.equipment_name" 
                    placeholder="Equipment Name (e.g., Projector)"
                  >
                </div>
                
                <div class="me-3" style="width: 100px;">
                  <input 
                    type="number" 
                    class="form-control form-control-sm" 
                    v-model.number="item.quantity" 
                    placeholder="Qty"
                    min="1"
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
          </div>

          <!-- Images -->
          <div class="col-12">
            <label for="resourcePhotoFile" class="form-label fw-bold">Upload Photos</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourcePhotoFile" 
              @change="handleFileUpload" 
              accept="image/*"
              multiple
            >
            
            <!-- Image Previews -->
            <div v-if="imagePreviews.length > 0" class="mt-3">
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
          
          <!-- Description - TRULY OPTIONAL FIELD -->
          <div class="col-12">
            <label for="resourceDescription" class="form-label fw-bold">Description</label>
            <textarea 
              class="form-control" 
              id="resourceDescription" 
              rows="4" 
              v-model="resource.description"
              placeholder="Optional: Provide a detailed description of the resource"
            ></textarea>
          </div>
        </div>

        <!-- Error/Success Messages -->
        <div v-if="errorMessage" class="alert alert-danger mt-3">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success mt-3">
          {{ successMessage }}
        </div>

        <!-- Buttons -->
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

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

export default {
  name: 'AddResource',
  components: {
    Navbar,
    MasterAdminSidebar
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const API_BASE_URL = 'http://localhost:8000/api';

    // Resource data
    const resource = ref({
      name: '',
      location_name: '',
      category_id: '',
      department_id: null,
      base_price: null,
      assigned_admin_id: null,
      description: '', // Completely optional - can be empty
      status: 'Active',
    });

    // Availability data
    const availability = ref([
      { day_name: 'Monday', is_available: false, slots: [] },
      { day_name: 'Tuesday', is_available: false, slots: [] },
      { day_name: 'Wednesday', is_available: false, slots: [] },
      { day_name: 'Thursday', is_available: false, slots: [] },
      { day_name: 'Friday', is_available: false, slots: [] },
      { day_name: 'Saturday', is_available: false, slots: [] },
      { day_name: 'Sunday', is_available: false, slots: [] },
    ]);

    // Equipment data
    const equipment = ref([]);

    // Other state
    const selectedFiles = ref([]);
    const imagePreviews = ref([]);
    const isSubmitting = ref(false);
    const errorMessage = ref('');
    const successMessage = ref('');
    const admins = ref([]);
    const categories = ref([]);
    const departments = ref([]);

    const isEditMode = computed(() => route.query.mode === 'edit' && !!route.query.id);

    // Get auth token
    const getAuthToken = () => {
      return localStorage.getItem('authToken') || 
             localStorage.getItem('token') || 
             localStorage.getItem('access_token');
    };

    // Equipment methods
    const addEquipment = () => {
      equipment.value.push({
        equipment_name: '',
        quantity: 1,
      });
    };

    const removeEquipment = (index) => {
      equipment.value.splice(index, 1);
    };

    // Availability methods
    const addSlot = (dayIndex) => {
      availability.value[dayIndex].slots.push({
        start_time: '',
        end_time: ''
      });
    };

    const removeSlot = (dayIndex, slotIndex) => {
      if (availability.value[dayIndex].slots.length > 1) {
        availability.value[dayIndex].slots.splice(slotIndex, 1);
      }
    };

    const handleAvailabilityChange = (dayIndex) => {
      const day = availability.value[dayIndex];
      if (day.is_available && day.slots.length === 0) {
        addSlot(dayIndex);
      } else if (!day.is_available) {
        day.slots = [];
      }
    };

    // Image methods
    const handleFileUpload = (event) => {
      const input = event.target;
      if (input.files) {
        const files = Array.from(input.files);
        selectedFiles.value = [...selectedFiles.value, ...files];
        
        files.forEach(file => {
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

    const removeImage = (index) => {
      selectedFiles.value.splice(index, 1);
      imagePreviews.value.splice(index, 1);
    };

    // Prepare form data for submission - Description is OPTIONAL
    const prepareFormData = () => {
      const formData = new FormData();
      
      // Add basic resource data
      formData.append('name', resource.value.name);
      formData.append('location_name', resource.value.location_name);
      formData.append('category_id', resource.value.category_id.toString());
      
      // Add department_id if selected
      formData.append('department_id',department.value.department_id.toString())
      
      // Handle base_price
      if (resource.value.base_price === null || resource.value.base_price === undefined || resource.value.base_price === '') {
        formData.append('base_price', '0.00');
      } else {
        const priceValue = parseFloat(resource.value.base_price);
        if (isNaN(priceValue)) {
          formData.append('base_price', '0.00');
        } else {
          formData.append('base_price', priceValue.toFixed(2));
        }
      }
      
      formData.append('status', resource.value.status);
      
      // Always send assigned_admin_id
      formData.append('assigned_admin_id', resource.value.assigned_admin_id?.toString() || '');
      
      // CRITICAL FIX: Send description field - it's OPTIONAL, can be empty string
      // Don't add any default text, just send what user entered (or empty string)
      formData.append('description', resource.value.description || '');
      
      // Add images
      selectedFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
      });
      
      // Add equipment - equipment fields are also optional
      // Don't filter out empty ones, just send what user entered
      equipment.value.forEach((item, index) => {
        formData.append(`equipment[${index}][equipment_name]`, item.equipment_name || '');
        formData.append(`equipment[${index}][quantity]`, item.quantity?.toString() || '1');
      });
      
      // Add availability - only available days with slots
      let availabilityIndex = 0;
      availability.value.forEach((day) => {
        if (day.is_available && day.slots.length > 0) {
          const validSlots = day.slots.filter(slot => 
            slot.start_time && slot.end_time && 
            slot.start_time.trim() && slot.end_time.trim()
          );
          
          if (validSlots.length > 0) {
            formData.append(`availability[${availabilityIndex}][day_of_week]`, day.day_name);
            formData.append(`availability[${availabilityIndex}][is_available]`, '1');
            
            validSlots.forEach((slot, slotIndex) => {
              formData.append(`availability[${availabilityIndex}][slots][${slotIndex}][start_time]`, slot.start_time);
              formData.append(`availability[${availabilityIndex}][slots][${slotIndex}][end_time]`, slot.end_time);
            });
            
            availabilityIndex++;
          }
        }
      });
      
      // Debug: Log what's being sent
      console.log('=== FORM DATA DEBUG ===');
      console.log('Description being sent:', `"${resource.value.description}"`);
      console.log('Description length:', resource.value.description?.length || 0);
      console.log('=== END DEBUG ===');
      
      return formData;
    };

    // Save resource
    const handleSave = async () => {
      errorMessage.value = '';
      successMessage.value = '';
      isSubmitting.value = true;
      
      try {
        const formData = prepareFormData();
        const token = getAuthToken();
        
        if (!token) {
          throw new Error('Authentication required. Please login again.');
        }
        
        console.log('Saving resource...');
        
        const response = await axios.post(`${API_BASE_URL}/resources`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });
        
        console.log('Success! Response:', response.data);
        successMessage.value = 'Resource created successfully!';
        
        setTimeout(() => {
          router.push('/master-admin/resource');
        }, 2000);
        
      } catch (error) {
        console.error('Error creating resource:', error);
        console.error('Error response data:', error.response?.data);
        
        if (error.response?.status === 401) {
          errorMessage.value = 'Authentication required. Please login again.';
        } else if (error.response?.data?.errors) {
          // Check if error is about description field
          const errors = error.response.data.errors;
          if (errors.description) {
            errorMessage.value = `Description error: ${errors.description.join(', ')}`;
          } else {
            errorMessage.value = Object.values(errors).flat().join(', ');
          }
        } else if (error.response?.data?.message) {
          errorMessage.value = error.response.data.message;
        } else {
          errorMessage.value = 'Failed to create resource. Please check console for details.';
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    // Update resource
    const handleUpdate = async () => {
      errorMessage.value = '';
      successMessage.value = '';
      isSubmitting.value = true;
      
      try {
        const formData = prepareFormData();
        const idToUpdate = route.query.id;
        const token = getAuthToken();
        
        if (!token) {
          throw new Error('Authentication required. Please login again.');
        }
        
        // For PUT method with FormData
        formData.append('_method', 'PUT');
        
        console.log('Updating resource ID:', idToUpdate);
        
        const response = await axios.post(`${API_BASE_URL}/resources/${idToUpdate}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });
        
        console.log('Update Success! Response:', response.data);
        successMessage.value = 'Resource updated successfully!';
        
        setTimeout(() => {
          router.push('/master-admin/resource');
        }, 2000);
        
      } catch (error) {
        console.error('Error updating resource:', error);
        console.error('Error details:', error.response?.data);
        
        if (error.response?.status === 401) {
          errorMessage.value = 'Authentication required. Please login again.';
        } else if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          errorMessage.value = Object.values(errors).flat().join(', ');
        } else if (error.response?.data?.message) {
          errorMessage.value = error.response.data.message;
        } else {
          errorMessage.value = 'Failed to update resource. Please try again.';
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    // Load resource for edit
    const loadResourceForEdit = async (resourceId) => {
      try {
        const token = getAuthToken();
        if (!token) {
          errorMessage.value = 'Authentication required. Please login again.';
          return;
        }
        
        console.log('Loading resource for edit:', resourceId);
        
        const response = await axios.get(`${API_BASE_URL}/resources/${resourceId}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        });
        
        const resourceData = response.data;
        
        // Set basic resource data
        resource.value = {
          name: resourceData.name || '',
          location_name: resourceData.location_name || '',
          category_id: resourceData.category_id || '',
          department_id: resourceData.department_id || null,
          base_price: resourceData.base_price !== null && resourceData.base_price !== undefined 
            ? parseFloat(resourceData.base_price) 
            : null,
          assigned_admin_id: resourceData.assigned_admin_id || null,
          description: resourceData.description || '', // Load description, can be empty
          status: resourceData.status || 'Active',
        };
        
        console.log('Description loaded:', `"${resource.value.description}"`);
        
        // Set equipment
        if (resourceData.equipment && Array.isArray(resourceData.equipment)) {
          equipment.value = resourceData.equipment.map(item => ({
            equipment_name: item.equipment_name || '',
            quantity: item.quantity || 1,
          }));
        } else {
          equipment.value = [];
        }
        
        // Load availability
        if (resourceData.availability && Array.isArray(resourceData.availability)) {
          // Reset availability array
          availability.value = [
            { day_name: 'Monday', is_available: false, slots: [] },
            { day_name: 'Tuesday', is_available: false, slots: [] },
            { day_name: 'Wednesday', is_available: false, slots: [] },
            { day_name: 'Thursday', is_available: false, slots: [] },
            { day_name: 'Friday', is_available: false, slots: [] },
            { day_name: 'Saturday', is_available: false, slots: [] },
            { day_name: 'Sunday', is_available: false, slots: [] },
          ];
          
          // Map saved availability to our array
          resourceData.availability.forEach(savedDay => {
            const dayIndex = availability.value.findIndex(
              day => day.day_name === savedDay.day_name
            );
            
            if (dayIndex !== -1) {
              availability.value[dayIndex].is_available = savedDay.is_available;
              
              if (savedDay.slots && Array.isArray(savedDay.slots)) {
                availability.value[dayIndex].slots = savedDay.slots.map(slot => ({
                  start_time: slot.start_time ? slot.start_time.substring(0, 5) : '',
                  end_time: slot.end_time ? slot.end_time.substring(0, 5) : ''
                }));
              }
            }
          });
        }
        
      } catch (error) {
        console.error('Error loading resource:', error);
        errorMessage.value = 'Failed to load resource data.';
      }
    };

    // Fetch departments from API
    const fetchDepartments = async () => {
      try {
        const token = getAuthToken();
        if (!token) {
          console.log('No token found for fetching departments');
          return;
        }
        
        const response = await axios.get(`${API_BASE_URL}/departments`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        });
        
        departments.value = response.data || [];
        
      } catch (error) {
        console.error('Error fetching departments:', error);
        departments.value = [];
      }
    };

    // Fetch admins
    const fetchAdmins = async () => {
      try {
        const token = getAuthToken();
        if (!token) {
          console.log('No token found for fetching admins');
          return;
        }
        
        const endpoints = [
          `${API_BASE_URL}/users/admins`,
          `${API_BASE_URL}/admins`,
          `${API_BASE_URL}/users`,
          `${API_BASE_URL}/users?role=admin`
        ];
        
        for (const endpoint of endpoints) {
          try {
            const response = await axios.get(endpoint, {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
              }
            });
            
            if (response.data) {
              let adminsData = [];
              
              if (Array.isArray(response.data)) {
                adminsData = response.data;
              } else if (response.data.users && Array.isArray(response.data.users)) {
                adminsData = response.data.users;
              } else if (response.data.admins && Array.isArray(response.data.admins)) {
                adminsData = response.data.admins;
              } else if (response.data.data && Array.isArray(response.data.data)) {
                adminsData = response.data.data;
              }
              
              if (endpoint.includes('role=admin')) {
                adminsData = adminsData.filter(user => user.role === 'admin');
              }
              
              admins.value = adminsData;
              return;
            }
          } catch (err) {
            // Continue to next endpoint
          }
        }
        
        admins.value = [];
        
      } catch (error) {
        console.error('Error fetching admins:', error);
        admins.value = [];
      }
    };

    // Fetch categories
    const fetchCategories = async () => {
      try {
        const token = getAuthToken();
        if (!token) {
          console.log('No token found for fetching categories');
          return;
        }
        
        const response = await axios.get(`${API_BASE_URL}/categories`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        });
        
        categories.value = response.data || [];
        
      } catch (error) {
        console.error('Error fetching categories:', error);
        categories.value = [];
      }
    };

    // Initialize
    onMounted(async () => {
      console.log('AddResource component mounted, isEditMode:', isEditMode.value);
      
      // Fetch all required data
      await Promise.all([
        fetchAdmins(), 
        fetchCategories(), 
        fetchDepartments()
      ]);
      
      if (isEditMode.value) {
        const idToEdit = route.query.id;
        await loadResourceForEdit(idToEdit);
      } else {
        // Add one empty equipment field by default for new resource
        addEquipment();
      }
    });

    return {
      resource,
      availability,
      equipment,
      selectedFiles,
      imagePreviews,
      isSubmitting,
      errorMessage,
      successMessage,
      admins,
      categories,
      departments,
      isEditMode,
      router,
      addEquipment,
      removeEquipment,
      addSlot,
      removeSlot,
      handleAvailabilityChange,
      handleFileUpload,
      removeImage,
      handleSave,
      handleUpdate
    };
  }
};
</script>

<style scoped>
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

.availability-matrix input[type="time"]:disabled {
  background-color: #e9ecef;
  opacity: 0.8;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25em 0.5em;
}

.form-check-input:checked {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.form-check-input:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

.alert {
  border-radius: 0.375rem;
  border: 1px solid transparent;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c2c7;
  color: #842029;
}

.alert-success {
  background-color: #d1e7dd;
  border-color: #badbcc;
  color: #0f5132;
}
</style>