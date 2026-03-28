<template>
  <Navbar />
  <UserSidebar />
  <div class="section">
    <div class="dashboard-header mb-4">
      <h2 class="section-title">Available Resources</h2>
      <p class="text-muted">Browse and reserve university resources.</p>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input 
            type="text" 
            class="form-control" 
            placeholder="Search resources by name or category..." 
            v-model="searchQuery"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="resourceStore.isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading resources...</span>
      </div>
    </div>

    <!-- Resources Grid -->
    <div v-else class="row g-4">
      <div class="col-md-4 col-lg-3" v-for="resource in filteredResources" :key="resource.id">
        <div class="card resource-card h-100 shadow-sm border-0">
          <div class="card-img-container bg-light">
            <img 
              v-if="resource.images && resource.images.length > 0" 
              :src="'http://localhost:8000/api/resources/storage/' + resource.images[0].file_path" 
              class="card-img-top" 
              alt="Resource Image"
              @error="handleImageError"
            >
            <div v-else class="placeholder-img d-flex align-items-center justify-content-center h-100 text-muted">
              <i class="bi bi-image" style="font-size: 2rem;"></i>
            </div>
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title text-truncate">{{ resource.name }}</h5>
            <p class="card-text text-muted small mb-2 text-truncate">
              <i class="bi bi-geo-alt me-1"></i>{{ resource.location_name || 'N/A' }}
            </p>
            <div class="mb-3 mt-auto">
              <span class="badge bg-success me-2" v-if="resource.status === 'Active'">Available</span>
              <span class="badge bg-danger me-2" v-else>Unavailable</span>
              <span class="fw-bold text-primary">Rs. {{ resource.base_price }}<small class="text-muted fw-normal">/hr</small></span>
            </div>
            <!-- Disable button if not active -->
            <button 
                class="btn btn-outline-primary w-100" 
                @click="openReservationModal(resource)"
                :disabled="resource.status !== 'Active'"
            >
              <i class="bi bi-calendar-plus me-2"></i>Reserve
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="filteredResources.length === 0" class="col-12 text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <h5>No resources found</h5>
        <p>Try completely different search keywords or check back later.</p>
      </div>
    </div>

    <!-- Booking Modal (Simplified visual indication) -->
    <div class="modal fade" id="reserveModal" tabindex="-1" ref="reserveModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-light">
            <h5 class="modal-title">Reserve {{ selectedResource?.name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="submitReservation">
            <div class="modal-body">
              <div class="alert alert-info small">
                <i class="bi bi-info-circle me-2"></i>You are requesting a reservation for <strong>{{ selectedResource?.name }}</strong>. 
                Your booking requires admin approval.
              </div>

              <div class="mb-3">
                <label class="form-label">Booking Date</label>
                <input type="date" class="form-control" v-model="bookingForm.date" required :min="minDate">
              </div>

              <div class="row mb-3">
                <div class="col">
                  <label class="form-label">Start Time</label>
                  <input type="time" class="form-control" v-model="bookingForm.startTime" required>
                </div>
                <div class="col">
                  <label class="form-label">End Time</label>
                  <input type="time" class="form-control" v-model="bookingForm.endTime" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Additional Notes</label>
                <textarea class="form-control" rows="2" v-model="bookingForm.notes" placeholder="Purpose of booking..."></textarea>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import { resourceStore } from '../../store/resourceStore';
import axios from 'axios';
import * as bootstrap from 'bootstrap';

const searchQuery = ref('');
const selectedResource = ref<any>(null);
const reserveModalRef = ref<HTMLElement | null>(null);
let modalInstance: bootstrap.Modal | null = null;
const isSubmitting = ref(false);

const today = new Date();
const minDate = new Date(today.getTime() - (today.getTimezoneOffset() * 60000)).toISOString().split('T')[0];

const bookingForm = ref({
  date: minDate,
  startTime: '08:00',
  endTime: '10:00',
  notes: ''
});

onMounted(() => {
  resourceStore.fetchAll();
  if (reserveModalRef.value) {
    modalInstance = new bootstrap.Modal(reserveModalRef.value);
  }
});

const filteredResources = computed(() => {
  return resourceStore.resources.filter(r => {
    if (!searchQuery.value) return true;
    const lowerQ = searchQuery.value.toLowerCase();
    return r.name.toLowerCase().includes(lowerQ) || 
           (r.location_name && r.location_name.toLowerCase().includes(lowerQ));
  });
});

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.style.display = 'none';
  // Attempt to show placeholder next to it if we want, but display:none is ok
  if (target.nextElementSibling) {
      (target.nextElementSibling as HTMLElement).style.display = 'flex';
  }
};

const openReservationModal = (resource: any) => {
  selectedResource.value = resource;
  modalInstance?.show();
};

const submitReservation = async () => {
  isSubmitting.value = true;
  try {
    const token = localStorage.getItem('authToken');
    const userEmail = localStorage.getItem('userEmail');
    const userId = localStorage.getItem('userId') || 0; // External users might be 0, internal use real ID

    const payload = {
      user_id: parseInt(userId as string),
      user_email: userEmail,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.notes,
      resources: [
        { resource_id: selectedResource.value.id }
      ]
    };

    const response = await axios.post('http://localhost:8000/api/bookings', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data) {
      alert("Booking Request Initiated! Please check your email for the OTP to confirm this request.");
      modalInstance?.hide();
    }
  } catch (error: any) {
    console.error("Booking failed:", error);
    alert("Failed to submit booking: " + (error.response?.data?.message || error.message));
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.section {
  margin-left: 250px;
  padding: 20px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 70px;
  }
}

.dashboard-header {
  background-color: #e5f4de; 
  color: #1e4449; 
  padding: 20px; 
  border-radius: 10px;
}

.section-title {
  margin: 0;
  font-weight: 600;
}

.resource-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.resource-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

.card-img-container {
  height: 180px;
  overflow: hidden;
}

.card-img-top {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-img {
  background-color: #f8f9fa;
  color: #adb5bd;
}
</style>
