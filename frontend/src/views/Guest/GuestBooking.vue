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
              <h2 class="mb-0 fw-bold text-dark-teal">Secure Reservation hhh</h2>
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

            <!-- Booking History Section -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Booking History</h5>
                <div>
                  <button 
                    class="btn btn-sm btn-outline-primary"
                    @click="loadBookings"
                    :disabled="isLoadingBookings"
                  >
                    <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoadingBookings }"></i>
                    Refresh
                  </button>
                </div>
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
                            <button 
                              v-if="booking.status === 'pending' || booking.status === 'confirmed'"
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

            <!-- Weekly Availability -->
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
                <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                  <i class="bi bi-person-badge me-2 fs-5"></i>
                  <span><strong>External User (Guest)</strong> - Standard Charges Apply</span>
                </div>

                <form @submit.prevent="createBookingAndSendOTP">
                  
                  <!-- Resource Unavailable Message -->
                  <div v-if="isResourceUnavailable" class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span>Resource is UNAVAILABLE on this day. (Check weekly schedule)</span>
                  </div>

                  <!-- Booking Conflict Message - FIXED like Admin page -->
                  <div v-if="isBookingConflict" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-calendar-x-fill me-2 fs-5"></i>
                    <span>Slot UNAVAILABLE: This time is already booked and confirmed.</span>
                  </div>

                  <!-- Invalid Range Message -->
                  <div v-if="bookingForm.startTime && bookingForm.endTime && bookingForm.startTime >= bookingForm.endTime" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-clock-fill me-2 fs-5"></i>
                    <span>Invalid Time: End time must be after start time.</span>
                  </div>

                  <!-- Email Input -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Email Address</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-teal"></i></span>
                      <input type="email" class="form-control border-start-0" v-model="bookingForm.email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-text x-small">Notification will be sent to this email.</div>
                  </div>

                  <!-- Phone Input -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Phone Number</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-teal"></i></span>
                      <input type="tel" class="form-control border-start-0" v-model="bookingForm.phone" placeholder="+94 77 123 4567" required>
                    </div>
                    <div class="form-text x-small">Used for booking verification.</div>
                  </div>

                  <!-- Reservation Details -->
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Select Date</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-day text-teal"></i></span>
                      <input type="date" class="form-control border-start-0" v-model="bookingForm.date" :min="minDate" required>
                    </div>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">Start Time (24h)</label>
                      <div class="d-flex gap-1 align-items-center">
                        <select v-model="startHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold">:</span>
                        <select v-model="startMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">End Time (24h)</label>
                      <div class="d-flex gap-1 align-items-center">
                        <select v-model="endHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold">:</span>
                        <select v-model="endMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="mt-3">
                    <p class="mb-1">
                      <strong>Resource Cost:</strong> 
                      <span v-if="calculatedCost">Rs. {{ calculatedCost }}</span>
                      <span v-else class="text-muted">--</span>
                    </p>
                    <small class="text-muted">Base Price: Rs. {{ resource.base_price }}/hour</small>
                  </div>

                  <!-- Equipment Section -->
                  <div class="booking-equipment-section mb-4 pb-3 border-bottom">
                    <h6 class="border-bottom pb-2 mb-3">Add Equipment/Accessories (Optional)</h6>
                    
                    <div class="mb-3">
                      <label class="form-label">Search Equipment</label>
                      <div class="input-group">
                        <input
                          type="text"
                          class="form-control"
                          placeholder="Search equipment by name..."
                          v-model="equipmentSearch"
                          @input="searchEquipment"
                          @focus="searchEquipment"
                        >
                        <button
                          class="btn btn-outline-secondary"
                          type="button"
                          @click="clearEquipmentSearch"
                        >
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                      
                      <div v-if="showEquipmentDropdown && filteredEquipment.length > 0" class="equipment-dropdown mt-2 border rounded">
                        <div 
                          v-for="item in filteredEquipment" 
                          :key="item.id"
                          class="equipment-dropdown-item p-2 border-bottom"
                          @click="addEquipmentItem(item)"
                        >
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <strong>{{ item.name }}</strong>
                              <div class="small text-muted">{{ item.description }}</div>
                            </div>
                            <div class="text-end">
                              <div class="fw-bold">Rs. {{ item.price_per_hour }}/hr</div>
                              <div class="small text-muted">Available: {{ item.available_quantity }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div v-if="equipmentSearch && filteredEquipment.length === 0" class="text-muted small mt-2">
                        No equipment found matching "{{ equipmentSearch }}"
                      </div>
                    </div>
                    
                    <div v-if="selectedEquipment.length > 0" class="selected-equipment-list">
                      <h6 class="mb-2">Selected Equipment:</h6>
                      <div class="list-group">
                        <div 
                          v-for="(item, index) in selectedEquipment" 
                          :key="item.id"
                          class="list-group-item p-3 mb-2 border rounded"
                        >
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                              <strong>{{ item.name }}</strong>
                              <div class="small text-muted">Rs. {{ item.price_per_hour }}/hr</div>
                            </div>
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-danger"
                              @click="removeEquipmentItem(index)"
                            >
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                          
                          <div class="row align-items-center">
                            <div class="col-6">
                              <label class="form-label small mb-1">Quantity</label>
                              <div class="input-group input-group-sm">
                                <button
                                  class="btn btn-outline-secondary"
                                  type="button"
                                  @click="decreaseQuantity(index)"
                                  :disabled="item.quantity <= 1"
                                >
                                  <i class="bi bi-dash"></i>
                                </button>
                                <input
                                  type="number"
                                  class="form-control text-center"
                                  v-model.number="item.quantity"
                                  min="1"
                                  :max="item.available_quantity"
                                  @change="validateQuantity(index)"
                                >
                                <button
                                  class="btn btn-outline-secondary"
                                  type="button"
                                  @click="increaseQuantity(index)"
                                  :disabled="item.quantity >= item.available_quantity"
                                >
                                  <i class="bi bi-plus"></i>
                                </button>
                              </div>
                            </div>
                            <div class="col-6 text-end">
                              <div class="small text-muted mb-1">Max: {{ item.available_quantity }}</div>
                              <div class="fw-bold text-success">
                                Rs. {{ calculateEquipmentItemCost(item) }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-2 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="fw-medium">Equipment Total:</span>
                          <span class="fw-bold text-primary">
                            Rs. {{ equipmentTotalCost }}
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div v-else class="text-center text-muted py-3 border rounded">
                      <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
                      <p class="mt-2 mb-0">No equipment added yet</p>
                      <small>Search and add equipment from above</small>
                    </div>
                  </div>

                  <!-- Cost Summary -->
                  <div class="cost-summary mb-4">
                    <h6 class="border-bottom pb-2">Cost Summary</h6>
                    <div class="cost-breakdown">
                      <div class="d-flex justify-content-between mb-2">
                        <span>Resource Cost:</span>
                        <span>Rs. {{ calculatedCost || 0 }}</span>
                      </div>
                      <div class="d-flex justify-content-between mb-2">
                        <span>Equipment Cost:</span>
                        <span>Rs. {{ equipmentTotalCost }}</span>
                      </div>
                      <div class="d-flex justify-content-between mb-2 border-top pt-2">
                        <span class="fw-bold">Total Cost:</span>
                        <span class="fw-bold fs-5 text-success">
                          Rs. {{ totalBookingCost }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Weekly Availability (Beautiful UI) -->
                  <div class="schedule-details mb-4 pb-3 border-bottom">
                    <h6 class="text-muted fw-bold mb-3">Weekly Availability</h6>
                    
                    <div v-if="!resource.availability || resource.availability.length === 0" class="text-muted small">
                        No schedule defined.
                    </div>
                    
                    <div v-else class="availability-list">
                      <div v-for="day in sortedAvailability" :key="day.day_name" class="day-availability mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="fw-medium text-dark">{{ day.day_name }}</span>
                          <span :class="day.is_available ? 'badge bg-success' : 'badge bg-secondary'">
                            {{ day.is_available ? 'Available' : 'Not Available' }}
                          </span>
                        </div>
                        
                        <div v-if="day.is_available && day.slots && day.slots.length > 0">
                          <div class="time-slots-container ms-2">
                            <div v-for="(slot, idx) in day.slots" :key="idx" class="time-slot mb-2">
                              <div class="d-flex align-items-center">
                                <i class="bi bi-clock text-dark-teal me-2"></i>
                                <span class="slot-time">
                                  {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                                </span>
                                <span v-if="day.slots.length > 1" class="badge bg-light text-dark border ms-2">
                                  Slot {{ idx + 1 }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <div v-else-if="day.is_available" class="text-muted small ms-2">
                          <i class="bi bi-info-circle me-1"></i> No specific time slots (available all day)
                        </div>
                        
                        <div v-else class="text-muted small ms-2">
                          <i class="bi bi-x-circle me-1"></i> Not available on this day
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Submit Button -->
                  <button 
                    type="submit" 
                    class="btn btn-success w-100"
                    :disabled="isCreatingBooking || isResourceUnavailable || isBookingConflict || (bookingForm.startTime >= bookingForm.endTime)"
                  >
                    <span v-if="isCreatingBooking" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-send-check me-2"></i>
                    {{ isCreatingBooking ? 'Creating Booking...' : 'Book Now & Verify OTP' }}
                  </button>
                </form>
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
                <i class="bi bi-check-circle me-1"></i>OTP sent successfully! Please check your email.
              </div>
            </div>
            
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
                  :ref="el => { if (el) otpInputs[n-1] = el as any }"
                  :disabled="isVerifyingOTP"
                />
              </div>
              <div v-if="otpError" class="text-danger text-center mt-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ otpError }}
              </div>
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
              @click="verifyOTPAndConfirmBooking"
              :disabled="!isOtpComplete || isVerifyingOTP"
            >
              <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
              <i class="bi bi-check-circle me-2"></i>
              {{ isVerifyingOTP ? 'Verifying...' : 'Verify & Confirm Booking' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="modal-overlay">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle-fill me-2"></i>Booking Confirmed Successfully!
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
              <div v-if="selectedEquipment.length > 0" class="mb-2">
                <strong>Equipment:</strong>
                <ul class="mb-0 ps-3 small">
                  <li v-for="item in selectedEquipment" :key="item.id">
                    {{ item.name }} (Qty: {{ item.quantity }})
                  </li>
                </ul>
              </div>
              <p class="mb-0"><strong>Total Cost:</strong> Rs. {{ totalBookingCost }}</p>
            </div>
            
            <p class="text-muted small">
              A confirmation email has been sent to <strong>{{ bookingForm.email }}</strong>
            </p>
            <div v-if="confirmedBookingReference" class="alert alert-info mt-3">
              <i class="bi bi-info-circle me-2"></i>
              Booking Reference: <strong>{{ confirmedBookingReference }}</strong>
              <br>
              <small>Status: <span class="badge status-confirmed">Confirmed</span></small>
            </div>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-outline-success" @click="closeSuccessModal">
              <i class="bi bi-calendar-plus me-2"></i>Book Another
            </button>
          </div>
        </div>
      </div>

      <!-- Booking Details Modal -->
      <div v-if="selectedBooking" class="modal-overlay">
        <div class="modal-content" style="max-width: 700px;">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-calendar-check me-2"></i>Booking Details
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedBooking = null"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Booking Information</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th width="40%">Reference:</th>
                      <td>{{ selectedBooking.booking_reference || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <th>Status:</th>
                      <td>
                        <span class="badge" :class="getBookingStatusClass(selectedBooking.status)">
                          {{ getBookingStatusText(selectedBooking.status) }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>Date:</th>
                      <td>{{ formatDate(selectedBooking.booking_date) }}</td>
                    </tr>
                    <tr>
                      <th>Time:</th>
                      <td>{{ formatTime(selectedBooking.start_time) }} - {{ formatTime(selectedBooking.end_time) }}</td>
                    </tr>
                    <tr>
                      <th>Duration:</th>
                      <td>{{ calculateDuration(selectedBooking.start_time, selectedBooking.end_time) }} hours</td>
                    </tr>
                    <tr>
                      <th>Amount:</th>
                      <td class="fw-bold text-success">
                        Rs. {{ calculateBookingAmount(selectedBooking) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Customer Information</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th width="40%">Name:</th>
                      <td>{{ selectedBooking.user?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <th>Email:</th>
                      <td>{{ selectedBooking.user?.email || selectedBooking.user_email || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <th>Phone:</th>
                      <td>{{ selectedBooking.phone || selectedBooking.user?.phone || 'N/A' }}</td>
                    </tr>
                  </tbody>
                </table>

                <h6 class="fw-bold mb-3 mt-4">Resource Details</h6>
                <table class="table table-sm table-borderless">
                  <tbody>
                    <tr>
                      <th width="40%">Resource:</th>
                      <td>{{ resource?.name }}</td>
                    </tr>
                    <tr>
                      <th>Category:</th>
                      <td>{{ resource?.category?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <th>Rate:</th>
                      <td>Rs. {{ resource?.base_price }}/hour</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-if="selectedBooking.notes" class="mt-4">
              <h6 class="fw-bold mb-2">Notes</h6>
              <div class="alert alert-light border">
                {{ selectedBooking.notes }}
              </div>
            </div>

            <div class="mt-4">
              <h6 class="fw-bold mb-3">Booking Timeline</h6>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-marker bg-success"></div>
                  <div class="timeline-content">
                    <h6 class="mb-1">Booking Created & Confirmed</h6>
                    <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.created_at) }}</p>
                  </div>
                </div>
                <div v-if="selectedBooking.confirmed_at" class="timeline-item">
                  <div class="timeline-marker bg-primary"></div>
                  <div class="timeline-content">
                    <h6 class="mb-1">Booking Confirmed via OTP</h6>
                    <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.confirmed_at) }}</p>
                  </div>
                </div>
                <div v-if="selectedBooking.cancelled_at" class="timeline-item">
                  <div class="timeline-marker bg-danger"></div>
                  <div class="timeline-content">
                    <h6 class="mb-1">Booking Cancelled</h6>
                    <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.cancelled_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="selectedBooking = null">
              Close
            </button>
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

// Helper to get auth token
const getAuthToken = () => localStorage.getItem('token') || '';

// Bookings computed property
const bookings = computed(() => {
  if (!resource.value) return [];
  const currentResourceId = resource.value.id;
  return bookingStore.bookings.filter((b: any) => {
    return b.details && b.details.some((detail: any) => 
      detail.item_type === 'resource' && Number(detail.item_id) === Number(currentResourceId)
    );
  });
});

// 🔥 FIXED: isBookingConflict - Same as Admin page
const isBookingConflict = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDateStr = bookingForm.value.date;
  const selectedStart = bookingForm.value.startTime.substring(0, 5);
  const selectedEnd = bookingForm.value.endTime.substring(0, 5);
  
  return bookings.value.some((b: any) => {
    const status = (b.status || '').toLowerCase();
    // Only check confirmed or approved bookings (not pending or cancelled)
    if (status !== 'confirmed' && status !== 'approved') return false;
    
    let bDateStr = '';
    if (b.booking_date) {
      const bDate = new Date(b.booking_date);
      bDateStr = bDate.toISOString().split('T')[0];
    }
    
    if (bDateStr !== selectedDateStr) return false;
    
    const bStart = (b.start_time || '').substring(0, 5);
    const bEnd = (b.end_time || '').substring(0, 5);
    
    if (!bStart || !bEnd) return false;
    
    // Check for time overlap
    const overlap = (selectedStart < bEnd) && (bStart < selectedEnd);
    return overlap;
  });
});

const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => 
    daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name)
  );
});

const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDate = new Date(bookingForm.value.date);
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    (day: any) => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  if (!dayAvailability || !dayAvailability.is_available) return true;
  
  if (dayAvailability.slots && dayAvailability.slots.length > 0) {
    const selectedStart = bookingForm.value.startTime.substring(0, 5);
    const selectedEnd = bookingForm.value.endTime.substring(0, 5);
    
    return !dayAvailability.slots.some((slot: any) => {
      const slotStart = slot.start_time.substring(0, 5);
      const slotEnd = slot.end_time.substring(0, 5);
      return selectedStart >= slotStart && selectedEnd <= slotEnd;
    });
  }
  
  return false;
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
const selectedBooking = ref<any>(null);

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
  phone: '',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '10:00',
  purpose: ''
});

// Time Helpers
const hourOptions = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minuteOptions = ['00', '15', '30', '45'];
const startHour = ref('08');
const startMin = ref('00');
const endHour = ref('10');
const endMin = ref('00');

// Sync time selects with bookingForm
watch([startHour, startMin], () => { bookingForm.value.startTime = `${startHour.value}:${startMin.value}`; });
watch([endHour, endMin], () => { bookingForm.value.endTime = `${endHour.value}:${endMin.value}`; });

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

const formatTimeShort = (time: string) => {
  if (!time) return '';
  return time.substring(0, 5);
};

const formatCountdownTimer = () => {
  const m = Math.floor(otpTimer.value / 60);
  const s = otpTimer.value % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
};

const calculateDuration = (startTime: string, endTime: string): string => {
  const start = new Date(`2000-01-01T${startTime}`);
  const end = new Date(`2000-01-01T${endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours.toFixed(1);
};

const calculateBookingAmount = (booking: any): number => {
  if (booking.total_amount) {
    return booking.total_amount;
  }
  
  if (booking.details && booking.details.length > 0) {
    return booking.details.reduce((sum: number, detail: any) => sum + detail.subtotal, 0);
  }
  
  return booking.total_amount || 0;
};

const getBookingStatusClass = (status: string) => {
  switch (status) {
    case 'pending': return 'status-pending';
    case 'confirmed': return 'status-confirmed';
    case 'cancelled': return 'status-cancelled';
    case 'completed': return 'status-completed';
    default: return 'bg-secondary';
  }
};

const getBookingStatusText = (status: string) => {
  switch (status) {
    case 'pending': return 'Pending';
    case 'confirmed': return 'Confirmed';
    case 'cancelled': return 'Cancelled';
    case 'completed': return 'Completed';
    default: return status.charAt(0).toUpperCase() + status.slice(1);
  }
};

// Main Functions
const loadResourceDetails = async () => {
  isLoading.value = true;
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await axios.get(`${API_BASE_URL}/resources/${route.params.id}`, { headers });
    resource.value = res.data.resource || res.data;
    
    // Process availability data
    if (resource.value.availability) {
      resource.value.availability = resource.value.availability.map((day: any) => {
        if (day.slots && Array.isArray(day.slots)) {
          return day;
        }
        const slots = [];
        if (day.start_time && day.end_time) {
          slots.push({
            start_time: day.start_time,
            end_time: day.end_time
          });
        }
        return { ...day, slots };
      });
    }
    
    await bookingStore.fetchByResource(resource.value.id);
  } catch (e) {
    errorMessage.value = "Could not load resource details.";
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
  } catch (error: any) {
    console.error('Error loading bookings:', error);
  } finally {
    isLoadingBookings.value = false;
  }
};

const createBooking = async () => {
  if (!resource.value) {
    throw new Error('Resource not loaded');
  }
  
  // Check for booking conflict before creating
  if (isBookingConflict.value) {
    throw new Error('This time slot is already booked and confirmed. Please choose another time.');
  }
  
  try {
    const token = getAuthToken();
    
    const bookingPayload: any = {
      user_id: 0,
      user_email: bookingForm.value.email,
      phone: bookingForm.value.phone,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || 'Guest Reservation',
      total_amount: totalBookingCost.value,
      resources: [
        {
          resource_id: resource.value.id
        }
      ],
      booking_items: []
    };
    
    if (selectedEquipment.value.length > 0) {
      selectedEquipment.value.forEach(item => {
        bookingPayload.booking_items.push({
          item_id: item.id,
          item_type: 'equipment',
          quantity: item.quantity,
          price_per_hour: item.price_per_hour
        });
      });
    }
    
    const response = await axios.post(`${API_BASE_URL}/bookings`, bookingPayload, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    const data = response.data;
    if (data.booking) {
      pendingBookingId.value = data.booking.id;
      bookingStore.updateBookingLocally(data.booking);
    } else if (data.booking_id) {
      pendingBookingId.value = data.booking_id;
    } else if (data.id) {
      pendingBookingId.value = data.id;
      bookingStore.updateBookingLocally(data);
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

const createBookingAndSendOTP = async () => {
  if (!bookingForm.value.email || !bookingForm.value.phone) {
    errorMessage.value = "Email and Phone Number are required.";
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
  
  // Check for past dates
  const selectedDate = new Date(bookingForm.value.date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (selectedDate < today) {
    errorMessage.value = 'Cannot book for past dates';
    return;
  }
  
  // Check resource availability
  if (isResourceUnavailable.value) {
    errorMessage.value = 'Resource is not available during the selected time';
    return;
  }
  
  // Check for booking conflict
  if (isBookingConflict.value) {
    alert("This time slot is already booked and confirmed for this resource. Please choose another time.");
    errorMessage.value = 'Time slot is already booked and confirmed.';
    return;
  }
  
  // Check equipment quantities
  for (const item of selectedEquipment.value) {
    if (item.quantity > item.available_quantity) {
      errorMessage.value = `Quantity for ${item.name} exceeds available quantity (${item.available_quantity})`;
      return;
    }
  }

  isCreatingBooking.value = true;
  errorMessage.value = '';
  
  try {
    await createBooking();
    
    if (pendingBookingId.value) {
      otpSentSuccess.value = true;
      showOTPModal.value = true;
      startOTPTimer();
      otpDigits.value = Array(6).fill('');
      
      await nextTick();
      if (otpInputs.value[0]) otpInputs.value[0].focus();
    }
    
  } catch (error: any) {
    console.error('Error in booking flow:', error);
    errorMessage.value = error.message || 'Failed to create booking. Please try again.';
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
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, 
      { otp_code: code },
      { headers }
    );

    confirmedBookingReference.value = response.data.booking?.booking_reference || 'REF-GUEST';
    showOTPModal.value = false;
    showSuccessModal.value = true;
    
    await loadBookings();
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Invalid OTP';
    otpDigits.value = Array(6).fill('');
    if (otpInputs.value[0]) otpInputs.value[0].focus();
  } finally {
    isVerifyingOTP.value = false;
  }
};

const cancelBooking = async (booking: any) => {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  try {
    const token = getAuthToken();
    await axios.post(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    alert('Booking cancelled successfully');
    await loadBookings();
  } catch (e) {
    alert('Failed to cancel booking');
  }
};

const viewBookingDetails = (booking: any) => {
  selectedBooking.value = booking;
};

// OTP Input Handlers
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

const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) clearInterval(otpTimerInterval.value);
  otpTimerInterval.value = setInterval(() => {
    if (otpTimer.value > 0) otpTimer.value--;
  }, 1000);
};

const resendOTP = async () => {
  if (!pendingBookingId.value) return;
  isResendingOTP.value = true;
  otpError.value = '';
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
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

const closeOTPModal = () => {
  showOTPModal.value = false;
  otpError.value = '';
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  router.push('/guest-resources');
};

// Equipment Functions
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
    } else {
      availableEquipment.value = [];
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

const validateQuantity = (index: number) => {
  const item = selectedEquipment.value[index];
  if (item.quantity < 1) {
    selectedEquipment.value[index].quantity = 1;
  } else if (item.quantity > item.available_quantity) {
    selectedEquipment.value[index].quantity = item.available_quantity;
    alert(`Maximum available quantity is ${item.available_quantity}`);
  }
};

const calculateEquipmentItemCost = (item: any): number => {
  const hours = calculateBookingDuration();
  return Math.round(item.price_per_hour * item.quantity * hours);
};

// Helper Functions
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

// Watchers
watch([() => bookingForm.value.date, () => bookingForm.value.startTime, () => bookingForm.value.endTime], () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime && bookingForm.value.startTime < bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

onMounted(() => {
  loadResourceDetails();
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

.extra-small { font-size: 0.75rem; }
.x-small { font-size: 0.75rem; }

.table {
  font-size: 0.85rem;
}

.table th, .table td {
  padding: 0.6rem 0.5rem;
  vertical-align: middle;
}

.availability-list {
  max-height: 350px;
  overflow-y: auto;
  padding-right: 5px;
}

.day-availability {
  padding: 12px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f3f5;
  border-left: 4px solid #1e4449;
  margin-bottom: 12px;
}

.time-slots-container {
  background-color: #f8f9fa;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.time-slot {
  padding: 4px 8px;
  background-color: white;
  border-radius: 4px;
  border-left: 3px solid #4BB66D;
  margin-bottom: 5px;
}

.time-slot:last-child {
  margin-bottom: 0;
}

.slot-time {
  font-family: 'Courier New', Courier, monospace;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.85rem;
}

.availability-list::-webkit-scrollbar {
  width: 4px;
}

.availability-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.availability-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>