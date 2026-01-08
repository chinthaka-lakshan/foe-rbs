<template>
  <navbar/>
  <master-admin-sidebar/>
  
  <div class="section">
    <!-- Debug Button (Temporary) -->
    <button 
      v-if="!isLoading && !resource" 
      class="btn btn-warning mb-3"
      @click="debugResourceLoading"
    >
      <i class="bi bi-bug me-1"></i> Debug Resource Loading
    </button>

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

    <!-- Main Content -->
    <div v-else-if="resource" class="container-fluid">
      <div class="row">
        <!-- Left Column - Resource Details -->
        <div class="col-md-8">
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

          <!-- Booking History Calendar -->
          <div class="card mt-4">
            <div class="card-header bg-light">
              <h5 class="mb-0">Booking History Calendar</h5>
            </div>
            <div class="card-body">
              <!-- Calendar will be implemented here -->
              <div class="text-center text-muted py-3">
                <i class="bi bi-calendar" style="font-size: 2rem;"></i>
                <p class="mt-2">Booking calendar will be displayed here</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Booking Form -->
        <div class="col-md-4">
          <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">Book This Resource</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="validateAndShowOTP">
                <!-- Resource Unavailable Message -->
                <div v-if="isResourceUnavailable" class="alert alert-warning">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  Resource is UNAVAILABLE on this day. (Check weekly schedule)
                </div>

                <!-- Email Input -->
                <div class="mb-3">
                  <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>E-Mail
                  </label>
                  <input
                    type="email"
                    id="email"
                    class="form-control"
                    placeholder="Enter e-mail (e.g. abc@gmail.com)"
                    v-model="bookingForm.email"
                    required
                  >
                </div>

                <!-- 1. Reservation Details -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2">1. Reservation Details</h6>
                  
                  <div class="mb-3">
                    <label for="date" class="form-label">Select Date</label>
                    <input
                      type="date"
                      id="date"
                      class="form-control"
                      v-model="bookingForm.date"
                      :min="minDate"
                      required
                    >
                  </div>
                  
                  <div class="row">
                    <div class="col-6">
                      <label for="startTime" class="form-label">Start Time</label>
                      <input
                        type="time"
                        id="startTime"
                        class="form-control"
                        v-model="bookingForm.startTime"
                        required
                      >
                    </div>
                    <div class="col-6">
                      <label for="endTime" class="form-label">End Time</label>
                      <input
                        type="time"
                        id="endTime"
                        class="form-control"
                        v-model="bookingForm.endTime"
                        required
                      >
                    </div>
                  </div>
                  
                  <div class="mt-3">
                    <p class="mb-1">
                      <strong>Total Cost:</strong> 
                      <span v-if="calculatedCost">Rs. {{ calculatedCost }}</span>
                      <span v-else class="text-muted">--</span>
                    </p>
                    <small class="text-muted">Base Price: Rs. {{ resource.base_price }}/hour</small>
                  </div>
                </div>

                <!-- Weekly Schedule Summary -->
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

                <!-- Submit Button -->
                <button 
                  type="submit" 
                  class="btn btn-success w-100"
                  :disabled="isSendingOTP || isResourceUnavailable"
                >
                  <span v-if="isSendingOTP" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-calendar-check me-2"></i>
                  {{ isSendingOTP ? 'Sending OTP...' : 'Send OTP & Book' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div v-if="showOTPModal" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-shield-lock me-2"></i>OTP Verification
        </h5>
        <button type="button" class="btn-close btn-close-white" @click="closeOTPModal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="otp-icon mb-3">
            <i class="bi bi-envelope-check" style="font-size: 3rem; color: #4BB66D;"></i>
          </div>
          <h6 class="fw-bold">Verify Your Email</h6>
          <p class="text-muted small">
            We've sent a 6-digit OTP to:<br>
            <strong>{{ bookingForm.email }}</strong>
          </p>
          <div v-if="otpSentSuccess" class="alert alert-success alert-sm py-2">
            <i class="bi bi-check-circle me-1"></i>OTP sent successfully!
          </div>
        </div>
        
        <!-- OTP Input -->
        <div class="otp-input-container mb-4">
          <div class="d-flex justify-content-center gap-2">
            <input
              v-for="n in 6"
              :key="n"
              type="text"
              maxlength="1"
              class="otp-digit"
              v-model="otpDigits[n-1]"
              @input="onOtpInput(n-1, $event)"
              @keydown="onOtpKeydown(n-1, $event)"
              :ref="el => { if (el) otpInputs[n-1] = el }"
              :disabled="isVerifyingOTP"
            />
          </div>
          <div v-if="otpError" class="text-danger text-center mt-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ otpError }}
          </div>
        </div>
        
        <!-- Countdown Timer -->
        <div class="text-center mb-3">
          <small class="text-muted">
            OTP expires in: 
            <span class="fw-bold" :class="otpExpired ? 'text-danger' : 'text-success'">
              {{ formatCountdownTimer() }}
            </span>
          </small>
        </div>
        
        <!-- Resend OTP -->
        <div class="text-center">
          <button 
            class="btn btn-link btn-sm text-decoration-none"
            @click="resendOTP"
            :disabled="!otpExpired || isResendingOTP"
          >
            <span v-if="isResendingOTP" class="spinner-border spinner-border-sm me-1"></span>
            <span v-else><i class="bi bi-arrow-clockwise me-1"></i></span>
            Resend OTP
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn btn-secondary" 
          @click="closeOTPModal"
          :disabled="isVerifyingOTP"
        >
          Cancel
        </button>
        <button 
          type="button" 
          class="btn btn-success" 
          @click="verifyOTPAndCompleteBooking"
          :disabled="!isOtpComplete || isVerifyingOTP"
        >
          <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
          <i class="bi bi-check-circle me-2"></i>
          {{ isVerifyingOTP ? 'Verifying...' : 'Verify & Complete Booking' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div v-if="showSuccessModal" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-check-circle-fill me-2"></i>Booking Confirmed!
        </h5>
      </div>
      <div class="modal-body text-center">
        <div class="success-icon mb-3">
          <i class="bi bi-check-circle" style="font-size: 4rem; color: #4BB66D;"></i>
        </div>
        <h6 class="fw-bold mb-3">Your booking has been confirmed!</h6>
        
        <div class="booking-details bg-light p-3 rounded mb-3">
          <p class="mb-2"><strong>Resource:</strong> {{ resource?.name }}</p>
          <p class="mb-2"><strong>Date:</strong> {{ bookingForm.date }}</p>
          <p class="mb-2"><strong>Time:</strong> {{ bookingForm.startTime }} - {{ bookingForm.endTime }}</p>
          <p class="mb-0"><strong>Total Cost:</strong> Rs. {{ calculatedCost }}</p>
        </div>
        
        <p class="text-muted small">
          A confirmation email has been sent to <strong>{{ bookingForm.email }}</strong>
        </p>
        <div v-if="pendingBookingReference" class="alert alert-info mt-3">
          <i class="bi bi-info-circle me-2"></i>
          Booking Reference: <strong>{{ pendingBookingReference }}</strong>
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-success" @click="redirectToBookings">
          <i class="bi bi-list-check me-2"></i>View My Bookings
        </button>
        <button type="button" class="btn btn-outline-success" @click="closeSuccessModal">
          <i class="bi bi-calendar-plus me-2"></i>Book Another
        </button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL_ROOT = 'http://localhost:8000/storage';

// Get auth token
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token');
};

const formatTime = (time: string | null): string => {
    if (!time) return '00:00';
    return time.substring(0, 5); 
};

// Interfaces (same as before)
interface Resource {
  id: number;
  name: string;
  location_name?: string;
  category_id: number;
  category: ResourceCategory;
  base_price: number;
  description?: string;
  status: 'Active' | 'Inactive' | 'Maintenance';
  capacity?: number;
  availability: ResourceAvailability[]; 
  images?: Array<{
    file_path: string;
    file_name: string;
  }>;
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

interface BookingForm {
  email: string;
  date: string;
  startTime: string;
  endTime: string;
  purpose?: string;
}

// State (same as before)
const resource = ref<Resource | null>(null);
const isLoading = ref(true);
const errorMessage = ref('');

// OTP State
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otpDigits = ref<string[]>(Array(6).fill(''));
const otpInputs = ref<(HTMLInputElement | null)[]>(Array(6).fill(null));
const otpError = ref('');
const isVerifyingOTP = ref(false);
const isSendingOTP = ref(false);
const isResendingOTP = ref(false);
const otpTimer = ref(300);
const otpTimerInterval = ref<number | null>(null);
const pendingBookingId = ref<number | null>(null);
const pendingBookingReference = ref<string>('');
const otpSentSuccess = ref(false);
const tempBookingData = ref<any>(null);

// Booking Form
const bookingForm = ref<BookingForm>({
  email: '',
  date: '',
  startTime: '09:00',
  endTime: '10:00',
  purpose: ''
});

// Computed Properties (same as before)
const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    return 0;
  }
  
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const hours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
  
  return Math.round(hours * resource.value.base_price);
});

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date) return false;
  
  const selectedDate = new Date(bookingForm.value.date);
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    day => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  return !dayAvailability || !dayAvailability.is_available;
});

const isOtpComplete = computed(() => {
  return otpDigits.value.every(digit => digit.length === 1);
});

const otpExpired = computed(() => {
  return otpTimer.value <= 0;
});

// Helper Functions (same as before)
const getImageUrl = (resource: Resource): string => {
  if (resource.images && resource.images.length > 0) {
    const filePath = resource.images[0].file_path;
    return `${STORAGE_URL_ROOT}/${filePath}`;
  }
  return 'https://via.placeholder.com/400x300?text=No+Image';
};

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'Active':
      return 'bg-success';
    case 'Inactive':
      return 'bg-secondary';
    case 'Maintenance':
      return 'bg-warning';
    default:
      return 'bg-secondary';
  }
};

const formatCountdownTimer = () => {
  const minutes = Math.floor(otpTimer.value / 60);
  const seconds = otpTimer.value % 60;
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

// OTP Functions - ENHANCED for better paste handling
const onOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement;
  const value = input.value;
  
  // Handle paste event - check if it's a 6-digit OTP
  if (value.length === 6 && /^\d{6}$/.test(value)) {
    // User pasted a complete OTP
    const digits = value.split('');
    digits.forEach((digit, i) => {
      if (i < 6) {
        otpDigits.value[i] = digit;
      }
    });
    
    // Focus on the last input
    nextTick(() => {
      const lastInput = otpInputs.value[5];
      if (lastInput) lastInput.focus();
    });
    return;
  }
  
  // Normal single digit input
  if (value && !/^\d$/.test(value)) {
    otpDigits.value[index] = '';
    return;
  }
  
  otpDigits.value[index] = value;
  
  if (value && index < 5) {
    nextTick(() => {
      const nextInput = otpInputs.value[index + 1];
      if (nextInput) nextInput.focus();
    });
  }
};

const onOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    nextTick(() => {
      const prevInput = otpInputs.value[index - 1];
      if (prevInput) prevInput.focus();
    });
  }
  
  // Allow paste with Ctrl+V or Cmd+V
  if ((event.ctrlKey || event.metaKey) && event.key === 'v') {
    // Allow paste, it will be handled in onOtpInput
  }
};

const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) {
    clearInterval(otpTimerInterval.value);
  }
  
  otpTimerInterval.value = window.setInterval(() => {
    if (otpTimer.value > 0) {
      otpTimer.value--;
    } else {
      if (otpTimerInterval.value) {
        clearInterval(otpTimerInterval.value);
      }
    }
  }, 1000);
};

// API Functions - FIXED: Remove automatic OTP filling
const loadResourceDetails = async () => {
  const resourceId = route.query.resourceId || route.params.id;
  
  if (!resourceId) {
    errorMessage.value = 'Resource ID is required';
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const token = getAuthToken();
    
    const resourceResponse = await axios.get(`${API_BASE_URL}/resources/${resourceId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    let resourceData = null;
    
    if (resourceResponse.data) {
      if (resourceResponse.data.resource) {
        resourceData = resourceResponse.data.resource;
      } else if (resourceResponse.data.data) {
        resourceData = resourceResponse.data.data;
      } else {
        resourceData = resourceResponse.data;
      }
    }
    
    if (resourceData) {
      resource.value = resourceData;
      
      if (!resource.value.availability) {
        resource.value.availability = [];
      }
    } else {
      errorMessage.value = 'Resource data not found in response';
    }

    bookingForm.value.date = minDate.value;

  } catch (error: any) {
    console.error('Error loading resource:', error);
    
    if (error.response) {
      if (error.response.status === 401) {
        errorMessage.value = 'Authentication required. Please login again.';
        setTimeout(() => router.push('/login'), 2000);
      } else if (error.response.status === 404) {
        errorMessage.value = 'Resource not found.';
        setTimeout(() => router.push('/resources'), 2000);
      } else if (error.response.status === 500) {
        errorMessage.value = 'Server error. Please try again later.';
      } else {
        errorMessage.value = `Failed to load resource: ${error.response.data?.message || 'Unknown error'}`;
      }
    } else if (error.request) {
      errorMessage.value = 'No response from server. Please check your connection.';
    } else {
      errorMessage.value = `Request error: ${error.message}`;
    }
  } finally {
    isLoading.value = false;
  }
};

// Step 1: Create booking
const createBooking = async () => {
  if (!resource.value) {
    throw new Error('Resource not loaded');
  }
  
  try {
    const token = getAuthToken();
    
    // Get current user from token or localStorage
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = currentUser.id || 0;
    
    // Prepare booking data according to backend requirements
    const bookingPayload = {
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
      booking_items: []
    };
    
    console.log('Creating booking with payload:', bookingPayload);
    
    const response = await axios.post(`${API_BASE_URL}/bookings`, bookingPayload, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    console.log('Booking created response:', response.data);
    
    // Store the booking ID and reference for OTP verification
    if (response.data.booking) {
      pendingBookingId.value = response.data.booking.id;
      pendingBookingReference.value = response.data.booking.booking_reference;
    } else if (response.data.id) {
      pendingBookingId.value = response.data.id;
      pendingBookingReference.value = response.data.booking_reference;
    }
    
    return response.data;
    
  } catch (error: any) {
    console.error('Error creating booking:', error);
    
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      if (errors) {
        throw new Error(Object.values(errors).flat().join(', '));
      } else if (error.response.data.message) {
        throw new Error(error.response.data.message);
      }
    } else if (error.response?.data?.message) {
      throw new Error(error.response.data.message);
    }
    throw error;
  }
};

// Step 1: Validate form and create booking
const validateAndShowOTP = async () => {
  if (!resource.value) {
    errorMessage.value = 'Resource not loaded. Please try again.';
    return;
  }
  
  // Validate form (same as before)
  if (!bookingForm.value.email || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    errorMessage.value = 'Please fill all required fields';
    return;
  }
  
  if (!bookingForm.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    errorMessage.value = 'Please enter a valid email address';
    return;
  }
  
  if (bookingForm.value.startTime >= bookingForm.value.endTime) {
    errorMessage.value = 'End time must be after start time';
    return;
  }
  
  const selectedDate = new Date(bookingForm.value.date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (selectedDate < today) {
    errorMessage.value = 'Cannot book for past dates';
    return;
  }
  
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    day => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  if (!dayAvailability || !dayAvailability.is_available) {
    errorMessage.value = `Resource is not available on ${selectedDayName}`;
    return;
  }
  
  if (dayAvailability.start_time && dayAvailability.end_time) {
    const selectedStartTime = bookingForm.value.startTime;
    const selectedEndTime = bookingForm.value.endTime;
    const availableStartTime = dayAvailability.start_time.substring(0, 5);
    const availableEndTime = dayAvailability.end_time.substring(0, 5);
    
    if (selectedStartTime < availableStartTime || selectedEndTime > availableEndTime) {
      errorMessage.value = `Booking time must be between ${availableStartTime} and ${availableEndTime} on ${selectedDayName}`;
      return;
    }
  }
  
  isSendingOTP.value = true;
  errorMessage.value = '';
  
  try {
    // Create booking (backend will send OTP automatically)
    const bookingResponse = await createBooking();
    
    // Check if booking was created successfully
    if (bookingResponse.requires_verification || pendingBookingId.value) {
      otpSentSuccess.value = true;
      
      // Show OTP modal
      showOTPModal.value = true;
      startOTPTimer();
      
      // IMPORTANT: DO NOT automatically fill OTP digits
      // Just log it for debugging purposes
      if (bookingResponse.otp_code_for_testing) {
        console.log('TEST OTP Code (for debugging only):', bookingResponse.otp_code_for_testing);
        // DO NOT fill it automatically - this is the fix!
      }
      
      // Clear any previous OTP digits
      otpDigits.value = Array(6).fill('');
      
      // Focus on first input
      nextTick(() => {
        const firstInput = otpInputs.value[0];
        if (firstInput) {
          firstInput.focus();
          // Clear any existing value
          firstInput.value = '';
        }
      });
    } else {
      // If no verification required, show success directly
      showSuccessModal.value = true;
    }
    
  } catch (error: any) {
    console.error('Error in booking flow:', error);
    
    if (error.response) {
      switch (error.response.status) {
        case 401:
          errorMessage.value = 'Authentication required. Please login again.';
          break;
        case 404:
          errorMessage.value = 'Booking service not available. Please try again later.';
          break;
        case 422:
          const errors = error.response.data.errors;
          if (errors) {
            errorMessage.value = Object.values(errors).flat().join(', ');
          } else if (error.response.data.message) {
            errorMessage.value = error.response.data.message;
          } else {
            errorMessage.value = 'Validation error. Please check your input.';
          }
          break;
        case 500:
          errorMessage.value = 'Server error. Please try again later.';
          break;
        default:
          errorMessage.value = error.message || 'Failed to create booking. Please try again.';
      }
    } else if (error.request) {
      errorMessage.value = 'No response from server. Please check your connection.';
    } else {
      errorMessage.value = `Request error: ${error.message}`;
    }
  } finally {
    isSendingOTP.value = false;
  }
};

// Step 2: Verify OTP with the booking ID
const verifyOTP = async (otp: string) => {
  if (!pendingBookingId.value) {
    throw new Error('No pending booking found');
  }
  
  try {
    const token = getAuthToken();
    
    console.log('Verifying OTP for booking ID:', pendingBookingId.value);
    
    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, {
      otp_code: otp
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('OTP verified successfully:', response.data);
    return response.data;
    
  } catch (error: any) {
    console.error('Error verifying OTP:', error);
    
    if (error.response?.status === 422) {
      if (error.response.data.message) {
        throw new Error(error.response.data.message);
      }
    } else if (error.response?.status === 400) {
      throw new Error('Invalid OTP. Please try again.');
    } else if (error.response?.data?.message) {
      throw new Error(error.response.data.message);
    }
    throw error;
  }
};

// Step 3: Resend OTP - FIXED: Don't auto-fill OTP
const resendOTP = async () => {
  if (!pendingBookingId.value) {
    throw new Error('No pending booking found');
  }
  
  isResendingOTP.value = true;
  otpError.value = '';
  
  try {
    const token = getAuthToken();
    
    console.log('Resending OTP for booking ID:', pendingBookingId.value);
    
    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('OTP resent successfully:', response.data);
    
    // Reset timer and clear OTP inputs
    startOTPTimer();
    otpDigits.value = Array(6).fill('');
    otpSentSuccess.value = true;
    otpError.value = 'New OTP sent successfully!';
    
    // IMPORTANT: DO NOT automatically fill OTP digits
    if (response.data.otp_code_for_testing) {
      console.log('TEST OTP Code (resend, for debugging only):', response.data.otp_code_for_testing);
      // DO NOT fill it automatically - this is the fix!
    }
    
    // Focus on first input
    nextTick(() => {
      const firstInput = otpInputs.value[0];
      if (firstInput) {
        firstInput.focus();
        // Clear any existing value
        firstInput.value = '';
      }
    });
    
    return response.data;
    
  } catch (error: any) {
    console.error('Error resending OTP:', error);
    throw error;
  } finally {
    isResendingOTP.value = false;
  }
};

// Step 2 & 3 combined: Verify OTP and complete booking
const verifyOTPAndCompleteBooking = async () => {
  const enteredOTP = otpDigits.value.join('');
  
  if (enteredOTP.length !== 6) {
    otpError.value = 'Please enter complete 6-digit OTP';
    return;
  }
  
  isVerifyingOTP.value = true;
  otpError.value = '';
  
  try {
    // Verify OTP with the booking
    await verifyOTP(enteredOTP);
    
    // Success: Show success modal
    closeOTPModal();
    showSuccessModal.value = true;
    
  } catch (error: any) {
    console.error('Error in verification:', error);
    otpError.value = error.message || 'Failed to verify OTP. Please try again.';
    
    // Reset OTP on error
    otpDigits.value = Array(6).fill('');
    nextTick(() => {
      const firstInput = otpInputs.value[0];
      if (firstInput) {
        firstInput.focus();
        // Clear input value
        firstInput.value = '';
      }
    });
  } finally {
    isVerifyingOTP.value = false;
  }
};

// Debug function
const debugResourceLoading = async () => {
  console.log('=== DEBUG RESOURCE LOADING ===');
  console.log('Route:', route);
  console.log('Query:', route.query);
  console.log('Params:', route.params);
  console.log('Resource ID:', route.query.resourceId || route.params.id);
  console.log('Current resource state:', resource.value);
  
  const resourceId = route.query.resourceId || route.params.id;
  if (resourceId) {
    await loadResourceDetails();
  } else {
    console.error('No resource ID found in URL');
  }
};

// Modal Functions
const closeOTPModal = () => {
  showOTPModal.value = false;
  otpDigits.value = Array(6).fill('');
  otpInputs.value.forEach(input => {
    if (input) input.value = '';
  });
  otpError.value = '';
  otpSentSuccess.value = false;
  isVerifyingOTP.value = false;
  isResendingOTP.value = false;
  
  if (otpTimerInterval.value) {
    clearInterval(otpTimerInterval.value);
    otpTimerInterval.value = null;
  }
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  
  // Reset form for new booking
  bookingForm.value.email = '';
  bookingForm.value.date = minDate.value;
  bookingForm.value.startTime = '09:00';
  bookingForm.value.endTime = '10:00';
  bookingForm.value.purpose = '';
  pendingBookingId.value = null;
  pendingBookingReference.value = '';
  tempBookingData.value = null;
};

const redirectToBookings = () => {
  closeSuccessModal();
  router.push('/master-admin/booking');
};

// Watch for route changes
watch(
  () => route.query.resourceId,
  (newResourceId) => {
    if (newResourceId) {
      loadResourceDetails();
    }
  }
);

// Initialize
onMounted(() => {
  loadResourceDetails();
});
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header {
  border-radius: 8px 8px 0 0 !important;
}

.sticky-top {
  position: sticky;
  z-index: 100;
}

.badge {
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.form-label {
  font-weight: 500;
  color: #1e4449;
}

.form-control, .form-select {
  border: 1px solid #ced4da;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
}

.form-control:focus, .form-select:focus {
  border-color: #1e4449;
  box-shadow: 0 0 0 0.25rem rgba(30, 68, 73, 0.25);
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

.alert-success {
  background-color: #d1e7dd;
  border-color: #badbcc;
  color: #0f5132;
}

.alert-success.alert-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 450px;
  animation: modalSlideIn 0.3s ease;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #dee2e6;
  border-radius: 12px 12px 0 0;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #dee2e6;
  border-radius: 0 0 12px 12px;
}

/* OTP Input Styles */
.otp-digit {
  width: 45px;
  height: 55px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 600;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  transition: all 0.2s;
}

.otp-digit:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 3px rgba(75, 182, 109, 0.1);
  outline: none;
}

.otp-digit:disabled {
  background-color: #f8f9fa;
  opacity: 0.7;
}

.otp-icon {
  font-size: 3rem;
}

.success-icon {
  font-size: 4rem;
}

.booking-details {
  background-color: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #4BB66D;
}

/* Schedule list styles */
.schedule-list li {
  padding: 0.35rem 0;
  border-bottom: 1px solid #f1f1f1;
}

.schedule-list li:last-child {
  border-bottom: none;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
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