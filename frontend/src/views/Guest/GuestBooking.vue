<template>
  <GuestLayout>
    <div class="section">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-teal" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Preparing booking interface...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
        <button class="btn btn-sm btn-outline-danger ms-3" @click="loadResourceDetails">
          <i class="bi bi-arrow-clockwise me-1"></i>Retry
        </button>
      </div>

      <!-- Main Content -->
      <div v-else-if="resource" class="container-fluid">
        <!-- Dashboard Style Header -->
        <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white border-start border-5 border-teal">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                  <li class="breadcrumb-item"><router-link to="/guest-resources" class="text-teal text-decoration-none">Resources</router-link></li>
                  <li class="breadcrumb-item active" aria-current="page">{{ resource.name }}</li>
                </ol>
              </nav>
              <h2 class="mb-0 fw-bold text-dark-teal">Secure Reservation</h2>
              <p class="text-muted mb-0">Complete the form below to request access to this facility.</p>
            </div>
            <div class="text-end d-none d-md-block">
               <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
                 <i class="bi bi-shield-lock me-1"></i> Secure Booking
               </span>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left Column - Calendar & Resource Info -->
          <div class="col-lg-8">
            <!-- Resource Summary Card -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
              <div class="row g-0">
                <div class="col-md-4">
                  <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid h-100 object-fit-cover" style="min-height: 200px;">
                </div>
                <div class="col-md-8">
                  <div class="card-body">
                    <h5 class="card-title fw-bold text-dark-teal">{{ resource.name }}</h5>
                    <p class="card-text text-muted small mb-3">{{ resource.description?.substring(0, 150) }}...</p>
                    <div class="d-flex gap-3 flex-wrap">
                      <div class="small"><i class="bi bi-geo-alt text-teal me-1"></i>{{ resource.location_name || 'N/A' }}</div>
                      <div class="small"><i class="bi bi-tag text-teal me-1"></i>{{ resource.category?.name || 'Unknown' }}</div>
                      <div class="small"><i class="bi bi-cash-stack text-teal me-1"></i>LKR {{ resource.base_price }}/hr</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Availability Calendar Visual (Placeholder for now, but stylized) -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 text-dark-teal"><i class="bi bi-calendar3 me-2"></i>Resource Availability</h5>
              </div>
              <div class="card-body">
                 <div class="row g-2">
                    <div v-for="day in sortedAvailability" :key="day.day_name" class="col">
                        <div class="text-center p-2 rounded border" :class="day.is_available ? 'bg-light-teal-soft border-teal-subtle' : 'bg-light border-light-subtle opacity-50'">
                            <div class="small fw-bold">{{ day.day_name.substring(0, 3) }}</div>
                            <div style="font-size: 0.65rem;" :class="day.is_available ? 'text-success' : 'text-danger'">
                                {{ day.is_available ? 'OPEN' : 'CLOSED' }}
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="mt-4">
                    <p class="small text-muted mb-2">Available slots on selected days:</p>
                    <div class="d-flex flex-wrap gap-2">
                         <div v-for="day in sortedAvailability.filter(d => d.is_available)" :key="'slots-'+day.day_name" class="p-2 px-3 bg-light rounded text-dark small border">
                            <strong>{{ day.day_name }}:</strong> 
                            <span v-if="day.slots && day.slots.length > 0">
                                {{ formatTimeShort(day.slots[0].start_time) }} - {{ formatTimeShort(day.slots[0].end_time) }}
                            </span>
                            <span v-else>All Day</span>
                         </div>
                    </div>
                 </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Booking Form -->
          <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
              <div class="card-header bg-teal text-white py-3 text-center">
                <h5 class="mb-0 fw-bold">Reservation Form</h5>
              </div>
              <div class="card-body p-4">
                <form @submit.prevent="createBookingAndSendOTP">
                  
                  <div v-if="isResourceUnavailable" class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span>This resource is not available on the selected date.</span>
                  </div>

                  <div v-if="isBookingConflict" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-calendar-x-fill me-2 fs-5"></i>
                    <span>This time slot is already reserved. Please try another time.</span>
                  </div>

                  <!-- Booking Inputs -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Confirm Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-teal"></i></span>
                        <input type="email" class="form-control border-start-0" v-model="bookingForm.email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-text x-small">Notification will be sent to this email.</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Select Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-day text-teal"></i></span>
                        <input type="date" class="form-control border-start-0" v-model="bookingForm.date" :min="minDate" required>
                    </div>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">From</label>
                      <input type="time" class="form-control" v-model="bookingForm.startTime" required>
                    </div>
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">To</label>
                      <input type="time" class="form-control" v-model="bookingForm.endTime" required>
                    </div>
                  </div>

                  <!-- Equipment Selection -->
                  <div class="mb-4">
                    <label class="form-label small fw-bold text-dark-teal mb-2">Optional Accessories</label>
                    <div class="equipment-box p-3 rounded border border-light-subtle bg-light-teal-hint">
                        <div class="input-group input-group-sm mb-2">
                           <span class="input-group-text bg-white"><i class="bi bi-search text-teal"></i></span>
                           <input type="text" class="form-control shadow-none" placeholder="Search equipment..." v-model="equipmentSearch" @input="searchEquipment">
                        </div>

                        <div v-if="showEquipmentDropdown && filteredEquipment.length > 0" class="equipment-dropdown shadow-sm rounded border">
                            <div v-for="item in filteredEquipment" :key="item.id" @click="addEquipmentItem(item)" class="p-2 border-bottom hover-bg-teal-light small cursor-pointer">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-medium">{{item.name}}</span>
                                    <span class="text-teal">LKR {{item.price_per_hour}}/hr</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedEquipment.length > 0" class="mt-2">
                            <div v-for="(item, index) in selectedEquipment" :key="item.id" class="d-flex justify-content-between align-items-center mb-1 small bg-white p-2 rounded shadow-xs">
                                <span class="text-truncate fw-medium" style="max-width: 60%;">{{item.name}} (x{{item.quantity}})</span>
                                <button type="button" @click="removeEquipmentItem(index)" class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <div v-else class="text-center py-2 text-muted x-small">
                            No equipment selected.
                        </div>
                    </div>
                  </div>

                  <!-- Cost Summary Modernized -->
                  <div class="pricing-summary rounded p-3 mb-4 border border-teal-subtle bg-white shadow-sm">
                    <div class="d-flex justify-content-between small mb-2">
                      <span class="text-muted">Resource Fee</span>
                      <span class="fw-bold">LKR {{ calculatedCost.toLocaleString() }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2 pb-2 border-bottom">
                      <span class="text-muted">Equipment Fee</span>
                      <span class="fw-bold">LKR {{ equipmentTotalCost.toLocaleString() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="text-dark-teal fw-bold">Estimated Total</span>
                      <span class="fs-4 fw-bold text-teal">LKR {{ totalBookingCost.toLocaleString() }}</span>
                    </div>
                  </div>

                  <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-teal-modern py-3 shadow-sm rounded-3" :disabled="isBookingConflict || isResourceUnavailable || isSubmitting">
                      <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-check2-circle me-2"></i> Confirm Reservation
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- OTP Modal Modernized -->
      <div class="modal fade" id="otpModal" tabindex="-1" role="dialog" data-bs-backdrop="static" ref="otpModalRef">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg overflow-hidden rounded-4">
            <div class="modal-header bg-dark-teal text-white border-0 py-3">
              <h5 class="modal-title fs-6"><i class="bi bi-shield-lock-fill me-2"></i>Identity Verification</h5>
            </div>
            <div class="modal-body p-4 text-center">
              <div class="mb-4">
                <div class="otp-pulse-circle mx-auto mb-4">
                  <i class="bi bi-key-fill text-teal fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark-teal mb-2">Verify Request</h4>
                <p class="text-muted">Enter the 6-digit code sent to<br><span class="fw-bold text-teal">{{ bookingForm.email }}</span></p>
              </div>

              <div class="otp-fields d-flex justify-content-center gap-2 mb-4">
                <input type="text" class="form-control otp-main-input text-center fw-bold fs-3" 
                       v-model="otpCode" maxlength="6" 
                       placeholder="••••••"
                       @keyup.enter="verifyOTP">
              </div>

              <div class="d-grid gap-3 px-4">
                <button @click="verifyOTP" class="btn btn-teal-modern py-2 rounded-pill" :disabled="isVerifying || otpCode.length < 6">
                  <span v-if="isVerifying" class="spinner-border spinner-border-sm me-2"></span>
                  Verify & Submit
                </button>
                <div class="text-center">
                    <button @click="resendOTP" class="btn btn-link text-teal text-decoration-none small fw-bold" :disabled="isResending || otpTimer > 0">
                      {{ otpTimer > 0 ? `Resend available in ${otpTimer}s` : 'Resend Code Now' }}
                    </button>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 justify-content-center">
                <span class="x-small text-muted"><i class="bi bi-info-circle me-1"></i> Check your spam folder if code not received.</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import * as bootstrap from 'bootstrap';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { bookingStore } from '../../store/bookingStore';

const route = useRoute();
const router = useRouter();

// State
const isLoading = ref(true);
const isSubmitting = ref(false);
const isVerifying = ref(false);
const isResending = ref(false);
const errorMessage = ref('');
const resource = ref<any>(null);
const equipment = ref<any[]>([]);
const bookings = ref<any[]>([]);

// Form State
const bookingForm = ref({
  email: localStorage.getItem('userEmail') || '',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '12:00',
  purpose: ''
});

// Equipment Search
const equipmentSearch = ref('');
const showEquipmentDropdown = ref(false);
const selectedEquipment = ref<any[]>([]);

// OTP State
const otpCode = ref('');
const otpTimer = ref(0);
const pendingBookingId = ref<number | null>(null);
const otpModalRef = ref<HTMLElement | null>(null);
let modalInstance: bootstrap.Modal | null = null;
let timerInterval: any = null;

const minDate = new Date().toISOString().split('T')[0];

const API_BASE_URL = 'http://localhost:8000/api';

// Computed
const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => 
    daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name)
  );
});

const isResourceUnavailable = computed(() => {
  if (!resource.value?.availability || !bookingForm.value.date) return false;
  const dayName = new Date(bookingForm.value.date).toLocaleDateString('en-US', { weekday: 'long' });
  const dayConfig = resource.value.availability.find((a: any) => a.day_name === dayName);
  return dayConfig ? !dayConfig.is_available : false;
});

const isBookingConflict = computed(() => {
  if (!bookings.value.length || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDate = bookingForm.value.date;
  const start = bookingForm.value.startTime;
  const end = bookingForm.value.endTime;

  return bookings.value.some(b => {
    if (b.status === 'Cancelled' || b.status === 'Rejected' || b.status === 'failed') return false;
    if (b.booking_date !== selectedDate) return false;
    
    // Check overlap: (StartA < EndB) and (EndA > StartB)
    return (start < b.end_time && end > b.start_time);
  });
});

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diffHours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
  return Math.max(0, Math.ceil(diffHours * resource.value.base_price));
});

const equipmentTotalCost = computed(() => {
  if (!bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diffHours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
  const hours = Math.max(0, Math.ceil(diffHours));

  return selectedEquipment.value.reduce((total, item) => {
    return total + (item.price_per_hour * item.quantity * hours);
  }, 0);
});

const totalBookingCost = computed(() => calculatedCost.value + equipmentTotalCost.value);

const filteredEquipment = computed(() => {
  if (!equipmentSearch.value) return equipment.value;
  const q = equipmentSearch.value.toLowerCase();
  return equipment.value.filter(e => e.name.toLowerCase().includes(q));
});

// Watchers
watch(() => bookingForm.value.date, (newVal) => {
    if (newVal) loadBookings();
});

// Lifecycle
onMounted(async () => {
  await loadResourceDetails();
  await loadEquipment();
  if (otpModalRef.value) {
    modalInstance = new bootstrap.Modal(otpModalRef.value);
  }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Methods
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
    await loadBookings();
  } catch (err: any) {
    console.error('Error loading resource details:', err);
    errorMessage.value = err.response?.data?.message || 'Failed to load resource details.';
  } finally {
    isLoading.value = false;
  }
};

const loadEquipment = async () => {
    try {
        const response = await axios.get(`${API_BASE_URL}/booking-items?type=equipment`);
        equipment.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
    } catch (e) {
        console.error("Failed to load equipment", e);
    }
};

const loadBookings = async () => {
    if (!resource.value) return;
    try {
        const response = await axios.get(`${API_BASE_URL}/bookings/resource/${resource.value.id}`);
        bookings.value = Array.isArray(response.data) ? response.data : (response.data.bookings || []);
    } catch (e) {
        console.error("Failed to load bookings", e);
    }
};

const getImageUrl = (res: any) => {
  if (res.images && res.images.length > 0) {
    return `${API_BASE_URL}/resources/storage/${res.images[0].file_path}`;
  }
  return 'https://via.placeholder.com/600x400?text=No+Resource+Image';
};

const formatTimeShort = (time: string) => {
  if (!time) return '';
  const [h, m] = time.split(':');
  const d = new Date();
  d.setHours(parseInt(h), parseInt(m));
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

// Form Logic
const searchEquipment = () => {
  showEquipmentDropdown.value = equipmentSearch.value.length > 0;
};

const addEquipmentItem = (item: any) => {
  const existing = selectedEquipment.value.find(e => e.id === item.id);
  if (existing) {
    if (existing.quantity < item.available_quantity) existing.quantity++;
  } else {
    selectedEquipment.value.push({ ...item, quantity: 1 });
  }
  equipmentSearch.value = '';
  showEquipmentDropdown.value = false;
};

const removeEquipmentItem = (index: number) => {
  selectedEquipment.value.splice(index, 1);
};

const createBookingAndSendOTP = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';
    
    try {
        const token = localStorage.getItem('authToken');
        const guestUserId = localStorage.getItem('userId');

        const payload: any = {
            user_id: guestUserId,
            user_email: bookingForm.value.email,
            booking_date: bookingForm.value.date,
            start_time: bookingForm.value.startTime,
            end_time: bookingForm.value.endTime,
            notes: bookingForm.value.purpose || 'Guest Booking Request',
            resources: [{ resource_id: resource.value.id }],
            booking_items: selectedEquipment.value.map(e => ({
                item_id: e.id,
                item_type: 'equipment',
                quantity: e.quantity
            }))
        };

        const response = await axios.post(`${API_BASE_URL}/bookings`, payload, {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.data.booking) {
            pendingBookingId.value = response.data.booking.id;
        } else if (response.data.id) {
            pendingBookingId.value = response.data.id;
        } else if (response.data.booking_id) {
            pendingBookingId.value = response.data.booking_id;
        }

        modalInstance?.show();
        startTimer();
    } catch (err: any) {
        alert(err.response?.data?.message || 'Failed to initiate booking.');
    } finally {
        isSubmitting.value = false;
    }
};

const startTimer = () => {
    otpTimer.value = 60;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (otpTimer.value > 0) otpTimer.value--;
        else clearInterval(timerInterval);
    }, 1000);
};

const verifyOTP = async () => {
    if (!pendingBookingId.value) return;
    isVerifying.value = true;
    try {
        const token = localStorage.getItem('authToken');
        await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, {
            otp_code: otpCode.value
        }, {
            headers: { Authorization: `Bearer ${token}` }
        });

        modalInstance?.hide();
        alert('Booking request submitted successfully! An admin will review it shortly.');
        router.push('/public-bookings');
    } catch (err: any) {
        alert(err.response?.data?.message || 'Invalid verification code.');
    } finally {
        isVerifying.value = false;
    }
};

const resendOTP = async () => {
    if (!pendingBookingId.value) return;
    isResending.value = true;
    try {
        const token = localStorage.getItem('authToken');
        await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, {}, {
            headers: { Authorization: `Bearer ${token}` }
        });
        startTimer();
    } catch (e) {
        alert('Failed to resend OTP.');
    } finally {
        isResending.value = false;
    }
};
</script>

<style scoped>
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-teal { background-color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.bg-light-teal-soft { background-color: #f7fdf4; }
.bg-light-teal-hint { background-color: #f9fbf8; }
.border-teal { border-color: #1e4449 !important; }
.border-teal-subtle { border-color: #d1e7dd !important; }

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

.btn-teal-modern {
    background: linear-gradient(135deg, #1e4449 0%, #2c5f65 100%);
    color: white;
    border: none;
    transition: all 0.2s ease;
    font-weight: 600;
}

.btn-teal-modern:hover {
    background: linear-gradient(135deg, #2c5f65 0%, #1e4449 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 68, 73, 0.2);
}

.btn-teal-modern:disabled {
    opacity: 0.6;
    transform: none;
}

.cursor-pointer { cursor: pointer; }

.hover-bg-teal-light:hover { background-color: #e5f4de; }

.equipment-dropdown {
    position: absolute;
    z-index: 1000;
    background: white;
    width: 250px;
    max-height: 200px;
    overflow-y: auto;
}

.otp-pulse-circle {
    width: 70px;
    height: 70px;
    background: #e5f4de;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.otp-main-input {
    letter-spacing: 0.5rem;
    font-size: 2rem !important;
    border-radius: 12px;
    border: 2px solid #e5f4de;
}

.otp-main-input:focus {
    border-color: #1e4449;
    box-shadow: 0 0 0 0.25rem rgba(30, 68, 73, 0.1);
}

.x-small { font-size: 0.75rem; }

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 68, 73, 0.2); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(30, 68, 73, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 68, 73, 0); }
}
</style>
