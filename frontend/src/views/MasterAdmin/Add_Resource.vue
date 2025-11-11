<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">{{ isEditMode ? 'Edit Resource' : 'Add New Resource' }}</h2>
      <button class="btn btn-outline-dark-teal" @click="handleCancel">
        <i class="bi bi-x-circle me-1"></i>Cancel
      </button>
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
            <label for="locationName" class="form-label fw-bold">Location Name</label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="newResource.location"
              placeholder="e.g., Building C, Floor 2"
            >
          </div>

          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select class="form-select" id="resourceCategory" v-model="newResource.category" required>
              <option value="" disabled>Select a Category</option>
              <option value="Academic Space">Academic Space</option>
              <option value="Medical & Health">Medical & Health</option>
              <option value="Sports & Recreational">Sports & Recreational</option>
              <option value="IT Space">IT Space</option>
              <option value="Cultural">Cultural</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="resourcePrice" class="form-label fw-bold">Resource Base Price (Rs.)</label>
            <input 
              type="number" 
              class="form-control" 
              id="resourcePrice" 
              v-model.number="newResource.price"
              placeholder="e.g., 500.00 Rs."
              min="0"
              step="0.01"
            >
          </div>
          
          <div class="col-md-6">
            <label for="assignee" class="form-label fw-bold">Assign Person Name</label>
            <select class="form-select" id="assignee" v-model="newResource.assignee">
              <option value="">No Assignee</option>
              <option v-for="person in assignees" :key="person.id" :value="person.name">{{ person.name }}</option>
            </select>
          </div>
          
          <div class="col-md-6"></div>

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
                    <div class="form-check me-3 flex-shrink-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            v-model="item.checked"
                            :id="'equipment-' + item.id"
                        >
                    </div>

                    <div class="flex-grow-1 me-3">
                        <input 
                            type="text" 
                            class="form-control form-control-sm" 
                            v-model="item.name" 
                            placeholder="Equipment Name (e.g., Projector)"
                            required
                        >
                    </div>

                    <div class="input-group input-group-sm w-25 me-2 flex-shrink-0">
                        <span class="input-group-text">Rs.</span>
                        <input 
                            type="number" 
                            class="form-control" 
                            v-model.number="item.price"
                            min="0"
                            step="0.01"
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
            <small class="form-text text-muted">Define custom equipment, its price, and mark if it should be included.</small>
          </div>
          <div class="col-12">
            <label for="resourcePhotoFile" class="form-label fw-bold">Upload Photo</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourcePhotoFile" 
              @change="handleFileUpload" 
              accept="image/*"
            >
            <small class="form-text text-muted">Select a file from your device to display below.</small>
          </div>

          <div class="col-12">
            <label for="resourcePhotoUrl" class="form-label fw-bold">Photo (Image URL)</label>
            <input 
              type="url" 
              class="form-control" 
              id="resourcePhotoUrl" 
              v-model="newResource.image" 
              placeholder="Paste image URL here (e.g., https://example.com/photo.jpg)"
            >
            <div v-if="newResource.image" class="mt-2">
              <img :src="newResource.image" alt="Resource Preview" class="img-thumbnail" style="max-height: 150px;">
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

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save me-1"></i> {{ isEditMode ? 'Update Resource' : 'Save Resource' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const router = useRouter();
const route = useRoute();

// --- DEFINITIONS ---

interface EquipmentItem {
    id: number;
    name: string;
    price: number | null;
    checked: boolean;
}

interface ScheduleDay {
    dayName: string;
    available: boolean;
    startTime: string; 
    endTime: string;   
}

interface NewResource {
    id: number;
    name: string;
    location: string;
    category: string;
    price: number | null; 
    assignee: string;
    description: string;
    status: 'active' | 'inactive';
    image: string; 
    equipment: EquipmentItem[]; 
    schedule: ScheduleDay[]; // Weekly schedule array
}

// Global counter for unique IDs for dynamic equipment rows
let equipmentIdCounter = 1;

// Base structure for a full weekly schedule (UPDATED: default to false)
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
    id: 0,
    name: '',
    location: '',
    category: '',
    price: null, 
    assignee: '',
    description: '',
    status: 'active',
    image: '',
    equipment: [], 
    schedule: JSON.parse(JSON.stringify(defaultSchedule)), 
};

const newResource = ref<NewResource>({ ...initialResourceState });

const assignees = ref([
    { id: 101, name: 'Alice Johnson' },
    { id: 102, name: 'Bob Smith' },
    { id: 103, name: 'Charlie Davis' },
]);

// --- EQUIPMENT MANAGEMENT ---

const addEquipment = (name: string = 'New Equipment', price: number = 0) => {
    newResource.value.equipment.push({
        id: equipmentIdCounter++,
        name: name,
        price: price,
        checked: true,
    });
};

const removeEquipment = (index: number) => {
    newResource.value.equipment.splice(index, 1);
};

// --- UTILITIES ---

const isEditMode = computed(() => route.query.mode === 'edit' && !!route.query.id);

const getStoredResources = () => {
    const storedResourcesString = localStorage.getItem('resources');
    return storedResourcesString ? JSON.parse(storedResourcesString) : [];
};

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = (e) => {
      if (e.target && typeof e.target.result === 'string') {
        newResource.value.image = e.target.result; 
      }
    };
    reader.readAsDataURL(file); 
  }
};

// --- LOGIC HANDLERS ---

const prepareEquipmentForSave = (): EquipmentItem[] => {
    return newResource.value.equipment
        .filter(item => item.checked && item.name.trim())
        .map(item => ({
            name: item.name.trim(),
            price: item.price === null || isNaN(Number(item.price)) ? 0 : Number(item.price),
            checked: true,
        })) as EquipmentItem[];
};


const handleSave = () => {
    let resources = getStoredResources();
    const maxId = resources.reduce((max: number, r: any) => (r.id > max ? r.id : max), 0);
    newResource.value.id = maxId + 1;

    if (!newResource.value.image) {
      newResource.value.image = 'https://via.placeholder.com/300x180?text=New+Resource';
    }
    
    const resourceToSave = { 
        ...newResource.value, 
        price: newResource.value.price === null ? null : Number(newResource.value.price),
        equipment: prepareEquipmentForSave(), 
    };

    resources.push(resourceToSave);
    localStorage.setItem('resources', JSON.stringify(resources));
    router.push('/master-admin/resource'); 
};

const handleUpdate = () => {
    let resources = getStoredResources();
    const idToUpdate = parseInt(route.query.id as string);
    
    if (isNaN(idToUpdate)) return;

    const index = resources.findIndex((r: NewResource) => r.id === idToUpdate);
    
    if (index !== -1) {
        const resourceToUpdate = { 
            ...newResource.value, 
            id: idToUpdate,
            price: newResource.value.price === null ? null : Number(newResource.value.price),
            equipment: prepareEquipmentForSave(),
        };
        
        resources[index] = resourceToUpdate;
        
        localStorage.setItem('resources', JSON.stringify(resources));
        router.push('/master-admin/resource'); 
    } else {
        alert("Error: Resource ID not found for update.");
    }
};


const handleCancel = () => {
    router.push('/'); 
};

// --- Auto-Fill Logic (on Component Load) ---
onMounted(() => {
    // Deep copy of schedule is required to avoid modifying the defaultSchedule constant
    let currentSchedule = JSON.parse(JSON.stringify(defaultSchedule)); // Use let instead of const

    if (isEditMode.value) {
        const idToEdit = parseInt(route.query.id as string);
        const resources = getStoredResources();
        const resourceToEdit = resources.find((r: NewResource) => r.id === idToEdit);

        if (resourceToEdit) {
            
            // Load primary data and existing schedule/equipment
            Object.assign(newResource.value, resourceToEdit);

            // Load complex equipment data
            if (Array.isArray(resourceToEdit.equipment)) {
                newResource.value.equipment = resourceToEdit.equipment.map(item => ({
                    id: equipmentIdCounter++,
                    name: item.name,
                    price: item.price !== undefined && item.price !== null ? Number(item.price) : 0,
                    checked: true,
                }));
            }
            
            // Load complex schedule data: Merge saved data over fresh schedule defaults
            if (Array.isArray(resourceToEdit.schedule)) {
                const newScheduleMap = new Map(resourceToEdit.schedule.map(d => [d.dayName, d]));
                
                newResource.value.schedule = currentSchedule.map(defaultDay => {
                    const savedDay = newScheduleMap.get(defaultDay.dayName);
                    return savedDay ? savedDay : defaultDay;
                });
            } else {
                 newResource.value.schedule = currentSchedule;
            }

            newResource.value.price = resourceToEdit.price === undefined ? null : Number(resourceToEdit.price);
            
            // Re-assign schedule to the ref so the component uses the merged/saved data
            // (This step is often redundant with Object.assign but ensures clarity)
            // newResource.value.schedule = newResource.value.schedule;

        } else {
            alert("Resource not found. Redirecting to resource list.");
            router.push('/'); 
        }
    } else if (newResource.value.equipment.length === 0) {
        // Initialize equipment and schedule for Add New mode
        newResource.value.schedule = currentSchedule;
        addEquipment('Projector', 150);
        addEquipment('AC Unit', 500);
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
</style>