<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resource Details: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
      </div>

    <div v-if="!resource" class="alert alert-danger text-center">
        Resource not found. Please ensure the resource exists and try again.
    </div>

    <div v-else class="resource-detail-container">
      <div class="row g-4">
        
        <div class="col-lg-6">
          <div class="card p-3 h-100 resource-main-details">
            <div class="resource-image-lg mb-3">
              <img :src="resource.image || 'https://via.placeholder.com/600x400?text=No+Image'" :alt="resource.name" class="img-fluid rounded">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                <span :class="resource.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'" class="fs-6">
                    {{ resource.status.toUpperCase() }}
                </span>
                <span class="fw-bold fs-5 text-dark-teal">
                    Base Price: {{ resource.price !== null && resource.price !== undefined ? `Rs. ${resource.price.toFixed(2)}` : 'N/A (Free)' }}
                </span>
            </div>

            <h5 class="text-dark-teal mb-2">Description</h5>
            <p>{{ resource.description || 'No detailed description available.' }}</p>

              <button 
                    v-if="resource.status === 'active'"
                    class="btn btn-sm btn-reserve-card" 
                    @click.stop="handleReserveClick(resource.id)"
                >
                    <i class="bi bi-calendar-check me-1"></i> Reserve
                </button>
            
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card p-4 h-100">
            
            <div class="details-list mb-4 pb-3 border-bottom">
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Resource Name</h6>
                    <p class="fw-bold">{{ resource.name }}</p>
                </div>
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Location Name</h6>
                    <p>{{ resource.location || 'N/A' }}</p>
                </div>
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Category</h6>
                    <p class="fw-bold">{{ resource.category }}</p>
                </div>
                <div class="detail-item">
                    <h6 class="text-muted mb-0">Assigned Person</h6>
                    <p class="fw-bold">{{ resource.assignee || 'Unassigned' }}</p>
                </div>
            </div>

            <div class="schedule-details mb-4 pb-3 border-bottom">
                <h6 class="text-muted fw-bold mb-3">Weekly Availability</h6>
                
                <div v-if="!resource.schedule || resource.schedule.length === 0" class="text-muted small">
                    Schedule not defined.
                </div>
                
                <ul v-else class="list-unstyled schedule-list">
                    <li v-for="day in resource.schedule" :key="day.dayName" class="d-flex justify-content-between align-items-center small">
                        <span class="fw-medium">{{ day.dayName }}</span>
                        
                        <span :class="day.available ? 'text-success fw-medium' : 'text-danger'">
                            <span v-if="day.available">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                {{ day.startTime }} - {{ day.endTime }}
                            </span>
                            <span v-else>
                                <i class="bi bi-x-circle-fill me-1"></i>
                                Unavailable
                            </span>
                        </span>
                        </li>
                </ul>
            </div>

            <div class="equipment-details">
                <h6 class="text-muted fw-bold mb-2">Included Equipment/Accessories</h6>
                <ul class="list-unstyled equipment-display-list">
                    <li v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted small">
                        No custom equipment listed.
                    </li>
                    <li v-else v-for="(item, index) in resource.equipment" :key="index" class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-medium">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>{{ item.name }}
                        </span>
                        <span class="text-secondary small ms-3">
                            (Rs. {{ item.price !== null && item.price !== undefined ? item.price.toFixed(2) : '0.00' }})
                        </span>
                    </li>
                </ul>
            </div>
           
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter} from 'vue-router';
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

interface EquipmentItem {
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

interface Resource {
    id: number;
    name: string;
    location?: string;
    category: string;
    price: number | null; 
    assignee?: string;
    description?: string;
    status: 'active' | 'inactive';
    image: string;
    equipment?: EquipmentItem[]; 
    schedule?: ScheduleDay[]; 
}

const route = useRoute();
const resource = ref<Resource | null>(null);
const router = useRouter();

// Function to load resources from Local Storage, initializing if empty
const getCombinedResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    
    if (!storedResourcesString) {
        return [];
    }
    return JSON.parse(storedResourcesString);
};

const handleReserveClick = (id: number) => {
    router.push({ path: '/single-resource-booking', query: { resourceId: id } });
};

const fetchResourceDetails = (id: number) => {
    // Fetch the LATEST data from Local Storage
    const latestResources = getCombinedResources();
    
    const fetchedResource = latestResources.find(r => r.id === id);
    if (fetchedResource) {
        // Ensure price is read correctly
        if (fetchedResource.price === undefined) fetchedResource.price = null;
        if (fetchedResource.equipment === undefined) fetchedResource.equipment = [];
        if (fetchedResource.schedule === undefined) fetchedResource.schedule = [];
        
        resource.value = fetchedResource;
    } else {
        resource.value = null;
    }
};

onMounted(() => {
    const resourceId = parseInt(route.params.id as string);
    if (!isNaN(resourceId)) {
        fetchResourceDetails(resourceId);
    }
});
</script>

<style scoped>
.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}
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
.card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.resource-image-lg {
    max-height: 350px; 
    overflow: hidden;
    border-radius: 6px;
}
.resource-image-lg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Base styles for list items/details */
.details-list h6, .schedule-details h6, .equipment-details h6 {
    font-size: 0.95rem;
    font-weight: 600;
}
.details-list p {
    font-size: 1rem;
    color: #343a40; 
    margin-bottom: 0;
}

/* Schedule List Styling (Simplified) */
.schedule-list li {
    padding: 4px 0;
    font-size: 0.9rem;
}


/* Badge colors */
.bg-success {
    background-color: #4BB66D !important;
}
.bg-secondary {
    background-color: #6c757d !important;
}
.text-success {
    color: #4BB66D !important;
}
.text-danger {
    color: #dc3545 !important;
}
.text-secondary {
    color: #6c757d !important;
}

.btn-reserve-card {
    background-color: #1e4449;
    color: white;
    border-color: #1e4449;
    font-size: 0.8rem;
    padding: 0.25rem 0.6rem;
    line-height: 4; /* Ensure button height is small */
    margin-top: 10%;
}
.btn-reserve-card:hover {
    background-color: #fcc300;
    color: #1e4449;
    border-color: #fcc300;
}
</style>