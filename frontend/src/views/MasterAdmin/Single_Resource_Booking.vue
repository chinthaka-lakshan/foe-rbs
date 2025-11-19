<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Book Resource: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
      <button class="btn btn-outline-dark-teal" @click="router.back()">
        <i class="bi bi-arrow-left me-1"></i> Back
      </button>
    </div>

    <div v-if="loading" class="alert alert-info text-center">
        Loading resource details...
    </div>
    
    <div v-else-if="!resource" class="alert alert-danger text-center">
        Resource not found. Cannot proceed with booking.
    </div>

    <div v-else class="card p-4 shadow-sm">
      <form @submit.prevent="submitBooking">
        <div class="row g-4">
          <div class="col-md-12 mb-3 border-bottom pb-3">
            <p class="fw-bold mb-1 fs-5">
                Location: {{ resource.location }} 
                <span class="badge" :class="resource.status === 'active' ? 'bg-success' : 'bg-secondary'">
                    {{ resource.status.toUpperCase() }}
                </span>
            </p>
            <p class="text-muted small mb-0">Category: {{ resource.category }} | Base Price: Rs. {{ resource.price?.toFixed(2) || '0.00' }}/hour</p>
          </div>

          <div class="fw-bold mb-3">
            <label for="userEmailInput" class="form-label">E-Mail</label>
            <input
              type="email"
              class="form-control"
              id="userEmailInput"
              placeholder="Enter e-mail (e.g. abc@gmail.com)"
              v-model="userEmail"
              required
            >
          </div>

          <div class="col-lg-6">
            <h5 class="fw-bold mb-3 section-subtitle">Booking History Calendar</h5>
            
            <div class="calendar-card border p-3 rounded bg-light mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-sm btn-outline-dark-teal" type="button" @click="changeMonth(-1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <h6 class="mb-0 calendar-title-header">{{ currentMonthName }} {{ currentYear }}</h6>
                <button class="btn btn-sm btn-outline-dark-teal" type="button" @click="changeMonth(1)">
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
                    'day-is-booked': day.isBooked,
                    'day-is-selected': day.dateString && day.dateString === dateFilter,
                  }"
                  @click="day.dateString && selectDate(day.dateString)"
                  :title="day.isBooked ? 'Already Booked' : 'Available'"
                >
                  <span class="day-number">{{ day.dayNumber }}</span>
                </div>
              </div>
            </div>

            <h5 class="fw-bold mb-3 section-subtitle">Upcoming Bookings (Filtered by Calendar)</h5>
            <div class="booking-table-placeholder border p-3 rounded bg-white">
              <div v-if="filteredUpcomingBookings.length === 0" class="text-muted text-center py-4 small">
                No upcoming bookings found.
              </div>
              <div v-else class="table-responsive history-list">
                <table class="table table-sm table-striped mb-0 small">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Time</th>
                      <th>User</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="booking in filteredUpcomingBookings" :key="booking.id">
                      <td>{{ booking.date }}</td>
                      <td>{{ booking.startTime }} - {{ booking.endTime }}</td>
                      <td>{{ booking.userId }}</td>
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

          <div class="col-lg-6">
            <h5 class="fw-bold mb-3 section-subtitle">1. Reservation Details</h5>

            <div class="date-time-box border p-3 rounded">
              <label for="bookingDate" class="form-label fw-bold">Select Date <span class="text-danger">*</span></label>
              <input 
                type="date" 
                class="form-control mb-3" 
                id="bookingDate" 
                v-model="bookingData.date" 
                required
                :min="todayDateString"
              >

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label for="startTime" class="form-label small-label">Start Time <span class="text-danger">*</span></label>
                  <input 
                    type="time" 
                    class="form-control" 
                    id="startTime" 
                    v-model="bookingData.startTime" 
                    required
                  >
                </div>
                <div class="col-6">
                  <label for="endTime" class="form-label small-label">End Time <span class="text-danger">*</span></label>
                  <input 
                    type="time" 
                    class="form-control" 
                    id="endTime" 
                    v-model="bookingData.endTime" 
                    required
                  >
                </div>
              </div>

              <small v-if="isCurrentDayAvailable" class="text-success fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i> Resource is AVAILABLE for reservation.
              </small>
              <small v-else class="text-danger fw-bold">
                <i class="bi bi-x-circle-fill me-1"></i> Resource is UNAVAILABLE on this day. (Check weekly schedule)
              </small>
            </div>
            
            <div class="schedule-reference mt-4 border p-3 rounded bg-light">
              <h6 class="fw-bold mb-2 section-subtitle">Weekly Schedule Summary</h6>
              <ul class="list-unstyled mb-0 small">
                <li v-for="day in resource.schedule" :key="day.dayName" class="d-flex justify-content-between py-1">
                  <span class="fw-medium">{{ day.dayName }}</span>
                  <span :class="day.available ? 'text-success' : 'text-danger'">
                    <span v-if="day.available">
                      {{ day.startTime }} - {{ day.endTime }}
                    </span>
                    <span v-else>
                      Unavailable
                    </span>
                  </span>
                </li>
              </ul>
            </div>
            
            <h5 class="fw-bold mb-3 mt-4 section-subtitle">2. Resource Equipment & Optional Items</h5>
            
            <div class="equipment-selection-box border p-3 rounded bg-light mb-4">
              <h6 class="fw-bold mb-2 small">Included Resource Equipment</h6>
              <div v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted text-center py-2 small">
                No fixed equipment included with this resource.
              </div>
              
              <div 
                v-for="item in resource.equipment" 
                :key="item.name" 
                class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2"
              >
                <div class="flex-grow-1">
                  <span class="fw-medium text-dark">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> {{ item.name }} 
                  </span>
                </div>
              </div>
            </div>

            <div class="booking-items-section border p-3 rounded bg-light mb-4">
              <h6 class="fw-bold mb-2 small">Optional Booking Items (Add Ons)</h6>
              <div class="mb-3">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Search & Add Rentable Items (e.g. Projector, Tennis Racket)" 
                  v-model="bookingItemSearch"
                  @input="filterBookingItems"
                >
                <div class="dropdown-items-list mt-2 shadow-sm" v-if="bookingItemSearch && filteredBookingItems.length > 0">
                  <div 
                    v-for="item in filteredBookingItems" 
                    :key="item.id" 
                    class="dropdown-item p-2 small"
                    @click="addBookingItem(item)"
                  >
                    <span class="fw-medium text-dark">{{ item.name }}</span> 
                    <span class="text-muted small ms-2">({{ item.description }})</span>
                    <span class="badge bg-info text-dark float-end">Rs. {{ item.price_per_hour.toFixed(2) }}/hr | Qty: {{ item.quantity }}</span>
                  </div>
                </div>
                <div class="text-muted text-center py-2 small" v-else-if="bookingItemSearch">
                  No items found matching "{{ bookingItemSearch }}".
                </div>
              </div>

              <div v-if="selectedBookingItems.length === 0" class="text-muted text-center py-2 small">
                No optional items selected. Use the search bar to add.
              </div>
              
              <ul class="list-unstyled mb-0 selected-items-list">
                <li 
                  v-for="(item, index) in selectedBookingItems" 
                  :key="item.id" 
                  class="d-flex align-items-center justify-content-between py-2 selected-item border-bottom"
                >
                  <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi bi-gear-fill text-dark-teal me-2"></i>
                    <span class="fw-medium text-dark">{{ item.name }}</span>
                    <span class="text-muted small ms-2">(Rs. {{ item.price_per_hour.toFixed(2) }}/hr)</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <div class="quantity-controls me-3">
                      <button type="button" class="btn btn-sm btn-outline-secondary" @click="decreaseQuantity(index)" :disabled="item.selectedQuantity! <= 1">
                        <i class="bi bi-dash"></i>
                      </button>
                      <span class="fw-bold">{{ item.selectedQuantity }}</span>
                      <button type="button" class="btn btn-sm btn-outline-secondary" @click="increaseQuantity(index)" :disabled="item.selectedQuantity! >= item.quantity">
                        <i class="bi bi-plus"></i>
                      </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeBookingItem(index)">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </li>
              </ul>
            </div>

            <h5 class="fw-bold mb-3 mt-4 section-subtitle">3. Purpose & Summary</h5>

            <div class="col-12 mb-4">
              <label for="purpose" class="form-label fw-bold small-label">Purpose/Notes</label>
              <textarea 
                class="form-control" 
                id="purpose" 
                v-model="bookingData.purpose"
                rows="2"
                placeholder="e.g., Team meeting, presentation practice, etc."
              ></textarea>
            </div>
            
            <div class="border p-3 rounded booking-summary">
              <div class="d-flex justify-content-between summary-item">
                <span>Base Resource Price:</span>
                <span class="fw-bold">Rs. {{ resource.price?.toFixed(2) || '0.00' }}/hr</span>
              </div>
              <div v-for="item in selectedBookingItems" :key="item.id" class="d-flex justify-content-between summary-item text-muted">
                <span class="ms-3">- {{ item.name }} (x{{ item.selectedQuantity }}) Rental Price:</span>
                <span>Rs. {{ (item.price_per_hour * item.selectedQuantity!).toFixed(2) }}/hr</span>
              </div>
              <div class="border-top mt-2 pt-2 d-flex justify-content-between summary-total">
                <span class="fw-bold fs-6">Total Estimated Price:</span>
                <span class="fw-bold fs-6 text-dark-teal">Rs. {{ calculateTotalPrice().toFixed(2) }}/hr</span>
              </div>
              <p class="text-muted small mt-2 mb-0">Note: Total price is an hourly estimate and is subject to approval.</p>
            </div>

            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
              <button 
                type="submit" 
                class="btn btn-success" 
                :disabled="!resource.status || resource.status !== 'active' || !isCurrentDayAvailable || !bookingData.date"
              >
                <i class="bi bi-send me-1"></i> Send Booking Request
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Email Verification Popup -->
  <div v-if="showEmailVerificationPopup" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Email Verification Required</h5>
        <button type="button" class="btn-close" @click="closeEmailVerificationPopup"></button>
      </div>
      <div class="modal-body">
        <p>We need to verify your email address before submitting the booking request.</p>
        <div class="alert alert-info">
          <strong>Email:</strong> {{ userEmail }}
        </div>
        <p>An OTP (One-Time Password) will be sent to your email address.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="closeEmailVerificationPopup">Cancel</button>
        <button type="button" class="btn btn-primary" @click="sendOTP">Send OTP</button>
      </div>
    </div>
  </div>

  <!-- OTP Verification Popup -->
  <div v-if="showOTPVerificationPopup" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enter OTP Code</h5>
        <button type="button" class="btn-close" @click="closeOTPVerificationPopup"></button>
      </div>
      <div class="modal-body">
        <p>Please enter the 6-digit OTP sent to your email:</p>
        <div class="alert alert-info mb-3">
          <strong>Email:</strong> {{ userEmail }}
        </div>
        <div class="otp-input-group">
          <input 
            type="text" 
            class="form-control otp-input" 
            v-model="otpCode"
            placeholder="Enter 6-digit OTP"
            maxlength="6"
          >
        </div>
        <div v-if="otpError" class="alert alert-danger mt-2">
          {{ otpError }}
        </div>
        <div class="text-muted small mt-2">
          Didn't receive the code? <a href="#" @click.prevent="resendOTP">Resend OTP</a>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="closeOTPVerificationPopup">Cancel</button>
        <button type="button" class="btn btn-primary" @click="verifyOTP" :disabled="!otpCode || otpCode.length !== 6">
          Verify & Submit
        </button>
      </div>
    </div>
  </div>

  <!-- Success Popup -->
  <div v-if="showSuccessPopup" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success">
          <i class="bi bi-check-circle-fill me-2"></i>Booking Request Submitted
        </h5>
      </div>
      <div class="modal-body">
        <div class="alert alert-success">
          <p class="mb-2"><strong>Your booking request has been submitted successfully!</strong></p>
          <p class="mb-1">Resource: {{ resource?.name }}</p>
          <p class="mb-1">Date: {{ bookingData.date }}</p>
          <p class="mb-1">Time: {{ bookingData.startTime }} - {{ bookingData.endTime }}</p>
          <p class="mb-0">Total Estimated Price: Rs. {{ calculateTotalPrice().toFixed(2) }}/hour</p>
        </div>
        <p class="text-muted">You will receive a confirmation email once your booking is approved.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" @click="redirectToBookings">View My Bookings</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const router = useRouter();
const route = useRoute();

// --- DATA INTERFACES ---

interface ScheduleDay { dayName: string; available: boolean; startTime: string; endTime: string; }
interface EquipmentItem { name: string; price: number | null; } 

interface Resource {
    id: number;
    name: string;
    location?: string;
    category: string;
    price: number | null; 
    status: 'active' | 'inactive';
    equipment?: EquipmentItem[]; // KEPT: Base equipment included with the resource
    schedule?: ScheduleDay[]; 
}
interface GlobalBooking {
    id: number;
    resourceId: number;
    date: string;
    startTime: string;
    endTime: string;
    status: 'approved' | 'pending' | 'rejected';
    userId: string;
}

interface BookingForm {
    date: string;
    startTime: string;
    endTime: string;
    purpose: string;
}

// NEW INTERFACE FOR BOOKING ITEMS
interface BookingItem {
  id: number;
  name: string;
  description: string;
  price_per_hour: number;
  quantity: number;
  available: boolean;
  selectedQuantity?: number;
}

// --- STATE ---

const resource = ref<Resource | null>(null);
const loading = ref(true);
const dateFilter = ref(''); // Used to filter the table list by clicking the calendar
const todayDateString = new Date().toISOString().split('T')[0]; 

const bookingData = ref<BookingForm>({
    date: todayDateString, 
    startTime: '09:00',
    endTime: '10:00',
    purpose: '',
});

// --- CALENDAR STATE ---
const calendarDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1)); // First of current month
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// --- MOCK BOOKING HISTORY (for calendar/display) ---
const MOCK_BOOKING_HISTORY: GlobalBooking[] = [
    { id: 1, resourceId: 1, date: '2025-11-15', startTime: '10:00', endTime: '12:00', status: 'approved', userId: 'U001' },
    { id: 2, resourceId: 1, date: '2025-11-20', startTime: '14:00', endTime: '16:00', status: 'pending', userId: 'U002' },
    { id: 3, resourceId: 1, date: '2025-12-05', startTime: '11:00', endTime: '13:00', status: 'approved', userId: 'U003' },
    { id: 4, resourceId: 2, date: '2025-12-10', startTime: '15:00', endTime: '16:00', status: 'approved', userId: 'U004' },
    { id: 5, resourceId: 3, date: '2025-11-10', startTime: '15:00', endTime: '16:00', status: 'approved', userId: 'U005' },
];

// NEW STATE FOR BOOKING ITEMS
const bookingItems = ref<BookingItem[]>([]);
const filteredBookingItems = ref<BookingItem[]>([]);
const selectedBookingItems = ref<BookingItem[]>([]);
const bookingItemSearch = ref('');

// MOCK BOOKING ITEMS DATA (Same as in booking items page)
const MOCK_BOOKING_ITEMS: BookingItem[] = [
  { id: 1, name: 'Tennis Racket', description: 'Standard adult size racket.', price_per_hour: 50.00, quantity: 15, available: true },
  { id: 2, name: 'Basketball', description: 'Size 7, official weight.', price_per_hour: 40.00, quantity: 10, available: true },
  { id: 3, name: 'Projector', description: 'High definition short-throw projector.', price_per_hour: 150.00, quantity: 3, available: false },
  { id: 4, name: 'Microphone (Wireless)', description: 'Lapel mic for presentations.', price_per_hour: 75.00, quantity: 5, available: true },
  { id: 5, name: 'Computer', description: 'Desktop computer for presentations.', price_per_hour: 100.00, quantity: 30, available: true },
  { id: 6, name: 'Whiteboard', description: 'Large magnetic whiteboard.', price_per_hour: 25.00, quantity: 8, available: true },
];

// --- POPUP STATE ---
const showEmailVerificationPopup = ref(false);
const showOTPVerificationPopup = ref(false);
const showSuccessPopup = ref(false);
const otpCode = ref('');
const otpError = ref('');
const generatedOTP = ref(''); // Store the generated OTP for verification

const userEmail = ref<string>('');

// --- UTILITIES & FETCHING ---

const getStoredResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    return storedResourcesString ? JSON.parse(storedResourcesString) : [];
};

const parseDate = (dateString: string) => {
    // Converts YYYY-MM-DD string to a number (YYYYMMDD) for numerical comparison
    return Number(dateString.replace(/-/g, ''));
};

const fetchResourceDetails = (id: number) => {
    const latestResources = getStoredResources();
    const fetchedResource = latestResources.find(r => r.id === id);
    
    loading.value = false;

    if (fetchedResource) {
        if (fetchedResource.price === undefined) fetchedResource.price = null;
        if (fetchedResource.equipment === undefined || !Array.isArray(fetchedResource.equipment)) {
             fetchedResource.equipment = [];
        } 

        if (fetchedResource.schedule === undefined) fetchedResource.schedule = [];

        resource.value = fetchedResource;
    } else {
        resource.value = null;
    }
};

// --- CALENDAR ACTIONS ---

const selectDate = (dateString: string) => {
    // 1. Update the Reservation Form date input
    bookingData.value.date = dateString; 
    
    // 2. Update the table filter
    dateFilter.value = dateString; 
};

const changeMonth = (delta: number) => {
    calendarDate.value = new Date(calendarDate.value.getFullYear(), calendarDate.value.getMonth() + delta, 1);
};


// --- COMPUTED PROPERTIES ---

const isCurrentDayAvailable = computed(() => {
    if (!bookingData.value.date || !resource.value || !resource.value.schedule) {
        return false;
    }
    
    const jsDayOfWeek = new Date(bookingData.value.date).getDay();
    const scheduleIndex = (jsDayOfWeek + 6) % 7; 
    
    const daySchedule = resource.value.schedule[scheduleIndex];
    return daySchedule?.available === true;
});

const currentMonthName = computed(() => 
    calendarDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => calendarDate.value.getFullYear());

const daysInMonth = computed(() => {
    const year = calendarDate.value.getFullYear();
    const month = calendarDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const jsDayOfWeek = firstDayOfMonth.getDay();
    const startingDayOfWeekIndex = (jsDayOfWeek + 6) % 7; 
    
    const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
    const daysInPreviousMonth = new Date(year, month, 0).getDate();
    const allDays = [];

    const bookedDates = new Set(
        MOCK_BOOKING_HISTORY
            .filter(b => b.resourceId === resource.value?.id)
            .map(b => b.date)
    );

    // Add padding days
    for (let i = startingDayOfWeekIndex; i > 0; i--) {
        allDays.push({ dayNumber: daysInPreviousMonth - i + 1, isOutsideMonth: true, dateString: '' });
    }

    // Add current month days
    for (let day = 1; day <= daysInCurrentMonth; day++) {
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isBooked = bookedDates.has(dateString);
        
        allDays.push({ dayNumber: day, isOutsideMonth: false, dateString, isBooked });
    }

    // Add padding days (from next month)
    const remainingCells = 42 - allDays.length; 
    for (let i = 1; i <= remainingCells; i++) {
        allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '' });
    }

    return allDays.slice(0, Math.ceil(allDays.length / 7) * 7);
});


const filteredUpcomingBookings = computed<GlobalBooking[]>(() => {
    if (!resource.value) return [];
    
    const resourceId = resource.value.id;
    const todayNum = parseDate(todayDateString);
    
    let filtered = MOCK_BOOKING_HISTORY.filter(b => b.resourceId === resourceId);

    // Filter 1: Only TODAY and FUTURE dates
    filtered = filtered.filter(b => parseDate(b.date) >= todayNum);

    // Filter 2: Filter by the date selected on the calendar/form
    if (dateFilter.value) {
        filtered = filtered.filter(b => b.date === dateFilter.value);
    }
    
    // Sort by date
    return filtered.sort((a, b) => parseDate(a.date) - parseDate(b.date));
});

const getBookingStatusClass = (status: string) => {
    switch (status) {
        case 'approved': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
};

// NEW FUNCTIONS FOR BOOKING ITEMS
const loadBookingItems = () => {
    // In a real app, this would be an API call
    bookingItems.value = MOCK_BOOKING_ITEMS.filter(item => item.available);
    filterBookingItems();
};

const filterBookingItems = () => {
    const searchTerm = bookingItemSearch.value.toLowerCase();
    if (!searchTerm) {
        filteredBookingItems.value = []; // Clear dropdown if no search term
    } else {
        filteredBookingItems.value = bookingItems.value.filter(item =>
            item.name.toLowerCase().includes(searchTerm) ||
            item.description.toLowerCase().includes(searchTerm)
        );
    }
};

const addBookingItem = (item: BookingItem) => {
    // Check if item is already selected
    const existingIndex = selectedBookingItems.value.findIndex(selected => selected.id === item.id);
    
    if (existingIndex === -1) {
        // Add new item with quantity 1
        selectedBookingItems.value.push({
            ...item,
            selectedQuantity: 1
        });
    } else {
        // Increase quantity if not at max
        if (selectedBookingItems.value[existingIndex].selectedQuantity! < item.quantity) {
            selectedBookingItems.value[existingIndex].selectedQuantity! += 1;
        }
    }
    
    // Clear search
    bookingItemSearch.value = '';
    filterBookingItems();
};

const removeBookingItem = (index: number) => {
    selectedBookingItems.value.splice(index, 1);
};

const increaseQuantity = (index: number) => {
    const item = selectedBookingItems.value[index];
    if (item.selectedQuantity! < item.quantity) {
        item.selectedQuantity! += 1;
    }
};

const decreaseQuantity = (index: number) => {
    const item = selectedBookingItems.value[index];
    if (item.selectedQuantity! > 1) {
        item.selectedQuantity! -= 1;
    }
};

const calculateTotalPrice = () => {
    let total = resource.value?.price || 0;
    selectedBookingItems.value.forEach(item => {
        // Calculate price for selected items based on quantity
        total += item.price_per_hour * (item.selectedQuantity || 1);
    });
    return total;
};

// --- POPUP FUNCTIONS ---

const submitBooking = () => {
    if (resource.value?.status !== 'active') { 
        alert("Cannot book: Resource is inactive or not loaded."); 
        return;
    }
    if (!bookingData.value.date || !bookingData.value.startTime || !bookingData.value.endTime) { 
        alert("Please select a valid date, start time, and end time."); 
        return; 
    }
    if (!isCurrentDayAvailable.value) { 
        alert("Booking failed: Resource is not available on the selected day according to its weekly schedule."); 
        return; 
    }
    if (!userEmail.value) {
        alert("Please enter your email address.");
        return;
    }

    // Show email verification popup instead of directly submitting
    showEmailVerificationPopup.value = true;
};

const closeEmailVerificationPopup = () => {
    showEmailVerificationPopup.value = false;
};

const closeOTPVerificationPopup = () => {
    showOTPVerificationPopup.value = false;
    otpCode.value = '';
    otpError.value = '';
};

const generateOTP = (): string => {
    // Generate a 6-digit OTP
    return Math.floor(100000 + Math.random() * 900000).toString();
};

const sendOTP = () => {
    // Generate and "send" OTP (in real app, this would call an API)
    generatedOTP.value = generateOTP();
    
    console.log(`OTP sent to ${userEmail.value}: ${generatedOTP.value}`); // For testing
    
    // Close email popup and show OTP popup
    showEmailVerificationPopup.value = false;
    showOTPVerificationPopup.value = true;
    
    // In a real application, you would call an API to send the OTP to the user's email
    // For demo purposes, we'll just log it and show the OTP popup
};

const resendOTP = () => {
    sendOTP();
    otpError.value = '';
    alert('New OTP has been sent to your email.');
};

const verifyOTP = () => {
    if (otpCode.value.length !== 6) {
        otpError.value = 'Please enter a valid 6-digit OTP code.';
        return;
    }

    // In a real app, this would verify against the OTP sent to the user
    // For demo purposes, we'll just check if it matches our generated OTP
    if (otpCode.value === generatedOTP.value) {
        // OTP is correct - proceed with booking submission
        completeBookingSubmission();
    } else {
        otpError.value = 'Invalid OTP code. Please try again.';
    }
};

const completeBookingSubmission = () => {
    // Prepare optional booking items for submission
    const bookingItemsPayload = selectedBookingItems.value.map(item => ({
        id: item.id,
        name: item.name,
        price_per_hour: item.price_per_hour,
        quantity: item.selectedQuantity || 1
    }));
    
    // Prepare FIXED equipment (used to be in old payload, keeping structure)
    const fixedEquipmentPayload = resource.value?.equipment?.map(item => ({
        name: item.name,
        price: item.price,
        quantity: 1, 
    })) || [];

    const bookingPayload = {
        resourceId: resource.value.id,
        date: bookingData.value.date,
        startTime: bookingData.value.startTime,
        endTime: bookingData.value.endTime,
        purpose: bookingData.value.purpose,
        userEmail: userEmail.value,
        
        // Include both types of items in the final payload
        fixedEquipment: fixedEquipmentPayload, 
        optionalItems: bookingItemsPayload, 
        
        totalPrice: calculateTotalPrice(), // This is the total price per hour
        status: 'pending', 
        createdAt: new Date().toISOString(),
    };
    
    console.log("Submitting Booking Payload:", bookingPayload);
    
    // In a real application, you would save the booking to your backend here
    // For demo purposes, we'll just show the success popup
    
    // Close OTP popup and show success popup
    showOTPVerificationPopup.value = false;
    showSuccessPopup.value = true;
};

const redirectToBookings = () => {
    showSuccessPopup.value = false;
    router.push('/master-admin/booking'); 
};

// --- LIFECYCLE ---

onMounted(() => {
    const resourceId = parseInt(route.query.resourceId as string);
    if (resourceId) {
        fetchResourceDetails(resourceId);
    } else {
        loading.value = false;
        resource.value = null;
    }
    
    // Load booking items when component mounts
    loadBookingItems();
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
.section-title {
    color: #1e4449;
    font-weight: 600;
    margin-bottom: 24px;
}
.section-subtitle {
    color: #1e4449;
    font-size: 1.1rem;
}
.small-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}
.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}
.card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* --- Calendar Styles --- */
.calendar-card {
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.calendar-title-header {
    color: #1e4449;
    font-weight: 600;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
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
    padding: 8px 0;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-weight: 500;
    font-size: 0.9em;
}
.day-outside-month {
    color: #ccc;
    cursor: default;
}
.calendar-day:not(.day-outside-month):hover {
    background-color: #fcc30040;
}

/* Calendar Booking Statuses */
.day-is-booked {
    background-color: #dc354540; /* Light Red for existing booking */
    color: #dc3545;
    font-weight: 700;
}
.day-is-booked:hover {
    background-color: #dc354560;
}
.day-is-selected {
    background-color: #fcc300; /* Yellow for selection */
    color: #1e4449;
    border: 1px solid #1e4449;
}
.day-is-selected:hover {
    background-color: #e6b200;
}

/* --- Layouts & Components --- */
.date-time-box, .equipment-selection-box, .schedule-reference {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
.schedule-reference li {
    padding: 0.25rem 0.5rem;
    border-bottom: 1px solid #eee;
}
.equipment-selection-box .form-check-label {
    font-size: 0.95rem;
}

/* Booking List Table Styling */
.booking-table-placeholder {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
.history-list {
    max-height: 250px;
    overflow-y: auto;
}
.booking-table-placeholder th {
    font-size: 0.8rem;
    color: #1e4449;
    font-weight: 600;
}
.booking-table-placeholder td {
    font-size: 0.85rem;
}

/* --- Button & Color Classes --- */
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
.bg-success {
    background-color: #4BB66D !important;
}
.bg-warning {
    background-color: #ffc107 !important;
}
.bg-danger {
    background-color: #dc3545 !important;
}
.text-success {
    color: #4BB66D !important;
}
.text-danger {
    color: #dc3545 !important;
}
.text-dark {
    color: #212529 !important;
}

/* --- CORRECTED STYLES FOR BOOKING ITEMS --- */
.booking-items-section {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    position: relative; 
}

.dropdown-items-list {
    position: absolute;
    z-index: 10;
    width: calc(100% - 30px); 
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    left: 15px; 
    top: 100%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #fcc30040;
}

.selected-item {
    background: white;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-controls button {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.booking-summary {
    background: #f8f9fa !important;
}

.summary-item {
    font-size: 0.9rem;
}

.summary-total {
    font-size: 1rem;
}

.text-dark-teal {
    color: #1e4449;
}
.form-label{
    color: #1e4449;
    font-size: 1.1rem;
}

/* --- MODAL STYLES --- */
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
    z-index: 1050;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    width: 90%;
    max-width: 500px;
    animation: modalFadeIn 0.3s ease;
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-weight: 600;
    color: #1e4449;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.otp-input-group {
    margin-bottom: 1rem;
}

.otp-input {
    text-align: center;
    font-size: 1.2rem;
    font-weight: 600;
    letter-spacing: 0.5em;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>