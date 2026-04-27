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
          <!-- Left Column - Resource Info & Booking History -->
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
                    <p class="card-text text-muted small mb-3">{{ resource.description?.substring(0, 150) }}{{ resource.description?.length > 150 ? '...' : '' }}</p>
                    <div class="d-flex gap-3 flex-wrap">
                      <div class="small"><i class="bi bi-geo-alt text-teal me-1"></i>{{ resource.location_name || 'N/A' }}</div>
                      <div class="small"><i class="bi bi-tag text-teal me-1"></i>{{ resource.category?.name || 'Unknown' }}</div>
                      <div class="small"><i class="bi bi-cash-stack text-teal me-1"></i>LKR {{ resource.base_price }}/hr</div>
                      <div class="small"><i class="bi bi-info-circle text-teal me-1"></i>Status: <span class="badge" :class="getStatusClass(resource.status)">{{ resource.status }}</span></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Booking History Section - FULL FEATURE -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark-teal"><i class="bi bi-clock-history me-2"></i>Booking History</h5>
                <button 
                  class="btn btn-sm btn-outline-teal"
                  @click="loadBookings"
                  :disabled="isLoadingBookings"
                >
                  <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoadingBookings }"></i>
                  Refresh
                </button>
              </div>
              <div class="card-body">
                <!-- Loading State for Bookings -->
                <div v-if="isLoadingBookings" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-teal" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2 text-muted small">Loading booking history...</p>
                </div>

                <!-- No Bookings Found -->
                <div v-else-if="bookings.length === 0" class="text-center py-5 text-muted">
                  <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                  <p class="mt-2 mb-0">No bookings found for this resource</p>
                </div>

                <!-- Bookings Table -->
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
                        <th>Created At</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(booking, index) in bookings" :key="booking.id">
                        <td>{{ index + 1 }}</td>
                        <td>{{ formatDate(booking.booking_date) }}</td>
                        <td>{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle me-2"></i>
                            <div>
                              <div class="text-muted extra-small">{{ booking.user?.email || booking.user_email || 'N/A' }}</div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="fw-bold text-success">
                            Rs. {{ calculateBookingAmount(booking) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="getBookingStatusClass(booking.status)">
                            {{ getBookingStatusText(booking.status) }}
                          </span>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ formatDateTime(booking.created_at) }}
                          </small>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm" role="group">
                            <button 
                              class="btn btn-outline-info"
                              @click="viewBookingDetails(booking)"
                              title="View Details"
                            >
                              <i class="bi bi-eye"></i>
                            </button>
                            <!-- Cancel button only shows for bookings made by the same email -->
                            <button 
                              v-if="(booking.status === 'pending' || booking.status === 'confirmed') && booking.user_email === bookingForm.email"
                              class="btn btn-outline-warning"
                              @click="cancelBooking(booking)"
                              title="Cancel Booking"
                            >
                              <i class="bi bi-x-circle"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Weekly Availability (Beautiful UI) -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 text-dark-teal"><i class="bi bi-calendar3 me-2"></i>Weekly Availability</h5>
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
                    <div v-for="day in sortedAvailability.filter(d => d.is_available && d.slots && d.slots.length > 0)" :key="'slots-'+day.day_name" class="p-2 px-3 bg-light rounded text-dark small border">
                      <strong>{{ day.day_name }}:</strong> 
                      <span v-for="(slot, idx) in day.slots" :key="idx">
                        {{ formatTimeShort(slot.start_time) }} - {{ formatTimeShort(slot.end_time) }}{{ idx < day.slots.length - 1 ? ', ' : '' }}
                      </span>
                    </div>
                    <div v-for="day in sortedAvailability.filter(d => d.is_available && (!d.slots || d.slots.length === 0))" :key="'all-day-'+day.day_name" class="p-2 px-3 bg-light rounded text-dark small border">
                      <strong>{{ day.day_name }}:</strong> All Day
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
                <h5 class="mb-0 fw-bold">Book This Resource</h5>
              </div>
              <div class="card-body p-4">
                <!-- User Type Display - Guest -->
                <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                  <i class="bi bi-person-badge me-2 fs-5"></i>
                  <span><strong>External User (Guest)</strong> - Standard Charges Apply</span>
                </div>

                <form @submit.prevent="createBookingAndSendOTP">
                  
                  <div v-if="isResourceUnavailable" class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span>This resource is not available on the selected date.</span>
                  </div>

                  <div v-if="isBookingConflict" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-calendar-x-fill me-2 fs-5"></i>
                    <span>This time slot is already reserved. Please try another time.</span>
                  </div>

                  <div v-if="bookingForm.startTime && bookingForm.endTime && bookingForm.startTime >= bookingForm.endTime" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-clock-fill me-2 fs-5"></i>
                    <span>Invalid Time: End time must be after start time.</span>
                  </div>

                  <!-- Email Input -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Confirm Email Address</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-teal"></i></span>
                      <input type="email" class="form-control border-start-0" v-model="bookingForm.email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-text x-small">Notification will be sent to this email.</div>
                  </div>

                  <!-- 1. Reservation Details -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Select Date</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-day text-teal"></i></span>
                      <input type="date" class="form-control border-start-0" v-model="bookingForm.date" :min="minDate" required>
                    </div>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">Start Time</label>
                      <div class="d-flex gap-1">
                        <select v-model="startHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold align-self-center">:</span>
                        <select v-model="startMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">End Time</label>
                      <div class="d-flex gap-1">
                        <select v-model="endHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold align-self-center">:</span>
                        <select v-model="endMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- 2. Booking Equipment Section with Quantity -->
                  <div class="mb-4">
                    <label class="form-label small fw-bold text-dark-teal mb-2">Add Equipment (Optional)</label>
                    <div class="equipment-box p-3 rounded border border-light-subtle bg-light-teal-hint">
                      <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-teal"></i></span>
                        <input type="text" class="form-control shadow-none" placeholder="Search equipment..." v-model="equipmentSearch" @input="searchEquipment" @focus="searchEquipment">
                      </div>

                      <!-- Equipment Dropdown -->
                      <div v-if="showEquipmentDropdown && filteredEquipment.length > 0" class="equipment-dropdown shadow-sm rounded border">
                        <div v-for="item in filteredEquipment" :key="item.id" @click="addEquipmentItem(item)" class="p-2 border-bottom hover-bg-teal-light small cursor-pointer">
                          <div class="d-flex justify-content-between">
                            <span class="fw-medium">{{ item.name }}</span>
                            <span class="text-teal">LKR {{ item.price_per_hour }}/hr</span>
                          </div>
                          <div class="x-small text-muted">Available: {{ item.available_quantity }}</div>
                        </div>
                      </div>

                      <!-- Selected Equipment List with Quantity Selector -->
                      <div v-if="selectedEquipment.length > 0" class="mt-2">
                        <div v-for="(item, index) in selectedEquipment" :key="item.id" class="d-flex justify-content-between align-items-center mb-2 small bg-white p-2 rounded shadow-xs">
                          <div class="flex-grow-1">
                            <span class="fw-medium">{{ item.name }}</span>
                            <div class="x-small text-muted">LKR {{ item.price_per_hour }}/hr</div>
                          </div>
                          <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 100px;">
                              <button class="btn btn-outline-secondary btn-sm" type="button" @click="decreaseQuantity(index)" :disabled="item.quantity <= 1">-</button>
                              <input type="number" class="form-control text-center form-control-sm" v-model.number="item.quantity" min="1" :max="item.available_quantity" style="width: 45px;">
                              <button class="btn btn-outline-secondary btn-sm" type="button" @click="increaseQuantity(index)" :disabled="item.quantity >= item.available_quantity">+</button>
                            </div>
                            <button type="button" @click="removeEquipmentItem(index)" class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                          </div>
                        </div>
                      </div>
                      <div v-else class="text-center py-2 text-muted x-small">
                        No equipment selected.
                      </div>
                    </div>
                  </div>

                  <!-- 3. Cost Summary -->
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
                      <span class="text-dark-teal fw-bold">Total Cost</span>
                      <span class="fs-4 fw-bold text-teal">LKR {{ totalBookingCost.toLocaleString() }}</span>
                    </div>
                  </div>

                  <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-teal-modern py-3 shadow-sm rounded-3" :disabled="isCreatingBooking || isResourceUnavailable || isBookingConflict || (bookingForm.startTime >= bookingForm.endTime) || !bookingForm.email">
                      <span v-if="isCreatingBooking" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-check2-circle me-2"></i>
                      {{ isCreatingBooking ? 'Sending Request...' : 'Request Now & Verify OTP' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>


    <!-- OTP Verification Modal (Synced with working User view) -->
    <div v-if="showOTPModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header bg-dark-teal text-white py-3 px-4">
          <h5 class="modal-title mb-0">
            <i class="bi bi-shield-lock me-2"></i>OTP Verification
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="showOTPModal = false"></button>
        </div>
        <div class="modal-body p-4">
          <div class="text-center mb-4">
            <div class="otp-pulse-circle mx-auto mb-3">
              <i class="bi bi-envelope-check fs-2 text-teal"></i>
            </div>
            <h6 class="fw-bold text-dark-teal">Verify Your Email</h6>
            <p class="text-muted small">
              We've sent a 6-digit code to:<br>
              <span class="fw-bold text-teal">{{ bookingForm.email }}</span>
            </p>
            <div v-if="otpSentSuccess" class="alert alert-success alert-sm py-2 x-small">
              <i class="bi bi-check-circle me-1"></i>OTP sent successfully!
            </div>
          </div>

          <div class="otp-input-container d-flex justify-content-center gap-2 mb-4">
            <input
              v-for="n in 6"
              :key="n"
              type="text"
              maxlength="1"
              class="otp-digit"
              v-model="otpDigits[n-1]"
              @input="onOtpInput(n-1, $event)"
              @keydown="onOtpKeydown(n-1, $event)"
              :ref="el => { if (el) otpInputs[n-1] = el as any }"
              :disabled="isVerifyingOTP"
              inputmode="numeric"
              autocomplete="one-time-code"
            />
          </div>

          <div v-if="otpError" class="text-danger text-center mt-2 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ otpError }}
          </div>

          <div class="text-center mb-3">
            <small class="text-muted">
              OTP expires in: 
              <span class="fw-bold" :class="otpExpired ? 'text-danger' : 'text-success'">
                {{ formatCountdownTimer() }}
              </span>
            </small>
          </div>

          <div class="text-center">
            <button 
              class="btn btn-link btn-sm text-decoration-none text-teal fw-bold"
              @click="resendOTP"
              :disabled="!otpExpired || isResendingOTP"
            >
              <span v-if="isResendingOTP" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-arrow-clockwise me-1"></i>
              Resend OTP
            </button>
          </div>
        </div>
        <div class="modal-footer d-flex gap-2 justify-content-center border-0 pb-4">
          <button @click="showOTPModal = false" class="btn btn-outline-secondary px-4 rounded-pill" :disabled="isVerifyingOTP">
            Cancel
          </button>
          <button @click="verifyOTPAndConfirmBooking" class="btn btn-teal-modern px-4 rounded-pill" :disabled="!isOtpComplete || isVerifyingOTP">
            <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
            Verify & Confirm
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay">
      <div class="modal-content text-center p-4">
        <div class="mb-4">
          <div class="success-icon-check mx-auto">
            <i class="bi bi-check-lg"></i>
          </div>
        </div>
        <h4 class="fw-bold text-dark-teal mb-3">Booking Confirmed!</h4>
        <p class="text-muted mb-4">Your booking request has been successfully processed and confirmed.</p>
        
        <div class="booking-details-box text-start p-3 bg-light rounded mb-4">
          <div class="mb-2"><small class="text-muted">Reference:</small> <span class="fw-bold text-dark-teal">{{ confirmedBookingReference }}</span></div>
          <div class="mb-2"><small class="text-muted">Resource:</small> <span class="fw-bold text-dark-teal">{{ resource.name }}</span></div>
          <div class="mb-0"><small class="text-muted">Date:</small> <span class="fw-bold text-dark-teal">{{ formatDate(bookingForm.date) }}</span></div>
        </div>

        <button @click="closeSuccessModal" class="btn btn-teal-modern w-100 py-2 rounded-pill">
          Return to Gallery
        </button>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedBooking" class="modal-overlay">
      <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header bg-dark-teal text-white py-3">
          <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Booking Details</h5>
          <button type="button" class="btn-close btn-close-white" @click="selectedBooking = null"></button>
        </div>
        <div class="modal-body p-4">
           <!-- Details info... -->
           <p>Reference: {{ selectedBooking.booking_reference }}</p>
           <p>Status: {{ selectedBooking.status }}</p>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-secondary px-4" @click="selectedBooking = null">Close</button>
        </div>
      </div>
    </div>

    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { bookingStore } from '../../store/bookingStore';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';

// Helper to get auth token (Guests might not have one, which is fine)
const getAuthToken = () => localStorage.getItem('token') || '';

// Computed Properties for template
const bookings = computed(() => {
    // Filter bookings for this resource only if needed, 
    // but the store might already be filtered by fetchByResource
    return bookingStore.bookings.filter(b => b.resources?.some((r: any) => r.id === resource.value?.id) || b.resource_id === resource.value?.id);
});

const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => 
    daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name)
  );
});

const minDate = computed(() => new Date().toISOString().split('T')[0]);

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date) return false;
  const dayName = new Date(bookingForm.value.date).toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvail = resource.value.availability?.find((a: any) => a.day_name === dayName);
  return dayAvail ? !dayAvail.is_available : true;
});

const isBookingConflict = computed(() => {
  if (!bookings.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDate = bookingForm.value.date;
  const selectedStart = bookingForm.value.startTime;
  const selectedEnd = bookingForm.value.endTime;

  return bookings.value.some(b => {
    if (b.booking_date !== selectedDate || b.status === 'cancelled') return false;
    return (selectedStart < b.end_time && selectedEnd > b.start_time);
  });
});

const otpExpired = computed(() => otpTimer.value <= 0);
const isOtpComplete = computed(() => otpDigits.value.join('').length === 6);


const calculateBookingDuration = (): number => {
  if (!bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours;
};

const calculateAmountWithUserType = (baseAmount: number): number => {
  // Guests always pay full amount
  return baseAmount;
};

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  try {
    const hours = calculateBookingDuration();
    const baseAmount = Math.round(hours * (resource.value.base_price || 0));
    return calculateAmountWithUserType(baseAmount) || 0;
  } catch (e) {
    return 0;
  }
});

const equipmentTotalCost = computed(() => {
  if (!selectedEquipment.value.length) return 0;
  try {
    const hours = calculateBookingDuration();
    const total = selectedEquipment.value.reduce((total, item) => {
      return total + ((item.price_per_hour || 0) * (item.quantity || 0) * hours);
    }, 0);
    return calculateAmountWithUserType(total) || 0;
  } catch (e) {
    return 0;
  }
});

const totalBookingCost = computed(() => {
  return (Number(calculatedCost.value) || 0) + (Number(equipmentTotalCost.value) || 0);
});




// State
const resource = ref<any>(null);
const isLoading = ref(true);
const isLoadingBookings = ref(false);
const isLoadingEquipment = ref(false);
const errorMessage = ref('');


// Equipment & Search
const availableEquipment = ref<any[]>([]);
const filteredEquipment = ref<any[]>([]);
const selectedEquipment = ref<any[]>([]);
const equipmentSearch = ref('');
const showEquipmentDropdown = ref(false);

// OTP & Modals
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otpDigits = ref<string[]>(Array(6).fill(''));
const otpInputs = ref<HTMLInputElement[]>([]);
const otpError = ref('');
const isVerifyingOTP = ref(false);
const isCreatingBooking = ref(false);
const isResendingOTP = ref(false);
const otpTimer = ref(300);
const otpTimerInterval = ref<any>(null);
const pendingBookingId = ref<number | null>(null);
const confirmedBookingReference = ref<string>('');
const otpSentSuccess = ref(false);

// Booking Form
const bookingForm = ref({
  email: '',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '09:00',
  purpose: ''
});

// Time Helpers
const hourOptions = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minuteOptions = ['00', '15', '30', '45'];
const startHour = ref('08');
const startMin = ref('00');
const endHour = ref('09');
const endMin = ref('00');

// Sync time selects with bookingForm
watch([startHour, startMin], () => { bookingForm.value.startTime = `${startHour.value}:${startMin.value}`; });
watch([endHour, endMin], () => { bookingForm.value.endTime = `${endHour.value}:${endMin.value}`; });

// --- CORE FIX: Create Booking Logic ---
const createBookingAndSendOTP = async () => {
  if (!bookingForm.value.email) {
    errorMessage.value = "Email is required for Guest verification.";
    return;
  }

  isCreatingBooking.value = true;
  errorMessage.value = '';
  
  try {
    const payload = {
      user_id: 0, // Guest marker
      user_email: bookingForm.value.email,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || 'Guest Reservation',
      resources: [{ resource_id: Number(route.params.id) }],
      booking_items: selectedEquipment.value.map(item => ({
        item_id: item.id,
        quantity: item.quantity
      }))
    };

    // Note the X-User-Type header to match your backend resolution
    const token = getAuthToken();
    const headers: any = { 
        'Accept': 'application/json',
        'X-User-Type': 'external' 
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.post(`${API_BASE_URL}/bookings`, payload, { headers });

    // FIX: Match the backend response key 'booking_id'
    if (response.data.booking_id) {
      pendingBookingId.value = response.data.booking_id;
      showOTPModal.value = true; // This will trigger the v-if in template
      startOTPTimer();
      otpSentSuccess.value = true;
      
      // Auto-focus first digit
      await nextTick();
      if (otpInputs.value[0]) otpInputs.value[0].focus();
    }
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Failed to initiate booking.';
  } finally {
    isCreatingBooking.value = false;
  }

};

const verifyOTPAndConfirmBooking = async () => {
  const code = otpDigits.value.join('');
  if (code.length < 6) return;

  isVerifyingOTP.value = true;
  otpError.value = '';

  try {
    const token = getAuthToken();
    const headers: any = { 
        'Accept': 'application/json',
        'X-User-Type': 'external' 
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, 
      { otp_code: code },
      { headers }
    );


    confirmedBookingReference.value = response.data.booking?.booking_reference || 'REF-GUEST';
    showOTPModal.value = false;
    showSuccessModal.value = true;
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Invalid OTP';
    otpDigits.value = Array(6).fill('');
    if (otpInputs.value[0]) otpInputs.value[0].focus();
  } finally {
    isVerifyingOTP.value = false;
  }
};

// --- OTP Input Management ---
const onOtpInput = (index: number, event: any) => {
  const val = event.target.value;
  if (val.length > 1) {
    otpDigits.value[index] = val.slice(-1);
  }
  if (val && index < 5) {
    otpInputs.value[index + 1]?.focus();
  }
};

const onOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpInputs.value[index - 1]?.focus();
  }
};

// --- Lifecycle & Load ---
const loadResourceDetails = async () => {
  isLoading.value = true;
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await axios.get(`${API_BASE_URL}/resources/${route.params.id}`, { headers });
    resource.value = res.data.resource || res.data;
    // Fetch bookings for this resource to show in history
    await bookingStore.fetchByResource(resource.value.id);
  } catch (e) {
    errorMessage.value = "Could not load resource details.";
  } finally {
    isLoading.value = false;
  }
};


const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) clearInterval(otpTimerInterval.value);
  otpTimerInterval.value = setInterval(() => {
    if (otpTimer.value > 0) otpTimer.value--;
  }, 1000);
};

const formatCountdownTimer = () => {
  const m = Math.floor(otpTimer.value / 60);
  const s = otpTimer.value % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
};

onMounted(loadResourceDetails);

// Equipment Watchers
watch(() => bookingForm.value.date, () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

watch([() => bookingForm.value.startTime, () => bookingForm.value.endTime], () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

const loadAvailableEquipment = async () => {
  isLoadingEquipment.value = true;
  try {
    const token = getAuthToken();
    const params = {
      date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime
    };
    
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.get(`${API_BASE_URL}/booking-items/availability`, {
      params,
      headers
    });
    
    const equipmentData = response.data;
    if (Array.isArray(equipmentData)) {
      availableEquipment.value = equipmentData.filter((item: any) => 
        item.status === 'Available' && item.available_quantity > 0
      );
    }
  } catch (error) {
    console.error('Error loading equipment:', error);
  } finally {
    isLoadingEquipment.value = false;
  }
};

const searchEquipment = () => {
  const searchTerm = equipmentSearch.value.toLowerCase().trim();
  filteredEquipment.value = availableEquipment.value.filter(item => {
    const nameMatch = item.name.toLowerCase().includes(searchTerm);
    return !searchTerm || nameMatch;
  });
  showEquipmentDropdown.value = true;
};

const clearEquipmentSearch = () => {
  equipmentSearch.value = '';
  filteredEquipment.value = [];
  showEquipmentDropdown.value = false;
};

const addEquipmentItem = (item: any) => {
  const existingIndex = selectedEquipment.value.findIndex(selected => selected.id === item.id);
  if (existingIndex !== -1) {
    if (selectedEquipment.value[existingIndex].quantity < item.available_quantity) {
      selectedEquipment.value[existingIndex].quantity++;
    }
  } else {
    selectedEquipment.value.push({ ...item, quantity: 1 });
  }
  clearEquipmentSearch();
};

const removeEquipmentItem = (index: number) => {
  selectedEquipment.value.splice(index, 1);
};

const increaseQuantity = (index: number) => {
  const item = selectedEquipment.value[index];
  if (item.quantity < item.available_quantity) {
    selectedEquipment.value[index].quantity++;
  }
};

const decreaseQuantity = (index: number) => {
  if (selectedEquipment.value[index].quantity > 1) {
    selectedEquipment.value[index].quantity--;
  }
};


// --- Helpers ---
const getImageUrl = (res: any) => {
  if (res?.images && res.images.length > 0) {
    return `${API_BASE_URL}/resources/storage/${res.images[0].file_path}`;
  }
  return 'https://via.placeholder.com/600x400?text=No+Image';
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-success';
    case 'Maintenance': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
};

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};

const formatTime = (time: string) => {
  if (!time) return '00:00';
  return time.substring(0, 5);
};

const formatTimeShort = (time: string) => formatTime(time);

const formatDateTime = (dateTimeString: string) => {
  if (!dateTimeString) return 'N/A';
  return new Date(dateTimeString).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const calculateBookingAmount = (booking: any) => {
  return booking.total_amount || 0;
};

const getBookingStatusClass = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'status-pending';
    case 'confirmed': return 'status-confirmed';
    case 'cancelled': return 'status-cancelled';
    case 'requested_by_guest': return 'bg-info text-white';
    default: return 'bg-secondary';
  }
};

const getBookingStatusText = (status: string) => {
  if (!status) return 'Unknown';
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const viewBookingDetails = (booking: any) => {
  alert(`Booking Reference: ${booking.booking_reference}\nDate: ${formatDate(booking.booking_date)}\nStatus: ${getBookingStatusText(booking.status)}`);
};

const cancelBooking = async (booking: any) => {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  try {
    const token = getAuthToken();
    await axios.post(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    alert('Booking cancelled successfully');
    loadResourceDetails();
  } catch (e) {
    alert('Failed to cancel booking');
  }
};

const closeOTPModal = () => {
  showOTPModal.value = false;
};

const resendOTP = async () => {
  if (!pendingBookingId.value) return;
  isResendingOTP.value = true;
  otpError.value = '';
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json', 'X-User-Type': 'external' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, {
        email: bookingForm.value.email
    }, { headers });
    
    startOTPTimer();
    otpDigits.value = Array(6).fill('');
    otpSentSuccess.value = true;
    otpError.value = 'New OTP sent successfully!';
    
    await nextTick();
    if (otpInputs.value[0]) otpInputs.value[0].focus();
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Failed to resend OTP';
  } finally {
    isResendingOTP.value = false;
  }
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  router.push('/guest-resources');
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

.btn-outline-teal-modern {
    background: transparent;
    border: 1px solid #1e4449;
    color: #1e4449;
    transition: all 0.2s ease;
    font-weight: 600;
}

.btn-outline-teal-modern:hover {
    background: #1e4449;
    color: white;
}

.btn-outline-teal {
    border-color: #1e4449;
    color: #1e4449;
}

.btn-outline-teal:hover {
    background-color: #1e4449;
    color: white;
}

.cursor-pointer { cursor: pointer; }
.hover-bg-teal-light:hover { background-color: #e5f4de; }

.equipment-dropdown {
    position: absolute;
    z-index: 1000;
    background: white;
    width: calc(100% - 2px);
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
}

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
    border-color: #1e4449;
    box-shadow: 0 0 0 3px rgba(30, 68, 73, 0.1);
    outline: none;
}

.details-modal-content {
    max-width: 800px;
}

.success-modal-header, .details-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}

.success-modal-body, .details-modal-body {
    padding: 1.5rem;
}

.success-modal-footer, .details-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.booking-details {
    background-color: #f8f9fa;
    border-radius: 8px;
}

/* Badge Styles */
.badge.status-pending {
    background-color: #ffffff !important;
    color: #8B8000 !important;
    border: 1px solid #FFD700;
}

.badge.status-confirmed {
    background-color: #28a745 !important;
    color: white !important;
}

.badge.status-cancelled {
    background-color: #dc3545 !important;
    color: white !important;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 68, 73, 0.2); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(30, 68, 73, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 68, 73, 0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>