<template>
  <navbar/>
  <master-admin-sidebar/>
  
  <div class="section">
    <!-- Debug Panel -->
    <div class="alert alert-info mb-3" v-if="!isLoading">
      <div class="d-flex justify-content-between align-items-center">
        <span><strong>Debug:</strong> API URL: {{ API_BASE_URL }}</span>
        <button class="btn btn-sm btn-outline-primary" @click="testAPIConnection">
          Test API Connection
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading resource details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadResourceDetails">
        <i class="bi bi-arrow-clockwise me-1"></i>Retry
      </button>
    </div>

    <!-- Validation Errors -->
    <div v-if="validationErrors.length > 0" class="alert alert-danger mb-3">
      <h6 class="alert-heading">Please fix the following errors:</h6>
      <ul class="mb-0">
        <li v-for="(error, index) in validationErrors" :key="index">{{ error }}</li>
      </ul>
    </div>

    <!-- Main Content -->
    <div v-else-if="resource" class="container-fluid">
      <div class="row">
        <!-- Left Column - Resource Details -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header bg-dark-teal text-white">
              <h4 class="mb-0">{{ resource.name }}</h4>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <strong><i class="bi bi-geo-alt me-2"></i>Location:</strong>
                    <p class="mb-0">{{ resource.location_name || 'N/A' }}</p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-tag me-2"></i>Category:</strong>
                    <p class="mb-0">{{ resource.category?.name || 'Unknown' }}</p>
                  </div>

                  <div class="mb-3">
                    <strong><i class="bi bi-building me-2"></i>Department:</strong>
                    <p class="mb-0">{{ resource.department?.name || 'Unknown' }}</p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-currency-rupee me-2"></i>Base Price:</strong>
                    <p class="mb-0">Rs. {{ resource.base_price }}/hour</p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-info-circle me-2"></i>Status:</strong>
                    <span class="badge" :class="getStatusClass(resource.status)">
                      {{ resource.status }}
                    </span>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="resource-image-large mb-3">
                    <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid rounded">
                  </div>
                  
                  <div v-if="resource.description" class="mb-3">
                    <strong><i class="bi bi-card-text me-2"></i>Description:</strong>
                    <p class="mb-0">{{ resource.description }}</p>
                  </div>
                  
                  <div v-if="resource.capacity" class="mb-3">
                    <strong><i class="bi bi-people me-2"></i>Capacity:</strong>
                    <p class="mb-0">{{ resource.capacity }} people</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Booking History Section -->
          <div class="card mt-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Booking History</h5>
              <button 
                class="btn btn-sm btn-outline-primary"
                @click="loadBookings"
                :disabled="isLoadingBookings"
              >
                <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoadingBookings }"></i>
                Refresh
              </button>
            </div>
            <div class="card-body">
              <div v-if="isLoadingBookings" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted small">Loading booking history...</p>
              </div>

              <div v-else-if="bookings.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">No bookings found for this resource</p>
              </div>

              <div v-else class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>No</th>
                      <th>Booking Date</th>
                      <th>Time Slot</th>
                      <th>Booked By</th>
                      <th>Amount</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(booking, index) in bookings" :key="booking.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ formatDate(booking.booking_date) }}</td>
                      <td>{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</td>
                      <td>{{ booking.user_email }}</td>
                      <td>Rs. {{ booking.total_amount }}</td>
                      <td>
                        <span class="badge" :class="getBookingStatusClass(booking.status)">
                          {{ booking.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Booking Form -->
        <div class="col-lg-4">
          <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">Book This Resource</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="submitBooking">
                <!-- Email Input -->
                <div class="mb-3">
                  <label for="user_email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>Email <span class="text-danger">*</span>
                  </label>
                  <input
                    type="email"
                    id="user_email"
                    class="form-control"
                    v-model="form.user_email"
                    required
                  >
                </div>

                <!-- Date Selection -->
                <div class="mb-3">
                  <label for="booking_date" class="form-label">Select Date <span class="text-danger">*</span></label>
                  <input
                    type="date"
                    id="booking_date"
                    class="form-control"
                    v-model="form.booking_date"
                    :min="minDate"
                    @change="onDateChange"
                    required
                  >
                </div>
                
                <!-- Time Slots -->
                <div v-if="selectedDayInfo && selectedDayInfo.slots.length > 0" class="mb-3">
                  <label class="form-label">Select Time Slot <span class="text-danger">*</span></label>
                  <div class="time-slots-grid">
                    <button
                      v-for="(slot, index) in selectedDayInfo.slots"
                      :key="index"
                      type="button"
                      class="btn time-slot-btn"
                      :class="{
                        'btn-outline-success': selectedSlotIndex !== index,
                        'btn-success': selectedSlotIndex === index
                      }"
                      @click="selectTimeSlot(index)"
                    >
                      <div>
                        <span>{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</span>
                        <small class="d-block">{{ calculateSlotDuration(slot) }} hrs</small>
                      </div>
                    </button>
                  </div>
                </div>
                
                <div v-else-if="selectedDayInfo && !selectedDayInfo.is_available" class="alert alert-warning">
                  Resource unavailable on this day
                </div>
                
                <div v-else-if="selectedDayInfo && selectedDayInfo.slots.length === 0" class="alert alert-warning">
                  No time slots configured
                </div>
                
                <div v-else class="alert alert-info">
                  Please select a date
                </div>

                <!-- Cost Display -->
                <div v-if="calculatedCost > 0" class="mb-3 p-2 bg-light rounded">
                  <div class="d-flex justify-content-between">
                    <span>Resource Cost:</span>
                    <span class="fw-bold">Rs. {{ calculatedCost }}</span>
                  </div>
                </div>

                <!-- Equipment Section -->
                <div class="mb-3">
                  <label class="form-label">Add Equipment (Optional)</label>
                  
                  <!-- Equipment Search -->
                  <div class="equipment-search-container mb-2">
                    <input
                      type="text"
                      class="form-control"
                      placeholder="Search equipment..."
                      v-model="equipmentSearch"
                      @input="searchEquipment"
                      @focus="showEquipmentDropdown = true"
                    >
                    
                    <div v-if="showEquipmentDropdown && filteredEquipment.length > 0" class="equipment-dropdown">
                      <div 
                        v-for="item in filteredEquipment" 
                        :key="item.id"
                        class="equipment-dropdown-item"
                        @click="addEquipment(item)"
                      >
                        <div class="d-flex justify-content-between">
                          <div>
                            <strong>{{ item.name }}</strong>
                            <small class="d-block text-muted">Rs. {{ item.price_per_hour }}/hr</small>
                          </div>
                          <span class="badge bg-info">Qty: {{ item.available_quantity }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Selected Equipment -->
                  <div v-if="selectedEquipment.length > 0" class="selected-equipment">
                    <div v-for="(item, index) in selectedEquipment" :key="item.id" class="equipment-item mb-2 p-2 border rounded">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>{{ item.name }}</strong>
                          <small class="text-muted d-block">Rs. {{ item.price_per_hour }}/hr</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeEquipment(index)">
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                      <div class="d-flex align-items-center mt-2">
                        <label class="me-2 small">Qty:</label>
                        <div class="input-group input-group-sm" style="width: 120px;">
                          <button type="button" class="btn btn-outline-secondary" @click="decreaseQuantity(index)">-</button>
                          <input type="number" class="form-control text-center" v-model.number="item.quantity" min="1" :max="item.available_quantity" @change="validateQuantity(index)">
                          <button type="button" class="btn btn-outline-secondary" @click="increaseQuantity(index)">+</button>
                        </div>
                        <span class="ms-auto fw-bold text-success">Rs. {{ calculateEquipmentCost(item) }}</span>
                      </div>
                    </div>
                    
                    <div class="mt-2 text-end">
                      <strong>Equipment Total: Rs. {{ equipmentTotalCost }}</strong>
                    </div>
                  </div>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                  <label for="notes" class="form-label">Notes (Optional)</label>
                  <textarea
                    id="notes"
                    class="form-control"
                    rows="2"
                    v-model="form.notes"
                  ></textarea>
                </div>

                <!-- Total Cost -->
                <div class="cost-summary mb-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Resource:</span>
                    <span>Rs. {{ calculatedCost || 0 }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Equipment:</span>
                    <span>Rs. {{ equipmentTotalCost }}</span>
                  </div>
                  <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                    <span>Total:</span>
                    <span class="text-success">Rs. {{ totalCost }}</span>
                  </div>
                </div>

                <!-- Submit Button -->
                <button 
                  type="submit" 
                  class="btn btn-success w-100"
                  :disabled="isSubmitting || !isFormValid"
                >
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-calendar-check me-2"></i>
                  {{ isSubmitting ? 'Processing...' : 'Book Now' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OTP Modal -->
  <div v-if="showOTPModal" class="modal-overlay" @click.self="closeOTPModal">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">OTP Verification</h5>
        <button type="button" class="btn-close btn-close-white" @click="closeOTPModal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <i class="bi bi-envelope-check" style="font-size: 3rem; color: #4BB66D;"></i>
          <h6 class="fw-bold mt-2">Verify Your Email</h6>
          <p class="text-muted small">
            We've sent a 6-digit OTP to:<br>
            <strong>{{ form.user_email }}</strong>
          </p>
          <div v-if="debugOTP" class="alert alert-warning py-1 small">
            Debug OTP: <strong>{{ debugOTP }}</strong>
          </div>
        </div>
        
        <!-- OTP Input -->
        <div class="otp-container mb-4">
          <div class="d-flex justify-content-center gap-2">
            <input
              v-for="n in 6"
              :key="n"
              type="text"
              maxlength="1"
              class="otp-input"
              v-model="otp[n-1]"
              @input="onOtpInput(n-1, $event)"
              @keydown="onOtpKeydown(n-1, $event)"
              :ref="el => otpRefs[n-1] = el"
            />
          </div>
          <div v-if="otpError" class="text-danger text-center mt-2 small">
            {{ otpError }}
          </div>
        </div>
        
        <!-- Timer & Resend -->
        <div class="text-center">
          <small class="text-muted">OTP expires in: {{ formatTimer(otpTimer) }}</small><br>
          <button type="button" class="btn btn-link btn-sm" @click="resendOTP" :disabled="isResending">
            {{ isResending ? 'Sending...' : 'Resend OTP' }}
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="closeOTPModal">Cancel</button>
        <button type="button" class="btn btn-success" @click="verifyOTP" :disabled="!isOtpComplete || isVerifying">
          {{ isVerifying ? 'Verifying...' : 'Verify & Complete' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Booking Confirmed!</h5>
      </div>
      <div class="modal-body text-center">
        <i class="bi bi-check-circle" style="font-size: 4rem; color: #4BB66D;"></i>
        <h6 class="fw-bold mt-3">Your booking has been confirmed!</h6>
        
        <div class="bg-light p-3 rounded mt-3 text-start">
          <p class="mb-1"><strong>Resource:</strong> {{ resource?.name }}</p>
          <p class="mb-1"><strong>Date:</strong> {{ formatDate(form.booking_date) }}</p>
          <p class="mb-1"><strong>Time:</strong> {{ form.start_time }} - {{ form.end_time }}</p>
          <p class="mb-0"><strong>Total:</strong> Rs. {{ totalCost }}</p>
        </div>
        
        <p class="text-muted small mt-3">
          Confirmation sent to {{ form.user_email }}
        </p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-success" @click="goToBookings">View My Bookings</button>
        <button type="button" class="btn btn-outline-success" @click="closeSuccessModal">Book Another</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import { bookingStore } from '../../store/bookingStore';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL = 'http://localhost:8000/storage';

// State
const resource = ref<Resource | null>(null);
const bookings = computed(() => {
  if (!resource.value) return [];
  return bookingStore.bookings.filter(b => 
    (b.resource_id === resource.value?.id) || 
    (b.resources && b.resources.some((r: any) => r.id === resource.value?.id))
  );
});
const selectedBooking = ref<Booking | null>(null);
const isLoading = ref(true);
const isLoadingBookings = ref(false);
const errorMessage = ref('');
const validationErrors = ref([]);

// Form State
const form = ref({
  user_email: '',
  booking_date: '',
  start_time: '',
  end_time: '',
  notes: ''
});

// Equipment
const availableEquipment = ref([]);
const filteredEquipment = ref([]);
const selectedEquipment = ref([]);
const equipmentSearch = ref('');
const showEquipmentDropdown = ref(false);

// Time Slots
const selectedDayInfo = ref(null);
const selectedSlotIndex = ref(-1);

// OTP
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otp = ref(Array(6).fill(''));
const otpRefs = ref([]);
const otpError = ref('');
const isVerifying = ref(false);
const isSubmitting = ref(false);
const isResending = ref(false);
const otpTimer = ref(300);
const timerInterval = ref(null);
const pendingBookingId = ref(null);
const debugOTP = ref('');

// User
const userId = ref(1); // Default user ID

// Computed
const minDate = computed(() => {
  return new Date().toISOString().split('T')[0];
});

const calculatedCost = computed(() => {
  if (!resource.value || !form.value.start_time || !form.value.end_time) return 0;
  
  const start = new Date(`2000-01-01T${form.value.start_time}`);
  const end = new Date(`2000-01-01T${form.value.end_time}`);
  const hours = (end - start) / (1000 * 60 * 60);
  
  return Math.round(hours * resource.value.base_price);
});

const calculateEquipmentCost = (item) => {
  const hours = calculateHours();
  return item.price_per_hour * item.quantity * hours;
};

const equipmentTotalCost = computed(() => {
  const hours = calculateHours();
  return selectedEquipment.value.reduce((sum, item) => 
    sum + (item.price_per_hour * item.quantity * hours), 0
  );
});

const totalCost = computed(() => {
  return calculatedCost.value + equipmentTotalCost.value;
});

const calculateHours = () => {
  if (!form.value.start_time || !form.value.end_time) return 0;
  const start = new Date(`2000-01-01T${form.value.start_time}`);
  const end = new Date(`2000-01-01T${form.value.end_time}`);
  return (end - start) / (1000 * 60 * 60);
};

const isFormValid = computed(() => {
  return form.value.user_email && 
         form.value.booking_date && 
         form.value.start_time && 
         form.value.end_time &&
         selectedDayInfo.value?.is_available;
});

const isOtpComplete = computed(() => {
  return otp.value.every(d => d && d.length === 1);
});

// Helper Functions
const formatTime = (time) => {
  if (!time) return '00:00';
  return time.substring(0, 5);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};

const formatTimer = (seconds) => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const calculateSlotDuration = (slot) => {
  const start = new Date(`2000-01-01T${slot.start_time}`);
  const end = new Date(`2000-01-01T${slot.end_time}`);
  return ((end - start) / (1000 * 60 * 60)).toFixed(1);
};

const getStatusClass = (status) => {
  return {
    'Active': 'bg-success',
    'Inactive': 'bg-secondary',
    'Maintenance': 'bg-warning'
  }[status] || 'bg-secondary';
};

const getBookingStatusClass = (status) => {
  return {
    'Pending': 'bg-warning text-dark',
    'Confirmed': 'bg-success',
    'Cancelled': 'bg-danger',
    'Completed': 'bg-info'
  }[status] || 'bg-secondary';
};

const getImageUrl = (resource) => {
  if (resource.images?.length) {
    return `${STORAGE_URL}/${resource.images[0].file_path}`;
  }
  return 'https://via.placeholder.com/400x300?text=No+Image';
};

const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('token') || 
         localStorage.getItem('auth_token');
};

// Test API Connection
const testAPIConnection = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/health`, {
      headers: { 'Accept': 'application/json' }
    });
    alert('API Connection successful! ' + JSON.stringify(response.data));
  } catch (error) {
    alert('API Connection failed: ' + error.message);
  }
};

// API Calls
const loadResourceDetails = async () => {
  const id = route.query.resourceId || route.params.id;
  if (!id) {
    errorMessage.value = 'Resource ID required';
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  
  try {
    const token = getAuthToken();
    const headers = { 
      'Accept': 'application/json'
    };
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await axios.get(`${API_BASE_URL}/resources/${id}`, { headers });
    
    resource.value = response.data.resource || response.data.data || response.data;
    
    if (resourceData) {
      if (!resourceData.availability) {
        resourceData.availability = [];
      }
      resource.value = resourceData;
      
      // Load bookings for this resource
      await loadBookings();
      // Load available equipment
      await loadAvailableEquipment();
    } else {
      errorMessage.value = 'Resource data not found in response';
    }

    bookingForm.value.date = minDate.value;

  } catch (error: any) {
    console.error('Error loading resource:', error);
    
    await Promise.all([
      loadBookings(),
      loadEquipment()
    ]);
    
    form.value.booking_date = minDate.value;
    await updateDayInfo();
    
  } catch (error) {
    console.error('Error loading resource:', error);
    errorMessage.value = error.response?.data?.message || 'Failed to load resource';
  } finally {
    isLoading.value = false;
  }
};

const loadBookings = async () => {
  if (!resource.value) return;
  
  isLoadingBookings.value = true;
  
  try {
    if (resource.value) {
      await bookingStore.fetchByResource(resource.value.id);
    }
    console.log(`Loaded bookings for resource ${resource.value.id} into store`);
  } catch (error: any) {
    console.error('Error loading bookings:', error);
  } finally {
    isLoadingBookings.value = false;
  }
};

const loadEquipment = async () => {
  try {
    const token = getAuthToken();
    const headers = { 'Accept': 'application/json' };
    
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = currentUser.id || 0;
    
    // Prepare booking data with equipment
    const bookingPayload: any = {
      user_id: userId,
      user_email: bookingForm.value.email,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || '',
      resources: [
        {
          resource_id: resource.value.id
        }
      ],
      booking_items: [] // This will hold equipment items
    };
    
    // Add equipment items if any selected
    if (selectedEquipment.value.length > 0) {
      selectedEquipment.value.forEach(item => {
        bookingPayload.booking_items.push({
          item_id: item.id,
          item_type: 'equipment',
          quantity: item.quantity
        });
      });
    }
    
    console.log('Creating booking with payload:', bookingPayload);
    
    const response = await axios.post(`${API_BASE_URL}/bookings`, bookingPayload, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    console.log('Booking created response:', response.data);
    
    if (response.data.booking) {
      pendingBookingId.value = response.data.booking.id;
      pendingBookingReference.value = response.data.booking.booking_reference;
      bookingStore.updateBookingLocally(response.data.booking);
    } else if (response.data.id) {
      pendingBookingId.value = response.data.id;
      pendingBookingReference.value = response.data.booking_reference;
      bookingStore.updateBookingLocally(response.data);
    }
    
    const response = await axios.get(`${API_BASE_URL}/booking-items`, { headers });
    
    const items = Array.isArray(response.data) ? response.data : 
                  response.data.data || [];
    availableEquipment.value = items.filter(i => i.status === 'Available' && i.available_quantity > 0);
    
  } catch (error) {
    console.error('Error loading equipment:', error);
  }
};

// FIXED: Submit Booking Function
const submitBooking = async () => {
  validationErrors.value = [];
  
  // Validation
  if (!form.value.user_email?.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    validationErrors.value = ['Valid email required'];
    return;
  }
  
  if (!form.value.booking_date) {
    validationErrors.value = ['Date required'];
    return;
  }
  
  if (!form.value.start_time || !form.value.end_time) {
    validationErrors.value = ['Time slot required'];
    return;
  }
  
  if (form.value.start_time >= form.value.end_time) {
    validationErrors.value = ['End time must be after start time'];
    return;
  }
  
  if (!selectedDayInfo.value?.is_available) {
    validationErrors.value = ['Resource unavailable on this day'];
    return;
  }
  
  // Check equipment quantities
  for (const item of selectedEquipment.value) {
    if (item.quantity > item.available_quantity) {
      validationErrors.value = [`${item.name} exceeds available quantity`];
      return;
    }
  }
  
  isSubmitting.value = true;
  
  try {
    // Prepare payload - EXACT format backend expects
    const payload = {
      user_id: userId.value,
      user_email: form.value.user_email,
      booking_date: form.value.booking_date,
      start_time: form.value.start_time + ':00',
      end_time: form.value.end_time + ':00',
      notes: form.value.notes || `Booking for ${resource.value.name}`,
      resources: [{ resource_id: resource.value.id }]
    };
    
    if (selectedEquipment.value.length > 0) {
      payload.booking_items = selectedEquipment.value.map(item => ({
        item_id: item.id,
        quantity: item.quantity
      }));
    }
    
    console.log('Sending payload:', payload);
    
    const token = getAuthToken();
    
    // Headers
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await axios.post(`${API_BASE_URL}/bookings`, payload, { headers });
    
    console.log('Booking response:', response.data);
    
    // Store booking ID
    pendingBookingId.value = response.data.booking_id || response.data.id;
    
    if (response.data.otp_code_for_testing) {
      debugOTP.value = response.data.otp_code_for_testing;
    }
    
    // Show OTP modal
    showOTPModal.value = true;
    startTimer();
    
  } catch (error) {
    console.error('Booking error:', error);
    
    if (error.response) {
      console.error('Error status:', error.response.status);
      console.error('Error data:', error.response.data);
      
      if (error.response.status === 404) {
        validationErrors.value = ['API endpoint not found. Check if the server is running and routes are correct.'];
      } else if (error.response.status === 500) {
        validationErrors.value = ['Server error. Check Laravel logs for details.'];
      } else {
        validationErrors.value = [error.response.data?.message || 'Booking failed'];
      }
    } else if (error.request) {
      validationErrors.value = ['No response from server. Check if the server is running.'];
    } else {
      validationErrors.value = [error.message];
    }
  } finally {
    isSubmitting.value = false;
  }
};

const verifyOTP = async () => {
  const code = otp.value.join('');
  
  if (code.length !== 6) {
    otpError.value = 'Enter 6-digit OTP';
    return;
  }
  
  isVerifying.value = true;
  
  try {
    const token = getAuthToken();
    
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await axios.post(
      `${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`,
      { otp_code: code },
      { headers }
    );
    
    console.log('Verify response:', response.data);
    
    // Success
    closeOTPModal();
    showSuccessModal.value = true;
    await loadBookings();
    
  } catch (error) {
    console.error('Verify error:', error);
    otpError.value = error.response?.data?.message || 'Invalid OTP';
    otp.value = Array(6).fill('');
    nextTick(() => otpRefs.value[0]?.focus());
  } finally {
    isVerifying.value = false;
  }
};

const resendOTP = async () => {
  if (!pendingBookingId.value) return;
  
  isResending.value = true;
  
  try {
    const token = getAuthToken();
    
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await axios.post(
      `${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`,
      {},
      { headers }
    );
    
    console.log('Resend response:', response.data);
    
    resetTimer();
    otp.value = Array(6).fill('');
    
    if (response.data.otp_code_for_testing) {
      debugOTP.value = response.data.otp_code_for_testing;
    }
    
    nextTick(() => otpRefs.value[0]?.focus());
    
  } catch (error) {
    console.error('Resend error:', error);
    otpError.value = 'Failed to resend OTP';
  } finally {
    isResending.value = false;
  }
};

// Time Slot Functions
const onDateChange = async () => {
  selectedSlotIndex.value = -1;
  form.value.start_time = '';
  form.value.end_time = '';
  await updateDayInfo();
};

const updateDayInfo = async () => {
  if (!form.value.booking_date || !resource.value) {
    selectedDayInfo.value = null;
    return;
  }
  
  const dayName = new Date(form.value.booking_date).toLocaleDateString('en-US', { weekday: 'long' });
  const dayInfo = resource.value.availability?.find(d => d.day_name === dayName);
  
  selectedDayInfo.value = dayInfo || {
    day_name: dayName,
    is_available: false,
    slots: []
  };
};

const selectTimeSlot = (index) => {
  const slot = selectedDayInfo.value.slots[index];
  selectedSlotIndex.value = index;
  form.value.start_time = formatTime(slot.start_time);
  form.value.end_time = formatTime(slot.end_time);
};

const confirmBooking = async (booking: Booking) => {
  if (!confirm('Are you sure you want to confirm this booking?')) return;
  
  try {
    const token = getAuthToken();
    
    const response = await axios.put(`${API_BASE_URL}/bookings/${booking.id}/confirm`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    // Update store state
    bookingStore.updateBookingLocally(response.data.booking || response.data);
    
    // Update selected booking if it's the same
    if (selectedBooking.value && selectedBooking.value.id === booking.id) {
      selectedBooking.value = response.data.booking || response.data;
    }
    
    alert('Booking confirmed successfully!');
    
  } catch (error: any) {
    console.error('Error confirming booking:', error);
    alert(error.response?.data?.message || 'Failed to confirm booking');
  }
  
  const search = equipmentSearch.value.toLowerCase();
  filteredEquipment.value = availableEquipment.value.filter(item =>
    item.name.toLowerCase().includes(search) ||
    item.description?.toLowerCase().includes(search)
  );
};

const addEquipment = (item) => {
  const existing = selectedEquipment.value.find(i => i.id === item.id);
  
  try {
    const token = getAuthToken();
    
    const response = await axios.put(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    // Update store state
    bookingStore.updateBookingLocally(response.data.booking || response.data);
    
    // Update selected booking if it's the same
    if (selectedBooking.value && selectedBooking.value.id === booking.id) {
      selectedBooking.value = response.data.booking || response.data;
    }
    
    alert('Booking cancelled successfully!');
    
  } catch (error: any) {
    console.error('Error cancelling booking:', error);
    alert(error.response?.data?.message || 'Failed to cancel booking');
  }
};

const decreaseQuantity = (index) => {
  const item = selectedEquipment.value[index];
  if (item.quantity > 1) {
    item.quantity--;
  }
};

const validateQuantity = (index) => {
  const item = selectedEquipment.value[index];
  if (item.quantity < 1) item.quantity = 1;
  if (item.quantity > item.available_quantity) {
    item.quantity = item.available_quantity;
  }
};

// OTP Functions
const onOtpInput = (index, event) => {
  const value = event.target.value;
  
  if (value && !/^\d$/.test(value)) {
    otp.value[index] = '';
    return;
  }
  
  otp.value[index] = value;
  
  if (value && index < 5) {
    nextTick(() => otpRefs.value[index + 1]?.focus());
  }
};

const onOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !otp.value[index] && index > 0) {
    nextTick(() => otpRefs.value[index - 1]?.focus());
  }
};

const startTimer = () => {
  otpTimer.value = 300;
  if (timerInterval.value) clearInterval(timerInterval.value);
  
  timerInterval.value = setInterval(() => {
    if (otpTimer.value > 0) {
      otpTimer.value--;
    } else {
      clearInterval(timerInterval.value);
    }
  }, 1000);
};

const resetTimer = () => {
  otpTimer.value = 300;
};

// Modal Functions
const closeOTPModal = () => {
  showOTPModal.value = false;
  otp.value = Array(6).fill('');
  otpError.value = '';
  if (timerInterval.value) clearInterval(timerInterval.value);
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  
  // Reset form
  form.value = {
    user_email: '',
    booking_date: minDate.value,
    start_time: '',
    end_time: '',
    notes: ''
  };
  selectedEquipment.value = [];
  selectedSlotIndex.value = -1;
  updateDayInfo();
};

const goToBookings = () => {
  closeSuccessModal();
  router.push('/master-admin/booking');
};

// Click outside handler
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.equipment-search-container')) {
      showEquipmentDropdown.value = false;
    }
  });
  
  loadResourceDetails();
});
</script>

<style scoped>
.section {
  margin-left: 260px;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

@media (max-width: 768px) {
  .section { margin-left: 80px; }
}

.bg-dark-teal {
  background-color: #1e4449;
  color: white;
}

.resource-image-large {
  height: 200px;
  overflow: hidden;
  border-radius: 8px;
}

.resource-image-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card {
  border: none;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-radius: 8px;
  margin-bottom: 20px;
}

.sticky-top {
  position: sticky;
  top: 20px;
  z-index: 100;
}

.time-slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.time-slot-btn {
  width: 100%;
  padding: 8px;
  font-size: 0.9rem;
}

.time-slot-btn.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.equipment-search-container {
  position: relative;
}

.equipment-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 250px;
  overflow-y: auto;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.equipment-dropdown-item {
  padding: 10px;
  cursor: pointer;
  border-bottom: 1px solid #dee2e6;
}

.equipment-dropdown-item:hover {
  background-color: #f8f9fa;
}

.cost-summary {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 450px;
  width: 100%;
  animation: slideIn 0.3s ease;
}

.otp-input {
  width: 45px;
  height: 55px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 600;
  border: 2px solid #dee2e6;
  border-radius: 8px;
}

.otp-input:focus {
  border-color: #4BB66D;
  outline: none;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>