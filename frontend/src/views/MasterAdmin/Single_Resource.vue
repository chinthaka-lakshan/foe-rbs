<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resource Details: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
      </div>

    <div v-if="!resource" class="alert alert-danger text-center">
        Resource not found. Please check the URL or try again.
    </div>

    <div v-else class="resource-detail-container">
      <div class="row g-4">
        
        <div class="col-lg-6">
          <div class="card p-3 h-100">
            <div class="resource-image-lg mb-3">
              <img :src="resource.image || 'https://via.placeholder.com/600x400?text=No+Image'" :alt="resource.name" class="img-fluid rounded">
            </div>
            <h5 class="text-dark-teal mb-2">Description</h5>
            <p>{{ resource.description || 'No detailed description available.' }}</p>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <span :class="resource.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'" class="fs-6">
                    {{ resource.status.toUpperCase() }}
                </span>
            </div>
            
            <div class="details-list">
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
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Assigned Person</h6>
                    <p class="fw-bolt">{{ resource.assignee || 'Unassigned' }}</p>
                </div>
                
                <div class="detail-item mb-3 pb-2 border-bottom">
                    <h6 class="text-muted mb-0">Base Price</h6>
                    <p class="fw-bold fs-5 text-dark-teal">{{ resource.price !== null ? `Rs. ${resource.price.toFixed(2)}` : 'N/A' }}</p>
                </div>

                <div class="detail-item">
                    <h6 class="text-muted mb-2">Included Equipment/Accessories</h6>
                    <ul class="list-unstyled equipment-display-list">
                        <li v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted small">
                            No custom equipment listed.
                        </li>
                        <li v-else v-for="(item, index) in resource.equipment" :key="index" class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-medium">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>{{ item.name }}
                            </span>
                            <span class="text-secondary small ms-3">
                                (Rs. {{ item.price !== null ? item.price.toFixed(2) : '0.00' }})
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute} from 'vue-router';
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

interface EquipmentItem {
    name: string;
    price: number | null;
    checked: boolean; // Not strictly needed for display, but kept for consistency with storage structure
}

interface Resource {
    id: number;
    name: string;
    location?: string;
    category: string;
    price: number | null; // NEW FIELD
    assignee?: string;
    description?: string;
    status: 'active' | 'inactive';
    image: string;
    equipment?: EquipmentItem[]; // NEW FIELD
}

const route = useRoute();
const resource = ref<Resource | null>(null);

// **MANUAL DEFAULT DATA SOURCE (Updated to include price and equipment):**
const DEFAULT_RESOURCES: Resource[] = [
    { 
        id: 1, 
        name: 'Conference Room A', 
        location: 'Main Building, Lvl 3', 
        category: 'Academic Space', 
        price: 1500.00, // Added price
        assignee: 'Dr. Jane Doe', 
        description: 'Large conference room equipped with video conferencing tools, seating 50.', 
        status: 'active', 
        image: 'https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg?auto=compress&cs=tinysrgb&w=600',
        equipment: [{ name: 'Projector', price: 150, checked: true }, { name: 'Smart Board', price: 200, checked: true }], // Added equipment
    },
    { 
        id: 2, 
        name: 'Sports Hall', 
        location: 'Gymnasium Complex', 
        category: 'Sports & Recreational', 
        price: 800.00,
        assignee: 'Mr. John Smith', 
        description: 'Large multi-purpose sports hall for basketball, volleyball, etc. Includes changing rooms and seating.', 
        status: 'active', 
        image: 'https://images.pexels.com/photos/260024/pexels-photo-260024.jpeg?auto=compress&cs=tinysrgb&w=600',
        equipment: [{ name: 'Basketballs', price: 20, checked: true }],
    },
    { 
        id: 3, 
        name: 'Library Study Room', 
        location: 'Central Library, Zone C', 
        category: 'Academic Space', 
        price: 0,
        assignee: 'Ms. Emily Brown', 
        description: 'Quiet small group study room for up to 6 people. Booking is highly recommended.', 
        status: 'inactive', 
        image: 'https://images.pexels.com/photos/2041540/pexels-photo-2041540.jpeg?auto=compress&cs=tinysrgb&w=600',
        equipment: [],
    },
    { 
        id: 4, 
        name: 'Medical Lab', 
        location: 'Health Wing, Ground Floor', 
        category: 'Medical & Health', 
        price: 2500.50,
        assignee: 'Dr. Alan Turing', 
        description: 'Fully equipped clinical testing laboratory, strictly for staff use.', 
        status: 'active', 
        image: 'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=600',
        equipment: [{ name: 'Medical Scanner', price: 1500, checked: true }, { name: 'AC Unit', price: 500, checked: true }],
    },
    { id: 5, name: 'Basketball Court', location: 'Outdoor Courts', category: 'Sports & Recreational', price: 50.00, assignee: 'Mr. John Smith', description: 'Full-size outdoor basketball court with floodlights. Open 24/7.', status: 'active', image: 'https://images.pexels.com/photos/1752757/pexels-photo-1752757.jpeg?auto=compress&cs=tinysrgb&w=600', equipment: [] },
    { id: 6, name: 'Lecture Hall B', location: 'Faculty of Eng, Block B', category: 'Academic Space', price: 1200.00, assignee: 'Dr. Jane Doe', description: 'Medium lecture hall with seating for 100 students. Features Smart Board and sound system.', status: 'active', image: 'https://images.pexels.com/photos/267885/pexels-photo-267885.jpeg?auto=compress&cs=tinysrgb&w=600', equipment: [] },
];

// Function to load resources from Local Storage, initializing if empty
const getCombinedResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    
    if (!storedResourcesString || JSON.parse(storedResourcesString).length === 0) {
        // Use the updated DEFAULT_RESOURCES for initialization
        localStorage.setItem('resources', JSON.stringify(DEFAULT_RESOURCES));
        return DEFAULT_RESOURCES;
    }
    
    return JSON.parse(storedResourcesString);
};

const fetchResourceDetails = (id: number) => {
    // Fetch the LATEST data from Local Storage
    const latestResources = getCombinedResources();
    
    // Find the specific resource
    const fetchedResource = latestResources.find(r => r.id === id);
    if (fetchedResource) {
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
    max-height: 400px;
    overflow: hidden;
    border-radius: 6px;
}
.resource-image-lg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.details-list p {
    font-size: 1rem;
    color: #343a40; 
    margin-bottom: 0;
}
.details-list h6 {
    font-size: 0.85rem;
    font-weight: 500;
}

.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #fcc300;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #fcc300;
}
.bg-success {
    background-color: #4BB66D !important;
}

/* New style for the equipment list display */
.equipment-display-list li {
    padding: 3px 0;
}
.text-secondary {
    color: #6c757d !important;
}
</style>