<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">Bookings</h2>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading bookings...</p>
    </div>

    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadBookings">
        <i class="bi bi-arrow-clockwise me-1"></i>Retry
      </button>
    </div>

    <div v-else>
      <div class="mb-4 filter-row">
        <div class="row g-3">
          
          <div class="col-sm-6 col-md-3">
            <select class="form-select" v-model="selectedResource">
              <option value="">All Resources</option>
              <option v-for="resource in uniqueResources" :key="resource" :value="resource">
                {{ resource }}
              </option>
            </select>
          </div>
          
          <div class="col-sm-6 col-md-3">
            <select class="form-select" v-model="selectedStatus">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Confirmed">Confirmed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="col-sm-6 col-md-3">
              <div class="input-group">
                  <input 
                      type="date" 
                      class="form-control" 
                      v-model="startDate" 
                      placeholder="Start Date"
                  >
                  <span class="input-group-text calendar-icon-fix">
                      <i class="bi bi-calendar-range"></i>
                  </span>
              </div>
          </div>
          
          <div class="col-sm-6 col-md-3">
              <div class="input-group">
                  <input 
                      type="date" 
                      class="form-control" 
                      v-model="endDate" 
                      placeholder="End Date"
                  >
                  <span class="input-group-text calendar-icon-fix">
                      <i class="bi bi-calendar-range"></i>
                  </span>
              </div>
          </div>
          
        </div>
      </div>

      <div class="calendar-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-sm btn-outline-dark-teal" @click="changeMonth(-1)">
                <i class="bi bi-chevron-left"></i>
            </button>
            <h5 class="mb-0 calendar-title-header">{{ currentMonthName }} {{ currentYear }}</h5>
            <button class="btn btn-sm btn-outline-dark-teal" @click="changeMonth(1)">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="calendar-grid">
            <div v-for="day in weekdays" :key="day" class="calendar-header">{{ day }}</div>
            
            <div 
              v-for="day in daysInMonth" 
              :key="day.dateString" 
              class="calendar-day"
              :class="{ 
                  'day-outside-month': day.isOutsideMonth,
                  'day-has-booking': day.hasBooking,
                  'day-is-start': day.dateString && day.dateString === startDate,
                  'day-is-end': day.dateString && day.dateString === endDate,
                  'day-in-range': day.dateString && isDateInRange(day.dateString),
                  'day-focused': day.dateString && focusedDate === day.dateString
              }"
              @click="day.dateString && handleDayClick(day.dateString)"
              :title="day.hasBooking ? `${day.bookingCount} booking(s) on ${day.dayNumber}` : ''"
            >
              <span class="day-label" v-if="day.dateString === startDate">Start</span>
              <span class="day-label" v-else-if="day.dateString === endDate">End</span>
              <span class="day-number">{{ day.dayNumber }}</span>
              <span v-if="day.bookingCount" class="booking-badge">{{ day.bookingCount }}</span>
            </div>
        </div>
      </div>

      <!-- Selected Day details for Admin -->
      <div v-if="focusedDate" class="card mb-4 border-0 shadow-sm animate-fade-in day-details-panel">
        <div class="card-header bg-light-teal py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark-teal"><i class="bi bi-info-circle-fill me-2"></i>Bookings for {{ formatDateLong(focusedDate) }}</h5>
            <button class="btn-close" @click="focusedDate = ''"></button>
        </div>
        <div class="card-body">
            <div v-if="focusedDateBookings.length > 0" class="row g-3">
                <div v-for="b in focusedDateBookings" :key="b.id" class="col-md-6 col-lg-4">
                    <div class="booking-mini-card p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ b.booking_reference }}</span>
                            <span class="badge" :class="getStatusClass(b.status)">{{ b.status }}</span>
                        </div>
                        <div class="user-info small mb-2">
                            <i class="bi bi-person me-1"></i>{{ b.user_email }}
                        </div>
                        <div class="resource-info small fw-bold mb-1">
                            <i class="bi bi-box-seam me-1"></i>{{ b.resource?.name || b.details?.[0]?.item_name || 'N/A' }}
                        </div>
                        <div class="time-info small text-muted">
                            <i class="bi bi-clock me-1"></i>{{ b.start_time }} - {{ b.end_time }}
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-xs btn-outline-primary py-0 px-2" @click="viewBookingDetails(b.id)" style="font-size: 0.7rem;">View</button>
                            <button v-if="b.status === 'Pending'" class="btn btn-xs btn-outline-success py-0 px-2" @click="confirmBooking(b.id)" style="font-size: 0.7rem;">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-4 text-muted">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                No bookings found for this specific date.
            </div>
        </div>
      </div>

      <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">All Bookings ({{ filteredBookings.length }})</h5>
          <div>
            <button class="btn btn-success btn-sm me-2" @click="loadBookings" :disabled="isRefreshing">
              <span v-if="isRefreshing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-arrow-clockwise me-1"></i>
              {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>User Email</th>
                <th>Resource</th>
                <th>Booking Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in filteredBookings" :key="booking.id">
                <td>
                  <span class="badge bg-light text-dark">{{ booking.booking_reference }}</span>
                </td>
                <td>{{ booking.user_email }}</td>
                
                <td>
                  <template v-if="booking.resource && booking.resource.name">
                    {{ booking.resource.name }}
                  </template>
                  <template v-else-if="booking.details && booking.details.length > 0">
                    {{ booking.details[0].item_name }}
                  </template>
                  <template v-else>
                    <span class="text-muted">N/A</span>
                  </template>
                </td>

                <td>{{ formatDate(booking.booking_date) }}</td>
                <td>{{ booking.start_time }}</td>
                <td>{{ booking.end_time }}</td>
                <td>Rs. {{ booking.total_amount }}</td>
                <td>
                  <span class="badge" :class="getStatusClass(booking.status)">
                    {{ booking.status }}
                  </span>
                </td>
               
                <td>
                  <div class="btn-group btn-group-sm">
                    <!-- ALWAYS SHOW PREVIEW ICON -->
                    <button class="btn btn-outline-info" @click="viewBookingDetails(booking.id)" title="View Details">
                      <i class="bi bi-eye"></i>
                    </button>
                    
                    <!-- ALWAYS SHOW DELETE ICON -->
                    <button class="btn btn-outline-danger" @click="openDeleteConfirmation(booking)" title="Delete Permanently">
                      <i class="bi bi-trash"></i>
                    </button>
                    
                    <!-- SHOW CONFIRM AND REJECT ICONS ONLY FOR PENDING BOOKINGS THAT HAVEN'T BEEN ACTIONED -->
                    <template v-if="booking.status === 'Pending' && !booking.actionTaken">
                      <button class="btn btn-outline-success" @click="confirmBooking(booking.id)" title="Confirm">
                        <i class="bi bi-check-circle"></i>
                      </button>
                      <button class="btn btn-outline-warning" @click="rejectBooking(booking.id)" title="Reject">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </template>
                    
                    <!-- SHOW ONLY PREVIEW AND DELETE AFTER ACTION IS TAKEN -->
                    <template v-else-if="booking.status === 'Pending' && booking.actionTaken">
                      <!-- Preview and Delete already shown above -->
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-if="filteredBookings.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x" style="font-size: 3rem;"></i>
            <p class="mt-3">No bookings found</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" :class="{ 'show d-block': showDeleteConfirmation }" tabindex="-1" @click.self="handleCancelDeletion" style="background-color: rgba(0,0,0,0.5);" v-if="showDeleteConfirmation">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">
                  Are you sure you want to delete the booking 
                  <strong>{{ bookingToDelete?.booking_reference }}</strong> 
                  for <strong>{{ bookingToDelete?.resource?.name || bookingToDelete?.details?.[0]?.item_name || 'N/A' }}</strong>?
                </p>
                <div class="alert alert-warning mt-3" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  This action cannot be undone!
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="isDeleting">
                  <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2"></span>
                  Continue to Delete
                </button>
            </div>
        </template>

        <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Final Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <div class="alert alert-danger" role="alert">
                  <i class="bi bi-exclamation-octagon-fill me-2"></i>
                  <strong>Permanent Deletion!</strong>
                </div>
                <p class="mb-0">You are about to <strong>permanently delete</strong> booking <strong>{{ bookingToDelete?.booking_reference }}</strong>.</p>
                <p class="mt-2 mb-0 text-danger">This action cannot be undone. Are you absolutely sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleDeleteBooking" :disabled="isDeleting">
                  <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2"></span>
                  Yes, Delete Permanently
                </button>
            </div>
        </template>
      </div>
    </div>
  </div>

  <div v-if="showSuccessToast" class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-success text-white">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong class="me-auto">Success</strong>
        <button type="button" class="btn-close btn-close-white" @click="showSuccessToast = false"></button>
      </div>
      <div class="toast-body">
        {{ successMessage }}
      </div>
    </div>
  </div>

  <div v-if="showErrorToast" class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" @click="showErrorToast = false"></button>
      </div>
      <div class="toast-body">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import { bookingStore } from '../../store/bookingStore';

const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';

// Get auth token
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token');
};

// State
const isLoading = computed(() => bookingStore.isLoading && !bookingStore.isLoaded);
const isRefreshing = ref(false);
const errorMessage = ref('');
const bookings = computed(() => bookingStore.bookings);

// Track which bookings have had action taken (Confirm/Reject clicked)
const actionedBookings = ref<Set<number>>(new Set());

// Filter State
const selectedResource = ref('');
const selectedStatus = ref('');
const startDate = ref(''); 
const endDate = ref('');   
const focusedDate = ref(''); 

// Delete Modal State
const bookingToDelete = ref<any>(null);
const showDeleteConfirmation = ref(false);
const deleteStep = ref<'confirm' | 'final'>('confirm');
const isDeleting = ref(false);

// Toast State
const showSuccessToast = ref(false);
const showErrorToast = ref(false);
const successMessage = ref('');

// Calendar State
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const currentDate = ref(new Date());

// --- Helper Functions ---
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Confirmed': return 'bg-success';
    case 'Pending': return 'bg-warning text-dark';
    case 'Cancelled': return 'bg-danger';
    case 'Completed': return 'bg-info';
    default: return 'bg-secondary';
  }
};

const showSuccess = (message: string) => {
  successMessage.value = message;
  showSuccessToast.value = true;
  setTimeout(() => {
    showSuccessToast.value = false;
  }, 3000);
};

const showError = (message: string) => {
  errorMessage.value = message;
  showErrorToast.value = true;
  setTimeout(() => {
    showErrorToast.value = false;
  }, 3000);
};

// --- API Functions ---
const loadBookings = async () => {
  isRefreshing.value = true;
  errorMessage.value = '';
  
  try {
    await bookingStore.fetchAll(true); // Force refresh toggle
    
    // Process local state flags if needed
    bookings.value.forEach(booking => {
      booking.actionTaken = actionedBookings.value.has(booking.id);
      booking.resource = booking.resource || booking.item || booking.details?.[0] || null;
    });
    
  } catch (error: any) {
    console.error('Error loading bookings:', error);
    errorMessage.value = 'Failed to load bookings. Please try again.';
  } finally {
    isRefreshing.value = false;
  }
};

const deleteBooking = async (bookingId: number) => {
  isDeleting.value = true;
  
  try {
    const token = getAuthToken();
    
    // Use DELETE endpoint for permanent deletion
    await axios.delete(`${API_BASE_URL}/bookings/${bookingId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Remove from local state and store
    bookingStore.removeBookingLocally(bookingId);
    
    // Also remove from actioned bookings set
    actionedBookings.value.delete(bookingId);
    
    showSuccess('Booking deleted successfully!');
    
  } catch (error: any) {
    console.error('Error deleting booking:', error);
    
    if (error.response) {
      if (error.response.status === 404) {
        throw new Error('Booking not found. It may have already been deleted.');
      } else if (error.response.status === 500) {
        throw new Error('Server error. Please try again later.');
      } else if (error.response.data?.message) {
        throw new Error(error.response.data.message);
      } else {
        throw new Error(`Failed to delete booking: ${error.response.statusText}`);
      }
    } else if (error.request) {
      throw new Error('No response from server. Please check your connection.');
    } else {
      throw new Error(`Request error: ${error.message}`);
    }
  } finally {
    isDeleting.value = false;
  }
};

// New function to confirm booking
const confirmBooking = async (bookingId: number) => {
  try {
    const token = getAuthToken();
    
    await axios.patch(`${API_BASE_URL}/bookings/${bookingId}/status`, {
      status: 'Confirmed'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Mark this booking as actioned
    actionedBookings.value.add(bookingId);
    
    // Update store state
    const booking = bookings.value.find(b => b.id === bookingId);
    if (booking) {
      bookingStore.updateBookingLocally({
        ...booking,
        status: 'Confirmed',
        actionTaken: true
      });
    }
    
    showSuccess('Booking confirmed successfully!');
    
  } catch (error: any) {
    console.error('Error confirming booking:', error);
    showError(error.response?.data?.message || 'Failed to confirm booking');
  }
};

// New function to reject booking
const rejectBooking = async (bookingId: number) => {
  try {
    const token = getAuthToken();
    
    await axios.patch(`${API_BASE_URL}/bookings/${bookingId}/status`, {
      status: 'Cancelled'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Mark this booking as actioned
    actionedBookings.value.add(bookingId);
    
    // Update store state
    const booking = bookings.value.find(b => b.id === bookingId);
    if (booking) {
      bookingStore.updateBookingLocally({
        ...booking,
        status: 'Cancelled',
        actionTaken: true
      });
    }
    
    showSuccess('Booking rejected successfully!');
    
  } catch (error: any) {
    console.error('Error rejecting booking:', error);
    showError(error.response?.data?.message || 'Failed to reject booking');
  }
};

const viewBookingDetails = (bookingId: number) => {
  router.push(`/booking-details/${bookingId}`);
};

// --- Delete Modal Functions ---
const openDeleteConfirmation = (booking: any) => {
  bookingToDelete.value = booking;
  deleteStep.value = 'confirm';
  showDeleteConfirmation.value = true;
};

const handleFirstConfirmation = () => {
  deleteStep.value = 'final';
};

const handleCancelDeletion = () => {
  showDeleteConfirmation.value = false;
  bookingToDelete.value = null;
  deleteStep.value = 'confirm';
  isDeleting.value = false;
};

const handleDeleteBooking = async () => {
  if (!bookingToDelete.value) return;
  
  try {
    await deleteBooking(bookingToDelete.value.id);
    handleCancelDeletion();
  } catch (error: any) {
    showError(error.message || 'Failed to delete booking');
    handleCancelDeletion();
  }
};

// --- Filtering ---
const uniqueResources = computed(() => {
  const resources = new Set<string>();
  
  bookings.value.forEach(booking => {
    // Try different possible locations for resource name
    if (booking.resource?.name) {
      resources.add(booking.resource.name);
    } else if (booking.item?.name) {
      resources.add(booking.item.name);
    } else if (booking.details && booking.details.length > 0) {
      booking.details.forEach((detail: any) => {
        if (detail.item_name) {
          resources.add(detail.item_name);
        }
        if (detail.name) {
          resources.add(detail.name);
        }
      });
    }
  });
  
  return Array.from(resources).sort();
});

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    // Resource filter
    if (selectedResource.value) {
      let resourceName = '';
      
      // Check all possible locations for resource name
      if (booking.resource?.name) {
        resourceName = booking.resource.name;
      } else if (booking.item?.name) {
        resourceName = booking.item.name;
      } else if (booking.details && booking.details.length > 0) {
        resourceName = booking.details[0]?.item_name || booking.details[0]?.name || '';
      }
      
      if (resourceName !== selectedResource.value) {
        return false;
      }
    }
    
    // Status filter
    if (selectedStatus.value && booking.status !== selectedStatus.value) {
      return false;
    }
    
    // Date range filter
    if (startDate.value && endDate.value) {
      const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
      if (bookingDate < startDate.value || bookingDate > endDate.value) {
        return false;
      }
    }
    
    return true;
  });
});

const focusedDateBookings = computed(() => {
  if (!focusedDate.value) return [];
  return bookings.value.filter(booking => {
    const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
    return bookingDate === focusedDate.value;
  });
});

const formatDateLong = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
};

// --- Calendar Functions ---
const currentMonthName = computed(() => 
  currentDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => currentDate.value.getFullYear());

const changeMonth = (delta: number) => {
  const newMonthDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1);
  currentDate.value = newMonthDate;
};

const parseDate = (dateString: string) => {
  return dateString.replace(/-/g, '');
};

const updateDateRange = (dateString: string) => {
  const clickedDateNum = parseDate(dateString);
  const startNum = startDate.value ? parseDate(startDate.value) : 0;

  if (!startDate.value || clickedDateNum < startNum || (startDate.value && endDate.value)) {
    startDate.value = dateString;
    endDate.value = '';
  } else if (clickedDateNum > startNum) {
    endDate.value = dateString;
  } else {
    startDate.value = '';
    endDate.value = '';
  }
};

const handleDayClick = (dateString: string) => {
  focusedDate.value = dateString;
  updateDateRange(dateString);
};

const isDateInRange = (dateString: string) => {
  const checkNum = parseDate(dateString);
  const startNum = startDate.value ? parseDate(startDate.value) : 0;
  const endNum = endDate.value ? parseDate(endDate.value) : 0;

  if (startNum && endNum && startNum < endNum) {
    return checkNum > startNum && checkNum < endNum;
  }
  return false;
};

const daysInMonth = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDayOfMonth = new Date(year, month, 1);
  const jsDayOfWeek = firstDayOfMonth.getDay();
  const startingDayOfWeekIndex = (jsDayOfWeek + 6) % 7;
  
  const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
  const daysInPreviousMonth = new Date(year, month, 0).getDate();

  const allDays = [];

  // Get bookings for the current month
  const monthStart = new Date(year, month, 1).toISOString().split('T')[0];
  const monthEnd = new Date(year, month + 1, 0).toISOString().split('T')[0];
  
  const monthBookings = filteredBookings.value.filter(booking => {
    const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
    return bookingDate >= monthStart && bookingDate <= monthEnd;
  });

  // Create booking count map
  const bookingCountMap = new Map();
  monthBookings.forEach(booking => {
    const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
    bookingCountMap.set(bookingDate, (bookingCountMap.get(bookingDate) || 0) + 1);
  });

  // Add padding days (from previous month)
  for (let i = startingDayOfWeekIndex; i > 0; i--) {
    const dayNumber = daysInPreviousMonth - i + 1;
    allDays.push({ dayNumber, isOutsideMonth: true, dateString: '', hasBooking: false, bookingCount: 0 });
  }

  // Add days of the current month
  for (let day = 1; day <= daysInCurrentMonth; day++) {
    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const bookingCount = bookingCountMap.get(dateString) || 0;
    const hasBooking = bookingCount > 0;
    
    allDays.push({ 
      dayNumber: day, 
      isOutsideMonth: false, 
      dateString, 
      hasBooking, 
      bookingCount 
    });
  }

  // Add padding days (from next month) to fill the grid
  const totalCells = Math.ceil(allDays.length / 7) * 7;
  const remainingCells = totalCells - allDays.length;
  for (let i = 1; i <= remainingCells; i++) {
    allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '', hasBooking: false, bookingCount: 0 });
  }

  return allDays;
});

// --- Initialize ---
onMounted(async () => {
  if (!bookingStore.isLoaded) {
    await bookingStore.fetchAll();
  }
  // Setup local flags for currently loaded bookings
  bookings.value.forEach(booking => {
    booking.resource = booking.resource || booking.item || booking.details?.[0] || null;
  });
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

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

.table-card,
.calendar-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.day-details-panel {
    border-top: 4px solid #10b981;
}

.bg-light-teal {
    background-color: #f0fdf4;
}

.booking-mini-card {
    background: white;
    transition: all 0.2s ease;
}

.booking-mini-card:hover {
    border-color: #10b981 !important;
    background-color: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.day-focused {
    box-shadow: inset 0 0 0 2px #10b981;
}

.btn-xs {
    padding: 2px 8px;
    font-size: 0.75rem;
    border-radius: 4px;
}

.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}

/* Custom styling for the input group icon */
.input-group .form-control {
    border-right: none; 
}

.calendar-icon-fix {
    background-color: #f8f9fa; 
    color: #495057;
    border-left: none;
}

/* --- Calendar Specific Styles --- */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    text-align: center;
    user-select: none;
}

.calendar-header {
    font-weight: 600;
    color: #1e4449;
    padding: 8px 0;
    font-size: 0.9em;
}

.calendar-day {
    position: relative; 
    padding: 8px 0; 
    min-height: 55px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 500;
    font-size: 0.9em;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Booking badge */
.booking-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: #4BB66D;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Inner elements for stacking */
.day-label {
    font-size: 0.65rem;
    font-weight: 700;
    line-height: 1;
    text-transform: uppercase;
    color: #1e4449;
    margin-bottom: 2px;
}
.day-number {
    line-height: 1;
}

.calendar-day:not(.day-outside-month):hover {
    background-color: #fcc30040;
    transform: scale(1.05);
}

.day-outside-month {
    color: #ccc;
    cursor: default;
}

/* Range Styling */
.day-in-range {
    background-color: #e6f7ff; 
    border-radius: 0;
}
.day-is-start, .day-is-end {
    background-color: #fcc300 !important; 
    color: #1e4449 !important;
    font-weight: 700;
    border: 1px solid #1e4449;
}
.day-is-start .day-label, .day-is-end .day-label {
    color: #1e4449 !important;
}

/* Existing Booking Style */
.day-has-booking {
    background-color: #e8f5e8; 
    border: 1px solid #4BB66D;
    font-weight: 700;
}
.day-has-booking .day-label {
    color: #1e4449;
}

/* --- Table and Button Styles --- */
.table thead {
  background: #f8f9fa;
}

.btn-outline-dark-teal {
    --bs-btn-color: #1e4449;
    --bs-btn-border-color: #1e4449;
    --bs-btn-hover-bg: #fcc300;
    --bs-btn-hover-color: #ffffff;
    --bs-btn-hover-border-color: #fcc300;
}

.btn-success {
  background-color: #4BB66D; 
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Badge classes */
.bg-success {
    background-color: #4BB66D !important;
}
.bg-warning {
    background-color: #ffc107 !important;
}
.bg-danger {
    background-color: #dc3545 !important;
}
.bg-info {
    background-color: #0dcaf0 !important;
}
.bg-secondary {
    background-color: #6c757d !important;
}
.text-dark { 
    color: #212529 !important;
}

/* Action button styles */
.btn-group-sm .btn {
    padding: 0.25rem 0.4rem; 
    font-size: 0.8rem;
    margin-right: 2px;
}
.btn-outline-success {
    --bs-btn-color: #4BB66D;
    --bs-btn-border-color: #4BB66D;
    --bs-btn-hover-bg: #4BB66D;
    --bs-btn-hover-color: white;
}
.btn-outline-danger {
    --bs-btn-color: #dc3545;
    --bs-btn-border-color: #dc3545;
    --bs-btn-hover-bg: #dc3545;
    --bs-btn-hover-color: white;
}
.btn-outline-warning {
    --bs-btn-color: #ffc107;
    --bs-btn-border-color: #ffc107;
    --bs-btn-hover-bg: #ffc107;
    --bs-btn-hover-color: #212529;
}
.btn-outline-info {
    --bs-btn-color: #0dcaf0;
    --bs-btn-border-color: #0dcaf0;
    --bs-btn-hover-bg: #0dcaf0;
    --bs-btn-hover-color: white;
}

/* --- DELETE MODAL STYLES --- */
.modal {
    position: fixed; top: 0; left: 0; z-index: 1050; width: 100%; height: 100%; 
    overflow-x: hidden; overflow-y: auto; outline: 0; opacity: 0; transition: opacity 0.15s linear;
}
.modal.show { opacity: 1; }
.modal-dialog { position: relative; width: auto; margin: 0.5rem; pointer-events: none; transition: transform 0.3s ease-out; transform: translate(0, -50px); }
.modal.show .modal-dialog { transform: none; }
.modal-dialog-centered { display: flex; align-items: center; min-height: calc(100% - 1rem); }
.modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 0.3rem; outline: 0; }

.modal-dialog.delete-modal-top { align-items: flex-start; margin-top: 50px; height: auto; }
@media (min-width: 576px) { 
    .modal-dialog.delete-modal-top { max-width: 400px; margin: 1.75rem auto; }
}

.bg-warning { background-color: #ffc107 !important; }
.btn-warning { color: #212529 !important; background-color: #ffc107 !important; border-color: #ffc107 !important; }
.btn-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; }
.btn-close-white { filter: invert(1); }

/* Toast styles */
.toast-container {
    z-index: 1060;
}
.toast {
    min-width: 300px;
}
</style>