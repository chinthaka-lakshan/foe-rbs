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
                      {{ isCreatingBooking ? 'Creating Booking...' : 'Book Now & Verify OTP' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- OTP Verification Modal -->
      <div class="modal fade" id="otpModal" tabindex="-1" role="dialog" data-bs-backdrop="static" ref="otpModalRef">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg overflow-hidden rounded-4">
            <div class="modal-header bg-dark-teal text-white border-0 py-3">
              <h5 class="modal-title fs-6"><i class="bi bi-shield-lock-fill me-2"></i>OTP Verification</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
              <div class="mb-4">
                <div class="otp-pulse-circle mx-auto mb-4">
                  <i class="bi bi-envelope-check-fill text-teal fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark-teal mb-2">Verify Your Email</h4>
                <p class="text-muted">Enter the 6-digit code sent to<br><span class="fw-bold text-teal">{{ bookingForm.email }}</span></p>
                <div v-if="otpSentSuccess" class="alert alert-success alert-sm py-2 mt-2">
                  <i class="bi bi-check-circle me-1"></i>OTP sent successfully!
                </div>
              </div>

              <div class="otp-fields d-flex justify-content-center gap-2 mb-4">
                <input
                  v-for="n in 6"
                  :key="n"
                  type="text"
                  maxlength="1"
                  class="form-control otp-digit text-center fw-bold"
                  v-model="otpDigits[n-1]"
                  @input="onOtpInput(n-1, $event)"
                  @keydown="onOtpKeydown(n-1, $event)"
                  :ref="el => { if (el) otpInputs[n-1] = el as any }"
                  :disabled="isVerifyingOTP"
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

              <div class="d-grid gap-3 px-4">
                <button @click="verifyOTPAndConfirmBooking" class="btn btn-teal-modern py-2 rounded-pill" :disabled="!isOtpComplete || isVerifyingOTP">
                  <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
                  Verify & Confirm Booking
                </button>
                <div class="text-center">
                  <button @click="resendOTP" class="btn btn-link text-teal text-decoration-none small fw-bold" :disabled="!otpExpired || isResendingOTP">
                    {{ !otpExpired ? `Resend available in ${otpTimer}s` : 'Resend Code' }}
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

      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="modal-overlay-custom" @click.self="closeSuccessModal">
        <div class="success-modal-content">
          <div class="success-modal-header bg-teal text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>Booking Confirmed!</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeSuccessModal"></button>
          </div>
          <div class="success-modal-body text-center">
            <i class="bi bi-check-circle" style="font-size: 4rem; color: #4BB66D;"></i>
            <h6 class="fw-bold mt-3">Your booking has been confirmed!</h6>
            <div class="booking-details bg-light p-3 rounded mt-3 text-start">
              <p class="mb-1"><strong>Resource:</strong> {{ resource?.name }}</p>
              <p class="mb-1"><strong>Date:</strong> {{ bookingForm.date }}</p>
              <p class="mb-1"><strong>Time:</strong> {{ bookingForm.startTime }} - {{ bookingForm.endTime }}</p>
              <div v-if="selectedEquipment.length > 0" class="mb-1">
                <strong>Equipment:</strong>
                <ul class="mb-0 ps-3 small">
                  <li v-for="item in selectedEquipment" :key="item.id">{{ item.name }} (Qty: {{ item.quantity }})</li>
                </ul>
              </div>
              <p class="mb-0"><strong>Total Cost:</strong> Rs. {{ totalBookingCost }}</p>
            </div>
            <p class="text-muted small mt-3">Confirmation sent to <strong>{{ bookingForm.email }}</strong></p>
            <div v-if="confirmedBookingReference" class="alert alert-info mt-2">
              <i class="bi bi-info-circle me-2"></i>
              Booking Reference: <strong>{{ confirmedBookingReference }}</strong>
            </div>
          </div>
          <div class="success-modal-footer justify-content-center">
            <button class="btn btn-teal-modern" @click="redirectToResources">Browse More Resources</button>
            <button class="btn btn-outline-teal-modern" @click="closeSuccessModal">Book Another</button>
          </div>
        </div>
      </div>

      <!-- Booking Details Modal -->
      <div v-if="selectedBooking" class="modal-overlay-custom" @click.self="selectedBooking = null">
        <div class="details-modal-content">
          <div class="details-modal-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Booking Details</h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedBooking = null"></button>
          </div>
          <div class="details-modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Booking Information</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr><th width="40%">Reference:</th><td>{{ selectedBooking.booking_reference || 'N/A' }}</td></tr>
                    <tr><th>Status:</th><td><span class="badge" :class="getBookingStatusClass(selectedBooking.status)">{{ getBookingStatusText(selectedBooking.status) }}</span></td></tr>
                    <tr><th>Date:</th><td>{{ formatDate(selectedBooking.booking_date) }}</td></tr>
                    <tr><th>Time:</th><td>{{ formatTime(selectedBooking.start_time) }} - {{ formatTime(selectedBooking.end_time) }}</td></tr>
                    <tr><th>Duration:</th><td>{{ calculateDuration(selectedBooking.start_time, selectedBooking.end_time) }} hours</td></tr>
                    <tr><th>Amount:</th><td class="fw-bold text-success">Rs. {{ calculateBookingAmount(selectedBooking) }}</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Customer Information</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr><th width="40%">Name:</th><td>{{ selectedBooking.user?.name || 'N/A' }}</td></tr>
                    <tr><th>Email:</th><td>{{ selectedBooking.user?.email || selectedBooking.user_email || 'N/A' }}</td></tr>
                  </tbody>
                </table>
                <h6 class="fw-bold mb-3 mt-4">Resource Details</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr><th width="40%">Resource:</th><td>{{ resource?.name }}</td></tr>
                    <tr><th>Category:</th><td>{{ resource?.category?.name || 'N/A' }}</td></tr>
                    <tr><th>Rate:</th><td>Rs. {{ resource?.base_price }}/hour</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-if="selectedBooking.notes" class="mt-3">
              <h6 class="fw-bold mb-2">Notes</h6>
              <div class="alert alert-light border">{{ selectedBooking.notes }}</div>
            </div>
          </div>
          <div class="details-modal-footer">
            <button class="btn btn-secondary" @click="selectedBooking = null">Close</button>
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
import * as bootstrap from 'bootstrap';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { bookingStore } from '../../store/bookingStore';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL_ROOT = 'http://localhost:8000/api/resources/storage';

// Get auth token
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token') || 
         '';
};

// Formatting Functions
const formatTime = (time: string | null): string => {
    if (!time) return '00:00';
    return time.substring(0, 5); 
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const calculateDuration = (startTime: string, endTime: string): string => {
  const start = new Date(`2000-01-01T${startTime}`);
  const end = new Date(`2000-01-01T${endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours.toFixed(1);
};

const formatTimeShort = (time: string) => {
  if (!time) return '';
  const [h, m] = time.split(':');
  const d = new Date();
  d.setHours(parseInt(h), parseInt(m));
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

// Interfaces
interface Resource {
  id: number;
  name: string;
  location_name?: string;
  category_id: number;
  category: ResourceCategory;
  base_price: number;
  department: string;
  description?: string;
  status: 'Active' | 'Inactive' | 'Maintenance';
  capacity?: number;
  availability: ResourceAvailability[]; 
  images?: Array<{ file_path: string; file_name: string; }>;
}

interface TimeSlot { start_time: string; end_time: string; }
interface ResourceAvailability { id: number; day_name: string; day_of_week: number; is_available: boolean; slots: TimeSlot[]; }
interface ResourceCategory { id: number; name: string; }
interface BookingEquipment { id: number; name: string; description: string; price_per_hour: number; available_quantity: number; status: string; }
interface SelectedEquipmentItem extends BookingEquipment { quantity: number; }
interface BookingForm { email: string; date: string; startTime: string; endTime: string; purpose?: string; }
interface Booking { id: number; booking_reference: string; user_id: number; user_email: string; booking_date: string; start_time: string; end_time: string; total_amount: number; status: string; notes: string; created_at: string; updated_at: string; confirmed_at: string | null; cancelled_at: string | null; details: BookingDetail[]; user?: { id: number; name: string; email: string; phone?: string; }; }
interface BookingDetail { id: number; item_type: string; item_id: number; quantity: number; unit_price: number; subtotal: number; }

// State
const resource = ref<Resource | null>(null);
const bookings = computed(() => {
  if (!resource.value) return [];
  const currentResourceId = resource.value.id;
  return bookingStore.bookings.filter((b: any) => {
    return b.details && b.details.some((detail: any) => 
      detail.item_type === 'resource' && Number(detail.item_id) === Number(currentResourceId)
    );
  });
});
const selectedBooking = ref<Booking | null>(null);
const isLoading = ref(true);
const isLoadingBookings = ref(false);
const errorMessage = ref('');

// Equipment State
const availableEquipment = ref<BookingEquipment[]>([]);
const filteredEquipment = ref<BookingEquipment[]>([]);
const selectedEquipment = ref<SelectedEquipmentItem[]>([]);
const equipmentSearch = ref('');
const isLoadingEquipment = ref(false);
const showEquipmentDropdown = ref(false);

// OTP State
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otpDigits = ref<string[]>(Array(6).fill(''));
const otpInputs = ref<(HTMLInputElement | null)[]>(Array(6).fill(null));
const otpError = ref('');
const isVerifyingOTP = ref(false);
const isCreatingBooking = ref(false);
const isResendingOTP = ref(false);
const otpTimer = ref(300);
const otpTimerInterval = ref<number | null>(null);
const pendingBookingId = ref<number | null>(null);
const confirmedBookingReference = ref<string>('');
const otpSentSuccess = ref(false);
let modalInstance: bootstrap.Modal | null = null;
const otpModalRef = ref<HTMLElement | null>(null);

// Booking Form
const bookingForm = ref<BookingForm>({
  email: localStorage.getItem('userEmail') || '',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '12:00',
  purpose: ''
});

// Computed Properties
const minDate = computed(() => new Date().toISOString().split('T')[0]);

const hourOptions = computed(() => Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0')));
const minuteOptions = computed(() => Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0')));

const startHour = ref('08');
const startMin = ref('00');
const endHour = ref('12');
const endMin = ref('00');

watch([startHour, startMin], () => { bookingForm.value.startTime = `${startHour.value}:${startMin.value}`; });
watch([endHour, endMin], () => { bookingForm.value.endTime = `${endHour.value}:${endMin.value}`; });

watch(() => bookingForm.value.startTime, (newVal: string) => {
  if (newVal && newVal.includes(':')) { const [h, m] = newVal.split(':'); startHour.value = h.padStart(2, '0'); startMin.value = m.padStart(2, '0'); }
}, { immediate: true });

watch(() => bookingForm.value.endTime, (newVal: string) => {
  if (newVal && newVal.includes(':')) { const [h, m] = newVal.split(':'); endHour.value = h.padStart(2, '0'); endMin.value = m.padStart(2, '0'); }
}, { immediate: true });

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return Math.round(hours * resource.value.base_price);
});

const equipmentTotalCost = computed(() => {
  return selectedEquipment.value.reduce((total, item) => {
    const hours = calculateBookingDuration();
    return total + (item.price_per_hour * item.quantity * hours);
  }, 0);
});

const totalBookingCost = computed(() => calculatedCost.value + equipmentTotalCost.value);

const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name));
});

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  const selectedDate = new Date(bookingForm.value.date);
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(day => day.day_name.toLowerCase() === selectedDayName.toLowerCase());
  if (!dayAvailability || !dayAvailability.is_available) return true;
  if (dayAvailability.slots && dayAvailability.slots.length > 0) {
    const selectedStart = bookingForm.value.startTime.substring(0, 5);
    const selectedEnd = bookingForm.value.endTime.substring(0, 5);
    return !dayAvailability.slots.some(slot => {
      const slotStart = slot.start_time.substring(0, 5);
      const slotEnd = slot.end_time.substring(0, 5);
      return selectedStart >= slotStart && selectedEnd <= slotEnd;
    });
  }
  return false;
});

const isBookingConflict = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  const selectedDateStr = bookingForm.value.date;
  const selectedStart = bookingForm.value.startTime.substring(0, 5);
  const selectedEnd = bookingForm.value.endTime.substring(0, 5);
  return bookings.value.some((b: any) => {
    const status = (b.status || '').toLowerCase();
    if (status !== 'confirmed' && status !== 'approved') return false;
    let bDateStr = '';
    if (b.booking_date) { const bDate = new Date(b.booking_date); bDateStr = bDate.toISOString().split('T')[0]; }
    if (bDateStr !== selectedDateStr) return false;
    const bStart = (b.start_time || '').substring(0, 5);
    const bEnd = (b.end_time || '').substring(0, 5);
    if (!bStart || !bEnd) return false;
    return (selectedStart < bEnd) && (bStart < selectedEnd);
  });
});

const isOtpComplete = computed(() => otpDigits.value.every(digit => digit.length === 1));
const otpExpired = computed(() => otpTimer.value <= 0);

// Helper Functions
const processAvailabilityData = (availabilityData: any[]) => {
  if (!availabilityData || !Array.isArray(availabilityData)) return [];
  return availabilityData.map(day => {
    if (day.slots && Array.isArray(day.slots)) {
      return { ...day, slots: day.slots.map((slot: any) => ({ start_time: slot.start_time || '', end_time: slot.end_time || '' })) };
    }
    const slots = [];
    if (day.start_time && day.end_time) slots.push({ start_time: day.start_time, end_time: day.end_time });
    return { ...day, slots };
  });
};

const calculateBookingDuration = (): number => {
  if (!bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  return (end.getTime() - start.getTime()) / (1000 * 60 * 60);
};

const getImageUrl = (res: any) => {
  if (res.images && res.images.length > 0) return `${API_BASE_URL}/resources/storage/${res.images[0].file_path}`;
  return 'https://via.placeholder.com/600x400?text=No+Resource+Image';
};

const getStatusClass = (status: string): string => {
  switch (status) { case 'Active': return 'bg-success'; case 'Inactive': return 'bg-secondary'; case 'Maintenance': return 'bg-warning'; default: return 'bg-secondary'; }
};

const getBookingStatusClass = (status: string): string => {
  switch (status) { case 'pending': return 'status-pending'; case 'confirmed': return 'status-confirmed'; case 'cancelled': return 'status-cancelled'; case 'completed': return 'status-completed'; default: return 'bg-secondary'; }
};

const getBookingStatusText = (status: string): string => {
  switch (status) { case 'pending': return 'Pending'; case 'confirmed': return 'Confirmed'; case 'cancelled': return 'Cancelled'; case 'completed': return 'Completed'; default: return status.charAt(0).toUpperCase() + status.slice(1); }
};

const calculateBookingAmount = (booking: Booking): number => {
  if (booking.total_amount) return booking.total_amount;
  if (booking.details && booking.details.length > 0) return booking.details.reduce((sum, detail) => sum + detail.subtotal, 0);
  const start = new Date(`2000-01-01T${booking.start_time}`);
  const end = new Date(`2000-01-01T${booking.end_time}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return Math.round(hours * (resource.value?.base_price || 0));
};

const formatCountdownTimer = () => {
  const minutes = Math.floor(otpTimer.value / 60);
  const seconds = otpTimer.value % 60;
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

// API Functions
const loadResourceDetails = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const id = route.params.id;
    const token = getAuthToken();
    const response = await axios.get(`${API_BASE_URL}/resources/${id}`, {
      headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
    });
    let resourceData = response.data.resource || response.data;
    if (resourceData) {
      if (resourceData.availability) resourceData.availability = processAvailabilityData(resourceData.availability);
      else resourceData.availability = [];
      resource.value = resourceData;
      await loadBookings();
      await loadAvailableEquipment();
    }
    bookingForm.value.date = minDate.value;
  } catch (err: any) {
    console.error('Error loading resource details:', err);
    errorMessage.value = err.response?.data?.message || 'Failed to load resource details.';
  } finally {
    isLoading.value = false;
  }
};

const loadBookings = async () => {
  if (!resource.value) return;
  isLoadingBookings.value = true;
  try {
    await bookingStore.fetchByResource(resource.value.id);
  } catch (error: any) { console.error('Error loading bookings:', error); }
  finally { isLoadingBookings.value = false; }
};

const loadAvailableEquipment = async () => {
  if (!bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    try {
      const token = getAuthToken();
      const response = await axios.get(`${API_BASE_URL}/booking-items`, {
        headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
      });
      let equipmentData = response.data.items || response.data.data || response.data;
      availableEquipment.value = equipmentData.filter((item: any) => item.status === 'Available');
    } catch (error) { console.error('Error loading static equipment:', error); }
    return;
  }
  isLoadingEquipment.value = true;
  try {
    const token = getAuthToken();
    const params = { date: bookingForm.value.date, start_time: bookingForm.value.startTime, end_time: bookingForm.value.endTime };
    const response = await axios.get(`${API_BASE_URL}/booking-items/availability`, {
      params, headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
    });
    const equipmentData = response.data;
    if (Array.isArray(equipmentData)) {
      availableEquipment.value = equipmentData.filter((item: any) => item.status === 'Available' && item.available_quantity > 0);
    } else { availableEquipment.value = []; }
    selectedEquipment.value.forEach(selectedItem => {
      const liveData = equipmentData.find((item: any) => item.id === selectedItem.id);
      if (liveData) {
        selectedItem.available_quantity = liveData.available_quantity;
        if (selectedItem.quantity > liveData.available_quantity) selectedItem.quantity = liveData.available_quantity;
      }
    });
  } catch (error: any) { console.error('Error loading dynamic equipment:', error); }
  finally { isLoadingEquipment.value = false; }
};

const searchEquipment = () => {
  const searchTerm = equipmentSearch.value.toLowerCase().trim();
  filteredEquipment.value = availableEquipment.value.filter(item => {
    const nameMatch = item.name.toLowerCase().includes(searchTerm);
    const descMatch = item.description?.toLowerCase().includes(searchTerm) || false;
    return (nameMatch || descMatch) && item.status === 'Available' && item.available_quantity > 0;
  });
  showEquipmentDropdown.value = filteredEquipment.value.length > 0;
};

const clearEquipmentSearch = () => { equipmentSearch.value = ''; filteredEquipment.value = []; showEquipmentDropdown.value = false; };

const addEquipmentItem = (item: BookingEquipment) => {
  const existingIndex = selectedEquipment.value.findIndex(selected => selected.id === item.id);
  if (existingIndex !== -1) {
    if (selectedEquipment.value[existingIndex].quantity < item.available_quantity) selectedEquipment.value[existingIndex].quantity++;
    else alert(`Maximum available quantity is ${item.available_quantity}`);
  } else {
    selectedEquipment.value.push({ ...item, quantity: 1 });
  }
  clearEquipmentSearch();
};

const removeEquipmentItem = (index: number) => { selectedEquipment.value.splice(index, 1); };
const increaseQuantity = (index: number) => { if (selectedEquipment.value[index].quantity < selectedEquipment.value[index].available_quantity) selectedEquipment.value[index].quantity++; };
const decreaseQuantity = (index: number) => { if (selectedEquipment.value[index].quantity > 1) selectedEquipment.value[index].quantity--; };

const createBooking = async () => {
  if (!resource.value) throw new Error('Resource not loaded');
  try {
    const token = getAuthToken();
    const bookingPayload: any = {
      user_email: bookingForm.value.email,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || '',
      total_amount: totalBookingCost.value,
      resources: [{ resource_id: resource.value.id }],
      booking_items: selectedEquipment.value.map(item => ({ item_id: item.id, item_type: 'equipment', quantity: item.quantity }))
    };
    const response = await axios.post(`${API_BASE_URL}/bookings`, bookingPayload, {
      headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' } : { 'Accept': 'application/json', 'Content-Type': 'application/json' }
    });
    const data = response.data;
    if (data.booking) { pendingBookingId.value = data.booking.id; bookingStore.updateBookingLocally(data.booking); }
    else if (data.id) { pendingBookingId.value = data.id; bookingStore.updateBookingLocally(data); }
    return response.data;
  } catch (error: any) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      throw new Error(errors ? Object.values(errors).flat().join(', ') : error.response.data.message);
    }
    throw error;
  }
};

const createBookingAndSendOTP = async () => {
  if (!resource.value) { errorMessage.value = 'Resource not loaded.'; return; }
  if (!bookingForm.value.email || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) { errorMessage.value = 'Please fill all required fields'; return; }
  if (!bookingForm.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { errorMessage.value = 'Please enter a valid email address'; return; }
  if (bookingForm.value.startTime >= bookingForm.value.endTime) { errorMessage.value = 'End time must be after start time'; return; }
  if (isBookingConflict.value) { alert("This time slot is already booked. Please choose another time."); return; }
  
  const selectedDate = new Date(bookingForm.value.date);
  const today = new Date(); today.setHours(0, 0, 0, 0);
  if (selectedDate < today) { errorMessage.value = 'Cannot book for past dates'; return; }
  
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(day => day.day_name.toLowerCase() === selectedDayName.toLowerCase());
  if (!dayAvailability || !dayAvailability.is_available) { errorMessage.value = `Resource is not available on ${selectedDayName}`; return; }
  if (isResourceUnavailable.value) { errorMessage.value = `Resource not available during selected time on ${selectedDayName}`; return; }
  
  for (const item of selectedEquipment.value) {
    if (item.quantity > item.available_quantity) { errorMessage.value = `Quantity for ${item.name} exceeds available quantity (${item.available_quantity})`; return; }
  }
  
  isCreatingBooking.value = true;
  errorMessage.value = '';
  try {
    await createBooking();
    if (pendingBookingId.value) {
      otpSentSuccess.value = true;
      if (otpModalRef.value) modalInstance = new bootstrap.Modal(otpModalRef.value);
      modalInstance?.show();
      startOTPTimer();
      otpDigits.value = Array(6).fill('');
      nextTick(() => { const firstInput = otpInputs.value[0]; if (firstInput) { firstInput.focus(); firstInput.value = ''; } });
    }
  } catch (error: any) { errorMessage.value = error.message || 'Failed to create booking.'; }
  finally { isCreatingBooking.value = false; }
};

const verifyOTPAndConfirmBooking = async () => {
  const enteredOTP = otpDigits.value.join('');
  if (enteredOTP.length !== 6) { otpError.value = 'Please enter complete 6-digit OTP'; return; }
  isVerifyingOTP.value = true;
  otpError.value = '';
  try {
    const token = getAuthToken();
    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, {
      otp_code: enteredOTP.trim(), email: bookingForm.value.email
    }, { headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' } });
    const confirmedBooking = response.data.booking || response.data;
    confirmedBookingReference.value = confirmedBooking.booking_reference;
    bookingStore.updateBookingLocally(confirmedBooking);
    modalInstance?.hide();
    showSuccessModal.value = true;
    await loadBookings();
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Invalid OTP. Please try again.';
    otpDigits.value = Array(6).fill('');
    nextTick(() => { const firstInput = otpInputs.value[0]; if (firstInput) { firstInput.focus(); firstInput.value = ''; } });
  } finally { isVerifyingOTP.value = false; }
};

const resendOTP = async () => {
  if (!pendingBookingId.value) { otpError.value = 'No pending booking found'; return; }
  isResendingOTP.value = true;
  otpError.value = '';
  try {
    const token = getAuthToken();
    await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, { email: bookingForm.value.email }, {
      headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
    });
    startOTPTimer();
    otpDigits.value = Array(6).fill('');
    otpSentSuccess.value = true;
    otpError.value = 'New OTP sent successfully!';
    nextTick(() => { const firstInput = otpInputs.value[0]; if (firstInput) { firstInput.focus(); firstInput.value = ''; } });
  } catch (error: any) { otpError.value = error.response?.data?.message || 'Failed to resend OTP.'; }
  finally { isResendingOTP.value = false; }
};

const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) clearInterval(otpTimerInterval.value);
  otpTimerInterval.value = window.setInterval(() => {
    if (otpTimer.value > 0) otpTimer.value--;
    else if (otpTimerInterval.value) clearInterval(otpTimerInterval.value);
  }, 1000);
};

const onOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement;
  const value = input.value;
  if (value && !/^\d$/.test(value)) { otpDigits.value[index] = ''; return; }
  otpDigits.value[index] = value;
  if (value && index < 5) { nextTick(() => { const nextInput = otpInputs.value[index + 1]; if (nextInput) nextInput.focus(); }); }
};

const onOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    nextTick(() => { const prevInput = otpInputs.value[index - 1]; if (prevInput) prevInput.focus(); });
  }
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  bookingForm.value.email = '';
  bookingForm.value.date = minDate.value;
  bookingForm.value.startTime = '08:00';
  bookingForm.value.endTime = '12:00';
  selectedEquipment.value = [];
  equipmentSearch.value = '';
  pendingBookingId.value = null;
  confirmedBookingReference.value = '';
};

const redirectToResources = () => {
  closeSuccessModal();
  router.push('/guest-resources');
};

const viewBookingDetails = (booking: Booking) => { selectedBooking.value = booking; };

const cancelBooking = async (booking: Booking) => {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  try {
    const token = getAuthToken();
    const response = await axios.put(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' }
    });
    bookingStore.updateBookingLocally(response.data.booking || response.data);
    if (selectedBooking.value && selectedBooking.value.id === booking.id) selectedBooking.value = response.data.booking || response.data;
    alert('Booking cancelled successfully!');
    await loadBookings();
  } catch (error: any) { alert(error.response?.data?.message || 'Failed to cancel booking'); }
};

watch([() => bookingForm.value.date, () => bookingForm.value.startTime, () => bookingForm.value.endTime], () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime && bookingForm.value.startTime < bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

onMounted(() => {
  loadResourceDetails();
  if (otpModalRef.value) modalInstance = new bootstrap.Modal(otpModalRef.value);
});
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

.otp-digit {
    width: 50px;
    height: 55px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    border: 2px solid #e5f4de;
    border-radius: 10px;
}

.otp-digit:focus {
    border-color: #1e4449;
    box-shadow: 0 0 0 0.25rem rgba(30, 68, 73, 0.1);
}

.x-small { font-size: 0.75rem; }
.extra-small { font-size: 0.7rem; }

/* Modal Overlay Styles */
.modal-overlay-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

.success-modal-content, .details-modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalFadeIn 0.3s ease;
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