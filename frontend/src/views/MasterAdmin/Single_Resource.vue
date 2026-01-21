<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resource Details: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-dark-teal" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading resource details...</p>
    </div>

    <div v-else-if="errorMessage" class="alert alert-danger text-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
    </div>

    <div v-else-if="resource" class="resource-detail-container">
      <div class="row g-4">
        
        <div class="col-lg-6">
          <div class="card p-3 h-100 resource-main-details">
            <div class="resource-image-lg mb-3">
              <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid rounded">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
              <span :class="resource.status === 'Active' ? 'badge bg-success' : 'badge bg-secondary'" class="fs-6">
                  {{ resource.status.toUpperCase() }}
              </span>
              <span class="fw-bold fs-5 text-dark-teal">
                  Base Price: 
                  {{ resource.base_price !== null && resource.base_price !== undefined ? 
                     `Rs. ${resource.base_price.toFixed(2)}` : 
                     'N/A (Free)' 
                  }}
              </span>
            </div>

            <h5 class="text-dark-teal mb-2">Description</h5>
            <p>{{ resource.description || 'No detailed description available.' }}</p>

              <button 
                  v-if="resource.status === 'Active'"
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
                    <p class="fw-bold">{{ resource.location_name || 'N/A' }}</p>
                </div>
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Category</h6>
                    <p class="fw-bold">{{ resource.category?.name || 'Unknown' }}</p>
                </div>
                <div class="detail-item">
                    <h6 class="text-muted mb-0">Assigned Person</h6>
                    <div v-if="assignedAdminName">
                        <p class="fw-bold">{{ assignedAdminName }}</p>
                    </div>
                    <div v-else-if="resource.assigned_admin_id">
                        <p class="fw-bold text-muted">Loading admin name...</p>
                        <small class="text-muted">Admin ID: {{ resource.assigned_admin_id }}</small>
                    </div>
                    <div v-else>
                        <p class="fw-bold text-muted">Unassigned</p>
                    </div>
                </div>
            </div>

            <div class="schedule-details mb-4 pb-3 border-bottom">
                <h6 class="text-muted fw-bold mb-3">Weekly Availability</h6>
                
                <div v-if="!resource.availability || resource.availability.length === 0" class="text-muted small">
                    Schedule not defined.
                </div>
                
                <ul v-else class="list-unstyled schedule-list">
                    <li v-for="day in resource.availability" :key="day.day_name" class="d-flex justify-content-between align-items-center small">
                        <span class="fw-medium">{{ day.day_name }}</span>
                        
                        <span :class="day.is_available ? 'text-success fw-medium' : 'text-danger'">
                            <span v-if="day.is_available">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                {{ formatTime(day.start_time) }} - {{ formatTime(day.end_time) }}
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
                    <li v-else v-for="item in resource.equipment" :key="item.id" class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-medium">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>{{ item.equipment_name }}
                        </span>
                        <span class="text-muted">Qty: {{ item.quantity }}</span>
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
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL_ROOT = 'http://localhost:8000/storage';

// Interfaces adjusted to match backend response structure
interface ResourceImage {
    id: number;
    file_path: string;
    image_url?: string; 
}

interface ResourceEquipment {
    id: number;
    equipment_name: string;
    quantity: number;
}

interface ResourceAvailability {
    id: number;
    day_name: string;
    day_of_week: number;
    is_available: boolean;
    start_time: string | null;
    end_time: string | null;
}

interface ResourceCategory {
    id: number;
    name: string;
}

interface Resource {
    id: number;
    name: string;
    location_name: string;
    category_id: number;
    category: ResourceCategory;
    base_price: number | null;
    assigned_admin_id: number | null;
    description: string | null;
    status: 'Active' | 'Inactive' | 'Maintenance';
    images: ResourceImage[];
    equipment: ResourceEquipment[]; 
    availability: ResourceAvailability[]; 
}

// State
const resource = ref<Resource | null>(null);
const isLoading = ref(false);
const errorMessage = ref('');
const assignedAdminName = ref<string>('');
const isFetchingAdminName = ref(false);

// Helper to get auth token
const getAuthToken = (): string | null => {
    return localStorage.getItem('authToken') || localStorage.getItem('token');
};

// Helper Functions
const getImageUrl = (resource: Resource): string => {
    if (resource.images && resource.images.length > 0) {
        return `${STORAGE_URL_ROOT}/${resource.images[0].file_path}`; 
    }
    return 'https://via.placeholder.com/600x400?text=No+Image';
};

const formatTime = (time: string | null): string => {
    if (!time) return '00:00';
    return time.substring(0, 5); 
};

// Fetch admin details from users table - SIMPLIFIED VERSION
const fetchAdminDetails = async (adminId: number) => {
    if (!adminId) return;
    
    isFetchingAdminName.value = true;
    try {
        const token = getAuthToken();
        if (!token) {
            console.warn('No auth token found for admin fetch');
            return;
        }

        // OPTION 1: Try to fetch from users endpoint
        try {
            const response = await axios.get(`${API_BASE_URL}/users`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                }
            });
            
            const users = response.data.users || response.data || [];
            const adminUser = users.find((user: any) => user.id === adminId);
            
            if (adminUser) {
                if (adminUser.name) {
                    assignedAdminName.value = adminUser.name;
                } else if (adminUser.first_name && adminUser.last_name) {
                    assignedAdminName.value = `${adminUser.first_name} ${adminUser.last_name}`;
                } else if (adminUser.username) {
                    assignedAdminName.value = adminUser.username;
                } else if (adminUser.email) {
                    assignedAdminName.value = adminUser.email;
                } else {
                    assignedAdminName.value = `Admin ID: ${adminId}`;
                }
                return;
            }
        } catch (usersError) {
            console.log('Users endpoint not available or failed, trying admins...');
        }

        // OPTION 2: Try admins endpoint
        try {
            const response = await axios.get(`${API_BASE_URL}/admins/${adminId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                }
            });
            
            const adminData = response.data.admin || response.data;
            
            if (adminData.name) {
                assignedAdminName.value = adminData.name;
            } else if (adminData.first_name && adminData.last_name) {
                assignedAdminName.value = `${adminData.first_name} ${adminData.last_name}`;
            } else if (adminData.username) {
                assignedAdminName.value = adminData.username;
            } else {
                assignedAdminName.value = `Admin ID: ${adminId}`;
            }
        } catch (adminsError) {
            console.error('Both users and admins endpoints failed');
            assignedAdminName.value = `Admin ID: ${adminId}`;
        }
        
    } catch (error: any) {
        console.error('Error fetching admin details:', error);
        assignedAdminName.value = `Admin ID: ${adminId}`;
    } finally {
        isFetchingAdminName.value = false;
    }
};

// API Calls
const fetchResourceDetails = async (id: number) => {
    isLoading.value = true;
    errorMessage.value = '';
    assignedAdminName.value = ''; // Reset admin name
    
    try {
        const token = getAuthToken();
        if (!token) {
            errorMessage.value = 'Authentication required. Redirecting to login.';
            router.push('/login');
            return;
        }

        const response = await axios.get(`${API_BASE_URL}/resources/${id}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        
        const fetchedResource = response.data.resource || response.data;
        
        // Log the response to see what data we're getting
        console.log('Resource API Response:', fetchedResource);
        
        if (fetchedResource.base_price) {
            fetchedResource.base_price = parseFloat(fetchedResource.base_price);
        } else {
             fetchedResource.base_price = null;
        }
        
        if (fetchedResource.status) {
             fetchedResource.status = fetchedResource.status.charAt(0).toUpperCase() + fetchedResource.status.slice(1);
        }

        resource.value = fetchedResource as Resource;

        // Fetch admin name if admin ID exists
        if (fetchedResource.assigned_admin_id) {
            console.log('Fetching admin name for ID:', fetchedResource.assigned_admin_id);
            await fetchAdminDetails(fetchedResource.assigned_admin_id);
        } else {
            console.log('No assigned_admin_id found in resource data');
        }

    } catch (error: any) {
        console.error('Error fetching resource details:', error);
        if (error.response?.status === 404) {
            errorMessage.value = `Resource ID ${id} was not found.`;
        } else {
            errorMessage.value = 'Failed to load resource details. Please try again.';
        }
        resource.value = null;
    } finally {
        isLoading.value = false;
    }
};

const handleReserveClick = (id: number) => {
    router.push({ path: '/single-resource-booking', query: { resourceId: id } });
};

onMounted(() => {
    const resourceId = parseInt(route.params.id as string);
    if (!isNaN(resourceId)) {
        fetchResourceDetails(resourceId);
    } else {
        errorMessage.value = 'Invalid resource ID provided.';
        resource.value = null;
    }
});
</script>

<style scoped>
/* NOTE: Retaining your existing styles */
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

.details-list h6, .schedule-details h6, .equipment-details h6 {
    font-size: 0.95rem;
    font-weight: 600;
}
.details-list p {
    font-size: 1rem;
    color: #343a40; 
    margin-bottom: 0;
}

.schedule-list li {
    padding: 4px 0;
    font-size: 0.9rem;
}

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
    line-height: 1.5; 
    margin-top: 17%;
}
.btn-reserve-card:hover {
    background-color: #fcc300;
    color: #1e4449;
    border-color: #fcc300;
}
</style>