<template>
  <GuestLayout>
    <div class="section">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-teal" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Fetching specific resource details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="alert alert-danger text-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      </div>

      <!-- Main Content -->
      <div v-else-if="resource" class="container-fluid">
        <!-- Modern Header -->
        <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white border-start border-5 border-teal">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                  <li class="breadcrumb-item"><router-link to="/guest-resources" class="text-teal text-decoration-none">Resources</router-link></li>
                  <li class="breadcrumb-item active" aria-current="page">Details</li>
                </ol>
              </nav>
              <h2 class="mb-0 fw-bold text-dark-teal">{{ resource.name }}</h2>
            </div>
            <div class="d-flex gap-2">
              <span class="badge" :class="getStatusClass(resource.status)">{{ resource.status }}</span>
              <button @click="navigateToBooking" class="btn btn-teal-modern btn-sm px-4 rounded-pill shadow-sm">
                <i class="bi bi-calendar-check me-2"></i>Reserve Now
              </button>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left: Images & Description -->
          <div class="col-lg-7">
            <!-- Image Carousel/Container -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden rounded-4">
              <div class="resource-main-image-container">
                <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid w-100 h-100 object-fit-cover">
              </div>
            </div>

            <!-- Full Description Card -->
            <div class="card shadow-sm border-0 mb-4 p-4">
              <h5 class="fw-bold text-dark-teal mb-3"><i class="bi bi-info-circle me-2 text-teal"></i>About this Resource</h5>
              <p class="text-muted lh-lg">{{ resource.description || 'No detailed description provided for this resource.' }}</p>
              
              <hr class="my-4 opacity-10">

              <div class="row g-3">
                <div class="col-sm-6">
                   <div class="d-flex align-items-center p-3 bg-light rounded-3 border-start border-3 border-teal">
                      <div class="me-3 fs-3 text-teal"><i class="bi bi-geo-alt"></i></div>
                      <div>
                        <div class="x-small text-muted text-uppercase fw-bold">Location</div>
                        <div class="fw-bold text-dark-teal">{{ resource.location_name || 'Main Campus' }}</div>
                      </div>
                   </div>
                </div>
                <div class="col-sm-6">
                   <div class="d-flex align-items-center p-3 bg-light rounded-3 border-start border-3 border-teal">
                      <div class="me-3 fs-3 text-teal"><i class="bi bi-people"></i></div>
                      <div>
                        <div class="x-small text-muted text-uppercase fw-bold">Capacity</div>
                        <div class="fw-bold text-dark-teal">{{ resource.capacity || 'Not Specified' }} Persons</div>
                      </div>
                   </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Schedule & Rates -->
          <div class="col-lg-5">
            <!-- Weekly Availability -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light">
                <h6 class="mb-0 text-dark-teal fw-bold"><i class="bi bi-calendar-event me-2 text-teal"></i>Weekly Availability</h6>
              </div>
              <div class="card-body">
                <div v-if="!resource.availability || resource.availability.length === 0" class="text-muted text-center py-4">
                  No weekly schedule defined for this resource.
                </div>
                <div v-else class="availability-list">
                   <div v-for="day in sortedAvailability" :key="day.day_name" 
                        class="d-flex justify-content-between align-items-center p-2 mb-2 rounded border-start border-3"
                        :class="day.is_available ? 'bg-light-teal-hint border-success' : 'bg-light border-secondary opacity-50'">
                        <span class="fw-medium small">{{ day.day_name }}</span>
                        <div class="text-end">
                            <span v-if="day.is_available" class="badge-dot-success x-small fw-bold text-success">
                                {{ day.slots && day.slots.length > 0 ? `${formatTime(day.slots[0].start_time)} - ${formatTime(day.slots[0].end_time)}` : 'Available' }}
                            </span>
                            <span v-else class="x-small text-muted fw-bold">Closed</span>
                        </div>
                   </div>
                </div>
              </div>
            </div>

            <!-- Included Features -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light">
                <h6 class="mb-0 text-dark-teal fw-bold"><i class="bi bi-collection me-2 text-teal"></i>Included Features</h6>
              </div>
              <div class="card-body px-4">
                <ul class="list-unstyled mb-0">
                  <li v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted small">
                     No additional equipment listed as included.
                  </li>
                  <li v-else v-for="item in resource.equipment" :key="item.id" class="mb-3 d-flex align-items-center">
                    <div class="feature-icon me-3 bg-teal bg-opacity-10 text-teal">
                      <i class="bi bi-check2"></i>
                    </div>
                    <div>
                      <div class="fw-bold small">{{ item.equipment_name }}</div>
                      <div class="x-small text-muted">Standard included qty: {{ item.quantity }}</div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Booking Shortcut -->
            <div class="card border-0 bg-dark-teal text-white p-4 shadow-lg rounded-4 overflow-hidden position-relative">
               <div class="position-relative z-index-1">
                  <h4 class="fw-bold mb-1">Reserve this Space</h4>
                  <p class="small opacity-75 mb-4">Starting from LKR {{ resource.base_price }}/hr. Click below to continue to the booking interface.</p>
                  <button @click="navigateToBooking" class="btn btn-teal-light w-100 py-2 fw-bold rounded-pill">
                    Proceed to Booking Form
                  </button>
               </div>
               <div class="decorative-blob"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import GuestLayout from '../../layouts/GuestLayout.vue';

const route = useRoute();
const router = useRouter();

const resource = ref<any>(null);
const isLoading = ref(true);
const errorMessage = ref('');

const API_BASE_URL = 'http://localhost:8000/api';

const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => 
    daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name)
  );
});

onMounted(() => {
  loadResourceDetails();
});

const loadResourceDetails = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        const id = route.params.id;
        const token = localStorage.getItem('authToken');
        const response = await axios.get(`${API_BASE_URL}/resources/${id}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        resource.value = response.data.resource || response.data;
    } catch (err: any) {
        console.error('Error loading resource details:', err);
        errorMessage.value = err.response?.data?.message || 'Failed to load resource details.';
    } finally {
        isLoading.value = false;
    }
};

const getImageUrl = (res: any) => {
    if (res.images && res.images.length > 0) {
        return `${API_BASE_URL}/resources/storage/${res.images[0].file_path}`;
    }
    return 'https://via.placeholder.com/1200x800?text=No+Image+Available';
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'Active': return 'bg-success';
        case 'Maintenance': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
};

const formatTime = (time: string) => {
    if (!time) return '';
    return time.substring(0, 5);
};

const navigateToBooking = () => {
    router.push(`/guest-resources/${resource.value.id}/book`);
};
</script>

<style scoped>
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-dark-teal { background-color: #1a3a3d; }
.btn-teal-light { background-color: white; color: #1e4449; border: none; }
.btn-teal-light:hover { background-color: #f8f9fa; }

.section {
  margin-left: 260px;
  padding: 24px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section { margin-left: 85px; }
}

.dashboard-header-modern {
    background: linear-gradient(to right, #ffffff, #f7fdf4);
}

.resource-main-image-container {
    height: 450px;
}

.btn-teal-modern {
    background: linear-gradient(135deg, #1e4449 0%, #2c5f65 100%);
    color: white;
}

.bg-light-teal-hint { background-color: #f7fdf4; }

.badge-dot-success::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    background-color: #28a745;
    border-radius: 50%;
    margin-right: 5px;
}

.feature-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.decorative-blob {
    position: absolute;
    bottom: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.x-small { font-size: 0.75rem; }
</style>
