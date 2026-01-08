<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">Bookings</h2>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading bookings...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadBookings">
        <i class="bi bi-arrow-clockwise me-1"></i>Retry
      </button>
    </div>

    <!-- Main Content -->
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
              <option value="Completed">Completed</option>
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
                  'day-in-range': day.dateString && isDateInRange(day.dateString)
              }"
              @click="day.dateString && updateDateRange(day.dateString)"
              :title="day.hasBooking ? `${day.bookingCount} booking(s) on ${day.dayNumber}` : ''"
            >
              <span class="day-label" v-if="day.dateString === startDate">Start</span>
              <span class="day-label" v-else-if="day.dateString === endDate">End</span>
              <span class="day-number">{{ day.dayNumber }}</span>
              <span v-if="day.bookingCount" class="booking-badge">{{ day.bookingCount }}</span>
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
                <th>Verified</th>
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
                  <template v-if="booking.resource_details && booking.resource_details.length > 0">
                    {{ booking.resource_details[0].name }}
                  </template>
                  <template v-else-if="booking.booking_item_details && booking.booking_item_details.length > 0">
                    {{ booking.booking_item_details[0].name }}
                  </template>
                  <template v-else>
                    N/A
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
                  <span class="badge" :class="booking.is_verified ? 'bg-success' : 'bg-warning'">
                    {{ booking.is_verified ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" @click="viewBookingDetails(booking.id)" title="View Details">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" v-if="booking.status === 'Pending'" @click="updateBookingStatus(booking.id, 'Confirmed')" title="Confirm">
                      <i class="bi bi-check-circle"></i>
                    </button>
                    <button class="btn btn-outline-warning" v-if="booking.status === 'Confirmed'" @click="updateBookingStatus(booking.id, 'Completed')" title="Mark Complete">
                      <i class="bi bi-check-all"></i>
                    </button>
                    <button class="btn btn-outline-danger" v-if="booking.status === 'Pending' || booking.status === 'Confirmed'" @click="updateBookingStatus(booking.id, 'Cancelled')" title="Cancel">
                      <i class="bi bi-x-circle"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="openDeleteConfirmation(booking)" title="Delete Permanently">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- No bookings message -->
          <div v-if="filteredBookings.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x" style="font-size: 3rem;"></i>
            <p class="mt-3">No bookings found</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" :class="{ 'show d-block': showDeleteConfirmation }" tabindex="-1" @click.self="handleCancelDeletion" style="background-color: rgba(0,0,0,0.5);" v-if="showDeleteConfirmation">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Are you sure you want to delete the booking <strong>{{ bookingToDelete?.booking_reference }}</strong> for {{ getBookingResourceName(bookingToDelete) }}?</p>
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

  <!-- Success Toast -->
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

  <!-- Error Toast -->
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

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
const isLoading = ref(true);
const isRefreshing = ref(false);
const errorMessage = ref('');
const bookings = ref<any[]>([]);

// Filter State
const selectedResource = ref('');
const selectedStatus = ref('');
const startDate = ref(''); 
const endDate = ref('');   

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

const getBookingResourceName = (booking: any) => {
  if (booking?.resource_details && booking.resource_details.length > 0) {
    return booking.resource_details[0].name;
  } else if (booking?.booking_item_details && booking.booking_item_details.length > 0) {
    return booking.booking_item_details[0].name;
  }
  return 'Resource';
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
  if (isLoading.value) {
    isLoading.value = true;
  } else {
    isRefreshing.value = true;
  }
  
  errorMessage.value = '';
  
  try {
    const token = getAuthToken();
    
    const response = await axios.get(`${API_BASE_URL}/bookings`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Handle different response structures
    if (response.data && Array.isArray(response.data)) {
      bookings.value = response.data;
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      bookings.value = response.data.data;
    } else if (response.data && response.data.bookings && Array.isArray(response.data.bookings)) {
      bookings.value = response.data.bookings;
    } else {
      bookings.value = [];
    }
    
    console.log('Bookings loaded:', bookings.value.length);
    
  } catch (error: any) {
    console.error('Error loading bookings:', error);
    
    if (error.response) {
      if (error.response.status === 401) {
        errorMessage.value = 'Authentication required. Please login again.';
        setTimeout(() => router.push('/login'), 2000);
      } else if (error.response.status === 404) {
        errorMessage.value = 'Bookings endpoint not found.';
      } else if (error.response.status === 500) {
        errorMessage.value = 'Server error. Please try again later.';
      } else {
        errorMessage.value = `Failed to load bookings: ${error.response.data?.message || 'Unknown error'}`;
      }
    } else if (error.request) {
      errorMessage.value = 'No response from server. Please check your connection.';
    } else {
      errorMessage.value = `Request error: ${error.message}`;
    }
  } finally {
    isLoading.value = false;
    isRefreshing.value = false;
  }
};

const deleteBooking = async (bookingId: number) => {
  isDeleting.value = true;
  
  try {
    const token = getAuthToken();
    
    // Since there's no delete endpoint, we'll use cancel and then manually delete from state
    // or we can make a direct API call to the booking service
    const response = await axios.post(`${API_BASE_URL}/bookings/${bookingId}/cancel`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Remove from local state
    const index = bookings.value.findIndex(b => b.id === bookingId);
    if (index !== -1) {
      bookings.value.splice(index, 1);
    }
    
    showSuccess('Booking deleted successfully!');
    return response.data;
    
  } catch (error: any) {
    console.error('Error deleting booking:', error);
    
    if (error.response) {
      if (error.response.data?.message) {
        throw new Error(error.response.data.message);
      }
    }
    throw error;
  } finally {
    isDeleting.value = false;
  }
};

const updateBookingStatus = async (bookingId: number, status: string) => {
  try {
    const token = getAuthToken();
    
    const response = await axios.patch(`${API_BASE_URL}/bookings/${bookingId}/status`, {
      status: status
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Update local state
    const index = bookings.value.findIndex(b => b.id === bookingId);
    if (index !== -1) {
      bookings.value[index] = response.data.booking || response.data;
    }
    
    showSuccess(`Booking status updated to ${status}`);
    loadBookings(); // Refresh to get updated data
    
  } catch (error: any) {
    console.error('Error updating booking status:', error);
    showError(error.response?.data?.message || 'Failed to update booking status');
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
    if (booking.resource_details && booking.resource_details.length > 0) {
      booking.resource_details.forEach((resource: any) => {
        if (resource.name) {
          resources.add(resource.name);
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
      const resourceNames = booking.resource_details?.map((r: any) => r.name) || [];
      const itemNames = booking.booking_item_details?.map((i: any) => i.name) || [];
      const allNames = [...resourceNames, ...itemNames];
      
      if (!allNames.includes(selectedResource.value)) {
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
  const endNum = endDate.value ? parseDate(endDate.value) : Infinity;

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
onMounted(() => {
  loadBookings();
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